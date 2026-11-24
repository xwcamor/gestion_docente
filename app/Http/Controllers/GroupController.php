<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\TeachingGroup;
use App\Models\Student;
use App\Models\CourseModality;
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
            'modalityCount',
            'coursesWithModalityCount',
            'curriculumCount',
            'configuredModalityCount',
            'courseModalityPercent',
            'curriculumPercent',
            'schedulePercent',
            'declaredWeeklyHours',
            'studentCount',
            'students',
            'lastRosterImport'
        ));
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
        $returnUrl = route('groups.show', [
            'group' => $group,
            'students_page' => $page,
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to($returnUrl.'?edit_student='.$student->id.'#group-roster')
                ->withErrors($validator)
                ->withInput();
        }

        $student->update($validator->validated());

        return redirect()
            ->to($returnUrl.'#group-roster')
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
