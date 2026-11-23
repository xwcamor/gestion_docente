<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TeachingGroup extends Model {
 protected $fillable=['cycle_id','professor_id','name','assigned_at','notes'];
 protected function casts(): array { return ['assigned_at'=>'date']; }
 public function cycle(): BelongsTo { return $this->belongsTo(Cycle::class); }
 public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
 public function assignments(): HasMany { return $this->hasMany(CourseAssignment::class); }
}
