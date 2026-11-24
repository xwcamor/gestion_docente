<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Professor extends Model {
 protected $fillable=['user_id','institutional_id','full_name','school','career'];
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function cycles(): HasMany { return $this->hasMany(Cycle::class); }
 public function groups(): HasMany { return $this->hasMany(TeachingGroup::class); }
 public function courses(): HasMany { return $this->hasMany(Course::class); }
 public function fiveMinuteTalkTopics(): HasMany { return $this->hasMany(FiveMinuteTalkTopic::class)->orderBy('position'); }
 public function holidays(): HasMany { return $this->hasMany(Holiday::class); }
}
