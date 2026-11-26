<?php

namespace App\Support;

use App\Models\Institution;

class CurrentInstitution
{
    /**
     * Resolve the institution available to the authenticated user.
     *
     * Legacy single-institution installations remain compatible when no
     * memberships have been assigned yet. Once multiple institutions exist,
     * a user without an explicit active membership is denied access.
     */
    public function get(): Institution
    {
        $user = auth()->user();

        abort_unless($user, 401);

        $today = today();

        $memberships = $user->institutionMemberships()
            ->with('institution')
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $today);
            })
            ->get()
            ->filter(fn ($membership) => $membership->institution
                && $membership->institution->status === 'active')
            ->values();

        if ($memberships->count() === 1) {
            return $memberships->first()->institution;
        }

        if ($memberships->count() > 1) {
            $selectedId = (int) session('current_institution_id');
            $selectedMembership = $memberships->firstWhere('institution_id', $selectedId);

            abort_unless(
                $selectedMembership,
                403,
                'Tu cuenta pertenece a varias instituciones. Selecciona primero la institución de trabajo.'
            );

            return $selectedMembership->institution;
        }

        // Do not silently fall back when a membership exists but is inactive,
        // expired, or points to a disabled institution.
        abort_if(
            $user->institutionMemberships()->exists(),
            403,
            'Tu cuenta no tiene una membresía institucional activa.'
        );

        // Compatibility for the current single-institution installation, whose users
        // predate institution memberships. This fallback stops working as soon
        // as a second active tenant is registered.
        $activeInstitutions = Institution::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->limit(2)
            ->get();

        abort_unless(
            $activeInstitutions->count() === 1,
            403,
            'Tu cuenta no tiene una institución asignada. Solicita al administrador que configure tu membresía.'
        );

        return $activeInstitutions->first();
    }
}
