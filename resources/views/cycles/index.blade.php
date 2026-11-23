@extends('layouts.app')
@section('title', 'Ciclos académicos')
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">Ciclos académicos</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Organización académica</p>
        <h1>Mis ciclos académicos</h1>
        <p class="subhead">Administra tus periodos académicos.</p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" type="button" data-modal-open="workflow-help-dialog"><x-icon name="circle-help" /> Cómo funciona</button>
        <button class="btn" type="button" data-modal-open="cycle-create-dialog">
            <x-icon name="plus" /> Crear ciclo
        </button>
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Ciclos registrados</span><span class="stat-icon"><x-icon name="calendar-days" /></span></div>
        <div class="stat">{{ $cycles->total() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Años disponibles</span><span class="stat-icon"><x-icon name="calendar-range" /></span></div>
        <div class="stat">{{ $years->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Grupos registrados</span><span class="stat-icon"><x-icon name="users" /></span></div>
        <div class="stat">{{ $groupCount }}</div>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div>
            <h2>Mis ciclos</h2>
        </div>
        <span class="badge">{{ $cycles->count() }} {{ $cycles->total() === 1 ? 'ciclo' : 'ciclos' }}</span>
    </div>

    <form method="GET" action="{{ route('cycles.index') }}" class="filter-bar">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por año o código…" aria-label="Buscar ciclo">
        <select class="filter-input" name="year" aria-label="Filtrar por año">
            <option value="">Todos los años</option>
            @foreach($years as $year)
                <option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="filter" /> Filtrar</button>
        @if(request()->hasAny(['q', 'year']))
            <a class="btn btn-ghost btn-sm" href="{{ route('cycles.index') }}"><x-icon name="x" /> Limpiar</a>
        @endif
    </form>

    @forelse($cycles as $cycle)
        <article class="cycle-item">
            <div class="cycle-item-main">
                <span class="cycle-item-icon"><x-icon name="calendar-days" size="18" /></span>
                <div>
                    <h3 class="cycle-item-title">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h3>
                    <div class="cycle-item-meta">
                        <span><x-icon name="calendar-range" size="12" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
                        <span>·</span>
                        <span><x-icon name="users" size="12" /> {{ $cycle->groups_count }} {{ $cycle->groups_count === 1 ? 'grupo' : 'grupos' }}</span>
                        <span class="badge {{ $cycle->status === 'active' ? 'badge-green' : '' }}">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
                    </div>
                </div>
            </div>
            <div class="cycle-item-actions">
                <a class="btn btn-secondary btn-sm" href="{{ route('cycles.show', $cycle) }}">Ver grupos <x-icon name="arrow-right" /></a>
            </div>
        </article>
    @empty
        <div class="empty">
            <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="calendar-days" size="20" /></span>
            <strong>{{ request()->hasAny(['q', 'year']) ? 'No encontramos ciclos con esos filtros.' : 'Todavía no has creado un ciclo.' }}</strong>
            <span>{{ request()->hasAny(['q', 'year']) ? 'Prueba con otro año o código, o limpia los filtros.' : 'Crea tu primer ciclo para empezar. Después podrás registrar los grupos y cursos.' }}</span>
            @if(!request()->hasAny(['q', 'year']))
                <div class="mt-16"><button class="btn" type="button" data-modal-open="cycle-create-dialog"><x-icon name="plus" /> Crear ciclo</button></div>
            @else
                <div class="mt-16"><a class="btn btn-secondary" href="{{ route('cycles.index') }}"><x-icon name="x" /> Limpiar filtros</a></div>
            @endif
        </div>
    @endforelse
    @if($cycles->hasPages())
        <x-pagination :paginator="$cycles" />
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="cycle-create-dialog" aria-labelledby="cycle-create-title" data-backdrop-close @if(request()->boolean('nuevo') || $errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="cycle-create-title">Crear ciclo académico</h2>
            <p>Registra el periodo que aparece en tu programación docente.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('cycles.store') }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any())
                <div class="dialog-error" role="alert">
                    <strong><x-icon name="circle-alert" size="13" /> Revisa estos datos:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="modal-context">
                <x-icon name="info" size="16" />
                <span>Un ciclo representa un periodo académico completo. Cuando lo guardes, entrarás al ciclo para agregar los grupos que te asignaron.</span>
            </div>
            <div class="form-row">
                <div>
                    <label for="academic_year">Año académico <span class="muted">*</span></label>
                    <input class="field" id="academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', date('Y')) }}" required>
                    <p class="help">Ejemplo: 2027.</p>
                    @error('academic_year')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cycle_code">Código del ciclo <span class="muted">*</span></label>
                    <input class="field" id="cycle_code" name="cycle_code" value="{{ old('cycle_code', '10') }}" maxlength="20" required placeholder="Ej. 10 o 20">
                    <p class="help">Usa el código de tu programación académica.</p>
                    @error('cycle_code')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label for="start_date">Fecha de inicio <span class="muted">*</span></label>
                    <input class="field" id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required data-cycle-start>
                    @error('start_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="end_date">Fecha de fin <span class="muted">*</span></label>
                    <input class="field" id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" min="{{ old('start_date') }}" required data-cycle-end>
                    @error('end_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="help mt-16"><x-icon name="calendar-range" size="12" /> La fecha de fin debe ser igual o posterior al inicio.</p>
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button>
            <button class="btn" type="submit"><x-icon name="check" /> Guardar ciclo</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
(() => {
    const start = document.querySelector('[data-cycle-start]');
    const end = document.querySelector('[data-cycle-end]');
    if (!start || !end) return;
    const syncDates = () => {
        end.min = start.value || '';
        end.setCustomValidity(start.value && end.value && end.value < start.value
            ? 'La fecha de fin debe ser igual o posterior al inicio.'
            : '');
    };
    start.addEventListener('change', syncDates);
    end.addEventListener('change', syncDates);
    syncDates();
})();
</script>
@endpush
@endsection
