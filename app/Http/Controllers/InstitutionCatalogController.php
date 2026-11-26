<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Institution;
use App\Models\InstitutionAcademicPeriod;
use App\Models\InstitutionCourseOffering;
use App\Models\InstitutionDocumentTemplate;
use App\Models\InstitutionFeature;

class InstitutionCatalogController extends Controller
{
    public function index()
    {
        $institution = Institution::query()->where('code', 'SENATI')->firstOrFail();

        $periods = InstitutionAcademicPeriod::query()
            ->whereHas('type', fn ($query) => $query->where('institution_id', $institution->id))
            ->with(['type', 'offerings.unit', 'offerings.course', 'offerings.instructionType'])
            ->orderByDesc('academic_year')
            ->orderByDesc('code')
            ->get();

        $activePrograms = AcademicProgram::query()
            ->where('institution_id', $institution->id)
            ->where('status', 'active')
            ->with(['unit', 'versions' => fn ($query) => $query->orderByDesc('is_current')])
            ->orderBy('name')
            ->get();

        $trainingPrograms = AcademicProgram::query()
            ->where('institution_id', $institution->id)
            ->where('status', 'reference_2025')
            ->with(['unit', 'versions'])
            ->orderBy('name')
            ->get();

        $reportedTrainingCareerCount = (int) data_get($institution->settings, '2025_career_catalog.reported_execution_careers', 90);
        $unresolvedTrainingNames = (int) data_get($institution->settings, '2025_career_catalog.names_pending_source_validation', 5);

        $historicalPrograms = AcademicProgram::where('institution_id', $institution->id)
            ->where('status', 'historical_reference')->count();
        $courseCount = $institution->courses()->count();
        $offeringCount = InstitutionCourseOffering::where('institution_id', $institution->id)->count();
        $pendingGroupCount = InstitutionCourseOffering::where('institution_id', $institution->id)
            ->where('group_mapping_status', 'pending_review')->count();
        $templateCount = InstitutionDocumentTemplate::where('institution_id', $institution->id)
            ->whereNotNull('file_path')->count();
        $features = InstitutionFeature::where('institution_id', $institution->id)
            ->orderBy('feature_key')->get()->keyBy('feature_key');

        return view('admin.institutions.catalog', compact(
            'institution', 'periods', 'activePrograms', 'trainingPrograms',
            'reportedTrainingCareerCount', 'unresolvedTrainingNames',
            'historicalPrograms', 'courseCount', 'offeringCount', 'pendingGroupCount',
            'templateCount', 'features'
        ));
    }
}
