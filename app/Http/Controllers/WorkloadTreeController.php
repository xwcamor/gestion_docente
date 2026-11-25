<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\StudentImportService;
use App\Services\CourseRegistrationService;
use App\Services\CalendarService;
use App\Models\CourseModality;

class WorkloadTreeController extends Controller
{
    public function index(Cycle $cycle, Request $request)
    {
        $this->authorizeCycle($cycle);

        $term = trim((string) $request->query('q', ''));
        $groupsQuery = $cycle->groups()
            ->withCount(['assignments', 'students'])
            ->orderBy('name');

        if ($term !== '' && !$this->cycleMatches($cycle, $term)) {
            $like = '%'.$term.'%';
            $groupsQuery->where(function ($groups) use ($like) {
                $groups->where('name', 'like', $like)
                    ->orWhereHas('assignments.course', function ($courses) use ($like) {
                        $courses->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('assignments.modalities', function ($modalities) use ($like) {
                        $modalities->where('nrc', 'like', $like)
                            ->orWhere('block_code', 'like', $like)
                            ->orWhere('block_reference', 'like', $like);
                    });
            });
        }

        $groups = $groupsQuery->get();

        return view('cycles.tree.index', compact('cycle', 'groups', 'term'));
    }

    public function groups(Cycle $cycle, Request $request)
    {
        $this->authorizeCycle($cycle);

        $term = trim((string) $request->query('q', ''));
        $query = $cycle->groups()
            ->withCount(['assignments', 'students'])
            ->orderBy('name');

        if ($term !== '' && !$this->cycleMatches($cycle, $term)) {
            $like = '%'.$term.'%';
            $query->where(function ($groups) use ($like) {
                $groups->where('name', 'like', $like)
                    ->orWhereHas('assignments.course', function ($courses) use ($like) {
                        $courses->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('assignments.modalities', function ($modalities) use ($like) {
                        $modalities->where('nrc', 'like', $like)
                            ->orWhere('block_code', 'like', $like)
                            ->orWhere('block_reference', 'like', $like);
                    });
            });
        }

        $groups = $query->get();

        return view('cycles.tree.groups', compact('cycle', 'groups'));
    }

    public function courses(TeachingGroup $group, Request $request)
    {
        $this->authorizeGroup($group);
        $group->loadMissing('cycle');

        $term = trim((string) $request->query('q', ''));
        $query = $group->assignments()
            ->with(['course', 'curriculum.weeks', 'modalities:id,course_assignment_id,modality,nrc'])
            ->withCount('modalities')
            ->orderBy('id');

        if ($term !== '' && !$this->cycleMatches($group->cycle, $term) && !$this->groupMatches($group, $term)) {
            $like = '%'.$term.'%';
            $query->where(function ($assignments) use ($like) {
                $assignments->whereHas('course', function ($courses) use ($like) {
                    $courses->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                })->orWhereHas('modalities', function ($modalities) use ($like) {
                    $modalities->where('nrc', 'like', $like)
                        ->orWhere('block_code', 'like', $like)
                        ->orWhere('block_reference', 'like', $like);
                });
            });
        }

        $assignments = $query->get();

        return view('cycles.tree.courses', compact('group', 'assignments'));
    }

    public function modalities(CourseAssignment $assignment)
    {
        $assignment->loadMissing(['group.cycle', 'course', 'curriculum.weeks']);
        $this->authorizeGroup($assignment->group);

        $modalities = $assignment->modalities()
            ->with('schedules')
            ->withCount('documents')
            ->orderByRaw("CASE WHEN modality = 'TEC' THEN 0 WHEN modality = 'TALL' THEN 1 ELSE 2 END")
            ->get();

        return view('cycles.tree.modalities', compact('assignment', 'modalities'));
    }

    public function storeCourse(
        Request $request,
        TeachingGroup $group,
        CourseRegistrationService $registration,
        StudentImportService $students,
        CalendarService $calendar
    ) {
        $this->authorizeGroup($group);
        $group->loadMissing('cycle');

        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:80', 'regex:/^(20\d{4}-)?[A-Z][A-Z0-9]{1,9}-[A-Z0-9]{1,12}$/i'],
            'course_name' => ['required', 'string', 'max:255'],
            'components' => ['required', 'array', 'min:1'],
            'components.*' => ['required', 'distinct', 'in:TEC,TALL'],
            'tec_nrc' => ['nullable', 'digits_between:3,12'],
            'tall_nrc' => ['nullable', 'digits_between:3,12'],
            'tec_start_date' => ['nullable', 'date'],
            'tec_end_date' => ['nullable', 'date'],
            'tall_start_date' => ['nullable', 'date'],
            'tall_end_date' => ['nullable', 'date'],
            'tec_days' => ['nullable', 'array'],
            'tec_days.*' => ['integer', 'between:1,7'],
            'tall_days' => ['nullable', 'array'],
            'tall_days.*' => ['integer', 'between:1,7'],
            'tec_start_time' => ['nullable', 'date_format:H:i'],
            'tec_end_time' => ['nullable', 'date_format:H:i'],
            'tall_start_time' => ['nullable', 'date_format:H:i'],
            'tall_end_time' => ['nullable', 'date_format:H:i'],
            'tec_classroom' => ['nullable', 'string', 'max:100'],
            'tall_classroom' => ['nullable', 'string', 'max:100'],
        ], [
            'course_code.regex' => 'Usa el código base institucional, por ejemplo 202620-PIAD-224.',
            'components.required' => 'Selecciona al menos una modalidad: TEC o TAL.',
            'components.min' => 'Selecciona al menos una modalidad: TEC o TAL.',
        ]);

        $codes = [];
        $modalityDates = [];
        $modalitySchedules = [];

        foreach (['TEC' => 'tec', 'TALL' => 'tall'] as $type => $prefix) {
            if (!in_array($type, $data['components'], true)) {
                continue;
            }

            $nrc = trim((string) ($data[$prefix.'_nrc'] ?? ''));
            $codes[] = strtoupper(trim($data['course_code'])).'-'.$type
                .($nrc !== '' ? '-NRC_'.$nrc : '');

            $startDate = $data[$prefix.'_start_date'] ?? null;
            $endDate = $data[$prefix.'_end_date'] ?? null;

            if (filled($startDate) xor filled($endDate)) {
                throw ValidationException::withMessages([
                    $prefix.'_start_date' => 'Completa tanto la fecha de inicio como la fecha de fin.',
                    $prefix.'_end_date' => 'Completa tanto la fecha de inicio como la fecha de fin.',
                ]);
            }

            if (filled($startDate) && filled($endDate) && $endDate < $startDate) {
                throw ValidationException::withMessages([
                    $prefix.'_end_date' => 'La fecha de fin debe ser igual o posterior al inicio.',
                ]);
            }

            $modalityDates[$type] = [
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ];

            $days = array_values(array_unique(array_map('intval', $data[$prefix.'_days'] ?? [])));
            $startTime = $data[$prefix.'_start_time'] ?? null;
            $endTime = $data[$prefix.'_end_time'] ?? null;
            $hasScheduleInput = $days !== [] || filled($startTime) || filled($endTime);

            if ($hasScheduleInput) {
                if (!$days || !$startTime || !$endTime || $endTime <= $startTime) {
                    throw ValidationException::withMessages([
                        $prefix.'_days' => 'Para crear el horario selecciona días y una hora de inicio y fin válida.',
                    ]);
                }

                $modalitySchedules[$type] = [[
                    'days' => $days,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'repeat_interval_weeks' => 1,
                    'instruction_type' => $type === 'TEC' ? 'THEORY' : 'LAB',
                    'classroom' => trim((string) ($data[$prefix.'_classroom'] ?? '')),
                ]];
            } else {
                $modalitySchedules[$type] = [];
            }
        }

        $component = count($data['components']) === 2 ? 'BOTH' : $data['components'][0];
        $result = $registration->register(
            $group,
            $data['course_name'],
            implode('; ', $codes),
            $component,
            $students,
            $modalityDates,
            $modalitySchedules
        );

        $calendarWarnings = [];
        if ($result['schedule_count'] > 0) {
            foreach ($result['assignment']->modalities->whereIn('modality', $result['created_modalities']) as $createdModality) {
                if (!$createdModality->start_date || !$createdModality->end_date) {
                    continue;
                }

                try {
                    $calendar->rebuild($createdModality);
                } catch (\Throwable $exception) {
                    report($exception);
                    $calendarWarnings[] = 'El curso se guardó, pero no se pudo generar uno de sus calendarios. Revisa ese horario.';
                }
            }
        }

        return response()->json([
            'message' => 'Curso agregado al grupo.',
            'group_id' => $group->id,
            'assignment_id' => $result['assignment']->id,
            'course_name' => $result['course']->name,
            'created_modalities' => $result['created_modalities'],
            'warnings' => $calendarWarnings,
        ]);
    }

    public function updateModalityDates(Request $request, CourseModality $modality, CalendarService $calendar)
    {
        $modality->loadMissing('assignment.group');
        $this->authorizeGroup($modality->assignment->group);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        if (filled($startDate) xor filled($endDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Para guardar el periodo debes completar ambas fechas.',
                'end_date' => 'Para guardar el periodo debes completar ambas fechas.',
            ]);
        }

        if (!$startDate && !$endDate && $modality->sessions()->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'Esta modalidad ya tiene sesiones generadas. No borres las fechas desde aquí; modifica el periodo y recalcula el calendario.',
            ]);
        }

        if ($startDate && $endDate && $endDate < $startDate) {
            throw ValidationException::withMessages([
                'end_date' => 'La fecha de fin debe ser igual o posterior al inicio.',
            ]);
        }

        $modality->update([
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
        ]);

        $calendarUpdated = false;
        if ($startDate && $endDate && $modality->schedules()->where('block_type', 'CLASS')->exists()) {
            $calendar->rebuild($modality->fresh());
            $calendarUpdated = true;
        }

        return response()->json([
            'message' => $calendarUpdated
                ? 'Fechas guardadas y calendario actualizado.'
                : 'Fechas guardadas. El calendario se generará cuando exista un horario completo.',
            'start_date' => $modality->start_date?->toDateString(),
            'end_date' => $modality->end_date?->toDateString(),
            'calendar_updated' => $calendarUpdated,
        ]);
    }

    public function updateModalityNrc(Request $request, CourseModality $modality)
    {
        $modality->loadMissing('assignment.group');
        $this->authorizeGroup($modality->assignment->group);

        $data = $request->validate([
            'nrc' => ['nullable', 'digits_between:3,12'],
        ]);

        $modality->update(['nrc' => filled($data['nrc'] ?? null) ? $data['nrc'] : null]);

        return response()->json([
            'message' => 'NRC actualizado.',
            'nrc' => $modality->nrc,
        ]);
    }

    private function cycleMatches(Cycle $cycle, string $term): bool
    {
        $searchable = implode(' ', [
            $cycle->academic_year,
            $cycle->cycle_code,
            $cycle->career,
            $cycle->semester,
        ]);

        return str_contains(mb_strtolower($searchable), mb_strtolower($term));
    }

    private function groupMatches(TeachingGroup $group, string $term): bool
    {
        return str_contains(mb_strtolower((string) $group->name), mb_strtolower($term));
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless((int) $cycle->professor_id === (int) auth()->user()->professor->id, 404);
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless((int) $group->professor_id === (int) auth()->user()->professor->id, 404);
    }
}
