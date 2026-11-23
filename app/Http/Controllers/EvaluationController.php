<?php

namespace App\Http\Controllers;

use App\Models\CourseModality;
use App\Models\Evaluation;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index(CourseModality $modality)
    {
        $this->owned($modality);
        $modality->load([
            'assignment.course',
            'assignment.group.cycle',
            'enrollments.student',
            'evaluations.grades',
        ]);

        return view('modalities.evaluations', compact('modality'));
    }

    public function store(Request $request, CourseModality $modality)
    {
        $this->owned($modality);
        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:180',
                Rule::unique('evaluations', 'title')
                    ->where(fn ($query) => $query->where('course_modality_id', $modality->id)),
            ],
            'evaluation_date' => 'nullable|date',
            'weight' => 'nullable|numeric|min:0|max:100',
            'maximum_score' => 'required|numeric|gt:0|max:100',
        ], [
            'title.unique' => 'Ya existe una evaluación con ese nombre en esta modalidad.',
        ]);
        $modality->evaluations()->create($data);

        return redirect()->route('evaluations.index', $modality)
            ->with('success', 'Evaluación creada. Ya puedes registrar las calificaciones reales de los alumnos de este NRC.');
    }

    public function saveGrades(Request $request, Evaluation $evaluation)
    {
        $modality = $evaluation->modality()->with('assignment.group')->firstOrFail();
        $this->owned($modality);

        $data = $request->validate([
            'scores' => 'nullable|array',
            'scores.*' => 'nullable|numeric|min:0',
            'observations' => 'nullable|array',
            'observations.*' => 'nullable|string|max:1000',
        ]);

        $enrollments = $modality->enrollments()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowed = array_flip($enrollments);
        $scores = $data['scores'] ?? [];
        $observations = $data['observations'] ?? [];

        foreach (array_unique(array_merge(array_keys($scores), array_keys($observations))) as $enrollmentId) {
            if (!isset($allowed[(int) $enrollmentId])) {
                throw ValidationException::withMessages(['scores' => 'La lista contiene un alumno que no pertenece a este NRC.']);
            }
        }

        foreach ($scores as $enrollmentId => $score) {
            if ($score === null || $score === '') {
                continue;
            }
            if ((float) $score > (float) $evaluation->maximum_score) {
                throw ValidationException::withMessages([
                    'scores' => "La nota del alumno #{$enrollmentId} supera el máximo de {$evaluation->maximum_score}.",
                ]);
            }

            Grade::updateOrCreate(
                ['enrollment_id' => (int) $enrollmentId, 'evaluation_id' => $evaluation->id],
                [
                    'score' => $score,
                    'origin' => 'manual',
                    'observations' => $observations[$enrollmentId] ?? null,
                ]
            );
        }

        return redirect()->route('evaluations.index', $modality)
            ->with('success', 'Calificaciones guardadas. Se conservaron por separado de la nota general importada del campus.');
    }

    private function owned(CourseModality $modality): void
    {
        abort_unless(
            $modality->assignment()->whereHas('group', fn ($query) => $query->where('professor_id', auth()->user()->professor->id))->exists(),
            404
        );
    }
}
