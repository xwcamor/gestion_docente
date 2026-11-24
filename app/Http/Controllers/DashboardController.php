<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $professor = auth()->user()->professor;

        $groups = $professor->groups()
            ->with('cycle')
            ->withCount('assignments')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact('groups'));
    }
}
