<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicProgram extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    protected $fillable = ['institution_id', 'institution_unit_id', 'code', 'name', 'qualification_level', 'status', 'verification_status', 'source_url', 'source_year', 'metadata'];

    protected function casts(): array { return ['metadata' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function unit(): BelongsTo { return $this->belongsTo(InstitutionUnit::class, 'institution_unit_id'); }
    public function versions(): HasMany { return $this->hasMany(AcademicProgramVersion::class); }
    public function courses(): HasMany { return $this->hasMany(InstitutionCourse::class); }
}
