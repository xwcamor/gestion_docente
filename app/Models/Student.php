<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Student extends Model {
 protected $fillable=['professor_id','institutional_id','username','surnames','given_names','last_access_source','availability_source'];
 public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
 public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
}
