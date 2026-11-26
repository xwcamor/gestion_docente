<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasSlugRouteKey;
    use ScopesToCurrentInstitution;

    protected $fillable = ['institution_id', 'professor_id', 'academic_year', 'cycle_code', 'career', 'semester', 'start_date', 'end_date', 'status'];

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    public function setCycleCodeAttribute($value): void
    {
        $this->attributes['cycle_code'] = mb_strtoupper(trim((string) $value));
    }

    public function setCareerAttribute($value): void
    {
        $this->attributes['career'] = filled($value) ? mb_strtoupper(trim((string) $value)) : null;
    }

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    protected function slugSource(): string
    {
        return "ciclo-{$this->academic_year}-{$this->cycle_code}";
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TeachingGroup::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }
}
