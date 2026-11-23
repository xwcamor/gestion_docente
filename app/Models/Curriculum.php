<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    protected $fillable = [
        'professor_id', 'course_id', 'catalog_code', 'module_code', 'module_name',
        'course_name', 'career', 'professional_school', 'occupational_module',
        'semester', 'general_objective', 'source_filename', 'source_path',
        'source_sha256', 'extracted_text', 'source_metadata', 'version',
        'is_active', 'status', 'imported_by',
    ];
    protected function casts(): array { return ['source_metadata' => 'array', 'is_active' => 'boolean']; }
    public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function assignments(): HasMany { return $this->hasMany(CourseAssignment::class); }
    public function tasks(): HasMany { return $this->hasMany(CurriculumTask::class)->orderBy('position'); }
    public function weeks(): HasMany { return $this->hasMany(CurriculumWeek::class)->orderBy('week_number'); }
}
