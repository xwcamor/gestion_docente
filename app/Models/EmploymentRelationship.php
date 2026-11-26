<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentRelationship extends Model
{
    protected $fillable = ['institution_id', 'user_id', 'employment_type', 'job_title', 'contracted_weekly_hours', 'starts_on', 'ends_on', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['contracted_weekly_hours' => 'decimal:2', 'starts_on' => 'date', 'ends_on' => 'date', 'metadata' => 'array'];
    }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
