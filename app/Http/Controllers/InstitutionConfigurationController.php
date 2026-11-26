<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\AcademicProgramVersion;
use App\Models\Institution;
use App\Models\InstitutionPeriodType;
use App\Models\InstitutionUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InstitutionConfigurationController extends Controller
{
    private function institution(): Institution
    {
        return Institution::query()->where('code', 'SENATI')->firstOrFail();
    }

    public function index()
    {
        $institution = $this->institution();

        $units = $institution->units()
            ->withCount(['programs', 'courses', 'offerings'])
            ->orderBy('unit_type')
            ->orderBy('name')
            ->get();

        $periodTypes = $institution->periodTypes()
            ->withCount('periods')
            ->with(['periods' => fn ($query) => $query->orderByDesc('academic_year')->orderByDesc('code')])
            ->orderBy('name')
            ->get();

        $programs = AcademicProgram::query()
            ->where('institution_id', $institution->id)
            ->with(['unit', 'versions' => fn ($query) => $query->orderByDesc('is_current')->orderByDesc('source_year')])
            ->orderBy('name')
            ->get();

        $durationOptions = data_get($institution->settings, 'career_duration_options_semesters', [4, 5, 6, 8]);
        $semesterWeeks = (int) data_get($institution->settings, 'semester_duration_weeks', 17);

        return view('admin.institutions.configuration', compact(
            'institution',
            'units',
            'periodTypes',
            'programs',
            'durationOptions',
            'semesterWeeks'
        ));
    }

    public function updateInstitution(Request $request)
    {
        $institution = $this->institution();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'timezone'],
        ]);

        $institution->update([
            ...$data,
            'country_code' => mb_strtoupper($data['country_code']),
        ]);

        return back()->with('success', 'Los datos institucionales fueron actualizados.');
    }

    public function updateAcademicRules(Request $request)
    {
        $institution = $this->institution();

        $data = $request->validate([
            'semester_duration_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'career_duration_options' => ['required', 'array', 'min:1'],
            'career_duration_options.*' => ['required', 'integer', 'min:1', 'max:30', 'distinct'],
        ]);

        $settings = $institution->settings ?? [];
        $settings['semester_duration_weeks'] = (int) $data['semester_duration_weeks'];
        $settings['career_duration_options_semesters'] = collect($data['career_duration_options'])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $institution->update(['settings' => $settings]);

        return back()->with('success', 'Las reglas académicas de referencia fueron guardadas.');
    }

    public function updateUnit(Request $request, InstitutionUnit $unit)
    {
        $institution = $this->institution();
        abort_unless($unit->institution_id === $institution->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $unit->update([
            'name' => trim($data['name']),
            'is_active' => (bool) $data['is_active'],
        ]);

        return back()->with('success', 'La unidad académica fue actualizada.');
    }

    public function updatePeriodType(Request $request, InstitutionPeriodType $periodType)
    {
        $institution = $this->institution();
        abort_unless($periodType->institution_id === $institution->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'period_unit' => ['required', Rule::in(['period', 'semester', 'trimester', 'quarter', 'module', 'year', 'term'])],
            'code_pattern' => ['nullable', 'string', 'max:80'],
            'default_duration_weeks' => ['nullable', 'integer', 'min:1', 'max:52'],
            'is_active' => ['required', 'boolean'],
        ]);

        $periodType->update($data);

        return back()->with('success', 'El sistema de periodos fue actualizado.');
    }

    public function updateProgramVersion(Request $request, AcademicProgram $program, AcademicProgramVersion $version)
    {
        $institution = $this->institution();
        abort_unless($program->institution_id === $institution->id, 404);
        abort_unless($version->academic_program_id === $program->id, 404);

        $data = $request->validate([
            'duration_semesters' => ['nullable', 'integer', 'min:1', 'max:30'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'status' => ['required', Rule::in(['current', 'reference_2025', 'historical_reference', 'draft', 'archived'])],
            'is_current' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($program, $version, $data) {
            $isCurrent = (bool) ($data['is_current'] ?? false);

            if ($isCurrent) {
                $program->versions()->whereKeyNot($version->id)->update(['is_current' => false]);
            }

            $version->update([
                'duration_semesters' => $data['duration_semesters'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'status' => $data['status'],
                'is_current' => $isCurrent,
            ]);
        });

        return back()->with('success', 'La versión de malla fue actualizada. La duración se aplica únicamente a esta carrera y versión.');
    }
}
