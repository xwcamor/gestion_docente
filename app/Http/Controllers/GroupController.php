<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function create(Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        return view('groups.create', compact('cycle'));
    }

    public function store(Request $request, Cycle $cycle)
    {
        $this->authorizeCycle($cycle);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'assigned_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $group = $cycle->groups()->create([
            'professor_id' => $cycle->professor_id,
            ...$data,
        ]);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado. Ahora puedes agregarle los cursos.');
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
