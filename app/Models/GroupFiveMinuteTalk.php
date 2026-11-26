<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupFiveMinuteTalk extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'group.cycle';
    }


    protected $fillable = [
        'teaching_group_id',
        'topic',
        'scheduled_date',
        'status',
        'status_source',
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
