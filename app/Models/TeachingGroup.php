<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingGroup extends Model
{
    use HasSlugRouteKey;

    protected $fillable = [
        'cycle_id', 'professor_id', 'name', 'assigned_at', 'notes',
        'workload_type', 'workload_start_date', 'workload_end_date', 'workload_days',
        'workload_start_time', 'workload_end_time', 'supervisor_theory_hours', 'supervisor_lab_hours',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'workload_start_date' => 'date',
            'workload_end_date' => 'date',
            'workload_days' => 'array',
            'supervisor_theory_hours' => 'decimal:2',
            'supervisor_lab_hours' => 'decimal:2',
        ];
    }

    protected function slugSource(): string
    {
        return "grupo-{$this->cycle_id}-{$this->name}";
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'teaching_group_students')
            ->withTimestamps();
    }

    public function fiveMinuteTalks(): HasMany
    {
        return $this->hasMany(GroupFiveMinuteTalk::class, 'teaching_group_id');
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'teaching_group_id');
    }
}
