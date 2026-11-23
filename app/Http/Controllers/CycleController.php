<?php

namespace App\\Http\\Controllers;

use App\\Models\\Cycle;
use Illuminate\\Http\\Request;

class CycleController extends Controller
{
    public function index()
    {
        $cycles = auth()->user()->professor
            ->cycles()
            ->withCount('groups')
            ->orderByDesc('academic_year')
            ->get();

        return view('cycles.index', compact('cycles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            [
                'academic_year' => 'required|integer|min:2020|max:2100',
                'cycle_code' => 'required|string|max:20',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ],
            [
                'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]
        );

        $data['professor_id'] = auth()->user()->professor->id;

        $cycle = Cycle::create($data);

        return redirect()
            ->route('cycles.show', $cycle)
            ->with('success', 'Ciclo creado. Ahora puede crear uno o varios grupos.');
    }

    public function show(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        $cycle->load([
            'groups.assignments.course',
            'groups.assignments.modalities',
        ]);

        return view('cycles.show', compact('cycle'));
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless(
            $cycle->professor_id === auth()->user()->professor->id,
            404
        );
    }
}
