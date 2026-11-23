@extends('layouts.app')
@section('title', 'Cuentas')
@section('content')
@php
    $professorCount = $professors->where('role', 'profesor')->count();
    $adminCount = $professors->where('role', 'admin')->count();
@endphp

<div class="page-head accounts-head">
    <div>
        <p class="eyebrow">Administración</p>
        <h1>Cuentas</h1>
        <p class="subhead">Administra los accesos y perfiles docentes desde un solo lugar.</p>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="account-create-dialog">＋ Nueva cuenta</button>
    </div>
</div>

<section class="accounts-overview">
    <div class="account-metric">
        <span>Total de cuentas</span>
        <strong>{{ $professors->count() }}</strong>
    </div>
    <div class="account-metric">
        <span>Docentes</span>
        <strong>{{ $professorCount }}</strong>
    </div>
    <div class="account-metric">
        <span>Administradores</span>
        <strong>{{ $adminCount }}</strong>
    </div>
</section>

<section class="card accounts-card">
    <div class="card-head accounts-card-head">
        <div>
            <h2>Personas con acceso</h2>
            <p class="card-description">Cada docente tiene su propio espacio y sus datos académicos.</p>
        </div>
        <span class="badge">{{ $professors->count() }} cuentas</span>
    </div>

    <div class="accounts-toolbar">
        <label class="accounts-search">
            <span aria-hidden="true">⌕</span>
            <input id="account-search" type="search" placeholder="Buscar por nombre, correo o escuela…" autocomplete="off" aria-label="Buscar cuentas">
        </label>
        <span class="accounts-counter" id="account-visible-count">{{ $professors->count() }} visibles</span>
    </div>

    <div class="account-grid" id="account-grid">
        @forelse($professors as $user)
            @php
                $profile = $user->professor;
                $searchText = mb_strtolower(implode(' ', [
                    $user->name,
                    $user->email,
                    $profile?->institutional_id,
                    $profile?->school,
                    $profile?->career,
                    $user->role,
                ]));
            @endphp
            <article class="account-item" data-account-search="{{ $searchText }}">
                <div class="account-item-top">
                    <div class="account-avatar">{{ mb_strtoupper(mb_substr(trim($user->name), 0, 1)) }}</div>
                    <span class="badge {{ $user->role === 'admin' ? 'badge-blue' : 'badge-green' }}">
                        {{ $user->role === 'admin' ? 'Administrador' : 'Docente' }}
                    </span>
                </div>
                <div class="account-identity">
                    <h3>{{ $user->name }}</h3>
                    <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                </div>
                <div class="account-details">
                    <div><span>ID institucional</span><strong>{{ $profile?->institutional_id ?: 'No registrado' }}</strong></div>
                    <div><span>Escuela</span><strong>{{ $profile?->school ?: 'No registrada' }}</strong></div>
                    <div><span>Carrera</span><strong>{{ $profile?->career ?: 'No registrada' }}</strong></div>
                </div>
            </article>
        @empty
            <div class="empty accounts-empty">
                <strong>No hay cuentas registradas.</strong>
                <span>Crea la primera cuenta docente para comenzar.</span>
                <div class="mt-16"><button class="btn btn-secondary btn-sm" type="button" data-modal-open="account-create-dialog">Crear cuenta</button></div>
            </div>
        @endforelse
    </div>
    <div class="empty accounts-empty" id="account-no-results" hidden>
        <strong>No encontramos cuentas.</strong>
        <span>Prueba con otro nombre, correo o escuela.</span>
    </div>
</section>

<dialog class="ui-dialog sheet-dialog" id="account-create-dialog" aria-labelledby="account-create-title" data-backdrop-close @if($errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="account-create-title">Crear cuenta docente</h2>
            <p>Los datos de acceso se utilizarán para ingresar al sistema.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar">×</button>
    </div>
    <form method="POST" action="{{ route('admin.professors.store') }}">
        @csrf
        <div class="dialog-body">
            @if($errors->any())
                <div class="dialog-error" role="alert">
                    <strong>Revisa los datos ingresados:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="form-row">
                <div>
                    <label for="full_name">Nombre completo</label>
                    <input class="field" id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="255" autocomplete="name" placeholder="Nombres y apellidos">
                    @error('full_name')<p class="help account-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email">Correo electrónico</label>
                    <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="docente@institucion.edu">
                    @error('email')<p class="help account-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <label for="password">Contraseña inicial</label>
            <input class="field" id="password" type="password" name="password" minlength="10" required autocomplete="new-password" placeholder="Mínimo 10 caracteres">
            <p class="help">Entrega esta contraseña al docente por un canal seguro.</p>
            @error('password')<p class="help account-field-error">{{ $message }}</p>@enderror

            <div class="account-section-label">Datos institucionales <span>Opcionales</span></div>
            <div class="form-row">
                <div>
                    <label for="institutional_id">ID institucional</label>
                    <input class="field" id="institutional_id" name="institutional_id" value="{{ old('institutional_id') }}" maxlength="80" placeholder="Código docente">
                </div>
                <div>
                    <label for="school">Escuela</label>
                    <input class="field" id="school" name="school" value="{{ old('school') }}" maxlength="255" placeholder="Escuela profesional">
                </div>
            </div>
            <label for="career">Carrera</label>
            <input class="field" id="career" name="career" value="{{ old('career') }}" maxlength="255" placeholder="Carrera o especialidad">
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button>
            <button class="btn" type="submit">Crear cuenta</button>
        </div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.accounts-head{align-items:center}
.accounts-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:0 0 13px}
.account-metric{display:flex;align-items:center;justify-content:space-between;gap:12px;min-width:0;padding:13px 15px;border:1px solid #e2e6e0;border-radius:8px;background:#fbfcfa}
.account-metric span{color:#788279;font-size:10px}
.account-metric strong{color:#40584a;font-size:21px;font-weight:690}
.accounts-card{padding:17px}
.accounts-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
.accounts-search{display:flex;align-items:center;gap:8px;width:min(100%,430px);min-height:38px;margin:0;padding:0 10px;border:1px solid #dfe5dd;border-radius:7px;background:#fff;color:#849087}
.accounts-search span{font-size:18px;line-height:1}
.accounts-search input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:#303a33;font-size:11px}
.accounts-search:focus-within{border-color:#829783;box-shadow:0 0 0 3px rgba(87,116,94,.09)}
.accounts-counter{color:#89938b;font-size:10px;white-space:nowrap}
.account-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.account-item{min-width:0;padding:14px;border:1px solid #e3e8e1;border-radius:8px;background:#fff;transition:border-color .14s ease}
.account-item:hover{border-color:#c3d0c1}
.account-item-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
.account-avatar{width:36px;height:36px;flex:none;display:grid;place-items:center;border:1px solid #dce5dc;border-radius:8px;background:#f1f5f0;color:#40584a;font-size:14px;font-weight:750}
.account-identity{min-width:0;margin:12px 0}
.account-identity h3{margin:0 0 3px;color:#303a33;font-size:13px;line-height:1.35;overflow-wrap:anywhere}
.account-identity a{color:#718075;font-size:10px;overflow-wrap:anywhere}
.account-identity a:hover{color:#40584a}
.account-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;padding-top:11px;border-top:1px solid #edf0eb}
.account-details>div{min-width:0}
.account-details>div:last-child{grid-column:1/-1}
.account-details span{display:block;margin-bottom:3px;color:#929b92;font-size:9px}
.account-details strong{display:block;color:#566158;font-size:10px;font-weight:620;overflow-wrap:anywhere}
.accounts-empty{grid-column:1/-1}
.account-section-label{display:flex;align-items:center;justify-content:space-between;margin-top:17px;padding-top:13px;border-top:1px solid #edf0eb;color:#526052;font-size:11px;font-weight:700}
.account-section-label span{color:#929b92;font-size:9px;font-weight:500}
.account-field-error{color:#914d45}
@media(max-width:700px){
 .accounts-head{align-items:stretch}
 .accounts-head .actions .btn{width:100%}
 .accounts-overview{gap:7px}
 .account-metric{align-items:flex-start;flex-direction:column;gap:5px;padding:10px}
 .account-metric span{font-size:9px}
 .account-metric strong{font-size:19px}
 .accounts-card{padding:12px}
 .account-grid{grid-template-columns:1fr}
 .accounts-toolbar{align-items:stretch;flex-direction:column;gap:7px}
 .accounts-search{width:100%}
 .accounts-counter{text-align:right}
 .account-item{padding:12px}
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const search = document.getElementById('account-search');
    const grid = document.getElementById('account-grid');
    const counter = document.getElementById('account-visible-count');
    const empty = document.getElementById('account-no-results');
    if (!search || !grid) return;

    const items = [...grid.querySelectorAll('[data-account-search]')];
    search.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        items.forEach(item => {
            const match = item.dataset.accountSearch.includes(term);
            item.hidden = !match;
            if (match) visible++;
        });
        counter.textContent = visible + (visible === 1 ? ' visible' : ' visibles');
        empty.hidden = visible > 0;
    });
})();
</script>
@endpush