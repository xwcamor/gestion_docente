<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionUnit extends Model
{
    protected $fillable = ['institution_id', 'parent_id', 'code', 'name', 'unit_type', 'source_label', 'source_year', 'verification_status', 'is_active', 'metadata'];

    protected function casts(): array { return ['is_active' => 'boolean', 'metadata' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function programs(): HasMany { return $this->hasMany(AcademicProgram::class); }
    public function courses(): HasMany { return $this->hasMany(InstitutionCourse::class); }
    public function offerings(): HasMany { return $this->hasMany(InstitutionCourseOffering::class); }
}
