<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleFiveMinuteTalk extends Model
{
    protected $fillable = ['cycle_id', 'topic', 'scheduled_date', 'position'];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }
}
