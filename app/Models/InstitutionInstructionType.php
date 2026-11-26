<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionInstructionType extends Model
{
    protected $fillable = ['institution_id', 'code', 'name', 'description', 'is_active', 'sort_order', 'settings'];

    protected function casts(): array { return ['is_active' => 'boolean', 'settings' => 'array']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function offerings(): HasMany { return $this->hasMany(InstitutionCourseOffering::class); }
    public function templates(): HasMany { return $this->hasMany(InstitutionDocumentTemplate::class); }
}
