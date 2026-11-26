<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionFeature extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    protected $fillable = ['institution_id', 'feature_key', 'enabled', 'settings'];

    protected function casts(): array { return ['enabled' => 'boolean', 'settings' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
}
