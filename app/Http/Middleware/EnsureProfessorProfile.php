<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
