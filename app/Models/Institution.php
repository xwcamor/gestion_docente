<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = ['code', 'slug', 'name', 'legal_name', 'country_code', 'timezone', 'status', 'settings'];

    protected function casts(): array { return ['settings' => 'array']; }

    public function memberships(): HasMany { return $this->hasMany(InstitutionMembership::class); }
    public function units(): HasMany { return $this->hasMany(InstitutionUnit::class); }
    public function programs(): HasMany { return $this->hasMany(AcademicProgram::class); }
    public function periodTypes(): HasMany { return $this->hasMany(InstitutionPeriodType::class); }
    public function instructionTypes(): HasMany { return $this->hasMany(InstitutionInstructionType::class); }
    public function features(): HasMany { return $this->hasMany(InstitutionFeature::class); }
    public function documentTemplates(): HasMany { return $this->hasMany(InstitutionDocumentTemplate::class); }
    public function courses(): HasMany { return $this->hasMany(InstitutionCourse::class); }
    public function offerings(): HasMany { return $this->hasMany(InstitutionCourseOffering::class); }
    public function employmentRelationships(): HasMany { return $this->hasMany(EmploymentRelationship::class); }
}
