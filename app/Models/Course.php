<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasSlugRouteKey;
    use ScopesToCurrentInstitution;

    protected $fillable = ['institution_id', 'professor_id', 'code', 'name', 'career', 'module', 'description'];

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim((string) $value));
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = filled($value) ? mb_strtoupper(trim((string) $value)) : null;
    }

    public function setCareerAttribute($value): void
    {
        $this->attributes['career'] = filled($value) ? mb_strtoupper(trim((string) $value)) : null;
    }

    protected function slugSource(): string
    {
        return 'curso-'.($this->code ?: $this->name);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function curricula(): HasMany
    {
        return $this->hasMany(Curriculum::class)->orderByDesc('version');
    }
}
