<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicProgramVersion extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'program';
    }

    protected $fillable = ['academic_program_id', 'version_code', 'duration_semesters', 'valid_from', 'valid_until', 'status', 'is_current', 'source_url', 'source_year', 'metadata'];

    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_until' => 'date', 'is_current' => 'boolean', 'metadata' => 'array'];
    }

    public function program(): BelongsTo { return $this->belongsTo(AcademicProgram::class, 'academic_program_id'); }
}
