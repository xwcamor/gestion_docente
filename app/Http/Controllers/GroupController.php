<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\TeachingGroup;
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

        // Compatibilidad: un formulario antiguo que envíe "name" sigue creando un grupo.
        $groupsInput = $request->input('groups');
        if (!is_array($groupsInput)) {
            $groupsInput = [[
                'name' => $request->input('name'),
                'assigned_at' => $request->input('assigned_at'),
            ]];
        }

        $request->merge(['groups' => $groupsInput]);

        $data = $request->validate(
            [
                'groups' => ['required', 'array', 'min:1', 'max:30'],
                'groups.*.name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('teaching_groups', 'name')
                        ->where(fn ($query) => $query->where('cycle_id', $cycle->id)),
                ],
                'groups.*.assigned_at' => ['nullable', 'date'],
            ],
            [
                'groups.*.name.required' => 'Escribe el nombre de cada grupo.',
                'groups.*.name.unique' => 'Ya existe un grupo con ese nombre en este ciclo.',
                'groups.max' => 'Puedes crear hasta 30 grupos por vez.',
            ]
        );

        $groups = collect($data['groups'])->map(fn ($group) => [
            'name' => trim($group['name']),
            'assigned_at' => $group['assigned_at'] ?? null,
        ])->values();

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
                    'name' => $groupData['name'],
                    'assigned_at' => $groupData['assigned_at'],
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

    public function show(TeachingGroup $group)
    {
        $this->authorizeGroup($group);
        $group->load('cycle');

        $assignments = $group->assignments()
            ->with(['course', 'modalities.schedules'])
            ->withCount('modalities')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $courseCount = $group->assignments()->count();
        $modalityCounts = $group->assignments()
            ->join('course_modalities', 'course_modalities.course_assignment_id', '=', 'course_assignments.id')
            ->selectRaw('course_modalities.modality, COUNT(*) as total')
            ->groupBy('course_modalities.modality')
            ->pluck('total', 'modality');

        $tecCount = (int) ($modalityCounts['TEC'] ?? 0);
        $tallCount = (int) ($modalityCounts['TALL'] ?? 0);

        $studentCount = $group->students()->count();
        $students = $group->students()
            ->orderBy('students.surnames')
            ->orderBy('students.given_names')
            ->paginate(20, ['students.*'], 'students_page')
            ->withQueryString();
        $lastRosterImport = $group->importBatches()->latest()->first();

        return view('groups.show', compact(
            'group',
            'assignments',
            'courseCount',
            'tecCount',
            'tallCount',
            'studentCount',
            'students',
            'lastRosterImport'
        ));
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
