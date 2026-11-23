<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseModality;
use App\Models\TeachingGroup;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseAssignmentController extends Controller
{
    public function create(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $group->load(['cycle', 'assignments.course', 'assignments.modalities.schedules']);

        $scheduleTemplates = [];
        foreach ($group->assignments as $assignment) {
            foreach ($assignment->modalities as $modality) {
                if ($modality->schedules->isEmpty()) {
                    continue;
                }

                $scheduleTemplates[] = [
                    'id' => (string) $modality->id,
                    'modality' => $modality->modality,
                    'label' => $assignment->course->name . ' · ' . $modality->modality . ' · NRC ' . ($modality->nrc ?: 'sin asignar'),
                    'start_date' => $modality->start_date?->format('Y-m-d'),
                    'end_date' => $modality->end_date?->format('Y-m-d'),
                    'total_hours' => $modality->total_hours,
                    'schedules' => $modality->schedules->map(fn ($schedule) => [
                        'day_of_week' => (int) $schedule->day_of_week,
                        'start_time' => substr((string) $schedule->start_time, 0, 5),
                        'end_time' => substr((string) $schedule->end_time, 0, 5),
                        'classroom' => $schedule->classroom,
                        'block_type' => $schedule->block_type ?: 'CLASS',
                    ])->values()->all(),
                ];
            }
        }

        return view('assignments.create', compact('group', 'scheduleTemplates'));
    }

    public function store(Request $request, TeachingGroup $group, CalendarService $calendar)
    {
        $this->authorizeGroup($group);

        $data = $request->validate([
            'course_name' => 'required|string|max:255',
            'course_code' => 'nullable|string|max:80',
            'career' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:50',
            'assigned_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'has_tec' => 'nullable|boolean',
            'has_tall' => 'nullable|boolean',
            'tec_nrc' => 'nullable|string|max:30',
            'tec_start_date' => 'nullable|date',
            'tec_end_date' => 'nullable|date|after_or_equal:tec_start_date',
            'tec_total_hours' => 'nullable|numeric|min:0',
            'tec_days' => 'nullable|array',
            'tec_days.*' => 'integer|min:1|max:7',
            'tec_start_time' => 'nullable|date_format:H:i',
            'tec_end_time' => 'nullable|date_format:H:i',
            'tec_classroom' => 'nullable|string|max:100',
            'tall_nrc' => 'nullable|string|max:30',
            'tall_start_date' => 'nullable|date',
            'tall_end_date' => 'nullable|date|after_or_equal:tall_start_date',
            'tall_total_hours' => 'nullable|numeric|min:0',
            'tall_days' => 'nullable|array',
            'tall_days.*' => 'integer|min:1|max:7',
            'tall_start_time' => 'nullable|date_format:H:i',
            'tall_end_time' => 'nullable|date_format:H:i',
            'tall_classroom' => 'nullable|string|max:100',
            'tec_schedules' => 'nullable|array',
            'tec_schedules.*.day_of_week' => 'nullable|integer|min:1|max:7',
            'tec_schedules.*.start_time' => 'nullable|date_format:H:i',
            'tec_schedules.*.end_time' => 'nullable|date_format:H:i',
            'tec_schedules.*.classroom' => 'nullable|string|max:100',
            'tec_schedules.*.block_type' => 'nullable|in:CLASS,ASYNC',
            'tall_schedules' => 'nullable|array',
            'tall_schedules.*.day_of_week' => 'nullable|integer|min:1|max:7',
            'tall_schedules.*.start_time' => 'nullable|date_format:H:i',
            'tall_schedules.*.end_time' => 'nullable|date_format:H:i',
            'tall_schedules.*.classroom' => 'nullable|string|max:100',
            'tall_schedules.*.block_type' => 'nullable|in:CLASS,ASYNC',
        ], [
            'tec_end_date.after_or_equal' => 'La fecha final de TEC debe ser igual o posterior a la fecha inicial.',
            'tall_end_date.after_or_equal' => 'La fecha final de TALL debe ser igual o posterior a la fecha inicial.',
        ]);

        if (!$request->boolean('has_tec') && !$request->boolean('has_tall')) {
            throw ValidationException::withMessages(['modalities' => 'Selecciona al menos una modalidad: TEC, TALL o ambas.']);
        }

        $professorId = $group->professor_id;
        $courseName = trim($data['course_name']);
        $code = trim($data['course_code'] ?? '') ?: Str::upper(Str::slug($courseName, '-'));

        $existingCourse = Course::where('professor_id', $professorId)
            ->where('code', $code)
            ->first();

        $existingAssignment = $existingCourse
            ? $group->assignments()->with('modalities')->where('course_id', $existingCourse->id)->first()
            : null;

        $requestedTypes = [];
        if ($request->boolean('has_tec')) {
            $requestedTypes[] = 'TEC';
        }
        if ($request->boolean('has_tall')) {
            $requestedTypes[] = 'TALL';
        }

        if ($existingAssignment) {
            $existingTypes = $existingAssignment->modalities->pluck('modality')->all();
            if (empty(array_diff($requestedTypes, $existingTypes))) {
                throw ValidationException::withMessages([
                    'course_name' => 'Este curso ya tiene las modalidades seleccionadas en el grupo. No se modificó ningún NRC ni horario existente.',
                ]);
            }
        }

        if ($existingCourse && mb_strtolower(trim($existingCourse->name)) !== mb_strtolower($courseName)) {
            throw ValidationException::withMessages([
                'course_code' => "El código {$code} ya pertenece al curso «{$existingCourse->name}». Verifica los datos.",
            ]);
        }

        $sameName = Course::where('professor_id', $professorId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($courseName)])
            ->first();

        if ($sameName && mb_strtolower((string) $sameName->code) !== mb_strtolower($code)) {
            throw ValidationException::withMessages([
                'course_code' => "Ya existe «{$courseName}» con el código {$sameName->code}. Reutiliza ese código para evitar duplicados.",
            ]);
        }

        $course = Course::firstOrCreate(
            ['professor_id' => $professorId, 'code' => $code],
            ['name' => $courseName, 'career' => $data['career'] ?? null, 'module' => $data['module'] ?? null]
        );

        $defaultCurriculum = $course->curricula()->where('is_active', true)->with('weeks')->first();
        $assignment = $existingAssignment ?: $group->assignments()->create([
            'course_id' => $course->id,
            'curriculum_id' => $defaultCurriculum?->id,
            'assigned_at' => $data['assigned_at'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);

        if (!$assignment->curriculum_id && $defaultCurriculum) {
            $assignment->update(['curriculum_id' => $defaultCurriculum->id]);
        }

        foreach (['TEC' => 'tec', 'TALL' => 'tall'] as $kind => $prefix) {
            if (!$request->boolean('has_' . $prefix)) {
                continue;
            }

            if ($assignment->modalities()->where('modality', $kind)->exists()) {
                continue;
            }

            $modality = $assignment->modalities()->create([
                'modality' => $kind,
                'nrc' => $data[$prefix . '_nrc'] ?? null,
                'start_date' => $data[$prefix . '_start_date'] ?? null,
                'end_date' => $data[$prefix . '_end_date'] ?? null,
                'total_hours' => $data[$prefix . '_total_hours'] ?? null,
                'curriculum_start_week' => 1,
                'active' => true,
                'semester' => $data['module'] ?? null,
                'period_label' => $group->cycle->academic_year . '-' . $group->cycle->cycle_code . '-' . $kind,
            ]);
            foreach (($data[$prefix . '_schedules'] ?? []) as $schedule) {
                if (empty($schedule['day_of_week']) || empty($schedule['start_time']) || empty($schedule['end_time']) || $schedule['end_time'] <= $schedule['start_time']) {
                    continue;
                }

                $modality->schedules()->create([
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'classroom' => $schedule['classroom'] ?? null,
                    'block_type' => $schedule['block_type'] ?? 'CLASS',
                ]);
            }

            if (empty($data[$prefix . '_schedules']) && !empty($data[$prefix . '_days']) && isset($data[$prefix . '_start_time'], $data[$prefix . '_end_time'])) {
                foreach (array_unique($data[$prefix . '_days']) as $day) {
                    if ($data[$prefix . '_end_time'] > $data[$prefix . '_start_time']) {
                        $modality->schedules()->create([
                            'day_of_week' => $day,
                            'start_time' => $data[$prefix . '_start_time'],
                            'end_time' => $data[$prefix . '_end_time'],
                            'classroom' => $data[$prefix . '_classroom'] ?? null,
                            'block_type' => 'CLASS',
                        ]);
                    }
                }
            }

            if ($modality->start_date && $modality->end_date && $modality->schedules()->where('block_type', 'CLASS')->exists()) {
                $calendar->rebuild($modality);
            }
        }

        return redirect()->route('groups.show', $group)->with(
            'success',
            'Curso guardado. Puedes abrir cada modalidad para revisar sus alumnos, calendario, evaluaciones y documentos.'
        );
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
