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
use App\Support\CurrentInstitution;

class InstitutionConfigurationController extends Controller
{
    public function __construct(private readonly CurrentInstitution $currentInstitution)
    {
    }

    private function institution(): Institution
    {
        return $this->currentInstitution->get();
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

        $durationOptions = data_get($institution->settings, 'career_duration_options_semesters', []);
        $semesterWeeks = data_get($institution->settings, 'semester_duration_weeks');

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
            'career_duration_options' => ['required', 'string', 'max:120'],
        ]);

        $tokens = array_values(array_filter(
            array_map('trim', preg_split('/[,;]+/', $data['career_duration_options']) ?: []),
            fn ($value) => $value !== ''
        ));

        $durationOptions = [];
        foreach ($tokens as $token) {
            if (! preg_match('/^\d+$/', $token) || (int) $token < 1 || (int) $token > 30) {
                return back()
                    ->withErrors(['career_duration_options' => 'Ingresa únicamente números enteros entre 1 y 30, separados por comas.'])
                    ->withInput();
            }

            $durationOptions[] = (int) $token;
        }

        if (count($durationOptions) === 0 || count($durationOptions) !== count(array_unique($durationOptions))) {
            return back()
                ->withErrors(['career_duration_options' => 'Indica al menos una duración y no repitas valores.'])
                ->withInput();
        }

        sort($durationOptions);

        $settings = $institution->settings ?? [];
        $settings['semester_duration_weeks'] = (int) $data['semester_duration_weeks'];
        $settings['career_duration_options_semesters'] = $durationOptions;

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
            'period_unit' => ['required', Rule::in(['period', 'institution_period', 'semester', 'trimester', 'quarter', 'module', 'year', 'term'])],
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

        $allowedDurations = data_get($institution->settings, 'career_duration_options_semesters', []);

        $data = $request->validate([
            'duration_semesters' => ['nullable', 'integer', Rule::in($allowedDurations)],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'status' => ['required', Rule::in(['current', 'reference_2025', 'historical_reference', 'draft', 'archived'])],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $isCurrent = (bool) ($data['is_current'] ?? false);
        if ($isCurrent && $data['status'] !== 'current') {
            return back()
                ->withErrors(['status' => 'Una versión marcada como actual debe tener estado Vigente.'])
                ->withInput();
        }

        DB::transaction(function () use ($program, $version, $data, $isCurrent) {

            if ($isCurrent) {
                $program->versions()->where('id', '<>', $version->id)->update(['is_current' => false]);
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
