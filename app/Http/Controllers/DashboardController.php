<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\CourseModality;

class DashboardController extends Controller
{
    public function index()
    {
        $professor = auth()->user()->professor;

        $cycleCount = $professor->cycles()->count();
        $cycles = $professor->cycles()
            ->withCount('groups')
            ->orderByDesc('academic_year')
            ->orderByDesc('cycle_code')
            ->take(5)
            ->get();

        $groupCount = $professor->groups()->count();
        $groups = $professor->groups()
            ->with('cycle')
            ->withCount('assignments')
            ->latest()
            ->take(8)
            ->get();

        $modalityCount = CourseModality::whereHas(
            'assignment.group',
            fn ($query) => $query->where('professor_id', $professor->id)
        )->count();

        $upcoming = Holiday::whereBetween('holiday_date', [
            now()->toDateString(),
            now()->addDays(45)->toDateString(),
        ])
            ->where('blocks_classes', true)
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professor->id))
            ->orderBy('holiday_date')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'cycles',
            'cycleCount',
            'groups',
            'groupCount',
            'modalityCount',
            'upcoming'
        ));
    }
}
