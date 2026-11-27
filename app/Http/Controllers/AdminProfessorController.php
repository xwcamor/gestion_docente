<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\InstitutionMembership;
use App\Models\GeneratedDocument;
use App\Models\ImportBatch;
use App\Support\CurrentInstitution;
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
        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();

        $query = User::query()
            ->whereHas('institutionMemberships', fn ($membership) => $membership
                ->where('institution_id', $institutionId))
            ->with([
                'professor' => fn ($profile) => $profile->withCount(['cycles', 'groups']),
                'institutionMemberships' => fn ($membership) => $membership
                    ->where('institution_id', $institutionId),
            ]);

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

        if ($request->input('status') === 'active') {
            $query->where('is_active', true)
                ->whereHas('institutionMemberships', fn ($membership) => $membership
                    ->where('institution_id', $institutionId)
                    ->where('status', 'active'));
        } elseif ($request->input('status') === 'inactive') {
            $query->where(function ($builder) use ($institutionId) {
                $builder->where('is_active', false)
                    ->orWhereHas('institutionMemberships', fn ($membership) => $membership
                        ->where('institution_id', $institutionId)
                        ->where('status', '<>', 'active'));
            });
        }

        if ($request->input('role') === 'admin') {
            $query->whereHas('institutionMemberships', fn ($membership) => $membership
                ->where('institution_id', $institutionId)
                ->whereIn('role', ['admin', 'owner']));
        } elseif ($request->input('role') === 'profesor') {
            $query->whereHas('institutionMemberships', fn ($membership) => $membership
                ->where('institution_id', $institutionId)
                ->whereIn('role', ['teacher', 'profesor']));
        }

        $sort = $request->input('sort', 'name');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (!in_array($sort, ['name', 'email', 'created_at', 'is_active', 'role'], true)) {
            $sort = 'name';
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $professors = $query
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.professors.index', compact(
            'professors',
            'perPage',
            'sort',
            'direction'
        ));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'institutional_id' => filled($request->input('institutional_id'))
                ? trim((string) $request->input('institutional_id'))
                : null,
        ]);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'institutional_id' => ['nullable', 'string', 'max:80', Rule::unique('professors', 'institutional_id')],
            'school' => ['nullable', 'string', 'max:255'],
            'career' => ['nullable', 'string', 'max:255'],
        ], [
            'email.unique' => 'Ese correo ya está asociado a otra cuenta.',
            'password.min' => 'La contraseña inicial debe tener al menos 10 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'institutional_id.unique' => 'Ese ID institucional ya está registrado en otro perfil.',
        ]);

        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();

        $user = DB::transaction(function () use ($data, $institutionId) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'profesor',
                'is_active' => true,
            ]);

            $user->professor()->create([
                'institutional_id' => $data['institutional_id'] ?? null,
                'full_name' => $data['full_name'],
                'school' => $data['school'] ?? null,
                'career' => $data['career'] ?? null,
            ]);

            $user->institutionMemberships()->create([
                'institution_id' => $institutionId,
                'role' => 'teacher',
                'status' => 'active',
            ]);

            return $user->load('professor');
        });

        $audit->record('cuentas', 'created', 'Se creó la cuenta docente '.$user->name, $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'institutional_id' => $user->professor?->institutional_id,
        ]);

        return redirect()->route('admin.professors.index', $this->returnParams($request))
            ->with('success', 'Cuenta docente creada. La contraseña inicial quedó guardada de forma segura.');
    }

    public function update(Request $request, User $user, AuditLogger $audit)
    {
        $this->authorizeInstitutionMember($user);
        abort_unless(in_array($user->role, ['profesor', 'admin'], true), 404);

        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();
        abort_if(
            $user->institutionMemberships()->where('institution_id', '<>', $institutionId)->exists(),
            403,
            'Esta cuenta pertenece a más de una institución. La edición de identidad compartida debe gestionarse desde el perfil global.'
        );
        $user->loadMissing('professor');
        $profile = $user->professor;

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
                Rule::unique('professors', 'institutional_id')->ignore($profile?->id),
            ],
            'school' => ['nullable', 'string', 'max:255'],
            'career' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ], [
            'email.unique' => 'Ese correo ya está asociado a otra cuenta.',
            'institutional_id.unique' => 'Ese ID institucional ya está registrado en otro perfil.',
            'password.min' => 'La nueva contraseña debe tener al menos 10 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $returnParams = $this->returnParams($request);
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
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'institutional_id' => $profile?->institutional_id,
            'school' => $profile?->school,
            'career' => $profile?->career,
        ];

        DB::transaction(function () use ($user, $data, $profile) {
            $userChanges = ['name' => $data['full_name'], 'email' => $data['email']];
            if (filled($data['password'] ?? null)) {
                $userChanges['password'] = Hash::make($data['password']);
            }
            $user->update($userChanges);

            $profileData = [
                'full_name' => $data['full_name'],
                'institutional_id' => $data['institutional_id'] ?? null,
                'school' => $data['school'] ?? null,
                'career' => $data['career'] ?? null,
            ];

            if ($profile) {
                $profile->update($profileData);
            } else {
                $user->professor()->create($profileData);
            }
        });

        $user->refresh()->load('professor');
        $audit->record(
            'cuentas',
            filled($data['password'] ?? null) ? 'updated_and_password_reset' : 'updated',
            'Se actualizaron los datos de la cuenta '.$user->name,
            $user,
            $before,
            [
                'name' => $user->name,
                'email' => $user->email,
                'institutional_id' => $user->professor?->institutional_id,
                'school' => $user->professor?->school,
                'career' => $user->professor?->career,
                'password_changed' => filled($data['password'] ?? null),
            ]
        );

        return redirect()->route('admin.professors.index', $returnParams)
            ->with('success', filled($data['password'] ?? null)
                ? 'Cuenta actualizada y contraseña restablecida.'
                : 'Datos de la cuenta actualizados.');
    }

    public function toggleStatus(Request $request, User $user, AuditLogger $audit)
    {
        $this->authorizeInstitutionMember($user);
        abort_if($user->id === $request->user()->id, 422, 'No puedes retirar tu propio acceso a la institución.');

        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();

        $result = DB::transaction(function () use ($user, $institutionId) {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $membership = InstitutionMembership::query()
                ->where('user_id', $target->id)
                ->where('institution_id', $institutionId)
                ->lockForUpdate()
                ->firstOrFail();

            $wasActive = $membership->status === 'active';

            if ($wasActive && in_array($membership->role, ['admin', 'owner'], true)) {
                $activeAdmins = InstitutionMembership::query()
                    ->where('institution_id', $institutionId)
                    ->whereIn('role', ['admin', 'owner'])
                    ->where('status', 'active')
                    ->where(fn ($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', today()))
                    ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
                    ->lockForUpdate()
                    ->count();

                if ($activeAdmins <= 1) {
                    return null;
                }
            }

            $membership->update(['status' => $wasActive ? 'inactive' : 'active']);

            return [
                'before' => $wasActive ? 'active' : 'inactive',
                'status' => $membership->fresh()->status,
                'user' => $target->fresh(),
                'membership' => $membership->fresh(),
            ];
        });

        if ($result === null) {
            return back()->with('error', 'No se puede desactivar al último administrador activo de esta institución.');
        }

        $isActive = $result['status'] === 'active';
        $audit->record(
            'cuentas',
            $isActive ? 'membership_activated' : 'membership_deactivated',
            ($isActive ? 'Se habilitó el acceso de ' : 'Se retiró el acceso de ').$result['user']->name.' en la institución actual.',
            $result['user'],
            ['institution_membership_status' => $result['before']],
            ['institution_membership_status' => $result['status']]
        );

        return back()->with('success', $isActive
            ? 'Acceso habilitado para esta institución.'
            : 'Acceso retirado de esta institución. La cuenta y sus datos se conservaron.');
    }

    public function destroy(Request $request, User $user, AuditLogger $audit)
    {
        $this->authorizeInstitutionMember($user);
        abort_if($user->id === $request->user()->id, 422, 'No puedes eliminar tu propia cuenta.');

        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();

        if ($user->institutionMemberships()->where('institution_id', '<>', $institutionId)->exists()) {
            return back()->with('error', 'Esta cuenta también pertenece a otra institución. No se puede eliminar la identidad global desde aquí; retira únicamente su acceso institucional.');
        }

        $professor = $user->professor;
        $hasAcademicData = ($professor && (
            $professor->cycles()->exists()
            || $professor->groups()->exists()
            || $professor->courses()->exists()
            || $professor->holidays()->exists()
            || $professor->fiveMinuteTalkTopics()->exists()
            || DB::table('students')
                ->where('professor_id', $professor->id)
                ->where('institution_id', $institutionId)
                ->exists()
        ))
            || GeneratedDocument::query()->where('generated_by', $user->id)->exists()
            || ImportBatch::query()->where('user_id', $user->id)->exists();

        if ($hasAcademicData) {
            return back()->with('error', 'No se eliminó la cuenta porque tiene historial académico. Desactiva su acceso institucional para conservar los registros.');
        }

        $wasDeleted = DB::transaction(function () use ($user, $professor, $institutionId) {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $membership = InstitutionMembership::query()
                ->where('user_id', $target->id)
                ->where('institution_id', $institutionId)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($membership->role, ['admin', 'owner'], true) && $membership->status === 'active') {
                $activeAdmins = InstitutionMembership::query()
                    ->where('institution_id', $institutionId)
                    ->whereIn('role', ['admin', 'owner'])
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->count();

                if ($activeAdmins <= 1) {
                    return false;
                }
            }

            $professor?->delete();
            $target->delete();

            return true;
        });

        if (!$wasDeleted) {
            return back()->with('error', 'No se puede eliminar al último administrador activo de esta institución.');
        }

        $audit->record('cuentas', 'deleted', 'Se eliminó la cuenta sin historial académico: '.$user->email, $user,
            ['name' => $user->name, 'email' => $user->email, 'role' => $user->role], null);

        return redirect()->route('admin.professors.index', $this->returnParams($request))
            ->with('success', 'Cuenta eliminada. No se encontraron registros académicos asociados.');
    }

    private function authorizeInstitutionMember(User $user): void
    {
        $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();

        abort_unless(
            $user->institutionMemberships()->where('institution_id', $institutionId)->exists(),
            404
        );
    }

    private function returnParams(Request $request): array
    {
        return array_filter([
            'q' => $request->input('return_q', $request->input('q')),
            'status' => $request->input('return_status', $request->input('status')),
            'role' => $request->input('return_role', $request->input('role')),
            'sort' => $request->input('return_sort', $request->input('sort')),
            'direction' => $request->input('return_direction', $request->input('direction')),
            'per_page' => $request->input('return_per_page', $request->input('per_page')),
            'page' => max(1, (int) $request->input('return_page', $request->input('page', 1))),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
