<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionAcademicPeriod extends Model
{
    protected $fillable = ['institution_period_type_id', 'code', 'label', 'academic_year', 'sequence_number', 'starts_on', 'ends_on', 'status', 'source_label', 'metadata'];

    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date', 'metadata' => 'array']; }

    public function type(): BelongsTo { return $this->belongsTo(InstitutionPeriodType::class, 'institution_period_type_id'); }
    public function offerings(): HasMany { return $this->hasMany(InstitutionCourseOffering::class); }
}
