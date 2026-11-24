<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Professor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->write($model, 'updated', $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $model->getAttributes(), null);
    }

    private function write(Model $model, string $event, ?array $before, ?array $after): void
    {
        if ($model instanceof AuditLog || $model instanceof User || $model instanceof Professor) {
            return;
        }

        app(AuditLogger::class)->record(
            $this->moduleFor($model),
            $event,
            class_basename($model).' '.$event,
            $model,
            $before,
            $after
        );
    }

    private function moduleFor(Model $model): string
    {
        return match (class_basename($model)) {
            'Cycle' => 'ciclos',
            'TeachingGroup' => 'grupos',
            'Course', 'CourseAssignment' => 'cursos',
            'CourseModality', 'ModalitySchedule', 'ClassSession' => 'modalidades',
            'Student', 'Enrollment' => 'alumnos',
            'Evaluation', 'Grade' => 'evaluaciones',
            'Curriculum', 'CurriculumWeek', 'CurriculumTask', 'CurriculumOperation', 'CurriculumContentItem' => 'malla_curricular',
            'Holiday' => 'feriados',
            'GeneratedDocument' => 'documentos',
            'ImportBatch' => 'importaciones',
            default => 'otros',
        };
    }
}
