@extends('layouts.app')
@section('title', 'Cuentas')
@section('content')

<div class="page-head accounts-head">
    <div>
        <p class="eyebrow">Administración</p>
        <h1>Cuentas</h1>
        <p class="subhead">Administra los accesos y perfiles docentes desde un solo lugar.</p>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="account-create-dialog"><x-icon name="plus" /> Nueva cuenta</button>
    </div>
</div>

<section class="accounts-overview">
    <div class="account-metric">
        <span>Total de cuentas</span>
        <strong>{{ $accountCount }}</strong>
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
        <span class="badge">{{ $professors->total() }} cuentas</span>
    </div>

    <form method="GET" action="{{ route('admin.professors.index') }}" class="accounts-toolbar">
        <label class="accounts-search">
            <x-icon name="search" size="15" />
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Buscar nombre, correo, escuela o carrera…" aria-label="Buscar cuentas">
        </label>
        <select class="field" name="status" aria-label="Estado">
            <option value="">Todos los estados</option>
            <option value="active" @selected(request('status') === 'active')>Activos</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
        </select>
        <select class="field" name="sort" aria-label="Ordenar por">
            <option value="name" @selected(request('sort','name') === 'name')>Nombre</option>
            <option value="email" @selected(request('sort') === 'email')>Correo</option>
            <option value="created_at" @selected(request('sort') === 'created_at')>Fecha de alta</option>
            <option value="is_active" @selected(request('sort') === 'is_active')>Estado</option>
        </select>
        <select class="field" name="direction" aria-label="Dirección">
            <option value="asc" @selected(request('direction','asc') === 'asc')>Ascendente</option>
            <option value="desc" @selected(request('direction') === 'desc')>Descendente</option>
        </select>
        <select class="field" name="per_page" aria-label="Registros por página">
            @foreach([10,25,50,100] as $limit)<option value="{{ $limit }}" @selected((int) request('per_page',15) === $limit)>{{ $limit }} por página</option>@endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="filter" /> Aplicar</button>
        @if(request()->hasAny(['q','status','sort','direction','per_page']))
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.professors.index') }}"><x-icon name="x" /> Limpiar</a>
        @endif
    </form>

    <div class="account-grid" id="account-grid">
        @forelse($professors as $user)
            @php
                $profile = $user->professor;
            @endphp
            <article class="account-item">
                <div class="account-item-top">
                    <div class="account-avatar">{{ mb_strtoupper(mb_substr(trim($user->name), 0, 1)) }}</div>
                    <span class="badge {{ $user->role === 'admin' ? 'badge-blue' : 'badge-green' }}">
                        {{ $user->role === 'admin' ? 'Administrador' : 'Docente' }}
                    </span>
                    <span class="status-pill {{ $user->is_active ? 'status-active' : 'status-inactive' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
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
                @if($profile)
                    <div class="account-actions">
                        <button class="inline-edit" type="button" data-modal-open="account-edit-dialog" data-edit-account
                            data-user-id="{{ $user->id }}"
                            data-update-url="{{ route('admin.professors.update', $user) }}"
                            data-full-name="{{ $profile->full_name }}"
                            data-email="{{ $user->email }}"
                            data-institutional-id="{{ $profile->institutional_id }}"
                            data-school="{{ $profile->school }}"
                            data-career="{{ $profile->career }}">
                            <x-icon name="file-text" size="13" /> Editar perfil
                        </button>
                        <form method="POST" action="{{ route('admin.professors.status', $user) }}" data-confirm="{{ $user->is_active ? 'El docente perderá acceso al sistema, pero conservará sus datos académicos. ¿Desactivar esta cuenta?' : 'Se restablecerá el acceso de esta cuenta. ¿Activarla?' }}">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm {{ $user->is_active ? 'btn-secondary' : 'btn-success' }}" type="submit">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.professors.destroy', $user) }}" data-confirm="Solo se eliminará si la cuenta no tiene historial académico asociado. Si tiene ciclos, cursos o registros, se conservará y deberás desactivarla. ¿Continuar?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="empty accounts-empty">
                <strong>No hay cuentas registradas.</strong>
                <span>Crea la primera cuenta docente para comenzar.</span>
                <div class="mt-16"><button class="btn btn-secondary btn-sm" type="button" data-modal-open="account-create-dialog"><x-icon name="users" /> Crear cuenta</button></div>
            </div>
        @endforelse
    </div>
    @if($professors->hasPages())
        <x-pagination :paginator="$professors" />
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="account-create-dialog" aria-labelledby="account-create-title" data-backdrop-close @if($errors->any() && request('dialog') !== 'edit-account') data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="account-create-title">Crear cuenta docente</h2>
            <p>Los datos de acceso se utilizarán para ingresar al sistema.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('admin.professors.store') }}">
        @csrf
        <input type="hidden" name="return_q" value="{{ request('q') }}">
        <input type="hidden" name="return_page" value="{{ request('page', 1) }}">
        <input type="hidden" name="return_q" value="{{ request('q') }}">
        <input type="hidden" name="return_page" value="{{ request('page', 1) }}">
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
            <button class="btn" type="submit"><x-icon name="users" /> Crear cuenta</button>
        </div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="account-edit-dialog" aria-labelledby="account-edit-title" data-backdrop-close @if(request('dialog') === 'edit-account' && $errors->any()) data-auto-open @endif>
 <div class="dialog-head"><div><h2 id="account-edit-title">Editar perfil docente</h2><p>Actualiza los datos de acceso y del perfil.</p></div><button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button></div>
 <form method="POST" action="#" id="account-edit-form" data-loading-form="1">
  @csrf
  @method('PATCH')
  <input type="hidden" name="return_q" value="{{ request('q') }}">
  <input type="hidden" name="return_page" value="{{ request('page', 1) }}">
  <div class="dialog-body">
   <div class="form-row">
    <div><label for="edit_full_name">Nombre completo</label><input class="field" id="edit_full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="255"></div>
    <div><label for="edit_email">Correo electrónico</label><input class="field" id="edit_email" type="email" name="email" value="{{ old('email') }}" required maxlength="255"></div>
   </div>
   <div class="form-row">
    <div><label for="edit_institutional_id">ID institucional</label><input class="field" id="edit_institutional_id" name="institutional_id" value="{{ old('institutional_id') }}" maxlength="80"></div>
    <div><label for="edit_school">Escuela</label><input class="field" id="edit_school" name="school" value="{{ old('school') }}" maxlength="255"></div>
   </div>
   <label for="edit_career">Carrera</label><input class="field" id="edit_career" name="career" value="{{ old('career') }}" maxlength="255">
   @if($errors->any() && request('dialog') === 'edit-account')
    <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
   @endif
  </div>
  <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
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

/* Cuentas: métricas y perfiles con acentos propios */
.account-metric{border-color:#d8e6ec;background:#f4f9fb}
.account-metric:nth-child(1){border-top:3px solid #4d84ad;background:#f2f7fc}
.account-metric:nth-child(2){border-top:3px solid #3f9a88;background:#f1faf7}
.account-metric:nth-child(3){border-top:3px solid #9278ba;background:#f8f5fc}
.account-metric span{color:#607c8a}
.account-metric strong{color:#2f7088}
.accounts-search{border-color:#ccdde5;color:#4d8396}
.accounts-search:focus-within{border-color:#4b91a9;box-shadow:0 0 0 3px rgba(53,131,158,.12)}
.account-item{border-color:#dce8ed;background:linear-gradient(160deg,#fff 0%,#fbfdfe 100%)}
.account-item:hover{border-color:#9dc3d1;background:#f8fcfd}
.account-avatar{border-color:#cfe1eb;background:linear-gradient(140deg,#e7f2f8,#edf7f6);color:#28677d}
.account-identity h3{color:#294e61}
.account-identity a{color:#39788f}
.account-details{border-top-color:#e5eef2}
.account-details span{color:#708b98}
.account-details strong{color:#3f6271}

</style>
@endpush

@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('account-edit-dialog');
    const form = document.getElementById('account-edit-form');
    if (!dialog || !form) return;
    const fields = {
        full_name: document.getElementById('edit_full_name'),
        email: document.getElementById('edit_email'),
        institutional_id: document.getElementById('edit_institutional_id'),
        school: document.getElementById('edit_school'),
        career: document.getElementById('edit_career'),
    };
    const populate = (button) => {
        form.action = button.dataset.updateUrl;
        fields.full_name.value = button.dataset.fullName || '';
        fields.email.value = button.dataset.email || '';
        fields.institutional_id.value = button.dataset.institutionalId || '';
        fields.school.value = button.dataset.school || '';
        fields.career.value = button.dataset.career || '';
    };
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-edit-account]');
        if (!button) return;
        populate(button);
        if (!dialog.open) dialog.showModal();
        fields.full_name.focus();
    });
    @if(request('dialog') === 'edit-account')
        const button = document.querySelector('[data-edit-account][data-user-id="{{ (int) request('user_id') }}"]');
        if (button) {
            populate(button);
            fields.full_name.value = @json(old('full_name', ''));
            fields.email.value = @json(old('email', ''));
            fields.institutional_id.value = @json(old('institutional_id', ''));
            fields.school.value = @json(old('school', ''));
            fields.career.value = @json(old('career', ''));
            if (!dialog.open) dialog.showModal();
            fields.full_name.focus();
        }
    @endif
})();
</script>
@endpush
