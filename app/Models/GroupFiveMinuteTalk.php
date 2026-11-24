<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupFiveMinuteTalk extends Model
{
    protected $fillable = [
        'teaching_group_id',
        'topic',
        'scheduled_date',
        'status',
        'participant_count',
        'observations',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'participant_count' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TeachingGroup::class, 'teaching_group_id');
    }
}
