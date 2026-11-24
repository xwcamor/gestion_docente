<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }

        $user = $request->user();

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Esta cuenta está desactivada. Solicita al administrador que restablezca el acceso.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (!$user->professor) {
            Auth::logout();
            return back()->withErrors(['email' => 'La cuenta no tiene perfil docente asociado.'])->onlyInput('email');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
