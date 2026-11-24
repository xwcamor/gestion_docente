<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\CourseModality;
use App\Models\GeneratedDocument;
use App\Models\ImportBatch;
use App\Models\CycleFiveMinuteTalk;
use App\Services\AuditLogger;
use App\Services\FiveMinuteTalkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CycleController extends Controller
{
    public function index(Request $request)
    {
        $professor = auth()->user()->professor;
        $query = $professor->cycles()
            ->withCount('groups')
            ->orderByDesc('academic_year')
            ->orderByDesc('start_date');

        if ($request->filled('year')) {
            $query->where('academic_year', $request->integer('year'));
        }

        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            $query->where(function ($builder) use ($term) {
                $builder->where('cycle_code', 'like', "%{$term}%")
                    ->orWhere('academic_year', 'like', "%{$term}%");
            });
        }

        $cycles = $query->paginate(12)->withQueryString();
        $years = $professor->cycles()
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');
        $groupCount = $professor->groups()->count();

        return view('cycles.index', compact('cycles', 'years', 'groupCount'));
    }

    public function create()
    {
        return redirect()->route('cycles.index', ['nuevo' => 1]);
    }

    public function store(Request $request)
    {
        $professorId = auth()->user()->professor->id;
        $request->merge(['cycle_code' => trim((string) $request->input('cycle_code'))]);

        $data = $request->validate(
            [
                'academic_year' => ['required', 'integer', 'min:2020', 'max:2100'],
                'cycle_code' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('cycles', 'cycle_code')->where(
                        fn ($query) => $query
                            ->where('professor_id', $professorId)
                            ->where('academic_year', $request->input('academic_year'))
                    ),
                ],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
            [
                'cycle_code.unique' => 'Ya existe un ciclo con ese año y código.',
                'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
            ]
        );

        $data['professor_id'] = $professorId;
        $cycle = Cycle::create($data);

        return redirect()->route('cycles.show', $cycle)
            ->with('success', 'Ciclo creado correctamente. Ahora puedes agregar sus grupos.');
    }

    public function update(Request $request, Cycle $cycle)
    {
        $this->authorizeCycle($cycle);
        $professorId = auth()->user()->professor->id;
        $request->merge(['cycle_code' => trim((string) $request->input('cycle_code'))]);

        $validator = Validator::make(
            $request->all(),
            [
                'academic_year' => ['required', 'integer', 'min:2020', 'max:2100'],
                'cycle_code' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('cycles', 'cycle_code')
                        ->ignore($cycle->id)
                        ->where(fn ($query) => $query
                            ->where('professor_id', $professorId)
                            ->where('academic_year', $request->input('academic_year'))),
                ],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
            [
                'cycle_code.unique' => 'Ya existe otro ciclo con ese año y código.',
                'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('cycles.show', ['cycle' => $cycle, 'dialog' => 'edit-cycle'])
                ->withErrors($validator)
                ->withInput();
        }

        $cycle->update($validator->validated());

        return redirect()->route('cycles.show', $cycle)->with('success', 'Datos del ciclo actualizados.');
    }

    public function show(Cycle $cycle, FiveMinuteTalkService $talkService)
    {
        $this->authorizeCycle($cycle);
        $talkService->ensureScheduled($cycle);

        $groups = $cycle->groups()
            ->withCount(['assignments', 'students'])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $groupCount = $cycle->groups()->count();
        $courseCount = DB::table('course_assignments')
            ->join('teaching_groups', 'teaching_groups.id', '=', 'course_assignments.teaching_group_id')
            ->where('teaching_groups.cycle_id', $cycle->id)
            ->count();
        $curriculumAssignedCount = DB::table('course_assignments')
            ->join('teaching_groups', 'teaching_groups.id', '=', 'course_assignments.teaching_group_id')
            ->where('teaching_groups.cycle_id', $cycle->id)
            ->whereNotNull('course_assignments.curriculum_id')
            ->count();

        $modalityCount = DB::table('course_modalities')
            ->join('course_assignments', 'course_assignments.id', '=', 'course_modalities.course_assignment_id')
            ->join('teaching_groups', 'teaching_groups.id', '=', 'course_assignments.teaching_group_id')
            ->where('teaching_groups.cycle_id', $cycle->id)
            ->count();

        $configuredModalityCount = DB::table('course_modalities')
            ->join('course_assignments', 'course_assignments.id', '=', 'course_modalities.course_assignment_id')
            ->join('teaching_groups', 'teaching_groups.id', '=', 'course_assignments.teaching_group_id')
            ->where('teaching_groups.cycle_id', $cycle->id)
            ->whereNotNull('course_modalities.start_date')
            ->whereNotNull('course_modalities.end_date')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('modality_schedules')
                    ->whereColumn('modality_schedules.course_modality_id', 'course_modalities.id')
                    ->where('modality_schedules.block_type', 'CLASS');
            })
            ->count();

        $curriculumPercent = $courseCount > 0
            ? (int) round(($curriculumAssignedCount / $courseCount) * 100)
            : 0;
        $schedulePercent = $modalityCount > 0
            ? (int) round(($configuredModalityCount / $modalityCount) * 100)
            : 0;

        $loadWeekdays = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
        ];
        $loadSchedules = \\App\\Models\\ModalitySchedule::query()
            ->with(['modality.assignment.course'])
            ->where('block_type', 'CLASS')
            ->whereHas('modality.assignment.group', fn ($query) => $query->where('cycle_id', $cycle->id))
            ->get();

        $timeToMinutes = fn ($time) => ((int) substr((string) $time, 0, 2) * 60) + (int) substr((string) $time, 3, 2);
        $loadBlocks = $loadSchedules->map(function ($schedule) use ($timeToMinutes) {
            $course = $schedule->modality->assignment->course;
            return [
                'day' => (int) $schedule->day_of_week,
                'start' => $timeToMinutes($schedule->start_time),
                'end' => $timeToMinutes($schedule->end_time),
                'course' => $course->code ?: $course->name,
                'room' => $schedule->classroom ?: 'Ambiente pendiente',
                'instruction_type' => $schedule->instruction_type ?? 'THEORY',
            ];
        })->values();

        $declaredWeeklyHours = round($loadBlocks->sum(fn ($block) => max(0, $block['end'] - $block['start'])) / 60, 2);
        $loadGridRows = [];
        $loadConflictCount = 0;
        if ($loadBlocks->isNotEmpty()) {
            $gridStart = intdiv((int) $loadBlocks->min('start'), 15) * 15;
            $gridEnd = (int) (ceil($loadBlocks->max('end') / 15) * 15);

            for ($minute = $gridStart; $minute < $gridEnd; $minute += 15) {
                $cells = [];
                foreach ($loadWeekdays as $day => $dayName) {
                    $active = $loadBlocks->filter(fn ($block) =>
                        $block['day'] === $day && $block['start'] < ($minute + 15) && $block['end'] > $minute
                    )->values();
                    $starts = $active->filter(fn ($block) => $block['start'] >= $minute && $block['start'] < ($minute + 15))->values();
                    $conflict = $active->count() > 1;
                    if ($conflict) {
                        $loadConflictCount++;
                    }
                    $cells[$day] = [
                        'active' => $active->isNotEmpty(),
                        'conflict' => $conflict,
                        'show' => $starts->isNotEmpty(),
                        'blocks' => $starts->all(),
                        'type' => $active->first()['instruction_type'] ?? 'THEORY',
                    ];
                }

                $loadGridRows[] = [
                    'start' => sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60),
                    'end' => sprintf('%02d:%02d', intdiv($minute + 15, 60), ($minute + 15) % 60),
                    'cells' => $cells,
                ];
            }
        }

        $cycleTalks = $cycle->fiveMinuteTalks()
            ->orderByRaw('scheduled_date IS NULL')
            ->orderBy('scheduled_date')
            ->orderBy('position')
            ->get();
        $talkAssignedCount = $cycleTalks->whereNotNull('scheduled_date')->count();
        $talkDoneCount = $cycleTalks->where('status', 'done')->count();
        $talkPendingCount = $cycleTalks->count() - $talkDoneCount;

        return view('cycles.show', compact(
            'cycle',
            'groups',
            'groupCount',
            'courseCount',
            'modalityCount',
            'declaredWeeklyHours',
            'loadWeekdays',
            'loadGridRows',
            'loadConflictCount',
            'curriculumAssignedCount',
            'configuredModalityCount',
            'curriculumPercent',
            'schedulePercent',
            'cycleTalks',
            'talkAssignedCount',
            'talkDoneCount',
            'talkPendingCount'
        ));
    }

    public function updateTalkExecution(Request $request, Cycle $cycle, CycleFiveMinuteTalk $talk, AuditLogger $audit)
    {
        $this->authorizeCycle($cycle);
        abort_unless((int) $talk->cycle_id === (int) $cycle->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,done'],
            'participant_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'done' && !array_key_exists('participant_count', $data)) {
            throw ValidationException::withMessages([
                'participant_count' => 'Indica cuántos participantes asistieron a la charla.',
            ]);
        }

        $before = $talk->only(['status', 'participant_count', 'observations']);
        $talk->update([
            'status' => $data['status'],
            'participant_count' => $data['participant_count'] ?? null,
            'observations' => $data['observations'] ?? null,
        ]);

        $audit->record('charlas', 'updated', 'Se registró la ejecución de la charla: '.$talk->topic, $talk,
            $before, $talk->only(['status', 'participant_count', 'observations']));

        return redirect()->route('cycles.show', ['cycle' => $cycle, 'talks' => 1])
            ->with('success', 'Registro de ejecución actualizado.');
    }

    public function destroy(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        $groupIds = $cycle->groups()->pluck('id')->all();
        $modalityIds = CourseModality::query()
            ->whereHas('assignment', fn ($query) => $query->whereIn('teaching_group_id', $groupIds))
            ->pluck('id')
            ->all();

        $generatedQuery = GeneratedDocument::query()->where('cycle_id', $cycle->id);
        if ($groupIds || $modalityIds) {
            $generatedQuery->orWhere(function ($query) use ($groupIds, $modalityIds) {
                if ($groupIds) {
                    $query->whereIn('teaching_group_id', $groupIds);
                }
                if ($modalityIds) {
                    $groupIds ? $query->orWhereIn('course_modality_id', $modalityIds) : $query->whereIn('course_modality_id', $modalityIds);
                }
            });
        }
        $generatedPaths = $generatedQuery->pluck('stored_path');

        $importQuery = ImportBatch::query();
        $hasImportScope = false;
        if ($groupIds) {
            $importQuery->whereIn('teaching_group_id', $groupIds);
            $hasImportScope = true;
        }
        if ($modalityIds) {
            $hasImportScope
                ? $importQuery->orWhereIn('course_modality_id', $modalityIds)
                : $importQuery->whereIn('course_modality_id', $modalityIds);
        }
        $importPaths = $hasImportScope ? $importQuery->pluck('stored_path') : collect();

        DB::transaction(fn () => $cycle->delete());

        Storage::disk('local')->delete(
            $generatedPaths->merge($importPaths)->filter()->unique()->values()->all()
        );

        return redirect()->route('cycles.index')
            ->with('success', 'Ciclo y sus grupos fueron eliminados. Los registros del catálogo de cursos se conservaron para reutilizarlos en otros ciclos.');
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless($cycle->professor_id === auth()->user()->professor->id, 404);
    }
}
