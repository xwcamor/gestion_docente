<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class CrearProfesor extends Command
{
    protected $signature = 'gestion:crear-profesor
        {--admin : Crear la cuenta con rol administrador y perfil docente asociado}
        {--name= : Nombre completo del profesor}
        {--email= : Correo que se usará para iniciar sesión}
        {--id= : ID institucional del profesor}
        {--school= : Escuela o área}
        {--career= : Carrera o especialidad}';

    protected $description = 'Crea una cuenta de acceso y su perfil docente en Gestión Docente';

    public function handle(): int
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('professors')) {
            $this->components->error('No se encontraron las tablas users y professors.');
            $this->line('Primero ejecuta php artisan migrate --seed para crear las tablas y vuelve a ejecutar este comando.');
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Nombre completo del profesor')));
        if ($name === '') {
            $this->components->error('El nombre completo es obligatorio.');
            return self::FAILURE;
        }

        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo para iniciar sesión'))));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $this->components->error('Ingresa un correo electrónico válido.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->components->error("Ya existe una cuenta con el correo {$email}. No se creó otro usuario.");
            $this->line('Usa otro correo o inicia sesión con la cuenta existente.');
            return self::FAILURE;
        }

        $institutionalId = trim((string) ($this->option('id') ?: $this->ask('ID institucional del profesor (opcional)', '')));
        $school = trim((string) ($this->option('school') ?: $this->ask('Escuela o área (opcional)', '')));
        $career = trim((string) ($this->option('career') ?: $this->ask('Carrera o especialidad (opcional)', '')));

        $password = $this->secret('Contraseña (mínimo 10 caracteres)');
        $confirmation = $this->secret('Repite la contraseña');

        if (!is_string($password) || mb_strlen($password) < 10) {
            $this->components->error('La contraseña debe tener al menos 10 caracteres.');
            return self::FAILURE;
        }

        if (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
            $this->components->error('Las contraseñas no coinciden.');
            return self::FAILURE;
        }

        $role = $this->option('admin') ? 'admin' : 'profesor';

        try {
            $user = DB::transaction(function () use ($name, $email, $password, $role, $institutionalId, $school, $career) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                ]);

                // Las cuentas de administración también tienen un perfil docente.
                // Así pueden entrar al panel y administrar sus propios ciclos/grupos.
                $user->professor()->create([
                    'institutional_id' => $institutionalId !== '' ? $institutionalId : null,
                    'full_name' => $name,
                    'school' => $school !== '' ? $school : null,
                    'career' => $career !== '' ? $career : null,
                ]);

                return $user;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('No se pudo crear la cuenta. Revisa la conexión MySQL y las tablas de la instalación.');
            $this->line('Detalle: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Cuenta creada correctamente.');
        $this->table(
            ['Dato', 'Valor'],
            [
                ['Usuario', $user->name],
                ['Correo de acceso', $user->email],
                ['Rol', $user->role],
                ['Perfil docente', 'Creado'],
                ['ID institucional', $institutionalId !== '' ? $institutionalId : 'No indicado'],
            ]
        );
        $this->line('Ya puedes ingresar en http://127.0.0.1:8000/login');
        return self::SUCCESS;
    }
}
