<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\TeachingGroup;
use Illuminate\Support\Facades\DB;

class GroupStarterCourseService
{
    /**
     * Guarantees that a newly-created group starts with one editable course.
     *
     * The starter intentionally has no modality: the teacher chooses TEC,
     * TALL or both after entering the real course name and institutional code.
     */
    public function ensure(TeachingGroup $group): CourseAssignment
    {
        return DB::transaction(function () use ($group): CourseAssignment {
            $lockedGroup = TeachingGroup::query()
                ->whereKey($group->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $existingAssignment = $lockedGroup->assignments()->first();
            if ($existingAssignment) {
                return $existingAssignment;
            }

            $course = Course::query()->create([
                'professor_id' => $lockedGroup->professor_id,
                'code' => null,
                'name' => 'Nuevo curso',
            ]);

            return $lockedGroup->assignments()->create([
                'course_id' => $course->id,
                'assigned_at' => now()->toDateString(),
                'notes' => 'Curso provisional creado automáticamente al crear el grupo.',
            ]);
        });
    }
}
