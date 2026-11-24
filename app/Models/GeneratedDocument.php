<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    use HasSlugRouteKey;

    protected $fillable = [
        'course_modality_id', 'teaching_group_id', 'cycle_id', 'document_type', 'version',
        'filename', 'stored_path', 'generated_by', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected function slugSource(): string
    {
        return "documento-{$this->document_type}-{$this->version}-{$this->filename}";
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(CourseModality::class, 'course_modality_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TeachingGroup::class, 'teaching_group_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }
}
