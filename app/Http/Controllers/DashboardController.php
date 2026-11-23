<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\CourseModality;

class DashboardController extends Controller
{
    public function index()
    {
        $professor = auth()->user()->professor;

        $cycles = $professor->cycles()
            ->withCount('groups')
            ->orderByDesc('academic_year')
            ->orderByDesc('cycle_code')
            ->get();

        $groupCount = $professor->groups()->count();

        $groups = $professor->groups()
            ->with(['cycle', 'assignments.course', 'assignments.modalities'])
            ->latest()
            ->take(8)
            ->get();

        $modalities = CourseModality::whereHas(
            'assignment.group',
            fn ($query) => $query->where('professor_id', $professor->id)
        )->with(['assignment.course', 'assignment.group'])->get();

        $upcoming = Holiday::whereBetween('holiday_date', [
            now()->toDateString(),
            now()->addDays(45)->toDateString(),
        ])
            ->where('blocks_classes', true)
            ->where(fn ($query) => $query->whereNull('professor_id')->orWhere('professor_id', $professor->id))
            ->orderBy('holiday_date')
            ->get();

        return view('dashboard', compact('cycles', 'groups', 'groupCount', 'modalities', 'upcoming'));
    }
}
