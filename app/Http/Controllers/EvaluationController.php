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
        $modality->load(['assignment.course', 'assignment.group.cycle']);

        $enrollmentCount = $modality->enrollments()->count();
        $enrollments = $modality->enrollments()
            ->with('student')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $enrollmentIds = $enrollments->getCollection()->pluck('id');
        $evaluations = $modality->evaluations()
            ->with(['grades' => fn ($query) => $query->whereIn('enrollment_id', $enrollmentIds)])
            ->orderBy('id')
            ->get();

        $modality->setRelation('evaluations', $evaluations);

        return view('modalities.evaluations', compact('modality', 'enrollments', 'enrollmentCount'));
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

        return redirect()->route('evaluations.index', ['modality' => $modality, 'page' => max(1, (int) $request->input('page', 1))])
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

        $scores = $data['scores'] ?? [];
        $observations = $data['observations'] ?? [];
        $submittedIds = array_values(array_unique(array_map(
            'intval',
            array_merge(array_keys($scores), array_keys($observations))
        )));

        if ($submittedIds) {
            $validCount = $modality->enrollments()->whereIn('id', $submittedIds)->count();
            if ($validCount !== count($submittedIds)) {
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
