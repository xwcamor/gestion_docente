@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="guest-card">
    <p class="guest-eyebrow">Bienvenido de nuevo</p>
    <h2>Inicia sesión</h2>
    <p class="guest-description">Ingresa con tu cuenta docente para continuar con tu espacio académico.</p>

    @if($errors->any())
        <div class="alert alert-error" role="alert">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field-group">
            <label class="field-label" for="email">Correo electrónico</label>
            <div class="field-wrap">
                <span class="field-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg></span>
                <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="tu@institucion.edu.pe">
            </div>
            @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>
        <div class="field-group">
            <label class="field-label" for="password">Contraseña</label>
            <div class="field-wrap">
                <span class="field-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg></span>
                <input class="field" id="password" type="password" name="password" autocomplete="current-password" required placeholder="Ingresa tu contraseña">
                <button class="field-toggle" type="button" id="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
            </div>
            @error('password')<div class="error-text">{{ $message }}</div>@enderror
        </div>
        <label class="remember-row"><input type="checkbox" name="remember" value="1"> Mantener mi sesión iniciada</label>
        <button class="btn" type="submit">Ingresar a mi espacio <span aria-hidden="true">→</span></button>
    </form>
    <div class="guest-security"><span aria-hidden="true">⌑</span> Tu cuenta es personal y tu acceso es privado</div>
</div>
<script>
(() => {
    const button=document.getElementById('toggle-password');
    const input=document.getElementById('password');
    if(!button||!input)return;
    button.addEventListener('click',()=>{
        const visible=input.type==='password';
        input.type=visible?'text':'password';
        button.textContent=visible?'Ocultar':'Mostrar';
        button.setAttribute('aria-label',visible?'Ocultar contraseña':'Mostrar contraseña');
        button.setAttribute('aria-pressed',String(visible));
    });
})();
</script>
@endsection