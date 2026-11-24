<?php

namespace App\Http\Controllers;

use App\Models\TeachingGroup;
use App\Services\CourseCodeParser;
use App\Services\CourseRegistrationService;
use App\Services\StudentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuickCourseEntryController extends Controller
{
    public function create(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $group->load('cycle');

        return view('assignments.quick', compact('group'));
    }

    public function store(
        Request $request,
        TeachingGroup $group,
        CourseCodeParser $parser,
        CourseRegistrationService $registration,
        StudentImportService $students
    ) {
        $this->authorizeGroup($group);

        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:60'],
            'rows.*.course_code' => ['nullable', 'string', 'max:200'],
            'rows.*.course_name' => ['nullable', 'string', 'max:255'],
            'rows.*.component' => ['nullable', 'in:TEC,TALL,BOTH'],
        ]);

        $rows = collect($data['rows'])
            ->filter(fn ($row) => filled($row['course_code'] ?? null) || filled($row['course_name'] ?? null))
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'rows' => 'Escribe el código y el nombre de al menos un curso.',
            ]);
        }

        $seenCodes = [];

        foreach ($rows as $index => $row) {
            if (blank($row['course_code'] ?? null)) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_code" => 'Ingresa el código completo del curso.',
                ]);
            }

            if (blank($row['course_name'] ?? null)) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_name" => 'Ingresa el nombre del curso.',
                ]);
            }

            if (blank($row['component'] ?? null)) {
                throw ValidationException::withMessages([
                    "rows.{$index}.component" => 'Selecciona Teoría, Taller o Ambos.',
                ]);
            }

            $parsed = $parser->parse($row['course_code']);

            if ($parsed['errors']) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_code" => $parsed['errors'],
                ]);
            }

            $normalizedCode = mb_strtolower($parsed['base_code']);

            if (isset($seenCodes[$normalizedCode])) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_code" => 'Este curso aparece más de una vez. Combina sus códigos TEC/TAL en la misma fila y selecciona Ambos.',
                ]);
            }

            $seenCodes[$normalizedCode] = true;
        }

        $created = 0;
        $createdModalities = 0;
        $missingNrc = [];

        DB::transaction(function () use ($rows, $group, $registration, $students, &$created, &$createdModalities, &$missingNrc) {
            foreach ($rows as $index => $row) {
                try {
                    $result = $registration->register(
                        $group,
                        $row['course_name'],
                        $row['course_code'],
                        $row['component'],
                        $students
                    );
                } catch (ValidationException $exception) {
                    $errors = [];

                    foreach ($exception->errors() as $field => $messages) {
                        $errors["rows.{$index}.{$field}"] = $messages;
                    }

                    throw ValidationException::withMessages($errors);
                }

                $created++;
                $createdModalities += count($result['created_modalities']);

                foreach ($result['missing_nrc'] as $type) {
                    $missingNrc[] = $row['course_name'].' ('.$type.')';
                }
            }
        });

        $message = "{$created} cursos registrados y {$createdModalities} modalidades creadas.";

        if ($missingNrc) {
            $message .= ' NRC pendiente: '.implode(', ', array_unique($missingNrc)).'.';
        }

        return redirect()->route('groups.show', $group)->with(
            $missingNrc ? 'warning' : 'success',
            $message
        );
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
