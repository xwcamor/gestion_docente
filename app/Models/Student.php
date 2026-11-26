<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Student extends Model {
 protected $fillable=['professor_id','institutional_id','username','surnames','given_names','last_access_source','availability_source'];

 public function setSurnamesAttribute($value): void
 {
  $this->attributes['surnames'] = mb_strtoupper(trim((string) $value));
 }
 public function setGivenNamesAttribute($value): void
 {
  $this->attributes['given_names'] = mb_strtoupper(trim((string) $value));
 }
 public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
 public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
 public function groups(): BelongsToMany { return $this->belongsToMany(TeachingGroup::class, 'teaching_group_students')->withTimestamps(); }
}
