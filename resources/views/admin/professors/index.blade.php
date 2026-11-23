@extends('layouts.app')
@section('title','Administración de profesores')
@section('content')
<div class="page-head"><div><h1>Profesores y cuentas</h1><p class="subhead">Crea accesos individuales. Cada profesor solo visualiza sus ciclos, grupos y documentos.</p></div></div>
<section class="card"><h2>Crear cuenta de profesor</h2>
<form method="POST" action="{{ route('admin.professors.store') }}">@csrf
<div class="form-row-3"><div><label>Nombre completo</label><input class="field" name="full_name" required></div><div><label>Correo</label><input class="field" type="email" name="email" required></div><div><label>Contraseña inicial</label><input class="field" type="password" name="password" minlength="10" required></div></div>
<div class="form-row-3"><div><label>ID institucional</label><input class="field" name="institutional_id"></div><div><label>Escuela</label><input class="field" name="school"></div><div><label>Carrera</label><input class="field" name="career"></div></div>
<button class="btn mt-16" type="submit">Crear profesor</button></form></section>
<section class="card"><h2>Cuentas registradas</h2><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>ID docente</th><th>Escuela / carrera</th><th>Rol</th></tr></thead><tbody>
@foreach($professors as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->professor?->institutional_id ?? '—' }}</td><td>{{ $user->professor?->school ?? '—' }} {{ $user->professor?->career }}</td><td><span class="badge">{{ $user->role }}</span></td></tr>@endforeach
</tbody></table></div></section>
@endsection
