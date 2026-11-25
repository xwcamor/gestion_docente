@extends('layouts.app')
@section('title', 'Cargas laborales')
@section('content')
<div class="breadcrumbs">
    <span class="current">Árbol de carga laboral</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Programación docente</p>
        <h1>Árbol de carga laboral</h1>
        <p class="subhead">Organiza todo desde un solo lugar: ciclo, grupo, cursos, malla curricular y modalidades.</p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" type="button" data-modal-open="cycle-create-dialog"><x-icon name="plus" /> Crear ciclo manualmente</button>
        <a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar carga laboral</a>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div>
            <h2>Árbol de carga laboral</h2>
            <p class="card-description">Abre cada nivel para consultar sus datos. Los grupos, cursos y horarios se cargan al desplegarlos para evitar consultas pesadas.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('cycles.index') }}" class="filter-bar">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar ciclo, grupo, curso, código, NRC o bloque…" aria-label="Buscar en la carga laboral">
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
        <details class="workload-tree-cycle" data-tree-load="{{ route('workload-tree.groups', $cycle) }}">
            <summary class="workload-tree-summary workload-cycle-summary">
                <span class="tree-node-icon tree-cycle-icon"><x-icon name="calendar-days" size="17" /></span>
                <span class="tree-node-copy">
                    <strong>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</strong>
                    <small>{{ $cycle->career ?: 'Carrera pendiente' }} · Semestre {{ $cycle->semester ?: '—' }} · {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</small>
                </span>
                <span class="tree-node-count">{{ $cycle->groups_count }} grupos · {{ $cycle->courses_count }} cursos</span>
                <span class="tree-chevron"><x-icon name="chevron-right" size="15" /></span>
            </summary>

            <div class="workload-tree-children workload-cycle-children">
                <div class="workload-tree-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ route('cycles.show', $cycle) }}"><x-icon name="folder-open" /> Abrir ciclo</a>
                    <a class="btn btn-ghost btn-sm" href="{{ route('groups.create', $cycle) }}"><x-icon name="plus" /> Agregar grupo</a>
                    <form method="POST" action="{{ route('cycles.destroy', $cycle) }}" data-confirm="Se eliminará este ciclo con sus grupos, asignaciones y modalidades. Los cursos del catálogo y los alumnos reutilizados en otros grupos se conservarán. Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost btn-sm tree-danger-action" type="submit"><x-icon name="trash-2" /> Eliminar ciclo</button>
                    </form>
                </div>
                <div data-tree-content>
                    <div class="tree-loading-state">Despliega este ciclo para cargar sus grupos.</div>
                </div>
            </div>
        </details>
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
<script>
(() => {
    const bindTreeNodes = (scope) => {
        scope.querySelectorAll('[data-tree-load]').forEach((node) => {
            if (node.dataset.treeBound === 'true') return;
            node.dataset.treeBound = 'true';

            node.addEventListener('toggle', async () => {
                if (!node.open || node.dataset.treeLoaded === 'true' || node.dataset.treeLoading === 'true') return;

                const content = node.querySelector('[data-tree-content]');
                if (!content) return;

                node.dataset.treeLoading = 'true';
                content.innerHTML = '<div class="tree-loading-state" role="status">Cargando información…</div>';

                const url = new URL(node.dataset.treeLoad, window.location.href);
                const search = document.querySelector('[name="q"]');
                if (search && search.value.trim()) url.searchParams.set('q', search.value.trim());

                try {
                    const response = await fetch(url.toString(), {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (!response.ok || response.redirected) {
                        throw new Error('La solicitud no pudo completarse.');
                    }

                    content.innerHTML = await response.text();
                    node.dataset.treeLoaded = 'true';
                    bindTreeNodes(content);
                } catch (error) {
                    content.innerHTML = '<div class="tree-load-error" role="alert">No se pudo cargar este nivel. Cierra y vuelve a abrirlo para intentar nuevamente.</div>';
                    node.dataset.treeLoaded = 'false';
                } finally {
                    node.dataset.treeLoading = 'false';
                }
            });
        });
    };

    bindTreeNodes(document);
})();
</script>
@endpush
@push('styles')
<style>
.workload-tree-cycle{border:1px solid var(--line);border-radius:10px;background:var(--surface);overflow:hidden}
.workload-tree-cycle+.workload-tree-cycle{margin-top:9px}
.workload-tree-summary{display:flex;align-items:center;gap:10px;min-width:0;list-style:none;cursor:pointer}
.workload-tree-summary::-webkit-details-marker{display:none}
.workload-tree-summary:focus-visible{outline:2px solid var(--accent);outline-offset:-2px}
.workload-cycle-summary{min-height:66px;padding:12px 14px;background:var(--surface-soft)}
.tree-node-icon{display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border-radius:8px}
.tree-cycle-icon{background:var(--accent-soft);color:var(--accent)}
.tree-group-icon{background:var(--info-bg);color:var(--info)}
.tree-course-icon{background:var(--surface-soft);color:var(--muted)}
.tree-node-copy{display:grid;gap:3px;min-width:0;flex:1}
.tree-node-copy strong{color:var(--ink);font-size:12px;font-weight:740;line-height:1.35;overflow-wrap:anywhere}
.tree-node-copy small{color:var(--muted);font-size:10px;line-height:1.4;overflow-wrap:anywhere}
.tree-node-count{flex:none;color:var(--muted);font-size:10px;font-weight:650;white-space:nowrap}
.tree-chevron{display:grid;place-items:center;flex:none;color:var(--muted);transition:transform .14s ease}
.workload-tree-cycle[open]>.workload-tree-summary .tree-chevron,
.workload-tree-group[open]>.workload-tree-summary .tree-chevron,
.workload-tree-course[open]>.workload-tree-summary .tree-chevron{transform:rotate(90deg)}
.workload-tree-children{position:relative;margin-left:28px;padding:5px 12px 12px 18px;border-left:1px solid var(--line)}
.workload-cycle-children{margin-left:28px;padding-top:10px}
.workload-tree-group{margin-top:6px;border:1px solid var(--line);border-radius:8px;background:var(--surface);overflow:hidden}
.workload-group-summary{min-height:52px;padding:9px 11px;background:var(--surface)}
.tree-branch-mark{position:relative;flex:0 0 11px;width:11px;height:1px;background:var(--line-strong)}
.workload-tree-group>.workload-tree-summary .tree-branch-mark:before,
.workload-tree-course>.workload-tree-summary .tree-branch-mark:before,
.workload-tree-modality>.tree-branch-mark:before{content:"";position:absolute;left:0;top:0;width:1px;height:13px;background:var(--line-strong)}
.workload-group-children{margin-left:18px;padding:8px 10px 10px 14px}
.workload-tree-actions{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin:0 0 9px}
.workload-tree-actions form{margin:0}
.workload-tree-actions .btn{min-height:29px;padding:5px 8px;font-size:10px}
.tree-danger-action{color:var(--danger)}
.workload-tree-course{margin:6px 0;border:1px solid var(--line);border-radius:7px;background:var(--surface);overflow:hidden}
.workload-course-summary{min-height:47px;padding:8px 10px}
.tree-course-status{display:inline-flex;align-items:center;gap:4px;flex:none;padding:4px 6px;border-radius:5px;font-size:9px;font-weight:680;white-space:nowrap}
.tree-course-status.is-ready{background:var(--success-bg);color:var(--success)}
.tree-course-status.is-pending{background:var(--warning-bg);color:var(--warning)}
.workload-course-children{margin-left:18px;padding:8px 10px 10px 14px}
.tree-curriculum-note{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin:0 0 8px;padding:7px 9px;border:1px solid var(--line);border-radius:6px;background:var(--surface-soft);color:var(--success);font-size:10px}
.tree-curriculum-note strong{color:var(--ink)}
.tree-curriculum-note span{color:var(--muted)}
.workload-tree-modality{position:relative;display:flex;align-items:center;gap:9px;min-width:0;padding:9px 0;border-top:1px solid var(--line)}
.tree-modality-tag{display:grid;place-items:center;flex:0 0 38px;height:27px;border-radius:6px;font-size:10px;font-weight:800}
.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}
.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}
.tree-modality-copy{display:grid;gap:3px;flex:1;min-width:0}
.tree-modality-copy strong{color:var(--ink);font-size:10px;line-height:1.4;overflow-wrap:anywhere}
.tree-modality-copy small{color:var(--muted);font-size:9px;line-height:1.4;overflow-wrap:anywhere}
.tree-open-modality{flex:none}
.tree-empty-child{padding:9px 10px;color:var(--muted);font-size:10px;line-height:1.5}
.tree-loading-state,.tree-load-error{padding:11px 12px;color:var(--muted);font-size:10px;line-height:1.5}
.tree-load-error{color:var(--danger);background:var(--danger-bg);border-radius:7px}
.tree-empty-child a{display:inline-flex;align-items:center;gap:4px;margin-left:5px;color:var(--accent);font-weight:680}
@media(max-width:700px){
    .workload-cycle-summary{align-items:flex-start}
    .tree-node-count{display:none}
    .workload-tree-children{margin-left:14px;padding-left:10px;padding-right:7px}
    .workload-group-children,.workload-course-children{margin-left:8px;padding-left:8px}
    .workload-tree-actions .btn{flex:1 1 auto}
    .workload-tree-modality{align-items:flex-start;flex-wrap:wrap}
    .tree-modality-copy{flex:1 1 calc(100% - 100px)}
    .tree-open-modality{margin-left:47px}
}
@media(prefers-reduced-motion:reduce){.tree-chevron{transition:none}}
</style>
@endpush

@endsection
