<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class GeneratedDocument extends Model {
 protected $fillable=['course_modality_id','teaching_group_id','document_type','version','filename','stored_path','generated_by','metadata'];
 protected function casts(): array { return ['metadata'=>'array']; }
 public function modality(): BelongsTo { return $this->belongsTo(CourseModality::class,'course_modality_id'); }
 public function group(): BelongsTo { return $this->belongsTo(TeachingGroup::class,'teaching_group_id'); }
}
