<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseModality;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuickCourseEntryController extends Controller
{
    public function create(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $group->load('cycle');
        return view('assignments.quick', compact('group'));
    }

    public function store(Request $request, TeachingGroup $group)
    {
        $this->authorizeGroup($group);

        $data = $request->validate([
            'rows' => 'required|array|min:1|max:60',
            'rows.*.course_name' => 'nullable|string|max:255',
            'rows.*.course_code' => 'nullable|string|max:80',
            'rows.*.modality' => 'nullable|in:TEC,TALL,BOTH',
            'rows.*.tec_block' => 'nullable|string|max:100',
            'rows.*.tec_nrc' => 'nullable|string|max:30',
            'rows.*.tall_block_reference' => 'nullable|string|max:120',
            'rows.*.tall_block' => 'nullable|string|max:100',
            'rows.*.tall_nrc' => 'nullable|string|max:30',
            'rows.*.start_date' => 'nullable|date',
            'rows.*.end_date' => 'nullable|date',
        ]);

        $rows = collect($data['rows'])->filter(fn ($row) => filled($row['course_name'] ?? null))->values();
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['rows' => 'Escribe al menos un nombre de curso.']);
        }

        foreach ($rows as $index => $row) {
            if (empty($row['modality'])) {
                throw ValidationException::withMessages(["rows.{$index}.modality" => 'Selecciona TEC, TALL o ambas modalidades para cada curso.']);
            }
            if (!empty($row['start_date']) && !empty($row['end_date']) && $row['end_date'] < $row['start_date']) {
                throw ValidationException::withMessages(["rows.{$index}.end_date" => 'La fecha final no puede ser anterior a la inicial.']);
            }
        }

        $seenCodes = [];
        foreach ($rows as $index => $row) {
            $courseName = trim($row['course_name']);
            $code = trim($row['course_code'] ?? '') ?: Str::upper(Str::slug($courseName, '-'));
            $normalizedCode = mb_strtolower($code);

            if (isset($seenCodes[$normalizedCode])) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_code" => "El curso «{$courseName}» repite un código dentro de esta carga. Revisa las filas antes de guardar.",
                ]);
            }
            $seenCodes[$normalizedCode] = true;

            $existingCourse = Course::where('professor_id', $group->professor_id)
                ->where('code', $code)
                ->first();

            if ($existingCourse && $group->assignments()->where('course_id', $existingCourse->id)->exists()) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_name" => "«{$courseName}» ya está registrado en este grupo. No se modificó ningún dato.",
                ]);
            }

            if ($existingCourse && mb_strtolower(trim($existingCourse->name)) !== mb_strtolower($courseName)) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_name" => "El código {$code} ya pertenece al curso «{$existingCourse->name}». Verifica el código o el nombre.",
                ]);
            }

            $sameName = Course::where('professor_id', $group->professor_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($courseName)])
                ->first();

            if ($sameName && mb_strtolower((string) $sameName->code) !== $normalizedCode) {
                throw ValidationException::withMessages([
                    "rows.{$index}.course_code" => "Ya existe «{$courseName}» con el código {$sameName->code}. Reutiliza ese código para evitar duplicados.",
                ]);
            }
        }

        DB::transaction(function () use ($rows, $group) {
            foreach ($rows as $row) {
                $code = trim($row['course_code'] ?? '') ?: Str::upper(Str::slug($row['course_name'], '-'));
                $course = Course::firstOrCreate(
                    ['professor_id' => $group->professor_id, 'code' => $code],
                    ['name' => trim($row['course_name'])]
                );
                $assignment = $group->assignments()->create([
                    'course_id' => $course->id,
                    'assigned_at' => now()->toDateString(),
                ]);

                $types = $row['modality'] === 'BOTH' ? ['TEC', 'TALL'] : [$row['modality']];
                foreach ($types as $type) {
                    $prefix = $type === 'TEC' ? 'tec' : 'tall';
                    $modality = CourseModality::firstOrNew([
                        'course_assignment_id' => $assignment->id,
                        'modality' => $type,
                    ]);
                    $modality->fill([
                        'nrc' => $row[$prefix.'_nrc'] ?? $modality->nrc,
                        'block_code' => $type === 'TEC' ? ($row['tec_block'] ?? null) : ($row['tall_block'] ?? null),
                        'block_reference' => $type === 'TALL' ? ($row['tall_block_reference'] ?? null) : null,
                        'start_date' => $row['start_date'] ?? $modality->start_date?->toDateString(),
                        'end_date' => $row['end_date'] ?? $modality->end_date?->toDateString(),
                        'curriculum_start_week' => $modality->curriculum_start_week ?: 1,
                        'active' => true,
                        'period_label' => $group->cycle->academic_year.'-'.$group->cycle->cycle_code.'-'.$type,
                    ]);
                    $modality->save();
                }
            }
        });

        return redirect()->route('groups.show', $group)
            ->with('success', 'Cursos registrados. Ya puedes configurar los días y horas de cada modalidad para calcular sus sesiones y feriados.');
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
