<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasSlugRouteKey;

    protected $fillable = ['professor_id', 'academic_year', 'cycle_code', 'start_date', 'end_date', 'status', 'five_minute_talk_days'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'five_minute_talk_days' => 'array'];
    }

    protected function slugSource(): string
    {
        return "ciclo-{$this->academic_year}-{$this->cycle_code}";
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TeachingGroup::class);
    }

    public function fiveMinuteTalks(): HasMany
    {
        return $this->hasMany(CycleFiveMinuteTalk::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }
}
