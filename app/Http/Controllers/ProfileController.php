<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load('professor');

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $professor = $user->professor;

        abort_unless($professor, 403, 'La cuenta no tiene un perfil docente asociado.');

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'institutional_id' => filled($request->input('institutional_id'))
                ? trim((string) $request->input('institutional_id'))
                : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'institutional_id' => ['nullable', 'string', 'max:80', Rule::unique('professors', 'institutional_id')->ignore($professor->id)],
            'school' => ['nullable', 'string', 'max:255'],
            'career' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ], [
            'current_password.current_password' => 'La contraseña actual no coincide.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $professor->update([
            'full_name' => $data['name'],
            'institutional_id' => $data['institutional_id'] ?? null,
            'school' => $data['school'] ?? null,
            'career' => $data['career'] ?? null,
        ]);

        return redirect()->route('profile.edit')->with('success', 'Tu perfil se actualizó correctamente.');
    }
}
