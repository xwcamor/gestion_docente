<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Course extends Model {
 protected $fillable=['professor_id','code','name','career','module','description'];
 public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
 public function assignments(): HasMany { return $this->hasMany(CourseAssignment::class); }
 public function curricula(): HasMany { return $this->hasMany(Curriculum::class)->orderByDesc('version'); }
}
