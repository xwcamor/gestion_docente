<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Cycle extends Model {
 protected $fillable=['professor_id','academic_year','cycle_code','start_date','end_date','status'];
 protected function casts(): array { return ['start_date'=>'date','end_date'=>'date']; }
 public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
 public function groups(): HasMany { return $this->hasMany(TeachingGroup::class); }
}
