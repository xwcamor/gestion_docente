<?php

namespace App\Http\Middleware;

use App\Support\CurrentInstitution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessorProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Esta cuenta fue desactivada. Comunícate con el administrador.',
            ]);
        }

        if ($request->user() && !$request->user()->professor) {
            abort(403, 'La cuenta no tiene un perfil docente asociado.');
        }

        if ($request->user()) {
            $today = today();
            $activeMemberships = $request->user()->institutionMemberships()
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
                ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))
                ->whereHas('institution', fn ($query) => $query->where('status', 'active'))
                ->pluck('institution_id');

            $selectedId = (int) $request->session()->get('current_institution_id');

            if ($activeMemberships->count() > 1 && !$activeMemberships->contains($selectedId)) {
                return redirect()->route('institution.select');
            }

            app(CurrentInstitution::class)->get();
        }

        return $next($request);
    }
}
