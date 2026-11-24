<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\TeachingGroup;
use App\Models\Student;
use App\Models\CourseModality;
use App\Models\GeneratedDocument;
use App\Models\ImportBatch;
use App\Models\GroupFiveMinuteTalk;
use App\Services\AuditLogger;
use App\Services\FiveMinuteTalkService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroupController extends Controller
{
    public function create(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        return redirect()->route('cycles.show', [
            'cycle' => $cycle,
            'nuevo_grupo' => 1,
        ]);
    }

    public function store(Request $request, Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        // Compatibilidad con formularios anteriores que envíen un solo grupo.
        $groupsInput = $request->input('groups');
        if (!is_array($groupsInput)) {
            $groupsInput = [[
                'name' => $request->input('name'),
                'assigned_at' => $request->input('assigned_at'),
            ]];
        }

        $request->merge(['groups' => $groupsInput]);

        $validator = Validator::make($request->all(), [
            'groups' => ['required', 'array', 'min:1', 'max:30'],
            'groups.*.name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('teaching_groups', 'name')
                    ->where(fn ($query) => $query->where('cycle_id', $cycle->id)),
            ],
            'groups.*.assigned_at' => ['nullable', 'date'],
            'groups.*.workload_type' => ['nullable', Rule::in(['PARTIAL', 'FULL'])],
            'groups.*.workload_start_date' => ['nullable', 'date'],
            'groups.*.workload_end_date' => ['nullable', 'date'],
            'groups.*.workload_days' => ['nullable', 'array'],
            'groups.*.workload_days.*' => ['integer', 'min:1', 'max:7'],
            'groups.*.workload_start_time' => ['nullable', 'date_format:H:i'],
            'groups.*.workload_end_time' => ['nullable', 'date_format:H:i'],
            'groups.*.supervisor_theory_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'groups.*.supervisor_lab_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ], [
            'groups.*.name.required' => 'Escribe el nombre de cada grupo.',
            'groups.*.name.unique' => 'Ya existe un grupo con ese nombre en este ciclo.',
            'groups.max' => 'Puedes crear hasta 30 grupos por vez.',
            'groups.*.workload_type.in' => 'Selecciona si la carga es parcial o completa.',
            'groups.*.workload_start_date.date' => 'La fecha inicial de la carga no es válida.',
            'groups.*.workload_end_date.date' => 'La fecha final de la carga no es válida.',
            'groups.*.workload_days.*.between' => 'Selecciona días válidos para la carga horaria.',
            'groups.*.supervisor_theory_hours.numeric' => 'Las horas de teoría asignadas deben ser numéricas.',
            'groups.*.supervisor_lab_hours.numeric' => 'Las horas de taller asignadas deben ser numéricas.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('groups', []) as $index => $groupData) {
                $days = $groupData['workload_days'] ?? [];
                $hasWorkload = filled($groupData['workload_type'] ?? null)
                    || !empty($days)
                    || filled($groupData['workload_start_time'] ?? null)
                    || filled($groupData['workload_end_time'] ?? null)
                    || (($groupData['supervisor_theory_hours'] ?? '') !== '')
                    || (($groupData['supervisor_lab_hours'] ?? '') !== '');

                if (!$hasWorkload) {
                    continue;
                }

                $required = [
                    'workload_type' => 'Indica si la carga del grupo es parcial o completa.',
                    'workload_start_date' => 'Indica la fecha de inicio de la carga.',
                    'workload_end_date' => 'Indica la fecha de término de la carga.',
                    'workload_start_time' => 'Indica la hora inicial de la disponibilidad.',
                    'workload_end_time' => 'Indica la hora final de la disponibilidad.',
                    'supervisor_theory_hours' => 'Indica las horas de teoría asignadas por el supervisor (usa 0 si no corresponde).',
                    'supervisor_lab_hours' => 'Indica las horas de taller asignadas por el supervisor (usa 0 si no corresponde).',
                ];

                foreach ($required as $field => $message) {
                    if (!array_key_exists($field, $groupData) || $groupData[$field] === null || $groupData[$field] === '') {
                        $validator->errors()->add("groups.{$index}.{$field}", $message);
                    }
                }

                if (empty($days)) {
                    $validator->errors()->add("groups.{$index}.workload_days", 'Selecciona al menos un día de disponibilidad.');
                }

                if (
                    filled($groupData['workload_start_date'] ?? null)
                    && filled($groupData['workload_end_date'] ?? null)
                    && $groupData['workload_end_date'] < $groupData['workload_start_date']
                ) {
                    $validator->errors()->add("groups.{$index}.workload_end_date", 'La fecha final no puede ser anterior a la inicial.');
                }

                foreach (['workload_start_time', 'workload_end_time'] as $timeField) {
                    if (filled($groupData[$timeField] ?? null) && ((int) substr($groupData[$timeField], 3, 2) % 15 !== 0)) {
                        $validator->errors()->add("groups.{$index}.{$timeField}", 'Usa intervalos de 15 minutos (por ejemplo, 08:00, 08:15 o 08:30).');
                    }
                }

                if (
                    filled($groupData['workload_start_time'] ?? null)
                    && filled($groupData['workload_end_time'] ?? null)
                    && $groupData['workload_end_time'] <= $groupData['workload_start_time']
                ) {
                    $validator->errors()->add("groups.{$index}.workload_end_time", 'La hora final debe ser posterior a la inicial.');
                }
            }
        });

        $data = $validator->validate();

        $groups = collect($data['groups'])->map(function ($groupData) {
            $hasWorkload = filled($groupData['workload_type'] ?? null)
                || !empty($groupData['workload_days'] ?? [])
                || filled($groupData['workload_start_time'] ?? null)
                || filled($groupData['workload_end_time'] ?? null)
                || (($groupData['supervisor_theory_hours'] ?? '') !== '')
                || (($groupData['supervisor_lab_hours'] ?? '') !== '');

            return [
                'name' => trim($groupData['name']),
                'assigned_at' => $groupData['assigned_at'] ?? null,
                'workload_type' => $hasWorkload ? $groupData['workload_type'] : null,
                'workload_start_date' => $hasWorkload ? ($groupData['workload_start_date'] ?? null) : null,
                'workload_end_date' => $hasWorkload ? ($groupData['workload_end_date'] ?? null) : null,
                'workload_days' => $hasWorkload ? array_values(array_unique(array_map('intval', $groupData['workload_days'] ?? []))) : null,
                'workload_start_time' => $hasWorkload ? ($groupData['workload_start_time'] ?? null) : null,
                'workload_end_time' => $hasWorkload ? ($groupData['workload_end_time'] ?? null) : null,
                'supervisor_theory_hours' => $hasWorkload ? $groupData['supervisor_theory_hours'] : null,
                'supervisor_lab_hours' => $hasWorkload ? $groupData['supervisor_lab_hours'] : null,
            ];
        })->values();

        $normalizedNames = $groups->pluck('name')->map(fn ($name) => mb_strtolower($name));
        if ($normalizedNames->unique()->count() !== $normalizedNames->count()) {
            throw ValidationException::withMessages([
                'groups' => 'Hay nombres repetidos en la lista. Corrígelos antes de guardar.',
            ]);
        }

        DB::transaction(function () use ($groups, $cycle) {
            foreach ($groups as $groupData) {
                $cycle->groups()->create([
                    'professor_id' => $cycle->professor_id,
                    ...$groupData,
                ]);
            }
        });

        $count = $groups->count();

        return redirect()->route('cycles.show', $cycle)
            ->with('success', $count.' '.($count === 1 ? 'grupo creado' : 'grupos creados').' correctamente.');
    }

    public function update(Request $request, TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('teaching_groups', 'name')
                        ->ignore($group->id)
                        ->where(fn ($query) => $query->where('cycle_id', $group->cycle_id)),
                ],
                'assigned_at' => ['nullable', 'date'],
            ],
            ['name.unique' => 'Ya existe otro grupo con ese nombre en este ciclo.']
        );

        if ($validator->fails()) {
            return redirect()
                ->route('cycles.show', [
                    'cycle' => $group->cycle,
                    'dialog' => 'edit-group',
                    'group_id' => $group->id,
                    'page' => max(1, (int) $request->input('page', 1)),
                ])
                ->withErrors($validator)
                ->withInput();
        }

        $group->update($validator->validated());

        return redirect()->route('cycles.show', $group->cycle)
            ->with('success', 'Datos del grupo actualizados.');
    }

    public function show(TeachingGroup $group, FiveMinuteTalkService $talkService)
    {
        $this->authorizeGroup($group);
        $group->load('cycle');
        $talkService->ensureScheduled($group);

        $assignments = $group->assignments()
            ->with(['course', 'curriculum', 'modalities.schedules'])
            ->withCount('modalities')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $courseCount = $group->assignments()->count();
        $coursesWithModalityCount = $group->assignments()->whereHas('modalities')->count();
        $curriculumCount = $group->assignments()->whereNotNull('curriculum_id')->count();

        $modalityQuery = CourseModality::query()
            ->whereHas('assignment', fn ($query) => $query->where('teaching_group_id', $group->id));

        $modalityCount = (clone $modalityQuery)->count();
        $configuredModalityCount = (clone $modalityQuery)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereHas('schedules', fn ($query) => $query->where('block_type', 'CLASS'))
            ->count();

        $courseModalityPercent = $courseCount > 0
            ? (int) round(($coursesWithModalityCount / $courseCount) * 100)
            : 0;
        $curriculumPercent = $courseCount > 0
            ? (int) round(($curriculumCount / $courseCount) * 100)
            : 0;
        $schedulePercent = $modalityCount > 0
            ? (int) round(($configuredModalityCount / $modalityCount) * 100)
            : 0;

        $declaredWeeklyHours = (float) $group->assignments()
            ->join('course_modalities', 'course_modalities.course_assignment_id', '=', 'course_assignments.id')
            ->sum('course_modalities.total_hours');

        // Sumar tiempo efectivo sin duplicar minutos cuando dos bloques del mismo tipo
        // se superponen en el mismo día dentro del grupo.
        $actualScheduleBlocks = DB::table('modality_schedules')
            ->join('course_modalities', 'course_modalities.id', '=', 'modality_schedules.course_modality_id')
            ->join('course_assignments', 'course_assignments.id', '=', 'course_modalities.course_assignment_id')
            ->where('course_assignments.teaching_group_id', $group->id)
            ->where('modality_schedules.block_type', 'CLASS')
            ->get([
                'modality_schedules.day_of_week',
                'modality_schedules.start_time',
                'modality_schedules.end_time',
                'modality_schedules.instruction_type',
            ]);

        $actualHoursByType = $actualScheduleBlocks
            ->groupBy('instruction_type')
            ->map(function ($typeBlocks) {
                $totalMinutes = 0;

                foreach ($typeBlocks->groupBy('day_of_week') as $dayBlocks) {
                    $intervals = $dayBlocks->map(function ($block) {
                        $start = ((int) substr((string) $block->start_time, 0, 2) * 60)
                            + (int) substr((string) $block->start_time, 3, 2);
                        $end = ((int) substr((string) $block->end_time, 0, 2) * 60)
                            + (int) substr((string) $block->end_time, 3, 2);

                        return [$start, $end];
                    })->sortBy(fn ($interval) => $interval[0])->values();

                    $mergedStart = null;
                    $mergedEnd = null;

                    foreach ($intervals as [$start, $end]) {
                        if ($mergedStart === null) {
                            $mergedStart = $start;
                            $mergedEnd = $end;
                            continue;
                        }

                        if ($start <= $mergedEnd) {
                            $mergedEnd = max($mergedEnd, $end);
                            continue;
                        }

                        $totalMinutes += max(0, $mergedEnd - $mergedStart);
                        $mergedStart = $start;
                        $mergedEnd = $end;
                    }

                    if ($mergedStart !== null) {
                        $totalMinutes += max(0, $mergedEnd - $mergedStart);
                    }
                }

                return round($totalMinutes / 60, 2);
            });

        $actualTheoryHours = (float) ($actualHoursByType['THEORY'] ?? 0);
        $actualLabHours = (float) ($actualHoursByType['LAB'] ?? 0);
        $supervisorTheoryHours = (float) ($group->supervisor_theory_hours ?? 0);
        $supervisorLabHours = (float) ($group->supervisor_lab_hours ?? 0);
        $theoryHoursDifference = round($supervisorTheoryHours - $actualTheoryHours, 2);
        $labHoursDifference = round($supervisorLabHours - $actualLabHours, 2);

        $workloadHasDetails = filled($group->workload_type)
            || !empty($group->workload_days)
            || filled($group->workload_start_time)
            || filled($group->workload_end_time)
            || $group->supervisor_theory_hours !== null
            || $group->supervisor_lab_hours !== null;
        $workloadConfigured = in_array($group->workload_type, ['PARTIAL', 'FULL'], true)
            && $group->workload_start_date !== null
            && $group->workload_end_date !== null
            && !empty($group->workload_days)
            && filled($group->workload_start_time)
            && filled($group->workload_end_time)
            && $group->supervisor_theory_hours !== null
            && $group->supervisor_lab_hours !== null;
        $workloadStatus = $workloadConfigured ? 'complete' : ($workloadHasDetails ? 'partial' : 'pending');
        $workloadDaysLabels = collect($group->workload_days ?? [])
            ->map(fn ($day) => [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'][(int) $day] ?? null)
            ->filter()
            ->values();

        $studentCount = $group->students()->count();
        $students = $group->students()
            ->orderBy('students.surnames')
            ->orderBy('students.given_names')
            ->paginate(20, ['students.*'], 'students_page')
            ->withQueryString();
        $lastRosterImport = $group->importBatches()->latest()->first();

        $groupTalks = $group->fiveMinuteTalks()
            ->orderByRaw('scheduled_date IS NULL')
            ->orderBy('scheduled_date')
            ->orderBy('position')
            ->get();
        $talkAssignedCount = $groupTalks->whereNotNull('scheduled_date')->count();
        $talkDoneCount = $groupTalks->where('status', 'done')->count();
        $talkPendingCount = $groupTalks->count() - $talkDoneCount;

        $rosterStatus = $studentCount > 0 ? 'complete' : 'pending';
        $coursesStatus = $courseCount === 0
            ? 'pending'
            : (
                $coursesWithModalityCount === $courseCount
                && $modalityCount > 0
                && $configuredModalityCount === $modalityCount
                    ? 'complete'
                    : 'partial'
            );
        $talkStatus = $groupTalks->isEmpty()
            ? 'pending'
            : ($talkPendingCount === 0 ? 'complete' : ($talkDoneCount > 0 ? 'partial' : 'pending'));

        return view('groups.show', compact(
            'group',
            'assignments',
            'courseCount',
            'modalityCount',
            'coursesWithModalityCount',
            'curriculumCount',
            'configuredModalityCount',
            'courseModalityPercent',
            'curriculumPercent',
            'schedulePercent',
            'declaredWeeklyHours',
            'actualTheoryHours',
            'actualLabHours',
            'supervisorTheoryHours',
            'supervisorLabHours',
            'theoryHoursDifference',
            'labHoursDifference',
            'workloadConfigured',
            'workloadHasDetails',
            'workloadStatus',
            'workloadDaysLabels',
            'rosterStatus',
            'coursesStatus',
            'talkStatus',
            'studentCount',
            'students',
            'lastRosterImport',
            'groupTalks',
            'talkAssignedCount',
            'talkDoneCount',
            'talkPendingCount'
        ));
    }

    public function updateWorkload(Request $request, TeachingGroup $group)
    {
        $this->authorizeGroup($group);

        $validator = Validator::make($request->all(), [
            'workload_type' => ['nullable', Rule::in(['PARTIAL', 'FULL'])],
            'workload_start_date' => ['nullable', 'date'],
            'workload_end_date' => ['nullable', 'date'],
            'workload_days' => ['nullable', 'array'],
            'workload_days.*' => ['integer', 'min:1', 'max:7'],
            'workload_start_time' => ['nullable', 'date_format:H:i'],
            'workload_end_time' => ['nullable', 'date_format:H:i'],
            'supervisor_theory_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'supervisor_lab_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $days = $request->input('workload_days', []);
            $hasWorkload = filled($request->input('workload_type'))
                || !empty($days)
                || filled($request->input('workload_start_time'))
                || filled($request->input('workload_end_time'))
                || $request->input('supervisor_theory_hours') !== null && $request->input('supervisor_theory_hours') !== ''
                || $request->input('supervisor_lab_hours') !== null && $request->input('supervisor_lab_hours') !== '';

            if (!$hasWorkload) {
                return;
            }

            foreach ([
                'workload_type' => 'Selecciona carga parcial o completa.',
                'workload_start_date' => 'Indica la fecha inicial.',
                'workload_end_date' => 'Indica la fecha final.',
                'workload_start_time' => 'Indica la hora inicial.',
                'workload_end_time' => 'Indica la hora final.',
                'supervisor_theory_hours' => 'Indica las horas de teoría asignadas (usa 0 si no corresponde).',
                'supervisor_lab_hours' => 'Indica las horas de taller asignadas (usa 0 si no corresponde).',
            ] as $field => $message) {
                if ($request->input($field) === null || $request->input($field) === '') {
                    $validator->errors()->add($field, $message);
                }
            }

            if (empty($days)) {
                $validator->errors()->add('workload_days', 'Selecciona al menos un día.');
            }

            if (
                filled($request->input('workload_start_date'))
                && filled($request->input('workload_end_date'))
                && $request->input('workload_end_date') < $request->input('workload_start_date')
            ) {
                $validator->errors()->add('workload_end_date', 'La fecha final no puede ser anterior a la inicial.');
            }

            foreach (['workload_start_time', 'workload_end_time'] as $timeField) {
                $timeValue = $request->input($timeField);
                if (filled($timeValue) && ((int) substr($timeValue, 3, 2) % 15 !== 0)) {
                    $validator->errors()->add($timeField, 'Usa intervalos de 15 minutos (por ejemplo, 08:00, 08:15 o 08:30).');
                }
            }

            if (
                filled($request->input('workload_start_time'))
                && filled($request->input('workload_end_time'))
                && $request->input('workload_end_time') <= $request->input('workload_start_time')
            ) {
                $validator->errors()->add('workload_end_time', 'La hora final debe ser posterior a la inicial.');
            }
        });

        $data = $validator->validate();
        $hasWorkload = filled($data['workload_type'] ?? null)
            || !empty($data['workload_days'] ?? [])
            || filled($data['workload_start_time'] ?? null)
            || filled($data['workload_end_time'] ?? null)
            || (($data['supervisor_theory_hours'] ?? '') !== '')
            || (($data['supervisor_lab_hours'] ?? '') !== '');

        $group->update([
            'workload_type' => $hasWorkload ? $data['workload_type'] : null,
            'workload_start_date' => $hasWorkload ? ($data['workload_start_date'] ?? null) : null,
            'workload_end_date' => $hasWorkload ? ($data['workload_end_date'] ?? null) : null,
            'workload_days' => $hasWorkload ? array_values(array_unique(array_map('intval', $data['workload_days'] ?? []))) : null,
            'workload_start_time' => $hasWorkload ? ($data['workload_start_time'] ?? null) : null,
            'workload_end_time' => $hasWorkload ? ($data['workload_end_time'] ?? null) : null,
            'supervisor_theory_hours' => $hasWorkload ? $data['supervisor_theory_hours'] : null,
            'supervisor_lab_hours' => $hasWorkload ? $data['supervisor_lab_hours'] : null,
        ]);

        return redirect()->to(route('groups.show', $group).'#group-workload')
            ->with('success', $hasWorkload ? 'Carga horaria del grupo actualizada.' : 'Se retiró la asignación de carga horaria del grupo.');
    }

    public function updateTalkExecution(
        Request $request,
        TeachingGroup $group,
        GroupFiveMinuteTalk $talk,
        AuditLogger $audit
    ) {
        $this->authorizeGroup($group);
        abort_unless((int) $talk->teaching_group_id === (int) $group->id, 404);

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,done'],
            'participant_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $talk) {
            if ($request->input('status') === 'done' && $request->input('participant_count') === null) {
                $validator->errors()->add('participant_count', 'Indica cuántos participantes asistieron a la charla.');
            }

            if ($request->input('status') === 'done' && !$talk->scheduled_date) {
                $validator->errors()->add('status', 'No puedes marcarla como realizada hasta que el grupo tenga una fecha de clase asignada.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->to(route('groups.show', ['group' => $group, 'talks' => 1]).'#group-talks')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $before = $talk->only(['status', 'participant_count', 'observations']);
        $talk->update([
            'status' => $data['status'],
            'participant_count' => $data['participant_count'] ?? null,
            'observations' => $data['observations'] ?? null,
        ]);

        $audit->record(
            'charlas',
            'updated',
            'Se registró la ejecución de la charla del grupo '.$group->name.': '.$talk->topic,
            $talk,
            $before,
            $talk->only(['status', 'participant_count', 'observations'])
        );

        return redirect()
            ->to(route('groups.show', ['group' => $group, 'talks' => 1]).'#group-talks')
            ->with('success', 'Registro de charla de '.$group->name.' actualizado.');
    }

    public function destroy(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $cycle = $group->cycle;

        $modalityIds = CourseModality::query()
            ->whereHas('assignment', fn ($query) => $query->where('teaching_group_id', $group->id))
            ->pluck('id')
            ->all();

        $generatedPaths = GeneratedDocument::query()
            ->where(function ($query) use ($group, $modalityIds) {
                $query->where('teaching_group_id', $group->id);
                if ($modalityIds) {
                    $query->orWhereIn('course_modality_id', $modalityIds);
                }
            })
            ->pluck('stored_path');

        $importQuery = ImportBatch::query()->where('teaching_group_id', $group->id);
        if ($modalityIds) {
            $importQuery->orWhereIn('course_modality_id', $modalityIds);
        }
        $importPaths = $importQuery->pluck('stored_path');

        DB::transaction(fn () => $group->delete());

        Storage::disk('local')->delete(
            $generatedPaths->merge($importPaths)->filter()->unique()->values()->all()
        );

        return redirect()->route('cycles.show', $cycle)
            ->with('success', 'Grupo eliminado. Los cursos del catálogo y los alumnos que se usan en otros grupos se conservaron.');
    }

    public function updateStudent(Request $request, TeachingGroup $group, Student $student)
    {
        $this->authorizeGroup($group);

        abort_unless(
            $group->students()->whereKey($student->getKey())->exists(),
            404
        );

        $request->merge([
            'institutional_id' => trim((string) $request->input('institutional_id')) ?: null,
            'username' => trim((string) $request->input('username')) ?: null,
            'surnames' => trim((string) $request->input('surnames')),
            'given_names' => trim((string) $request->input('given_names')),
        ]);

        $validator = Validator::make($request->all(), [
            'institutional_id' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('students', 'institutional_id')
                    ->where(fn ($query) => $query->where('professor_id', $group->professor_id))
                    ->ignore($student->id),
            ],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('students', 'username')
                    ->where(fn ($query) => $query->where('professor_id', $group->professor_id))
                    ->ignore($student->id),
            ],
            'surnames' => ['required', 'string', 'max:255'],
            'given_names' => ['required', 'string', 'max:255'],
            'editing_student_id' => ['required', 'integer', 'in:'.$student->id],
            'students_page' => ['nullable', 'integer', 'min:1'],
        ], [
            'institutional_id.unique' => 'Ese ID de estudiante ya está registrado para otro alumno.',
            'username.unique' => 'Ese usuario ya está registrado para otro alumno.',
            'surnames.required' => 'Los apellidos son obligatorios.',
            'given_names.required' => 'Los nombres son obligatorios.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (blank($request->input('institutional_id')) && blank($request->input('username'))) {
                $validator->errors()->add('institutional_id', 'Debes indicar el ID institucional o el usuario del alumno.');
            }
        });

        $page = max(1, (int) $request->input('students_page', 1));
        if ($validator->fails()) {
            return redirect()
                ->to(route('groups.show', [
                    'group' => $group,
                    'students_page' => $page,
                    'edit_student' => $student->id,
                ]).'#group-roster')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $student->update([
            'institutional_id' => $validated['institutional_id'] ?? null,
            'username' => $validated['username'] ?? null,
            'surnames' => $validated['surnames'],
            'given_names' => $validated['given_names'],
        ]);

        return redirect()
            ->to(route('groups.show', [
                'group' => $group,
                'students_page' => $page,
            ]).'#group-roster')
            ->with('success', 'Datos del alumno actualizados. El cambio se refleja en los cursos donde está matriculado.');
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless($cycle->professor_id === auth()->user()->professor->id, 404);
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
