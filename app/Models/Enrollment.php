<?php
namespace App\Models;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Enrollment extends Model {
    use ScopesToCurrentInstitution;

    protected static function applyInstitutionScope(Builder $builder, int $institutionId): void
    {
        $builder
            ->whereHas(
                'modality.assignment.group.cycle',
                fn (Builder $cycle) => $cycle->where('institution_id', $institutionId)
            )
            ->whereHas(
                'student',
                fn (Builder $student) => $student->where('institution_id', $institutionId)
            );
    }


 protected $fillable=['course_modality_id','student_id','import_batch_id','platform_grade','platform_last_access','platform_availability','status'];
 protected function casts(): array { return ['platform_grade'=>'decimal:2']; }
 public function modality(): BelongsTo { return $this->belongsTo(CourseModality::class,'course_modality_id'); }
 public function student(): BelongsTo { return $this->belongsTo(Student::class); }
 public function grades(): HasMany { return $this->hasMany(Grade::class); }
}
