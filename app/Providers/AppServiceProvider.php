<?php

namespace App\Providers;

use App\Models\ClassSession;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseModality;
use App\Models\Curriculum;
use App\Models\CurriculumContentItem;
use App\Models\CurriculumOperation;
use App\Models\CurriculumTask;
use App\Models\CurriculumWeek;
use App\Models\Cycle;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\GeneratedDocument;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\ImportBatch;
use App\Models\ModalitySchedule;
use App\Models\Student;
use App\Models\TeachingGroup;
use App\Models\SystemSetting;
use App\Observers\AuditObserver;
use App\Support\CurrentInstitution;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentInstitution::class);
    }

    public function boot(): void
    {
        foreach ([
            Cycle::class, TeachingGroup::class, Course::class, CourseAssignment::class,
            CourseModality::class, ModalitySchedule::class, ClassSession::class,
            Student::class, Enrollment::class, Evaluation::class, Grade::class,
            Curriculum::class, CurriculumWeek::class, CurriculumTask::class,
            CurriculumOperation::class, CurriculumContentItem::class, Holiday::class,
            ImportBatch::class, GeneratedDocument::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }

        View::composer('layouts.app', function ($view) {
            $branding = SystemSetting::branding();
            $user = auth()->user();
            $personalAccent = $user?->accent_color;

            $view->with('appBranding', $branding);
            $view->with('appAppearance', [
                'accent_color' => $personalAccent ?: $branding['primary_color'],
                'accent_hover' => $personalAccent
                    ? 'color-mix(in srgb, var(--accent) 82%, #000)'
                    : $branding['primary_hover'],
                'sidebar_visible' => $user?->sidebar_visible ?? true,
                'sidebar_color' => $user?->sidebar_color ?: 'blue',
            ]);
        });
    }
}
