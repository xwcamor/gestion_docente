<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSession extends Model
{
    use HasSlugRouteKey;

    protected $fillable = [
        'course_modality_id', 'curriculum_week_id', 'curriculum_week_number',
        'content_origin', 'modality_schedule_id', 'session_date', 'session_number',
        'duration_minutes', 'status', 'topic', 'objective', 'activities', 'evidence',
        'system_note',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    protected function slugSource(): string
    {
        $date = $this->session_date ? $this->session_date->format('Y-m-d') : 'sin-fecha';

        return "sesion-modalidad-{$this->course_modality_id}-{$date}-{$this->session_number}";
    }

    public function curriculumWeek(): BelongsTo
    {
        return $this->belongsTo(CurriculumWeek::class, 'curriculum_week_id');
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(CourseModality::class, 'course_modality_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ModalitySchedule::class, 'modality_schedule_id');
    }
}
