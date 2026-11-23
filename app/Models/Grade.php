<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Grade extends Model {
 protected $fillable=['enrollment_id','evaluation_id','score','origin','observations'];
 protected function casts(): array { return ['score'=>'decimal:2']; }
 public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
 public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class); }
}
