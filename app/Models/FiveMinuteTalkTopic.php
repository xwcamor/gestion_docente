<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiveMinuteTalkTopic extends Model
{
    protected $fillable = ['professor_id', 'title', 'position'];

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
}
