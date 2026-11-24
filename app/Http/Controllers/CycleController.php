<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

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

    public function show(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        $groups = $cycle->groups()
            ->withCount('assignments')
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

        $declaredWeeklyHours = (float) DB::table('course_modalities')
            ->join('course_assignments', 'course_assignments.id', '=', 'course_modalities.course_assignment_id')
            ->join('teaching_groups', 'teaching_groups.id', '=', 'course_assignments.teaching_group_id')
            ->where('teaching_groups.cycle_id', $cycle->id)
            ->sum('course_modalities.total_hours');

        return view('cycles.show', compact(
            'cycle',
            'groups',
            'groupCount',
            'courseCount',
            'modalityCount',
            'declaredWeeklyHours',
            'curriculumAssignedCount',
            'configuredModalityCount',
            'curriculumPercent',
            'schedulePercent'
        ));
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless($cycle->professor_id === auth()->user()->professor->id, 404);
    }
}
