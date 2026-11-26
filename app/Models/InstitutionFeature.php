<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionFeature extends Model
{
    protected $fillable = ['institution_id', 'feature_key', 'enabled', 'settings'];

    protected function casts(): array { return ['enabled' => 'boolean', 'settings' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
}
