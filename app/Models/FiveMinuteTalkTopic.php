<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiveMinuteTalkTopic extends Model
{
    use ScopesToCurrentInstitution;

    protected $fillable = ['institution_id', 'professor_id', 'title', 'position'];

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
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
