@extends('layouts.app')
@section('title', 'Cargas laborales')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Programación docente</p>
        <h1>Ciclos académicos</h1>
        <p class="subhead">Administra tus ciclos, grupos y cursos.</p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" type="button" data-modal-open="cycle-create-dialog"><x-icon name="plus" /> Crear ciclo</button>
        <a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar distribución</a>
    </div>
</div>

<section class="card cycle-directory-card">
    <div class="card-head">
        <div>
            <h2>Mis ciclos</h2>
            <p class="card-description">Cada ciclo tiene su propio explorador de carga laboral. Abre el árbol solo cuando necesites trabajar con sus grupos y cursos.</p>
        </div>
        <span class="badge badge-blue">{{ $cycles->total() }} ciclos</span>
    </div>

    <form method="GET" action="{{ route('cycles.index') }}" class="filter-bar">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar ciclo, carrera, grupo, curso o NRC…" aria-label="Buscar ciclo">
        <select class="filter-input" name="year" aria-label="Filtrar por año">
            <option value="">Todos los años</option>
            @foreach($years as $year)
                <option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="filter" /> Buscar</button>
        @if(request()->hasAny(['q', 'year']))
            <a class="btn btn-ghost btn-sm" href="{{ route('cycles.index') }}"><x-icon name="x" /> Limpiar</a>
        @endif
    </form>

    <div class="cycle-directory-list">
        @forelse($cycles as $cycle)
            <article class="cycle-directory-row">
                <div class="cycle-directory-main">
                    <div class="cycle-directory-mark" aria-hidden="true"><x-icon name="calendar-days" size="21" /></div>
                    <div class="cycle-directory-copy">
                        <p class="cycle-directory-career">{{ $cycle->career ?: 'Carrera pendiente' }}</p>
                        <h3>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h3>
                        <p class="cycle-directory-meta">
                            <span>Semestre {{ $cycle->semester ?: '—' }}</span>
                            <span>{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
                        </p>
                    </div>
                </div>

                <div class="cycle-directory-metrics" aria-label="Resumen del ciclo">
                    <div><strong>{{ $cycle->groups_count }}</strong><span>Grupos</span></div>
                    <div><strong>{{ $cycle->courses_count }}</strong><span>Cursos</span></div>
                </div>

                <div class="cycle-directory-actions">
                    <a class="btn" href="{{ route('cycles.tree', $cycle) }}"><x-icon name="git-branch" /> Ver árbol</a>
                    <a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}"><x-icon name="folder-open" /> Detalles</a>
                </div>
            </article>
        @empty
            <div class="empty cycle-directory-empty">
                <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="calendar-days" size="20" /></span>
                <strong>{{ request()->hasAny(['q', 'year']) ? 'No encontramos ciclos con esos filtros.' : 'Todavía no tienes ciclos académicos.' }}</strong>
                <span>{{ request()->hasAny(['q', 'year']) ? 'Prueba otra búsqueda o limpia los filtros.' : 'Importa tu distribución académica o crea el primer ciclo.' }}</span>
                <div class="cycle-directory-empty-actions">
                    <a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar distribución</a>
                    <button class="btn btn-secondary" type="button" data-modal-open="cycle-create-dialog"><x-icon name="plus" /> Crear ciclo</button>
                </div>
            </div>
        @endforelse
    </div>

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
                    <input class="field" id="cycle_code" data-uppercase name="cycle_code" value="{{ old('cycle_code', '10') }}" maxlength="20" required placeholder="Ej. 10 o 20">
                    <p class="help">Usa el código de tu programación académica.</p>
                    @error('cycle_code')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label for="career">Carrera predeterminada para grupos nuevos <span class="muted">*</span></label>
                    <input class="field" id="career" data-uppercase name="career" value="{{ old('career', auth()->user()->professor->career) }}" maxlength="255" required placeholder="Ej. Desarrollo de Software">
                    @error('career')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="semester">Semestre predeterminado para grupos nuevos <span class="muted">*</span></label>
                    <select class="field" id="semester" name="semester" required>
                        <option value="">Seleccionar semestre</option>
                        @foreach(['I', 'II', 'III', 'IV', 'V', 'VI'] as $semesterOption)
                            <option value="{{ $semesterOption }}" @selected(old('semester') === $semesterOption)>{{ $semesterOption }}</option>
                        @endforeach
                    </select>
                    @error('semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="help">Estos valores se copian al crear grupos. Luego puedes cambiar la carrera y el semestre de cada grupo por separado.</p>
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
                <input class="field" id="group_name" data-uppercase name="group_name" value="{{ old('group_name') }}" maxlength="100" required placeholder="Ej. IA 202">
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
@push('styles')
<style>
.cycle-directory-card{padding:clamp(14px,1.8vw,22px)}
.cycle-directory-list{display:grid}
.cycle-directory-row{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(145px,.5fr) auto;align-items:center;gap:20px;padding:17px 2px;border-bottom:1px solid var(--line)}
.cycle-directory-row:last-child{border-bottom:0}
.cycle-directory-main{display:flex;align-items:center;gap:14px;min-width:0}
.cycle-directory-mark{width:48px;height:48px;flex:none;display:grid;place-items:center;border:1px solid color-mix(in srgb,var(--accent) 22%,var(--line));border-radius:13px;background:var(--accent-soft);color:var(--accent)}
.cycle-directory-copy{min-width:0}
.cycle-directory-career{margin:0 0 4px;color:var(--muted);font-size:10px;font-weight:650;letter-spacing:.03em}
.cycle-directory-copy h3{margin:0;color:var(--ink);font-size:17px;font-weight:780;letter-spacing:-.025em;line-height:1.25}
.cycle-directory-meta{display:flex;flex-wrap:wrap;gap:5px 12px;margin:6px 0 0;color:var(--muted);font-size:10px}
.cycle-directory-metrics{display:flex;gap:20px;justify-content:flex-start}
.cycle-directory-metrics div{display:grid;gap:3px}
.cycle-directory-metrics strong{color:var(--ink);font-size:21px;font-weight:800;line-height:1;font-variant-numeric:tabular-nums}
.cycle-directory-metrics span{color:var(--muted);font-size:10px}
.cycle-directory-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap}
.cycle-directory-empty{padding:36px 16px}
.cycle-directory-empty-actions{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:15px}
@media(max-width:850px){
 .cycle-directory-row{grid-template-columns:minmax(0,1fr) auto;gap:12px}
 .cycle-directory-metrics{grid-column:1;grid-row:2;padding-left:62px}
 .cycle-directory-actions{grid-column:2;grid-row:1/3;flex-direction:column;align-items:stretch}
 .cycle-directory-actions .btn{width:100%}
}
@media(max-width:560px){
 .cycle-directory-row{grid-template-columns:minmax(0,1fr);gap:12px;padding:15px 0}
 .cycle-directory-main{align-items:flex-start}
 .cycle-directory-mark{width:42px;height:42px}
 .cycle-directory-copy h3{font-size:16px}
 .cycle-directory-metrics{grid-column:auto;grid-row:auto;padding-left:56px}
 .cycle-directory-actions{grid-column:auto;grid-row:auto;display:grid;grid-template-columns:1fr 1fr}
 .cycle-directory-actions .btn{min-height:40px}
 .cycle-directory-empty-actions{display:grid;grid-template-columns:1fr}
}
</style>
@endpush

@endsection
