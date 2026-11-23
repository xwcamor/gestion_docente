<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Cycle;
use App\Models\CourseModality;
use App\Models\TeachingGroup;
use App\Services\CourseDistributionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CourseDistributionImportController extends Controller
{
    public function create()
    {
        return view('distribution.create');
    }

    public function preview(Request $request, CourseDistributionImportService $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:15360',
        ], [
            'file.mimes' => 'Sube un Excel .xlsx o .xls.',
            'file.max' => 'El Excel no debe superar los 15 MB.',
        ]);

        $data = $importer->parse($request->file('file'));
        if (!$data['start_date'] || !$data['end_date']) {
            throw ValidationException::withMessages([
                'file' => 'El Excel no contiene ninguna fecha completa en la columna FECHA. Completa al menos un rango en la pestaña Cursos y vuelve a subirlo.',
            ]);
        }

        session(['distribution_import.preview' => $data]);
        return view('distribution.preview', ['data' => $data]);
    }

    public function confirm(Request $request)
    {
        $data = session('distribution_import.preview');
        abort_unless(is_array($data) && !empty($data['rows']), 419, 'La vista previa expiró. Vuelve a subir el Excel.');

        $validated = $request->validate([
            'group_name' => 'required|string|max:100',
        ]);

        $professor = auth()->user()->professor;
        $cycle = DB::transaction(function () use ($data, $validated, $professor) {
            $cycle = Cycle::firstOrCreate(
                ['professor_id' => $professor->id, 'academic_year' => $data['academic_year'], 'cycle_code' => $data['cycle_code']],
                ['start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'status' => 'active']
            );
            if ($cycle->start_date->gt($data['start_date'])) $cycle->update(['start_date' => $data['start_date']]);
            if ($cycle->end_date->lt($data['end_date'])) $cycle->update(['end_date' => $data['end_date']]);

            $group = TeachingGroup::firstOrCreate(
                ['cycle_id' => $cycle->id, 'professor_id' => $professor->id, 'name' => $validated['group_name']],
                ['assigned_at' => now()->toDateString(), 'notes' => 'Creado mediante importación de la pestaña Cursos.']
            );

            foreach ($data['rows'] as $row) {
                $course = Course::firstOrCreate(
                    ['professor_id' => $professor->id, 'code' => $row['code']],
                    ['name' => $row['name']]
                );
                if ($course->name !== $row['name']) $course->update(['name' => $row['name']]);

                $assignment = $group->assignments()->firstOrCreate(
                    ['course_id' => $course->id],
                    ['assigned_at' => now()->toDateString()]
                );

                foreach ($row['modalities'] as $item) {
                    $modality = CourseModality::firstOrNew([
                        'course_assignment_id' => $assignment->id,
                        'modality' => $item['type'],
                    ]);
                    $modality->fill([
                        'nrc' => $item['nrc'] ?: $modality->nrc,
                        'start_date' => $item['start_date'] ?: $modality->start_date?->toDateString(),
                        'end_date' => $item['end_date'] ?: $modality->end_date?->toDateString(),
                        'curriculum_start_week' => $modality->curriculum_start_week ?: 1,
                        'active' => true,
                        'period_label' => $data['academic_year'].'-'.$data['cycle_code'].'-'.$item['type'],
                    ]);
                    $modality->save();
                    // La hoja Cursos no contiene días ni horas: no se crean horarios ficticios.
                }
            }

            return $cycle;
        });

        session()->forget('distribution_import.preview');
        return redirect()->route('cycles.show', $cycle)
            ->with('success', 'Importación confirmada: ciclo, grupo, cursos, NRC y periodos registrados. Los horarios quedaron pendientes porque la pestaña Cursos no contiene días ni horas.');
    }
}