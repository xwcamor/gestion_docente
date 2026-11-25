<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseModality;
use App\Models\TeachingGroup;
use App\Models\GeneratedDocument;
use App\Models\ImportBatch;
use App\Models\Curriculum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\CalendarService;
use App\Services\CourseRegistrationService;
use App\Services\StudentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseAssignmentController extends Controller
{
    public function create(TeachingGroup $group)
    {
        $this->authorizeGroup($group);

        // Un solo flujo de alta: alias, NRC, fechas, datos adicionales y horario.
        return redirect()->route('assignments.quick', $group);
    }

    public function store(Request $request, TeachingGroup $group, CourseRegistrationService $registration, StudentImportService $students)
    {
        $this->authorizeGroup($group);

        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:200'],
            'course_name' => ['required', 'string', 'max:255'],
            'component' => ['required', 'in:TEC,TALL,BOTH'],
        ], [
            'course_code.required' => 'Ingresa el código completo del curso.',
            'course_name.required' => 'Ingresa el nombre del curso.',
            'component.required' => 'Selecciona Teoría, Taller o Ambos.',
            'component.in' => 'Selecciona Teoría, Taller o Ambos.',
        ]);

        $result = $registration->register(
            $group,
            $data['course_name'],
            $data['course_code'],
            $data['component'],
            $students
        );

        $message = 'Curso registrado. Se crearon las modalidades: '.implode(' y ', $result['created_modalities']).'.';

        if ($result['missing_nrc']) {
            $message .= ' Falta NRC para '.implode(' y ', $result['missing_nrc']).'; puedes completarlo al abrir cada modalidad.';
        }

        return redirect()->route('groups.show', $group)->with(
            $result['missing_nrc'] ? 'warning' : 'success',
            $message
        );
    }

    public function updateName(Request $request, TeachingGroup $group, Course $course)
    {
        $this->authorizeGroup($group);

        abort_unless(
            (int) $course->professor_id === (int) $group->professor_id
                && $group->assignments()->where('course_id', $course->id)->exists(),
            404
        );

        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Escribe el nombre del curso.',
            'name.max' => 'El nombre del curso no puede superar los 255 caracteres.',
        ]);

        $course->update(['name' => $data['name']]);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Nombre del curso actualizado. El cambio se refleja en todos los grupos donde se utiliza este curso.');
    }

    public function destroy(Request $request, CourseAssignment $assignment)
    {
        $assignment->load('group');
        $group = $assignment->group;
        $this->authorizeGroup($group);

        $modalityIds = $assignment->modalities()->pluck('id')->all();
        $generatedPaths = GeneratedDocument::query()
            ->whereIn('course_modality_id', $modalityIds)
            ->pluck('stored_path');
        $importPaths = ImportBatch::query()
            ->whereIn('course_modality_id', $modalityIds)
            ->pluck('stored_path');

        $courseId = (int) $assignment->course_id;

        DB::transaction(function () use ($assignment, $courseId) {
            $assignment->delete();

            // La malla pertenece al curso del catálogo y se conserva como historial.
            // Si ya no queda ninguna asignación del curso en ningún grupo, se archiva
            // para que una futura alta no la vincule automáticamente.
            $hasOtherAssignments = CourseAssignment::query()
                ->where('course_id', $courseId)
                ->exists();

            if (!$hasOtherAssignments) {
                Curriculum::query()
                    ->where('course_id', $courseId)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });

        Storage::disk('local')->delete(
            $generatedPaths->merge($importPaths)->filter()->unique()->values()->all()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Curso retirado del grupo. Su historial curricular se conserva.',
                'group_id' => $group->id,
                'assignments_count' => $group->assignments()->count(),
            ]);
        }

        return redirect()->route('groups.show', $group)
            ->with('success', 'Curso retirado del grupo. Si ya no está asignado en otros grupos, su malla pasa al historial y no se volverá a vincular automáticamente. Las versiones históricas se conservan.');
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
