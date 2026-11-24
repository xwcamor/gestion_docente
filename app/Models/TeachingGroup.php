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

    protected $fillable = ['cycle_id', 'professor_id', 'name', 'assigned_at', 'notes'];

    protected function casts(): array
    {
        return ['assigned_at' => 'date'];
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
}
