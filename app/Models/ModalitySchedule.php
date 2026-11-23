<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ModalitySchedule extends Model {
 protected $fillable=['course_modality_id','day_of_week','start_time','end_time','classroom','block_type'];
 public function modality(): BelongsTo { return $this->belongsTo(CourseModality::class,'course_modality_id'); }
 public function sessions() { return $this->hasMany(ClassSession::class); }
 public function getDurationMinutesAttribute(): int { return max(0, (strtotime($this->end_time)-strtotime($this->start_time))/60); }
}
