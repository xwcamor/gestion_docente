<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportBatch extends Model {
 protected $fillable=['course_modality_id','teaching_group_id','user_id','original_filename','stored_path','detected_format','total_rows','created_count','updated_count','skipped_count','errors'];
 protected function casts(): array { return ['errors'=>'array']; }
 public function modality(): BelongsTo { return $this->belongsTo(CourseModality::class,'course_modality_id'); }
 public function group(): BelongsTo { return $this->belongsTo(TeachingGroup::class, 'teaching_group_id'); }
}
