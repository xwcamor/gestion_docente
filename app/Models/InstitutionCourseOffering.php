<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionCourseOffering extends Model
{
    protected $fillable = [
        'institution_id', 'institution_academic_period_id', 'institution_unit_id', 'institution_course_id',
        'institution_instruction_type_id', 'teaching_group_id', 'nrc', 'external_identifier',
        'course_code_snapshot', 'course_name_snapshot', 'source_status', 'source_status_label',
        'instructor_label', 'group_mapping_status', 'source_capture', 'source_metadata',
    ];

    protected function casts(): array { return ['source_metadata' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function period(): BelongsTo { return $this->belongsTo(InstitutionAcademicPeriod::class, 'institution_academic_period_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(InstitutionUnit::class, 'institution_unit_id'); }
    public function course(): BelongsTo { return $this->belongsTo(InstitutionCourse::class, 'institution_course_id'); }
    public function instructionType(): BelongsTo { return $this->belongsTo(InstitutionInstructionType::class, 'institution_instruction_type_id'); }
    public function teachingGroup(): BelongsTo { return $this->belongsTo(TeachingGroup::class); }
}
