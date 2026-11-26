<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use ScopesToCurrentInstitution;

    protected $fillable = [
        'institution_id', 'professor_id', 'holiday_date', 'name', 'scope',
        'blocks_classes', 'source_url', 'source_note', 'verified',
    ];

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'blocks_classes' => 'boolean',
            'verified' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
}
