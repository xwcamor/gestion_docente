<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\CourseAssignment;
use App\Models\CourseModality;
use App\Models\Holiday;
use App\Support\CurrentInstitution;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $professor = $user->professor;
        $professorId = (int) $professor->id;
        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();

        $cyclesCount = $professor->cycles()->count();
        $activeCyclesCount = $professor->cycles()->where('status', 'active')->count();
        $groupsCount = $professor->groups()->count();

        $coursesCount = CourseAssignment::query()
            ->whereHas('group', fn ($query) => $query->where('professor_id', $professorId))
            ->count();

        $modalities = CourseModality::query()
            ->whereHas('assignment.group', fn ($query) => $query->where('professor_id', $professorId))
            ->where('active', true);

        $activeModalitiesCount = (clone $modalities)->count();
        $pendingModalitiesCount = (clone $modalities)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhereNull('end_date')
                    ->orWhereDoesntHave('schedules', fn ($schedules) => $schedules->where('block_type', 'CLASS'));
            })
            ->count();

        $studentsCount = DB::table('teaching_group_students')
            ->join('teaching_groups', 'teaching_groups.id', '=', 'teaching_group_students.teaching_group_id')
            ->join('cycles', 'cycles.id', '=', 'teaching_groups.cycle_id')
            ->where('teaching_groups.professor_id', $professorId)
            ->where('cycles.institution_id', $institutionId)
            ->distinct()
            ->count('teaching_group_students.student_id');

        $groups = $professor->groups()
            ->with('cycle')
            ->withCount(['assignments', 'students'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        $cycles = $professor->cycles()
            ->withCount('groups')
            ->orderByDesc('academic_year')
            ->orderByDesc('start_date')
            ->limit(4)
            ->get();

        $upcomingSessions = ClassSession::query()
            ->with(['modality.assignment.course', 'modality.assignment.group.cycle'])
            ->whereDate('session_date', '>=', now()->toDateString())
            ->where('status', 'scheduled')
            ->whereHas('modality.assignment.group', fn ($query) => $query->where('professor_id', $professorId))
            ->orderBy('session_date')
            ->orderBy('session_number')
            ->limit(6)
            ->get();

        $upcomingHolidays = Holiday::query()
            ->whereBetween('holiday_date', [now()->toDateString(), now()->addDays(45)->toDateString()])
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professorId))
            ->orderBy('holiday_date')
            ->limit(5)
            ->get();

        $adminStats = null;
        if ($user->isAdmin()) {
            $adminStats = [
                'accounts' => \App\Models\User::count(),
                'active' => \App\Models\User::where('is_active', true)->count(),
                'inactive' => \App\Models\User::where('is_active', false)->count(),
            ];
        }

        return view('dashboard', compact(
            'user',
            'professor',
            'cyclesCount',
            'activeCyclesCount',
            'groupsCount',
            'coursesCount',
            'activeModalitiesCount',
            'pendingModalitiesCount',
            'studentsCount',
            'groups',
            'cycles',
            'upcomingSessions',
            'upcomingHolidays',
            'adminStats'
        ));
    }
}
