<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModality extends Model
{
    use HasSlugRouteKey;

    protected $fillable = [
        'course_assignment_id', 'modality', 'nrc', 'block_code', 'block_reference',
        'start_date', 'end_date', 'total_hours', 'curriculum_start_week', 'active',
        'period_label', 'semester',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_hours' => 'decimal:2',
            'curriculum_start_week' => 'integer',
            'active' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return "modalidad-{$this->modality}-asignacion-{$this->course_assignment_id}";
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class, 'course_assignment_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ModalitySchedule::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->modality === 'TEC' ? 'Técnica' : 'Taller';
    }
}
