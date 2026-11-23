<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClassSession extends Model {
 protected $fillable=['course_modality_id','curriculum_week_id','curriculum_week_number','content_origin','modality_schedule_id','session_date','session_number','duration_minutes','status','topic','objective','activities','evidence','system_note'];
 protected function casts(): array { return ['session_date'=>'date']; }
 public function curriculumWeek(): BelongsTo { return $this->belongsTo(CurriculumWeek::class, 'curriculum_week_id'); }
 public function modality(): BelongsTo { return $this->belongsTo(CourseModality::class,'course_modality_id'); }
 public function schedule(): BelongsTo { return $this->belongsTo(ModalitySchedule::class,'modality_schedule_id'); }
}
