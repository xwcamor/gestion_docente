<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CourseAssignment extends Model {
 protected $fillable=['teaching_group_id','course_id','curriculum_id','assigned_at','notes'];
 protected function casts(): array { return ['assigned_at'=>'date']; }
 public function group(): BelongsTo { return $this->belongsTo(TeachingGroup::class,'teaching_group_id'); }
 public function course(): BelongsTo { return $this->belongsTo(Course::class); }
 public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
 public function modalities(): HasMany { return $this->hasMany(CourseModality::class); }
}
