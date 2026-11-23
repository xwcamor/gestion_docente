<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasSlugRouteKey;

    protected $fillable = ['professor_id', 'academic_year', 'cycle_code', 'start_date', 'end_date', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
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
}
