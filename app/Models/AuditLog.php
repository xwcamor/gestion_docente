<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use ScopesToCurrentInstitution;

    protected $fillable = [
        'institution_id', 'user_id', 'professor_id', 'module', 'event', 'auditable_type',
        'auditable_id', 'description', 'before_data', 'after_data',
        'ip_address', 'user_agent',
    ];

    protected static function institutionIdColumn(): ?string
    {
        return 'institution_id';
    }

    protected function casts(): array
    {
        return ['before_data' => 'array', 'after_data' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
}
