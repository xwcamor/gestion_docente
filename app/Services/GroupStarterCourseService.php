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
     * Both TEC and TALL begin active; the teacher can deactivate either one
     * from the course tree after reviewing the actual course requirements.
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

            $lockedGroup->loadMissing('cycle');

            $assignment = $lockedGroup->assignments()->create([
                'course_id' => $course->id,
                'assigned_at' => now()->toDateString(),
                'notes' => 'Curso provisional creado automáticamente al crear el grupo.',
            ]);

            foreach (['TEC', 'TALL'] as $type) {
                $assignment->modalities()->create([
                    'modality' => $type,
                    'active' => true,
                    'curriculum_start_week' => 1,
                    'period_label' => $lockedGroup->cycle->academic_year.'-'.$lockedGroup->cycle->cycle_code.'-'.$type,
                ]);
            }

            return $assignment->load('modalities');
        });
    }
}
