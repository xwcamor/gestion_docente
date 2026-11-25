<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssignment extends Model
{
    use HasSlugRouteKey;

    protected $fillable = ['teaching_group_id', 'course_id', 'curriculum_id', 'assigned_at', 'course_start_date', 'course_end_date', 'notes'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'course_start_date' => 'date',
            'course_end_date' => 'date',
        ];
    }

    protected function slugSource(): string
    {
        return "asignacion-grupo-{$this->teaching_group_id}-curso-{$this->course_id}";
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TeachingGroup::class, 'teaching_group_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function modalities(): HasMany
    {
        return $this->hasMany(CourseModality::class);
    }
}
