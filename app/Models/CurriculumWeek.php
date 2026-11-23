<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumWeek extends Model
{
    protected $fillable = [
        'curriculum_id', 'curriculum_task_id', 'week_number', 'source_week_number', 'hours_theory',
        'hours_practice', 'hours_virtual', 'is_evaluation', 'source_pages', 'notes',
    ];
    protected function casts(): array
    {
        return [
            'hours_theory' => 'decimal:2', 'hours_practice' => 'decimal:2',
            'hours_virtual' => 'decimal:2', 'is_evaluation' => 'boolean', 'source_pages' => 'array',
        ];
    }
    public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
    public function task(): BelongsTo { return $this->belongsTo(CurriculumTask::class, 'curriculum_task_id'); }
    public function contentItems(): HasMany { return $this->hasMany(CurriculumContentItem::class)->orderBy('category')->orderBy('position'); }
}
