<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminProfessorController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('professor');

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

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $sort = $request->input('sort', 'name');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (!in_array($sort, ['name', 'email', 'created_at', 'is_active'], true)) {
            $sort = 'name';
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $accountCount = User::count();
        $professorCount = User::where('role', 'profesor')->count();
        $adminCount = User::where('role', 'admin')->count();
        $professors = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        return view('admin.professors.index', compact(
            'professors', 'accountCount', 'professorCount', 'adminCount', 'perPage', 'sort', 'direction'
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
                'is_active' => true,
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

    public function update(Request $request, User $user, AuditLogger $audit)
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
                'nullable', 'string', 'max:80',
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
        $before = ['name' => $user->name, 'email' => $user->email, 'institutional_id' => $user->professor->institutional_id,
            'school' => $user->professor->school, 'career' => $user->professor->career];

        DB::transaction(function () use ($user, $data) {
            $user->update(['name' => $data['full_name'], 'email' => $data['email']]);
            $user->professor->update([
                'full_name' => $data['full_name'],
                'institutional_id' => $data['institutional_id'] ?? null,
                'school' => $data['school'] ?? null,
                'career' => $data['career'] ?? null,
            ]);
        });

        $audit->record('cuentas', 'updated', 'Se actualizó el perfil de '.$user->name, $user, $before, $data);

        return redirect()->route('admin.professors.index', $returnParams)
            ->with('success', 'Perfil docente actualizado.');
    }

    public function toggleStatus(Request $request, User $user, AuditLogger $audit)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puedes desactivar la cuenta con la que has iniciado sesión.');

        if ($user->isAdmin() && $user->is_active && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'No se puede desactivar al último administrador activo.');
        }

        $before = ['is_active' => (bool) $user->is_active];
        $user->is_active = !$user->is_active;
        $user->save();

        $audit->record('cuentas', $user->is_active ? 'activated' : 'deactivated',
            ($user->is_active ? 'Se activó ' : 'Se desactivó ').$user->name, $user, $before, ['is_active' => (bool) $user->is_active]);

        return back()->with('success', $user->is_active ? 'Cuenta activada. Puede iniciar sesión.' : 'Cuenta desactivada. Se conservaron sus datos académicos.');
    }

    public function destroy(Request $request, User $user, AuditLogger $audit)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puedes eliminar tu propia cuenta.');

        if ($user->isAdmin() && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'No se puede eliminar al último administrador activo.');
        }

        $professor = $user->professor;
        $hasAcademicData = $professor && (
            $professor->cycles()->exists()
            || $professor->groups()->exists()
            || $professor->courses()->exists()
            || $professor->holidays()->exists()
            || $professor->fiveMinuteTalkTopics()->exists()
        );

        if ($hasAcademicData) {
            return back()->with('error', 'No se eliminó la cuenta porque tiene historial académico. Desactívala para retirar el acceso sin perder sus registros.');
        }

        $audit->record('cuentas', 'deleted', 'Se eliminó la cuenta sin historial académico: '.$user->email, $user,
            ['name' => $user->name, 'email' => $user->email, 'role' => $user->role], null);

        DB::transaction(function () use ($professor, $user) {
            $professor?->delete();
            $user->delete();
        });

        return back()->with('success', 'Cuenta eliminada. No se encontraron registros académicos asociados.');
    }
}
