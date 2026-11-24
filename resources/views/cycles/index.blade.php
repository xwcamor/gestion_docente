@extends('layouts.app')
@section('title', 'Cargas laborales')
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">Cargas laborales</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Programación docente</p>
        <h1>Cargas laborales</h1>
        <p class="subhead">Cada carga se organiza por ciclo y grupo. Importa el archivo maestro para registrar cursos, NRC y fechas.</p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" type="button" data-modal-open="cycle-create-dialog"><x-icon name="plus" /> Crear ciclo manualmente</button>
        <a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar carga laboral</a>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div>
            <h2>Cargas laborales registradas</h2>
        </div>
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
                        <span><x-icon name="graduation-cap" size="12" /> {{ $cycle->career ?: 'Carrera pendiente' }}</span>
                        <span>·</span>
                        <span>Semestre {{ $cycle->semester ?: '—' }}</span>
                        <span>·</span>
                        <span><x-icon name="calendar-range" size="12" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
                        <span>·</span>
                        <span><x-icon name="users" size="12" /> {{ $cycle->groups_count }} {{ $cycle->groups_count === 1 ? 'grupo' : 'grupos' }}</span>
                        <span class="badge {{ $cycle->status === 'active' ? 'badge-green' : '' }}">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
                    </div>
                </div>
            </div>
            <div class="cycle-item-actions">
                <a class="btn btn-secondary btn-sm" href="{{ route('cycles.show', $cycle) }}">Ver ciclo <x-icon name="arrow-right" /></a>
                <form method="POST" action="{{ route('cycles.destroy', $cycle) }}" data-confirm="Se eliminará este ciclo junto con sus grupos, asignaciones, modalidades, matrículas, sesiones y documentos generados. El catálogo general de cursos y los alumnos que se utilicen en otros grupos se conservarán. Esta acción no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Eliminar ciclo</button>
                </form>
            </div>
        </article>
    @empty
        <div class="empty">
            <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="calendar-days" size="20" /></span>
            <strong>{{ request()->hasAny(['q', 'year']) ? 'No encontramos cargas laborales con esos filtros.' : 'Todavía no has importado una carga laboral.' }}</strong>
            <span>{{ request()->hasAny(['q', 'year']) ? 'Prueba con otro año o código, o limpia los filtros.' : 'Sube el archivo maestro para registrar el ciclo, grupo, cursos, NRC y fechas.' }}</span>
            @if(!request()->hasAny(['q', 'year']))
                <div class="mt-16"><a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar carga laboral</a></div>
            @else
                <div class="mt-16"><a class="btn btn-secondary" href="{{ route('cycles.index') }}"><x-icon name="x" /> Limpiar filtros</a></div>
            @endif
        </div>
    @endforelse
    @if($cycles->hasPages())
        <x-pagination :paginator="$cycles" />
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="cycle-create-dialog" aria-labelledby="cycle-create-title" @if(request()->boolean('nuevo') || $errors->any()) data-auto-open @endif>
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
                <span>Todo ciclo debe tener al menos un grupo. Registra aquí el primero; después podrás agregar más.</span>
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
                    <label for="career">Nombre de la carrera <span class="muted">*</span></label>
                    <input class="field" id="career" name="career" value="{{ old('career', auth()->user()->professor->career) }}" maxlength="255" required placeholder="Ej. Desarrollo de Software">
                    @error('career')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="semester">Semestre académico <span class="muted">*</span></label>
                    <input class="field" id="semester" name="semester" value="{{ old('semester') }}" maxlength="60" required placeholder="Ej. IV semestre">
                    @error('semester')<p class="field-error">{{ $message }}</p>@enderror
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
            <div>
                <label for="group_name">Nombre del primer grupo <span class="muted">*</span></label>
                <input class="field" id="group_name" name="group_name" value="{{ old('group_name') }}" maxlength="100" required placeholder="Ej. IA 202">
                @error('group_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <p class="help mt-16"><x-icon name="calendar-range" size="12" /> La fecha de fin debe ser igual o posterior al inicio.</p>
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close><x-icon name="x" /> Cancelar</button>
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
