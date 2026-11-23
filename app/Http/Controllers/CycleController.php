<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CycleController extends Controller
{
    public function index(Request $request)
    {
        $professor = auth()->user()->professor;
        $query = $professor->cycles()->withCount('groups')->orderByDesc('academic_year')->orderByDesc('start_date');

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

        $cycles = $query->get();
        $years = $professor->cycles()->select('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year');

        return view('cycles.index', compact('cycles', 'years'));
    }

    public function create()
    {
        return redirect()->route('cycles.index', ['nuevo' => 1]);
    }

    public function store(Request $request)
    {
        $professorId = auth()->user()->professor->id;

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
                'cycle_code.unique' => 'Ya tienes registrado un ciclo con ese año y código. Abre el ciclo existente o verifica los datos.',
                'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]
        );

        $data['professor_id'] = $professorId;
        $cycle = Cycle::create($data);

        return redirect()->route('cycles.show', $cycle)
            ->with('success', 'Ciclo creado. Ahora agrega los grupos que te asignaron.');
    }

    public function show(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);
        $cycle->load(['groups.assignments.course', 'groups.assignments.modalities.schedules']);

        return view('cycles.show', compact('cycle'));
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless($cycle->professor_id === auth()->user()->professor->id, 404);
    }
}
