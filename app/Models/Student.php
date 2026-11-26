<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use ScopesToCurrentInstitution;

    protected $fillable = [
        'institution_id', 'professor_id', 'institutional_id', 'username', 'surnames',
        'given_names', 'last_access_source', 'availability_source',
    ];

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    public function setSurnamesAttribute($value): void
    {
        $this->attributes['surnames'] = mb_strtoupper(trim((string) $value));
    }

    public function setGivenNamesAttribute($value): void
    {
        $this->attributes['given_names'] = mb_strtoupper(trim((string) $value));
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TeachingGroup::class, 'teaching_group_students')
            ->withTimestamps();
    }
}
