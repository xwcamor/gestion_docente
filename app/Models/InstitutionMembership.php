<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionMembership extends Model
{
    protected $fillable = ['institution_id', 'user_id', 'role', 'status', 'starts_on', 'ends_on'];

    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
