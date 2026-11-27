<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\CurrentInstitution;
use App\Models\Institution;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'sidebar_visible', 'accent_color', 'sidebar_color'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'sidebar_visible' => 'boolean',
        ];
    }

    public function professor(): HasOne
    {
        return $this->hasOne(Professor::class);
    }

    public function institutionMemberships(): HasMany
    {
        return $this->hasMany(InstitutionMembership::class);
    }

    public function employmentRelationships(): HasMany
    {
        return $this->hasMany(EmploymentRelationship::class);
    }

    public function isAdmin(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // Legacy installations may create the first administrator before any
        // membership exists. That fallback is safe only with one active tenant.
        if (!$this->institutionMemberships()->exists()) {
            return $this->role === 'admin'
                && Institution::query()->where('status', 'active')->limit(2)->count() === 1;
        }

        try {
            $institutionId = app(CurrentInstitution::class)->get()->getKey();
        } catch (\\Throwable) {
            return false;
        }

        $today = today();

        return $this->institutionMemberships()
            ->where('institution_id', $institutionId)
            ->whereIn('role', ['admin', 'owner'])
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))
            ->exists();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
