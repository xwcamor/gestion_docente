<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\StudentImportService;
use App\Services\CourseRegistrationService;
use App\Services\CalendarService;
use App\Models\CourseModality;

class WorkloadTreeController extends Controller
{
    public function index(Cycle $cycle, Request $request)
    {
        $this->authorizeCycle($cycle);

        $cycleGroupCount = $cycle->groups()->count();
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

        return view('cycles.tree.index', compact('cycle', 'groups', 'term', 'cycleGroupCount'));
    }

    public function updateCycleContext(Request $request, Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        $data = $request->validate([
            'career' => ['sometimes', 'required', 'string', 'max:255'],
            'semester' => ['sometimes', 'required', 'string', 'max:60'],
        ], [
            'career.required' => 'Escribe el nombre de la carrera.',
            'semester.required' => 'Escribe el semestre.',
        ]);

        if (!$data) {
            return response()->json([
                'message' => 'No hay datos del ciclo para actualizar.',
            ], 422);
        }

        $cycle->update($data);
        $cycle->refresh();

        return response()->json([
            'message' => 'Datos académicos del ciclo actualizados.',
            'career' => $cycle->career,
            'semester' => $cycle->semester,
        ]);
    }

    public function groups(Cycle $cycle, Request $request)
    {
        $this->authorizeCycle($cycle);

        $cycleGroupCount = $cycle->groups()->count();
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

        return view('cycles.tree.groups', compact('cycle', 'groups', 'cycleGroupCount'));
    }

    public function courses(TeachingGroup $group, Request $request)
    {
        $this->authorizeGroup($group);
        $group->loadMissing('cycle');
        $group->loadCount('assignments');

        $term = trim((string) $request->query('q', ''));
        $query = $group->assignments()
            ->with(['group', 'course', 'modalities' => fn ($modalities) => $modalities->with('schedules')->withCount('documents')])
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
        $assignment->loadMissing(['group.cycle', 'course']);
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
        StudentImportService $students
    ) {
        $this->authorizeGroup($group);

        $data = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['nullable', 'string', 'max:80', 'regex:/^(20\d{4}-)?[A-Z][A-Z0-9]{1,9}-[A-Z0-9]{1,12}$/i'],
            'components' => ['required', 'array', 'min:1'],
            'components.*' => ['required', 'distinct', 'in:TEC,TALL'],
        ], [
            'course_name.required' => 'Escribe el nombre del curso.',
            'course_code.regex' => 'El código opcional debe tener el formato institucional, por ejemplo 202620-PIAD-222.',
            'components.required' => 'Deja al menos una modalidad activa: TEC o TAL.',
            'components.min' => 'Deja al menos una modalidad activa: TEC o TAL.',
        ]);

        $sourceCode = '';
        $baseCode = trim((string) ($data['course_code'] ?? ''));
        if ($baseCode !== '') {
            $sourceCode = implode('; ', array_map(
                fn ($type) => strtoupper($baseCode).'-'.$type,
                $data['components']
            ));
        }

        $component = count($data['components']) === 2 ? 'BOTH' : $data['components'][0];
        $result = $registration->register(
            $group,
            trim($data['course_name']),
            $sourceCode,
            $component,
            $students
        );

        return response()->json([
            'message' => 'Curso agregado. Completa NRC, fechas y horario directamente en los nodos TEC y TAL.',
            'group_id' => $group->id,
            'assignment_id' => $result['assignment']->id,
            'assignments_count' => $group->assignments()->count(),
            'course_id' => $result['course']->id,
            'course_name' => $result['course']->name,
            'course_code' => $result['course']->code,
            'created_modalities' => $result['created_modalities'],
            'warnings' => [],
        ]);
    }

    public function storeModality(
        Request $request,
        CourseAssignment $assignment,
        string $type,
        StudentImportService $students
    ) {
        $assignment->loadMissing(['group.cycle']);
        $this->authorizeGroup($assignment->group);

        abort_unless(in_array($type, ['TEC', 'TALL'], true), 404);

        $modality = DB::transaction(function () use ($assignment, $type, $students) {
            $lockedAssignment = CourseAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = $lockedAssignment->modalities()->where('modality', $type)->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'modality' => 'Este componente ya existe. Actívalo desde Edición rápida.',
                ]);
            }

            $modality = $lockedAssignment->modalities()->create([
                'modality' => $type,
                'active' => true,
                'curriculum_start_week' => 1,
                'period_label' => $assignment->group->cycle->academic_year.'-'.$assignment->group->cycle->cycle_code.'-'.$type,
            ]);

            $students->syncGroupRosterToModality($modality);

            return $modality;
        });

        return response()->json([
            'message' => ($type === 'TEC' ? 'Teoría' : 'Taller').' agregado y activado.',
            'modality_id' => $modality->id,
            'active' => true,
        ]);
    }

    public function updateCourseCode(Request $request, CourseAssignment $assignment)
    {
        $assignment->loadMissing(['group', 'course']);
        $this->authorizeGroup($assignment->group);

        $request->merge([
            'code' => trim((string) $request->input('code')) ?: null,
        ]);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:80'],
        ], [
            'code.max' => 'El código del curso no puede superar los 80 caracteres.',
        ]);

        $code = filled($data['code'] ?? null) ? mb_strtoupper(trim($data['code'])) : null;
        if ($code !== null) {
            $duplicate = Course::query()
                ->where('professor_id', $assignment->group->professor_id)
                ->where('code', $code)
                ->where('id', '<>', $assignment->course_id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'code' => 'Ese código ya pertenece a otro curso del catálogo.',
                ]);
            }
        }

        $assignment->course->update(['code' => $code]);

        return response()->json([
            'message' => 'Código institucional actualizado en todos los grupos donde se utiliza este curso.',
            'code' => $assignment->course->fresh()->code,
        ]);
    }

    public function updateCourseName(Request $request, CourseAssignment $assignment)
    {
        $assignment->loadMissing(['group', 'course']);
        $this->authorizeGroup($assignment->group);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Escribe el nombre del curso.',
            'name.max' => 'El nombre del curso no puede superar los 255 caracteres.',
        ]);

        $assignment->course->update(['name' => trim($data['name'])]);

        return response()->json([
            'message' => 'Nombre del curso actualizado.',
            'name' => $assignment->course->fresh()->name,
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
        $calendarWarning = null;
        if ($startDate && $endDate && $modality->schedules()->where('block_type', 'CLASS')->exists()) {
            try {
                $calendar->rebuild($modality->fresh());
                $calendarUpdated = true;
            } catch (\Throwable $exception) {
                report($exception);
                $calendarWarning = 'Las fechas se guardaron, pero el calendario no pudo recalcularse. Revisa el horario de esta modalidad.';
            }
        }

        return response()->json([
            'message' => $calendarWarning
                ?: ($calendarUpdated
                    ? 'Fechas guardadas y calendario actualizado.'
                    : 'Fechas guardadas. El calendario se generará cuando exista un horario completo.'),
            'start_date' => $modality->start_date?->toDateString(),
            'end_date' => $modality->end_date?->toDateString(),
            'calendar_updated' => $calendarUpdated,
            'warning' => $calendarWarning,
        ]);
    }

    public function updateModalityActive(Request $request, CourseModality $modality, CalendarService $calendar)
    {
        $modality->loadMissing('assignment.group');
        $this->authorizeGroup($modality->assignment->group);

        $data = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $active = (bool) $data['active'];
        $switchedToModalityId = null;

        DB::transaction(function () use ($modality, $active, &$switchedToModalityId) {
            $assignment = CourseAssignment::query()
                ->whereKey($modality->course_assignment_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedModality = CourseModality::query()
                ->whereKey($modality->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$active && $lockedModality->active) {
                $activeCount = $assignment->modalities()->where('active', true)->count();

                if ($activeCount <= 1) {
                    // Si existe el otro componente, cambiar de TEC a TAL (o viceversa)
                    // en una sola transacción; nunca se deja el curso sin componente activo.
                    $replacement = $assignment->modalities()
                        ->where('id', '<>', $lockedModality->id)
                        ->where('active', false)
                        ->lockForUpdate()
                        ->first();

                    if (!$replacement) {
                        throw ValidationException::withMessages([
                            'active' => 'El curso debe conservar al menos un componente activo. Agrega o activa el otro componente antes de desactivar este.',
                        ]);
                    }

                    $replacement->update(['active' => true]);
                    $switchedToModalityId = $replacement->id;
                }
            }

            $lockedModality->update(['active' => $active]);
        });

        $calendarTarget = $active
            ? $modality->fresh()
            : ($switchedToModalityId ? CourseModality::query()->find($switchedToModalityId) : null);

        $warning = null;
        $calendarUpdated = false;

        if ($calendarTarget && $calendarTarget->active && $calendarTarget->start_date && $calendarTarget->end_date
            && $calendarTarget->schedules()->where('block_type', 'CLASS')->exists()) {
            try {
                $calendar->rebuild($calendarTarget);
                $calendarUpdated = true;
            } catch (\Throwable $exception) {
                report($exception);
                $warning = 'El componente cambió de estado, pero el calendario no pudo recalcularse. Revisa sus fechas y horario.';
            }
        }

        $activeCount = $modality->assignment->modalities()->where('active', true)->count();
        $switchedTo = $switchedToModalityId
            ? CourseModality::query()->whereKey($switchedToModalityId)->value('modality')
            : null;

        return response()->json([
            'message' => $warning ?: (
                $switchedTo
                    ? 'Cambio realizado: ahora está activo '.($switchedTo === 'TEC' ? 'TEC (Teoría)' : 'TAL (Taller)').'.'
                    : ($active
                        ? ($calendarUpdated ? 'Componente activado y calendario actualizado.' : 'Componente activado.')
                        : 'Componente desactivado. Se conservaron su NRC, fechas, horarios e historial.')
            ),
            'active' => $active,
            'active_count' => $activeCount,
            'switched_to' => $switchedTo,
            'warning' => $warning,
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
