<?php

namespace App\Http\Controllers;

use App\Models\TeachingGroup;
use App\Services\CourseCodeParser;
use App\Services\CourseRegistrationService;
use App\Services\StudentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

        $validator = Validator::make($request->all(), [
            'rows' => ['required', 'array', 'min:1', 'max:60'],
            'rows.*' => ['array'],
            'rows.*.course_name' => ['nullable', 'string', 'max:255'],
            'rows.*.tec_code' => ['nullable', 'string', 'max:200'],
            'rows.*.tec_start_date' => ['nullable', 'date'],
            'rows.*.tec_end_date' => ['nullable', 'date'],
            'rows.*.tall_code' => ['nullable', 'string', 'max:200'],
            'rows.*.tall_start_date' => ['nullable', 'date'],
            'rows.*.tall_end_date' => ['nullable', 'date'],
        ]);

        $validator->after(function ($validator) use ($request, $parser) {
            $hasAtLeastOneCourse = false;
            $seenCourseCodes = [];

            foreach ((array) $request->input('rows', []) as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $courseName = trim((string) ($row['course_name'] ?? ''));
                $codes = [
                    'TEC' => trim((string) ($row['tec_code'] ?? '')),
                    'TALL' => trim((string) ($row['tall_code'] ?? '')),
                ];
                $hasAnyCode = $codes['TEC'] !== '' || $codes['TALL'] !== '';
                $hasAnyDate = filled($row['tec_start_date'] ?? null)
                    || filled($row['tec_end_date'] ?? null)
                    || filled($row['tall_start_date'] ?? null)
                    || filled($row['tall_end_date'] ?? null);

                if ($courseName === '' && !$hasAnyCode && !$hasAnyDate) {
                    continue;
                }

                $hasAtLeastOneCourse = true;

                if ($courseName === '') {
                    $validator->errors()->add("rows.{$index}.course_name", 'Escribe el nombre del curso.');
                }

                if (!$hasAnyCode) {
                    $message = $hasAnyDate
                        ? 'Ingresa el código TEC o TAL antes de indicar sus fechas.'
                        : 'Ingresa al menos un código: TEC o TAL.';
                    $validator->errors()->add("rows.{$index}.tec_code", $message);
                    continue;
                }

                $parsedByType = [];

                foreach ($codes as $type => $code) {
                    $prefix = $type === 'TEC' ? 'tec' : 'tall';
                    $startDate = $row[$prefix.'_start_date'] ?? null;
                    $endDate = $row[$prefix.'_end_date'] ?? null;

                    if ($code === '') {
                        if (filled($startDate) || filled($endDate)) {
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_code",
                                "No puedes indicar fechas de {$type} sin ingresar su código."
                            );
                        }

                        continue;
                    }

                    $parsed = $parser->parse($code);

                    if ($parsed['errors']) {
                        foreach ($parsed['errors'] as $message) {
                            $validator->errors()->add("rows.{$index}.{$prefix}_code", $message);
                        }
                        continue;
                    }

                    if ($parsed['has_structured_code']) {
                        if (count($parsed['matches']) !== 1 || $parsed['detected_modalities'] !== [$type]) {
                            $expectedLabel = $type === 'TEC' ? 'un código TEC' : 'un código TAL/TALL';
                            $validator->errors()->add(
                                "rows.{$index}.{$prefix}_code",
                                "Este campo debe contener solamente {$expectedLabel} y su NRC."
                            );
                            continue;
                        }
                    } elseif ($codes['TEC'] !== '' && $codes['TALL'] !== '') {
                        $validator->errors()->add(
                            "rows.{$index}.{$prefix}_code",
                            'Cuando registres ambos componentes, usa los códigos institucionales completos para comprobar que pertenecen al mismo curso.'
                        );
                        continue;
                    }

                    $parsedByType[$type] = $parsed;

                    $hasStartDate = filled($startDate);
                    $hasEndDate = filled($endDate);

                    if ($hasStartDate !== $hasEndDate) {
                        $validator->errors()->add(
                            "rows.{$index}.{$prefix}_end_date",
                            "Completa Desde y Hasta de {$type}, o deja ambas fechas vacías."
                        );
                    } elseif ($hasStartDate && $hasEndDate && $endDate < $startDate) {
                        $validator->errors()->add(
                            "rows.{$index}.{$prefix}_end_date",
                            "La fecha Hasta de {$type} no puede ser anterior a Desde."
                        );
                    }
                }

                if (isset($parsedByType['TEC'], $parsedByType['TALL'])) {
                    if ($parsedByType['TEC']['base_code'] !== $parsedByType['TALL']['base_code']) {
                        $validator->errors()->add(
                            "rows.{$index}.tall_code",
                            'Los códigos TEC y TAL no corresponden al mismo periodo, programa y número de curso.'
                        );
                    }
                }

                $baseCode = $parsedByType['TEC']['base_code']
                    ?? $parsedByType['TALL']['base_code']
                    ?? null;

                if ($baseCode !== null) {
                    $normalizedCode = mb_strtolower($baseCode);

                    if (isset($seenCourseCodes[$normalizedCode])) {
                        $validator->errors()->add(
                            "rows.{$index}.course_name",
                            'Este curso ya aparece en otra fila. Coloca sus códigos TEC y TAL en la misma fila.'
                        );
                    }

                    $seenCourseCodes[$normalizedCode] = true;
                }
            }

            if (!$hasAtLeastOneCourse) {
                $validator->errors()->add('rows', 'Ingresa el nombre y al menos un código de un curso.');
            }
        });

        $data = $validator->validate();

        $rows = collect($data['rows'])
            ->filter(fn ($row) => filled($row['course_name'] ?? null)
                || filled($row['tec_code'] ?? null)
                || filled($row['tall_code'] ?? null))
            ->values();

        $created = 0;
        $createdModalities = 0;
        $missingNrc = [];

        DB::transaction(function () use ($rows, $group, $registration, $students, &$created, &$createdModalities, &$missingNrc) {
            foreach ($rows as $index => $row) {
                $codes = array_values(array_filter([
                    trim((string) ($row['tec_code'] ?? '')),
                    trim((string) ($row['tall_code'] ?? '')),
                ], fn ($code) => $code !== ''));

                $component = match (count($codes)) {
                    2 => 'BOTH',
                    default => filled($row['tec_code'] ?? null) ? 'TEC' : 'TALL',
                };

                $modalityDates = [
                    'TEC' => [
                        'start_date' => $row['tec_start_date'] ?? null,
                        'end_date' => $row['tec_end_date'] ?? null,
                    ],
                    'TALL' => [
                        'start_date' => $row['tall_start_date'] ?? null,
                        'end_date' => $row['tall_end_date'] ?? null,
                    ],
                ];

                try {
                    $result = $registration->register(
                        $group,
                        $row['course_name'],
                        implode('; ', $codes),
                        $component,
                        $students,
                        $modalityDates
                    );
                } catch (ValidationException $exception) {
                    $errors = [];

                    foreach ($exception->errors() as $field => $messages) {
                        $target = match ($field) {
                            'course_name' => 'course_name',
                            default => 'row_error',
                        };
                        $errors["rows.{$index}.{$target}"] = $messages;
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
