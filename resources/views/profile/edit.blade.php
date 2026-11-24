@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Cuenta personal</p>
        <h1>Mi perfil</h1>
        <p class="subhead">Actualiza tus datos personales e institucionales.</p>
    </div>
</div>

<section class="card form-card" style="max-width:820px">
    <form method="POST" action="{{ route('profile.update') }}" data-loading-form="1">
        @csrf
        @method('PATCH')
        <div class="form-grid">
            <div class="form-field">
                <label for="name">Nombre completo</label>
                <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="email">Correo electrónico</label>
                <input class="field" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="institutional_id">ID institucional</label>
                <input class="field" id="institutional_id" name="institutional_id" value="{{ old('institutional_id', $user->professor->institutional_id) }}" maxlength="80">
                @error('institutional_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="school">Escuela</label>
                <input class="field" id="school" name="school" value="{{ old('school', $user->professor->school) }}" maxlength="255">
                @error('school')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="career">Carrera o especialidad</label>
                <input class="field" id="career" name="career" value="{{ old('career', $user->professor->career) }}" maxlength="255">
                @error('career')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <details class="settings-accordion">
            <summary>Cambiar contraseña</summary>
            <div class="form-grid">
                <div class="form-field">
                    <label for="current_password">Contraseña actual</label>
                    <input class="field" id="current_password" type="password" name="current_password" autocomplete="current-password">
                    @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="password">Nueva contraseña</label>
                    <input class="field" id="password" type="password" name="password" minlength="10" autocomplete="new-password">
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <input class="field" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>
        </details>

        <div class="dialog-footer" style="padding:16px 0 0">
            <button class="btn" type="submit">Guardar cambios</button>
        </div>
    </form>
</section>
@endsection
