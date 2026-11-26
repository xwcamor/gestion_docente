<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'modality.assignment.group.cycle';
    }


    use HasSlugRouteKey;

    protected $fillable = ['course_modality_id', 'title', 'evaluation_date', 'weight', 'maximum_score'];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'weight' => 'decimal:2',
            'maximum_score' => 'decimal:2',
        ];
    }

    protected function slugSource(): string
    {
        return "evaluacion-modalidad-{$this->course_modality_id}-{$this->title}";
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(CourseModality::class, 'course_modality_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
