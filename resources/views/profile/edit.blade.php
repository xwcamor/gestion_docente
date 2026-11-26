@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
@php
    $profileInitial = mb_strtoupper(mb_substr(trim($user->name) ?: 'U', 0, 1));
    $profileActive = (bool) ($user->is_active ?? true);
@endphp

<div class="page-head profile-page-head">
    <div>
        <p class="eyebrow">Cuenta personal</p>
        <h1>Mi perfil</h1>
        <p class="subhead">Administra tus datos personales, institucionales y la seguridad de tu acceso.</p>
    </div>
    <span class="badge {{ $profileActive ? 'badge-green' : 'badge-red' }}">{{ $profileActive ? 'Cuenta activa' : 'Cuenta inactiva' }}</span>
</div>

<div class="profile-layout">
    <section class="card profile-editor-card">
        <div class="profile-section-heading">
            <div class="profile-avatar">{{ $profileInitial }}</div>
            <div>
                <h2>Información del perfil</h2>
                <p class="card-description">Estos datos identifican tu cuenta y tu asignación docente.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" data-loading-form="1">
            @csrf
            @method('PATCH')

            <div class="profile-fields-grid">
                <div class="form-field">
                    <label for="name">Nombre completo</label>
                    <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="email">Correo electrónico</label>
                    <input class="field" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
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
                <div class="form-field profile-field-wide">
                    <label for="career">Carrera o especialidad</label>
                    <input class="field" id="career" data-uppercase name="career" value="{{ old('career', $user->professor->career) }}" maxlength="255">
                    @error('career')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <details class="profile-password-panel">
                <summary>
                    <span class="profile-password-icon"><x-icon name="settings" size="16" /></span>
                    <span><strong>Cambiar contraseña</strong><small>Actualiza tu clave de acceso cuando lo necesites.</small></span>
                </summary>
                <div class="profile-password-fields">
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

            <div class="profile-form-footer">
                <p>Los cambios se aplican a tu cuenta y a tu ficha docente.</p>
                <button class="btn" type="submit"><x-icon name="check" size="15" /> Guardar cambios</button>
            </div>
        </form>
    </section>

    <aside class="profile-aside">
        <section class="card profile-account-card">
            <p class="eyebrow">Resumen de cuenta</p>
            <h2>{{ $user->name }}</h2>
            <p class="profile-email">{{ $user->email }}</p>
            <div class="profile-account-meta">
                <div><span>Tipo de acceso</span><strong>{{ $user->isAdmin() ? 'Administrador' : 'Docente' }}</strong></div>
                <div><span>Estado</span><strong class="{{ $profileActive ? 'profile-state-active' : 'profile-state-inactive' }}">{{ $profileActive ? 'Activo' : 'Inactivo' }}</strong></div>
                <div><span>ID institucional</span><strong>{{ $user->professor->institutional_id ?: 'No registrado' }}</strong></div>
            </div>
        </section>

        <section class="card profile-security-card">
            <div class="profile-aside-icon"><x-icon name="shield-check" size="19" /></div>
            <h3>Seguridad de la cuenta</h3>
            <p>Para cambiar la contraseña debes ingresar tu contraseña actual. La nueva contraseña debe tener al menos 10 caracteres.</p>
            <p class="help">No compartas tus credenciales. Cierra sesión cuando utilices un equipo compartido.</p>
        </section>

        @if(auth()->user()->isAdmin())
            <section class="card profile-admin-shortcut">
                <p class="eyebrow">Administración</p>
                <strong>¿Necesitas personalizar la aplicación?</strong>
                <p class="card-description">Cambia el nombre, el logotipo y los colores institucionales desde los ajustes del sistema.</p>
                <a class="btn btn-secondary btn-block" href="{{ route('admin.settings.edit') }}"><x-icon name="settings" size="15" /> Ajustes del sistema</a>
            </section>
        @endif
    </aside>
</div>
@endsection

@push('styles')
<style>
.profile-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(280px,.78fr);align-items:start;gap:15px;width:100%;max-width:1240px}
.profile-editor-card{padding:22px;min-width:0}
.profile-section-heading{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid var(--line)}
.profile-section-heading h2{margin:0 0 3px}
.profile-section-heading .card-description{margin:0}
.profile-avatar{width:44px;height:44px;flex:none;display:grid;place-items:center;border-radius:12px;background:var(--accent-soft);color:var(--accent);font-size:18px;font-weight:800}
.profile-fields-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 16px}
.profile-field-wide{grid-column:1/-1}
.profile-password-panel{margin-top:20px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft);overflow:hidden}
.profile-password-panel summary{display:flex;align-items:center;gap:10px;padding:13px 14px;cursor:pointer;list-style:none}
.profile-password-panel summary::-webkit-details-marker{display:none}
.profile-password-panel summary:after{content:"+";margin-left:auto;color:var(--muted);font-size:18px}
.profile-password-panel[open] summary:after{content:"−"}
.profile-password-panel summary strong,.profile-password-panel summary small{display:block}
.profile-password-panel summary strong{font-size:11px;color:var(--ink-soft)}
.profile-password-panel summary small{margin-top:3px;color:var(--muted);font-size:10px}
.profile-password-icon{width:32px;height:32px;flex:none;display:grid;place-items:center;border-radius:8px;background:var(--accent-soft);color:var(--accent)}
.profile-password-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 14px;padding:0 14px 14px;border-top:1px solid var(--line)}
.profile-password-fields .form-field:first-child{grid-column:1/-1}
.profile-form-footer{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:18px;padding-top:15px;border-top:1px solid var(--line)}
.profile-form-footer p{margin:0;color:var(--muted);font-size:10px;line-height:1.5}
.profile-aside{display:grid;gap:13px;min-width:0}
.profile-account-card{padding:20px}
.profile-account-card h2{margin:5px 0;font-size:19px;overflow-wrap:anywhere}
.profile-email{margin:0;color:var(--muted);font-size:11px;overflow-wrap:anywhere}
.profile-account-meta{display:grid;gap:0;margin-top:18px}
.profile-account-meta>div{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid var(--line)}
.profile-account-meta span{color:var(--muted);font-size:10px}
.profile-account-meta strong{color:var(--ink-soft);font-size:10px;text-align:right}
.profile-account-meta .profile-state-active{color:#23804d}
.profile-account-meta .profile-state-inactive{color:#a33d3d}
.profile-security-card{padding:18px}
.profile-aside-icon{width:36px;height:36px;display:grid;place-items:center;margin-bottom:11px;border-radius:9px;background:var(--accent-soft);color:var(--accent)}
.profile-security-card h3{margin-bottom:5px}
.profile-security-card p{margin:5px 0;color:var(--muted);font-size:10px;line-height:1.6}
.profile-admin-shortcut{padding:18px}
.profile-admin-shortcut strong{display:block;margin:4px 0 6px;color:var(--ink-soft);font-size:12px}
.profile-admin-shortcut .btn{margin-top:13px}
.profile-page-head{max-width:1240px}
html[data-theme="dark"] .profile-password-panel{background:#111d25;border-color:#344650}
html[data-theme="dark"] .profile-section-heading{border-bottom-color:#2b3a45}
html[data-theme="dark"] .profile-account-meta>div{border-top-color:#2b3a45}
html[data-theme="dark"] .profile-admin-shortcut strong{color:#d7e4ea}
@media(max-width:900px){.profile-layout{grid-template-columns:minmax(0,1fr)}.profile-aside{grid-template-columns:repeat(2,minmax(0,1fr))}.profile-admin-shortcut{grid-column:1/-1}}
@media(max-width:600px){.profile-editor-card{padding:14px}.profile-fields-grid,.profile-password-fields{grid-template-columns:minmax(0,1fr)}.profile-field-wide,.profile-password-fields .form-field:first-child{grid-column:auto}.profile-aside{grid-template-columns:minmax(0,1fr)}.profile-admin-shortcut{grid-column:auto}.profile-form-footer{align-items:stretch;flex-direction:column}.profile-form-footer .btn{width:100%}}
</style>
@endpush
