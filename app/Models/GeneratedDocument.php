<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    use ScopesToCurrentInstitution;

    protected static function applyInstitutionScope(Builder $builder, int $institutionId): void
    {
        $builder->where(function (Builder $query) use ($institutionId): void {
            $query
                ->whereHas('cycle', fn (Builder $cycle) => $cycle->where('institution_id', $institutionId))
                ->orWhereHas(
                    'group.cycle',
                    fn (Builder $cycle) => $cycle->where('institution_id', $institutionId)
                )
                ->orWhereHas(
                    'modality.assignment.group.cycle',
                    fn (Builder $cycle) => $cycle->where('institution_id', $institutionId)
                );
        });
    }


    use HasSlugRouteKey;

    protected $fillable = [
        'course_modality_id', 'teaching_group_id', 'cycle_id', 'document_type', 'version',
        'filename', 'stored_path', 'generated_by', 'metadata', 'source_fingerprint', 'generated_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'generated_at' => 'datetime'];
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
