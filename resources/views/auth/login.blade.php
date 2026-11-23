@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="guest-card">
    <div class="brand-mark">GD</div>
    <p class="muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;margin:20px 0 8px">Plataforma académica</p>
    <h1 style="font-size:27px;margin:0 0 8px;color:#17365d">Bienvenido</h1>
    <p class="muted" style="margin:0 0 22px">Ingresa con la cuenta individual que te asignaron para gestionar tus ciclos, cursos y documentos.</p>

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Correo electrónico</label>
        <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="profesor@institucion.edu.pe">
        @error('email')<div class="error-text">{{ $message }}</div>@enderror

        <label for="password">Contraseña</label>
        <input class="field" id="password" type="password" name="password" autocomplete="current-password" required placeholder="Tu contraseña">
        @error('password')<div class="error-text">{{ $message }}</div>@enderror

        <label class="checkline" style="margin-top:14px"><input type="checkbox" name="remember" value="1"> Mantener mi sesión iniciada</label>
        <button class="btn btn-block" type="submit">Ingresar al sistema</button>
    </form>
    <p class="muted small" style="text-align:center;margin:22px 0 0">Acceso personal · Gestión Docente</p>
</div>
@endsection
