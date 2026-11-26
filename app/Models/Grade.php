<?php
namespace App\Models;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Grade extends Model {
    use ScopesToCurrentInstitution;

    protected static function applyInstitutionScope(Builder $builder, int $institutionId): void
    {
        $builder
            ->whereHas(
                'enrollment.modality.assignment.group.cycle',
                fn (Builder $cycle) => $cycle->where('institution_id', $institutionId)
            )
            ->whereHas(
                'evaluation.modality.assignment.group.cycle',
                fn (Builder $cycle) => $cycle->where('institution_id', $institutionId)
            );
    }


 protected $fillable=['enrollment_id','evaluation_id','score','origin','observations'];
 protected function casts(): array { return ['score'=>'decimal:2']; }
 public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
 public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class); }
}
