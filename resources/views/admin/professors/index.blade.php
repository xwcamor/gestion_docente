@extends('layouts.app')
@section('title', 'Administración de profesores')
@section('content')
<div class="page-head"><div><p class="eyebrow">Administración</p><h1>Profesores y cuentas</h1><p class="subhead">Cada profesor utiliza una cuenta individual y solo puede consultar sus ciclos, grupos y documentos.</p></div></div>
<section class="card">
    <details class="panel">
        <summary>＋ Crear una cuenta de profesor</summary>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.professors.store') }}">
                @csrf
                <div class="form-row-3">
                    <div><label for="full_name">Nombre completo</label><input class="field" id="full_name" name="full_name" required></div>
                    <div><label for="email">Correo electrónico</label><input class="field" id="email" type="email" name="email" required></div>
                    <div><label for="password">Contraseña inicial</label><input class="field" id="password" type="password" name="password" minlength="10" required><p class="help">Mínimo 10 caracteres.</p></div>
                </div>
                <div class="form-row-3">
                    <div><label for="institutional_id">ID institucional (opcional)</label><input class="field" id="institutional_id" name="institutional_id"></div>
                    <div><label for="school">Escuela (opcional)</label><input class="field" id="school" name="school"></div>
                    <div><label for="career">Carrera (opcional)</label><input class="field" id="career" name="career"></div>
                </div>
                <div class="form-actions"><span class="help">Comparte las credenciales mediante un canal seguro.</span><button class="btn" type="submit">Crear cuenta</button></div>
            </form>
        </div>
    </details>
</section>
<section class="card">
    <div class="card-head"><div><h2>Cuentas registradas</h2><p class="card-description">Listado de cuentas docentes creadas en la plataforma.</p></div><span class="badge">{{ $professors->count() }} cuentas</span></div>
    <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>ID docente</th><th>Escuela / carrera</th><th>Rol</th></tr></thead><tbody>
        @forelse($professors as $user)
            <tr><td><strong>{{ $user->name }}</strong></td><td>{{ $user->email }}</td><td>{{ $user->professor?->institutional_id ?? '—' }}</td><td>{{ $user->professor?->school ?? '—' }} {{ $user->professor?->career }}</td><td><span class="badge">{{ $user->role }}</span></td></tr>
        @empty
            <tr><td colspan="5">No hay cuentas adicionales registradas.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection
