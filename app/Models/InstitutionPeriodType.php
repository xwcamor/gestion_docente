<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionPeriodType extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    protected $fillable = ['institution_id', 'code', 'name', 'period_unit', 'code_pattern', 'default_duration_weeks', 'is_active', 'metadata'];

    protected function casts(): array { return ['is_active' => 'boolean', 'metadata' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function periods(): HasMany { return $this->hasMany(InstitutionAcademicPeriod::class); }
}
