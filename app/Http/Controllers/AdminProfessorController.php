<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminProfessorController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('professor')->orderBy('name');

        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('professor', function ($profile) use ($term) {
                        $profile->where('institutional_id', 'like', "%{$term}%")
                            ->orWhere('school', 'like', "%{$term}%")
                            ->orWhere('career', 'like', "%{$term}%");
                    });
            });
        }

        $accountCount = User::count();
        $professorCount = User::where('role', 'profesor')->count();
        $adminCount = User::where('role', 'admin')->count();
        $professors = $query->paginate(15)->withQueryString();

        return view('admin.professors.index', compact(
            'professors',
            'accountCount',
            'professorCount',
            'adminCount'
        ));
    }

    public function store(Request $request)
    {
        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'institutional_id' => filled($request->input('institutional_id'))
                ? trim((string) $request->input('institutional_id'))
                : null,
        ]);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:10',
            'institutional_id' => ['nullable', 'string', 'max:80', Rule::unique('professors', 'institutional_id')],
            'school' => 'nullable|string|max:255',
            'career' => 'nullable|string|max:255',
        ], [
            'email.unique' => 'Ese correo ya está asociado a otra cuenta.',
            'institutional_id.unique' => 'Ese ID institucional ya está registrado en otro perfil.',
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['full_name']),
                'email' => mb_strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'role' => 'profesor',
            ]);

            $user->professor()->create([
                'institutional_id' => filled($data['institutional_id'] ?? null) ? trim($data['institutional_id']) : null,
                'full_name' => trim($data['full_name']),
                'school' => $data['school'] ?? null,
                'career' => $data['career'] ?? null,
            ]);
        });

        return redirect()->route('admin.professors.index', array_filter([
            'q' => $request->input('return_q'),
            'page' => $request->input('return_page'),
        ]))->with('success', 'Cuenta docente creada.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'profesor' && $user->professor, 404);

        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'institutional_id' => filled($request->input('institutional_id'))
                ? trim((string) $request->input('institutional_id'))
                : null,
        ]);

        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'institutional_id' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('professors', 'institutional_id')->ignore($user->professor->id),
            ],
            'school' => ['nullable', 'string', 'max:255'],
            'career' => ['nullable', 'string', 'max:255'],
        ], [
            'email.unique' => 'Ese correo ya está asociado a otra cuenta.',
            'institutional_id.unique' => 'Ese ID institucional ya está registrado en otro perfil.',
        ]);

        $returnParams = array_filter([
            'q' => $request->input('return_q'),
            'page' => max(1, (int) $request->input('return_page', 1)),
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.professors.index', $returnParams + [
                    'dialog' => 'edit-account',
                    'user_id' => $user->id,
                ])
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['full_name'],
                'email' => $data['email'],
            ]);

            $user->professor->update([
                'full_name' => $data['full_name'],
                'institutional_id' => $data['institutional_id'] ?? null,
                'school' => $data['school'] ?? null,
                'career' => $data['career'] ?? null,
            ]);
        });

        return redirect()->route('admin.professors.index', $returnParams)
            ->with('success', 'Perfil docente actualizado.');
    }
}
