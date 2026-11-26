<?php
namespace App\Models;
use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ModalitySchedule extends Model {
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'modality.assignment.group.cycle';
    }


 protected $fillable=['course_modality_id','day_of_week','start_time','end_time','classroom','block_type','instruction_type','repeat_interval_weeks'];

 public function setClassroomAttribute($value): void
 {
  $this->attributes['classroom'] = filled($value) ? mb_strtoupper(trim((string) $value)) : null;
 }
 public function setBlockTypeAttribute($value): void
 {
  $this->attributes['block_type'] = filled($value) ? mb_strtoupper(trim((string) $value)) : null;
 }
 public function setInstructionTypeAttribute($value): void
 {
  $this->attributes['instruction_type'] = filled($value) ? mb_strtoupper(trim((string) $value)) : null;
 }
 public function modality(): BelongsTo { return $this->belongsTo(CourseModality::class,'course_modality_id'); }
 public function sessions() { return $this->hasMany(ClassSession::class); }
 public function getDurationMinutesAttribute(): int { return max(0, (strtotime($this->end_time)-strtotime($this->start_time))/60); }
}
