<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumContentItem extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'week.curriculum.course';
    }


    protected $fillable = ['curriculum_week_id', 'category', 'content', 'source_kind', 'approved', 'position', 'metadata'];
    protected function casts(): array { return ['approved' => 'boolean', 'metadata' => 'array']; }
    public function week(): BelongsTo { return $this->belongsTo(CurriculumWeek::class, 'curriculum_week_id'); }
}
