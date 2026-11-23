<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $data = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('teaching_groups', 'name')
                        ->where(fn ($query) => $query->where('cycle_id', $cycle->id)),
                ],
                'assigned_at' => ['nullable', 'date'],
            ],
            [
                'name.unique' => 'Ya existe un grupo con ese nombre en este ciclo. Revisa el nombre antes de guardarlo.',
            ]
        );

        $group = $cycle->groups()->create([
            'professor_id' => $cycle->professor_id,
            ...$data,
        ]);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado. Ahora puedes agregar los cursos que pertenecen a este grupo.');
    }

    public function show(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $group->load(['cycle', 'assignments.course', 'assignments.modalities.schedules']);

        return view('groups.show', compact('group'));
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
