<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\CourseModality;
use App\Models\ModalitySchedule;
use App\Models\GeneratedDocument;
use App\Models\ImportBatch;
use App\Services\AuditLogger;
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
        $request->merge([
            'cycle_code' => trim((string) $request->input('cycle_code')),
            'group_name' => trim((string) $request->input('group_name')),
        ]);

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
                'group_name' => ['required', 'string', 'max:100'],
            ],
            [
                'cycle_code.unique' => 'Ya existe un ciclo con ese año y código.',
                'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
                'group_name.required' => 'Registra el nombre del primer grupo del ciclo.',
            ]
        );

        $groupName = $data['group_name'];
        unset($data['group_name']);

        $cycle = DB::transaction(function () use ($data, $professorId, $groupName) {
            $cycle = Cycle::create([...$data, 'professor_id' => $professorId]);

            $cycle->groups()->create([
                'professor_id' => $professorId,
                'name' => $groupName,
            ]);

            return $cycle;
        });

        return redirect()->route('cycles.show', $cycle)
            ->with('success', 'Ciclo y primer grupo creados correctamente.');
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

    public function show(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        $groups = $cycle->groups()
            ->withCount([
                'assignments',
                'assignments as assignments_with_modalities_count' => fn ($query) => $query->whereHas('modalities'),
            ])
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $groupIds = $groups->getCollection()->modelKeys();
        $groupCount = $groups->total();
        $progressByGroup = collect();

        if ($groupIds) {
            // Una fila por modalidad evita contarla varias veces cuando tiene varios bloques.
            $classScheduleModalities = DB::table('modality_schedules')
                ->select('course_modality_id')
                ->where('block_type', 'CLASS')
                ->groupBy('course_modality_id');

            $progressByGroup = DB::table('course_assignments as assignments')
                ->join('course_modalities as modalities', 'modalities.course_assignment_id', '=', 'assignments.id')
                ->leftJoinSub(
                    $classScheduleModalities,
                    'class_schedules',
                    fn ($join) => $join->on('class_schedules.course_modality_id', '=', 'modalities.id')
                )
                ->whereIn('assignments.teaching_group_id', $groupIds)
                ->groupBy('assignments.teaching_group_id')
                ->selectRaw('
                    assignments.teaching_group_id,
                    COUNT(modalities.id) AS modalities_count,
                    SUM(
                        CASE
                            WHEN modalities.start_date IS NOT NULL
                                AND modalities.end_date IS NOT NULL
                                AND class_schedules.course_modality_id IS NOT NULL
                            THEN 1 ELSE 0
                        END
                    ) AS configured_modalities_count
                ')
                ->get()
                ->keyBy('teaching_group_id');
        }

        $groups->getCollection()->each(function ($group) use ($progressByGroup) {
            $progress = $progressByGroup->get($group->id);

            $group->setAttribute('modalities_count', (int) ($progress->modalities_count ?? 0));
            $group->setAttribute('configured_modalities_count', (int) ($progress->configured_modalities_count ?? 0));
        });

        return view('cycles.show', compact('cycle', 'groups', 'groupCount'));
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
