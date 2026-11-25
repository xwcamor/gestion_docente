@extends('layouts.app')
@section('title', 'Gestión de cuentas')
@section('content')
<div class="page-head accounts-head">
    <div>
        <p class="eyebrow">Administración</p>
        <h1>Gestión de cuentas</h1>
        <p class="subhead">Controla accesos, perfiles docentes y estado de cada cuenta.</p>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="account-create-dialog"><x-icon name="users-round" /> Nueva cuenta docente</button>
    </div>
</div>

<section class="accounts-overview" aria-label="Resumen de cuentas">
    <article class="account-metric metric-total"><span>Total de cuentas</span><strong>{{ number_format($accountCount) }}</strong></article>
    <article class="account-metric metric-active"><span>Activas</span><strong>{{ number_format($activeCount) }}</strong></article>
    <article class="account-metric metric-inactive"><span>Inactivas</span><strong>{{ number_format($inactiveCount) }}</strong></article>
    <article class="account-metric metric-professors"><span>Docentes</span><strong>{{ number_format($professorCount) }}</strong></article>
    <article class="account-metric metric-admins"><span>Administradores</span><strong>{{ number_format($adminCount) }}</strong></article>
</section>

<section class="card accounts-card">
    <div class="card-head accounts-card-head">
        <div>
            <h2>Cuentas registradas</h2>
            <p class="card-description">Busca una persona para editar su perfil, restablecer su contraseña o gestionar su acceso.</p>
        </div>
        <span class="badge">{{ $professors->total() }} resultados</span>
    </div>

    <form method="GET" action="{{ route('admin.professors.index') }}" class="accounts-toolbar">
        <label class="accounts-search">
            <x-icon name="search" size="16" />
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Nombre, correo, ID o escuela…" aria-label="Buscar cuentas">
        </label>
        <select class="field" name="role" aria-label="Tipo de cuenta">
            <option value="">Todos los roles</option>
            <option value="profesor" @selected(request('role') === 'profesor')>Docentes</option>
            <option value="admin" @selected(request('role') === 'admin')>Administradores</option>
        </select>
        <select class="field" name="status" aria-label="Estado de la cuenta">
            <option value="">Todos los estados</option>
            <option value="active" @selected(request('status') === 'active')>Activas</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactivas</option>
        </select>
        <select class="field" name="sort" aria-label="Ordenar por">
            <option value="name" @selected($sort === 'name')>Nombre</option>
            <option value="email" @selected($sort === 'email')>Correo</option>
            <option value="created_at" @selected($sort === 'created_at')>Fecha de alta</option>
            <option value="is_active" @selected($sort === 'is_active')>Estado</option>
            <option value="role" @selected($sort === 'role')>Rol</option>
        </select>
        <select class="field accounts-per-page" name="per_page" aria-label="Registros por página">
            @foreach([10,15,25,50,100] as $limit)
                <option value="{{ $limit }}" @selected($perPage === $limit)>{{ $limit }} por página</option>
            @endforeach
        </select>
        <input type="hidden" name="direction" value="{{ $direction }}">
        <button class="btn btn-secondary" type="submit"><x-icon name="filter" size="15" /> Filtrar</button>
        @if(request()->hasAny(['q','role','status','sort','per_page']))
            <a class="btn btn-ghost" href="{{ route('admin.professors.index') }}">Limpiar</a>
        @endif
    </form>

    <div class="accounts-list">
        @forelse($professors as $account)
            @php($profile = $account->professor)
            @php($isSelf = (int) $account->id === (int) auth()->id())
            <article class="account-row">
                <div class="account-person">
                    <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($account->name), 0, 1)) }}</span>
                    <span class="account-person-copy">
                        <strong>{{ $account->name }}</strong>
                        <a href="mailto:{{ $account->email }}">{{ $account->email }}</a>
                        @if($isSelf)<small class="account-self-label">Tu cuenta actual</small>@endif
                    </span>
                </div>
                <div class="account-role-status">
                    <span class="account-role {{ $account->role === 'admin' ? 'is-admin' : 'is-professor' }}">{{ $account->role === 'admin' ? 'Administrador' : 'Docente' }}</span>
                    <span class="account-status {{ $account->is_active ? 'is-active' : 'is-inactive' }}">
                        <i></i>{{ $account->is_active ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
                <div class="account-profile">
                    <span><small>ID institucional</small><strong>{{ $profile?->institutional_id ?: '—' }}</strong></span>
                    <span><small>Escuela / carrera</small><strong>{{ $profile?->school ?: ($profile?->career ?: 'Sin datos') }}</strong></span>
                    @if($profile)
                        <span><small>Actividad académica</small><strong>{{ $profile->cycles_count }} ciclos · {{ $profile->groups_count }} grupos</strong></span>
                    @else
                        <span><small>Perfil</small><strong>No vinculado</strong></span>
                    @endif
                </div>
                <div class="account-actions">
                    <button class="btn btn-secondary btn-sm" type="button" data-modal-open="account-edit-dialog" data-edit-account
                        data-user-id="{{ $account->id }}"
                        data-update-url="{{ route('admin.professors.update', $account) }}"
                        data-full-name="{{ $profile?->full_name ?? $account->name }}"
                        data-email="{{ $account->email }}"
                        data-institutional-id="{{ $profile?->institutional_id }}"
                        data-school="{{ $profile?->school }}"
                        data-career="{{ $profile?->career }}">
                        <x-icon name="pencil" size="14" /> Editar
                    </button>
                    <form method="POST" action="{{ route('admin.professors.status', $account) }}"
                          data-confirm="{{ $account->is_active ? 'Se retirará el acceso de '.$account->name.'. Sus datos y registros académicos se conservarán. ¿Continuar?' : 'Se habilitará nuevamente el ingreso de '.$account->name.'. ¿Activar la cuenta?' }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm {{ $account->is_active ? 'btn-ghost' : 'btn-success' }}" type="submit" @disabled($isSelf)>
                            {{ $account->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.professors.destroy', $account) }}"
                          data-confirm="Solo se eliminará una cuenta sin historial académico. Si tiene ciclos, cursos, feriados u otros registros, el sistema impedirá la eliminación. ¿Continuar?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit" @disabled($isSelf) aria-label="Eliminar cuenta">
                            <x-icon name="x" size="14" /> Eliminar
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="accounts-empty">
                <span><x-icon name="users" size="25" /></span>
                <strong>{{ request()->hasAny(['q','role','status']) ? 'No se encontraron cuentas' : 'Todavía no hay cuentas' }}</strong>
                <p>{{ request()->hasAny(['q','role','status']) ? 'Cambia los filtros o limpia la búsqueda.' : 'Crea una cuenta docente para darle acceso al sistema.' }}</p>
                @if(request()->hasAny(['q','role','status']))
                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.professors.index') }}">Limpiar filtros</a>
                @else
                    <button class="btn btn-secondary btn-sm" type="button" data-modal-open="account-create-dialog">Crear cuenta</button>
                @endif
            </div>
        @endforelse
    </div>
    @if($professors->hasPages())
        <div class="accounts-pagination"><x-pagination :paginator="$professors" /></div>
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="account-create-dialog" aria-labelledby="account-create-title" @if($errors->any() && request('dialog') !== 'edit-account') data-auto-open @endif>
    <div class="dialog-head">
        <div><p class="eyebrow">Nueva cuenta</p><h2 id="account-create-title">Crear cuenta docente</h2><p>La persona podrá ingresar con el correo y la contraseña inicial.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('admin.professors.store') }}">
        @csrf
        @foreach(['q','status','role','sort','direction','per_page','page'] as $filter)
            <input type="hidden" name="return_{{ $filter === 'q' ? 'q' : $filter }}" value="{{ request($filter) }}">
        @endforeach
        <div class="dialog-body">
            @if($errors->any() && request('dialog') !== 'edit-account')
                <div class="dialog-error" role="alert"><strong>Revisa los datos ingresados:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
            <div class="account-form-section"><strong>Contraseña inicial</strong><span>Al menos 10 caracteres</span></div>
            <div class="form-row">
                <div>
                    <label for="password">Contraseña</label>
                    <input class="field" id="password" type="password" name="password" minlength="10" required autocomplete="new-password">
                    @error('password')<p class="help account-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation">Repetir contraseña</label>
                    <input class="field" id="password_confirmation" type="password" name="password_confirmation" minlength="10" required autocomplete="new-password">
                </div>
            </div>
            <p class="help">Entrega la contraseña inicial por un canal seguro. Podrás restablecerla desde esta pantalla.</p>
            <div class="account-form-section"><strong>Datos institucionales</strong><span>Opcionales</span></div>
            <div class="form-row">
                <div>
                    <label for="institutional_id">ID institucional</label>
                    <input class="field" id="institutional_id" name="institutional_id" value="{{ old('institutional_id') }}" maxlength="80" placeholder="Código docente">
                    @error('institutional_id')<p class="help account-field-error">{{ $message }}</p>@enderror
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
            <button class="btn" type="submit"><x-icon name="users-round" /> Crear cuenta</button>
        </div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="account-edit-dialog" aria-labelledby="account-edit-title" @if(request('dialog') === 'edit-account' && $errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div><p class="eyebrow">Administración</p><h2 id="account-edit-title">Editar cuenta</h2><p>Actualiza datos personales o restablece la contraseña de acceso.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="#" id="account-edit-form" data-loading-form="1">
        @csrf
        @method('PATCH')
        @foreach(['q','status','role','sort','direction','per_page','page'] as $filter)
            <input type="hidden" name="return_{{ $filter === 'q' ? 'q' : $filter }}" value="{{ request($filter) }}">
        @endforeach
        <div class="dialog-body">
            <div class="form-row">
                <div><label for="edit_full_name">Nombre completo</label><input class="field" id="edit_full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="255"></div>
                <div><label for="edit_email">Correo electrónico</label><input class="field" id="edit_email" type="email" name="email" value="{{ old('email') }}" required maxlength="255"></div>
            </div>
            <div class="form-row">
                <div><label for="edit_institutional_id">ID institucional</label><input class="field" id="edit_institutional_id" name="institutional_id" value="{{ old('institutional_id') }}" maxlength="80"></div>
                <div><label for="edit_school">Escuela</label><input class="field" id="edit_school" name="school" value="{{ old('school') }}" maxlength="255"></div>
            </div>
            <label for="edit_career">Carrera</label>
            <input class="field" id="edit_career" name="career" value="{{ old('career') }}" maxlength="255">
            <div class="account-form-section"><strong>Restablecer contraseña</strong><span>Opcional</span></div>
            <div class="form-row">
                <div><label for="edit_password">Nueva contraseña</label><input class="field" id="edit_password" type="password" name="password" minlength="10" autocomplete="new-password" placeholder="Dejar vacío para mantenerla"></div>
                <div><label for="edit_password_confirmation">Repetir contraseña</label><input class="field" id="edit_password_confirmation" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" placeholder="Repite la nueva contraseña"></div>
            </div>
            <p class="help">Por seguridad, la contraseña actual nunca se muestra. Si no necesitas cambiarla, deja ambos campos vacíos.</p>
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
.accounts-overview{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;margin:0 0 13px}
.account-metric{display:flex;align-items:center;justify-content:space-between;gap:8px;min-width:0;padding:12px;border:1px solid var(--line);border-top:3px solid var(--accent);border-radius:9px;background:var(--surface)}
.account-metric span{color:var(--muted);font-size:9px;font-weight:700}
.account-metric strong{color:var(--ink);font-size:22px;font-weight:800;font-variant-numeric:tabular-nums}
.metric-total{border-top-color:#4f86a8}.metric-active{border-top-color:#3b9a79}.metric-inactive{border-top-color:#b87979}.metric-professors{border-top-color:#7a82b4}.metric-admins{border-top-color:#aa8b4c}
.accounts-card{padding:15px}
.accounts-card-head{align-items:center}
.accounts-toolbar{display:grid;grid-template-columns:minmax(200px,1.5fr) repeat(4,minmax(110px,.75fr)) auto auto;align-items:center;gap:7px;margin:0 0 12px}
.accounts-search{display:flex;align-items:center;gap:8px;min-width:0;min-height:39px;padding:0 10px;border:1px solid var(--line-strong);border-radius:8px;background:var(--surface);color:var(--muted)}
.accounts-search:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}
.accounts-search input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--ink);font-size:11px}
.accounts-toolbar .field{min-width:0;min-height:39px;padding:7px 8px;font-size:10px}
.accounts-toolbar .btn{min-height:39px;padding:7px 10px;white-space:nowrap}
.accounts-list{display:grid;gap:8px}
.account-row{display:grid;grid-template-columns:minmax(190px,1.15fr) minmax(110px,.55fr) minmax(210px,1fr) auto;align-items:center;gap:14px;min-width:0;padding:13px;border:1px solid var(--line);border-radius:9px;background:var(--surface);transition:border-color .15s ease,background .15s ease}
.account-row:hover{border-color:var(--line-strong);background:var(--surface-soft)}
.account-person{display:flex;align-items:center;gap:10px;min-width:0}
.account-avatar{display:grid;place-items:center;width:39px;height:39px;flex:none;border:1px solid #d3e5ed;border-radius:10px;background:#edf6fa;color:#34758e;font-size:14px;font-weight:850}
.account-person-copy{display:grid;gap:3px;min-width:0}
.account-person-copy>strong{color:var(--ink);font-size:11px;line-height:1.35;overflow-wrap:anywhere}
.account-person-copy>a{color:var(--muted);font-size:9px;line-height:1.35;overflow-wrap:anywhere}
.account-self-label{color:var(--accent);font-size:8px;font-weight:700}
.account-role-status{display:grid;justify-items:start;gap:6px;min-width:0}
.account-role,.account-status{display:inline-flex;align-items:center;gap:5px;max-width:100%;padding:4px 7px;border-radius:6px;font-size:8px;font-weight:750;line-height:1.2;white-space:nowrap}
.account-role.is-admin{background:#f1edf9;color:#715a9e}
.account-role.is-professor{background:#eaf3f8;color:#3b7590}
.account-status{border:1px solid var(--line);background:var(--surface-soft);color:var(--muted)}
.account-status i{width:6px;height:6px;border-radius:50%;background:currentColor}
.account-status.is-active{border-color:#cbe6d5;background:#eef8f1;color:#347c50}
.account-status.is-inactive{border-color:#ead4d4;background:#fbf1f1;color:#9a5656}
.account-profile{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px 12px;min-width:0}
.account-profile>span{display:grid;gap:2px;min-width:0}
.account-profile>span:last-child{grid-column:1/-1}
.account-profile small{color:var(--muted);font-size:8px}
.account-profile strong{color:var(--ink-soft);font-size:9px;font-weight:650;line-height:1.35;overflow-wrap:anywhere}
.account-actions{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:5px;min-width:0}
.account-actions form{margin:0}
.account-actions .btn{min-height:32px;padding:6px 8px;border-radius:7px;font-size:9px;white-space:nowrap}
.account-actions .btn:disabled{opacity:.45;cursor:not-allowed}
.accounts-empty{display:grid;justify-items:center;gap:7px;padding:32px 14px;border:1px dashed var(--line-strong);border-radius:9px;text-align:center}
.accounts-empty>span{display:grid;place-items:center;width:42px;height:42px;border-radius:10px;background:var(--surface-soft);color:var(--muted)}
.accounts-empty strong{color:var(--ink);font-size:12px}
.accounts-empty p{margin:0;color:var(--muted);font-size:10px}
.accounts-pagination{margin-top:12px;padding-top:11px;border-top:1px solid var(--line)}
.account-form-section{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:16px 0 9px;padding-top:12px;border-top:1px solid var(--line)}
.account-form-section strong{color:var(--ink);font-size:11px}
.account-form-section span{color:var(--muted);font-size:9px}
.account-field-error{color:var(--danger)}
@media(max-width:1180px){
 .accounts-overview{grid-template-columns:repeat(3,minmax(0,1fr))}
 .accounts-toolbar{grid-template-columns:minmax(180px,1fr) repeat(2,minmax(110px,.6fr)) auto}
 .accounts-toolbar select[name="sort"],.accounts-toolbar select[name="per_page"]{display:none}
 .account-row{grid-template-columns:minmax(170px,1fr) minmax(100px,.5fr) auto}
 .account-profile{grid-column:1/-1;grid-row:2;grid-template-columns:repeat(3,minmax(0,1fr))}
 .account-profile>span:last-child{grid-column:auto}
 .account-actions{grid-column:3;grid-row:1/3;align-self:center;max-width:180px}
}
@media(max-width:760px){
 .accounts-head{align-items:stretch}
 .accounts-head .actions .btn{width:100%}
 .accounts-overview{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
 .account-metric{padding:10px}
 .account-metric strong{font-size:19px}
 .accounts-card{padding:11px}
 .accounts-toolbar{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
 .accounts-search{grid-column:1/-1}
 .accounts-toolbar select[name="sort"],.accounts-toolbar select[name="per_page"]{display:block}
 .accounts-toolbar button,.accounts-toolbar>a{min-width:0}
 .account-row{grid-template-columns:minmax(0,1fr) auto;gap:9px;padding:11px}
 .account-person{grid-column:1;grid-row:1}
 .account-role-status{grid-column:2;grid-row:1;justify-items:end}
 .account-profile{grid-column:1/-1;grid-row:2;grid-template-columns:repeat(2,minmax(0,1fr));padding-top:8px;border-top:1px solid var(--line)}
 .account-profile>span:last-child{grid-column:1/-1}
 .account-actions{grid-column:1/-1;grid-row:3;justify-content:flex-start;max-width:none;padding-top:7px;border-top:1px solid var(--line)}
 .account-actions .btn{min-height:36px;padding:7px 10px;font-size:10px}
}
@media(max-width:420px){
 .accounts-overview{grid-template-columns:repeat(2,minmax(0,1fr))}
 .accounts-toolbar{grid-template-columns:minmax(0,1fr)}
 .accounts-toolbar>*{width:100%}
 .account-row{grid-template-columns:minmax(0,1fr)}
 .account-person,.account-role-status,.account-profile,.account-actions{grid-column:1}
 .account-role-status{grid-row:2;display:flex;flex-wrap:wrap;justify-items:start}
 .account-profile{grid-row:3;grid-template-columns:repeat(2,minmax(0,1fr))}
 .account-actions{grid-row:4;display:grid;grid-template-columns:1fr 1fr 1fr}
 .account-actions .btn{width:100%;justify-content:center;font-size:9px;padding:6px}
 .account-actions .inline-edit{min-width:0}
}
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
        password: document.getElementById('edit_password'),
        password_confirmation: document.getElementById('edit_password_confirmation'),
    };

    const populate = button => {
        form.action = button.dataset.updateUrl;
        fields.full_name.value = button.dataset.fullName || '';
        fields.email.value = button.dataset.email || '';
        fields.institutional_id.value = button.dataset.institutionalId || '';
        fields.school.value = button.dataset.school || '';
        fields.career.value = button.dataset.career || '';
        fields.password.value = '';
        fields.password_confirmation.value = '';
    };

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-edit-account]');
        if (!button) return;
        populate(button);
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
            fields.password.value = '';
            fields.password_confirmation.value = '';
            if (!dialog.open) dialog.showModal();
            fields.full_name.focus();
        }
    @endif
})();
</script>
@endpush
