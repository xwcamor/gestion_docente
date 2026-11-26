<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleFiveMinuteTalk extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'cycle';
    }


    protected $fillable = [
        'cycle_id', 'topic', 'scheduled_date', 'status',
        'participant_count', 'observations', 'position',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'participant_count' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }
}
