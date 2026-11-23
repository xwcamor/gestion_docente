<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Holiday extends Model {
 protected $fillable=['professor_id','holiday_date','name','scope','blocks_classes','source_url','verified'];
 protected function casts(): array { return ['holiday_date'=>'date','blocks_classes'=>'boolean','verified'=>'boolean']; }
 public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
}
