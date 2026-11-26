<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionCourse extends Model
{
    protected $fillable = ['institution_id', 'institution_unit_id', 'academic_program_id', 'external_code', 'name', 'catalog_status', 'verification_status', 'metadata'];

    protected function casts(): array { return ['metadata' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function unit(): BelongsTo { return $this->belongsTo(InstitutionUnit::class, 'institution_unit_id'); }
    public function program(): BelongsTo { return $this->belongsTo(AcademicProgram::class, 'academic_program_id'); }
    public function offerings(): HasMany { return $this->hasMany(InstitutionCourseOffering::class); }
}
