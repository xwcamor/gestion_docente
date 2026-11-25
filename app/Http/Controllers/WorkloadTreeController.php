<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Cycle;
use App\Models\TeachingGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkloadTreeController extends Controller
{
    public function index(Cycle $cycle, Request $request)
    {
        $this->authorizeCycle($cycle);

        $term = trim((string) $request->query('q', ''));
        $groupsQuery = $cycle->groups()
            ->withCount(['assignments', 'students'])
            ->orderBy('name');

        if ($term !== '' && !$this->cycleMatches($cycle, $term)) {
            $like = '%'.$term.'%';
            $groupsQuery->where(function ($groups) use ($like) {
                $groups->where('name', 'like', $like)
                    ->orWhereHas('assignments.course', function ($courses) use ($like) {
                        $courses->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('assignments.modalities', function ($modalities) use ($like) {
                        $modalities->where('nrc', 'like', $like)
                            ->orWhere('block_code', 'like', $like)
                            ->orWhere('block_reference', 'like', $like);
                    });
            });
        }

        $groups = $groupsQuery->get();

        return view('cycles.tree.index', compact('cycle', 'groups', 'term'));
    }

    public function groups(Cycle $cycle, Request $request)
    {
        $this->authorizeCycle($cycle);

        $term = trim((string) $request->query('q', ''));
        $query = $cycle->groups()
            ->withCount(['assignments', 'students'])
            ->orderBy('name');

        if ($term !== '' && !$this->cycleMatches($cycle, $term)) {
            $like = '%'.$term.'%';
            $query->where(function ($groups) use ($like) {
                $groups->where('name', 'like', $like)
                    ->orWhereHas('assignments.course', function ($courses) use ($like) {
                        $courses->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('assignments.modalities', function ($modalities) use ($like) {
                        $modalities->where('nrc', 'like', $like)
                            ->orWhere('block_code', 'like', $like)
                            ->orWhere('block_reference', 'like', $like);
                    });
            });
        }

        $groups = $query->get();

        return view('cycles.tree.groups', compact('cycle', 'groups'));
    }

    public function courses(TeachingGroup $group, Request $request)
    {
        $this->authorizeGroup($group);
        $group->loadMissing('cycle');

        $term = trim((string) $request->query('q', ''));
        $query = $group->assignments()
            ->with(['course', 'curriculum.weeks', 'modalities:id,course_assignment_id,modality,nrc'])
            ->withCount('modalities')
            ->orderBy('id');

        if ($term !== '' && !$this->cycleMatches($group->cycle, $term) && !$this->groupMatches($group, $term)) {
            $like = '%'.$term.'%';
            $query->where(function ($assignments) use ($like) {
                $assignments->whereHas('course', function ($courses) use ($like) {
                    $courses->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                })->orWhereHas('modalities', function ($modalities) use ($like) {
                    $modalities->where('nrc', 'like', $like)
                        ->orWhere('block_code', 'like', $like)
                        ->orWhere('block_reference', 'like', $like);
                });
            });
        }

        $assignments = $query->get();

        return view('cycles.tree.courses', compact('group', 'assignments'));
    }

    public function modalities(CourseAssignment $assignment)
    {
        $assignment->loadMissing(['group.cycle', 'course', 'curriculum.weeks']);
        $this->authorizeGroup($assignment->group);

        $modalities = $assignment->modalities()
            ->with('schedules')
            ->withCount('documents')
            ->orderByRaw("CASE WHEN modality = 'TEC' THEN 0 WHEN modality = 'TALL' THEN 1 ELSE 2 END")
            ->get();

        return view('cycles.tree.modalities', compact('assignment', 'modalities'));
    }

    private function cycleMatches(Cycle $cycle, string $term): bool
    {
        $searchable = implode(' ', [
            $cycle->academic_year,
            $cycle->cycle_code,
            $cycle->career,
            $cycle->semester,
        ]);

        return str_contains(mb_strtolower($searchable), mb_strtolower($term));
    }

    private function groupMatches(TeachingGroup $group, string $term): bool
    {
        return str_contains(mb_strtolower((string) $group->name), mb_strtolower($term));
    }

    private function authorizeCycle(Cycle $cycle): void
    {
        abort_unless((int) $cycle->professor_id === (int) auth()->user()->professor->id, 404);
    }

    private function authorizeGroup(TeachingGroup $group): void
    {
        abort_unless((int) $group->professor_id === (int) auth()->user()->professor->id, 404);
    }
}
