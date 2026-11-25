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
            <h2>Árbol de carga laboral</h2>
            <p class="card-description">Despliega cada nivel para llegar al grupo, curso, malla, horario o documento que necesitas.</p>
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
        @php($cycleCourseCount = $cycle->groups->sum('assignments_count'))
        <details class="workload-tree-cycle">
            <summary class="workload-tree-summary workload-cycle-summary">
                <span class="tree-node-icon tree-cycle-icon"><x-icon name="calendar-days" size="17" /></span>
                <span class="tree-node-copy">
                    <strong>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</strong>
                    <small>{{ $cycle->career ?: 'Carrera pendiente' }} · Semestre {{ $cycle->semester ?: '—' }} · {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</small>
                </span>
                <span class="tree-node-count">{{ $cycle->groups_count }} grupos · {{ $cycleCourseCount }} cursos</span>
                <span class="tree-chevron"><x-icon name="chevron-right" size="15" /></span>
            </summary>

            <div class="workload-tree-children workload-cycle-children">
                <div class="workload-tree-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ route('cycles.show', $cycle) }}"><x-icon name="folder-open" /> Abrir ciclo</a>
                    <form method="POST" action="{{ route('cycles.destroy', $cycle) }}" data-confirm="Se eliminará este ciclo con sus grupos, asignaciones y modalidades. Los cursos del catálogo y los alumnos reutilizados en otros grupos se conservarán. Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost btn-sm tree-danger-action" type="submit"><x-icon name="trash-2" /> Eliminar ciclo</button>
                    </form>
                </div>

                @forelse($cycle->groups as $group)
                    <details class="workload-tree-group">
                        <summary class="workload-tree-summary workload-group-summary">
                            <span class="tree-branch-mark"></span>
                            <span class="tree-node-icon tree-group-icon"><x-icon name="users" size="15" /></span>
                            <span class="tree-node-copy">
                                <strong>{{ $group->name }}</strong>
                                <small>{{ $group->assignments_count }} cursos · {{ $group->students_count }} alumnos</small>
                            </span>
                            <span class="tree-node-count">{{ $group->assignments_count }} cursos</span>
                            <span class="tree-chevron"><x-icon name="chevron-right" size="14" /></span>
                        </summary>

                        <div class="workload-tree-children workload-group-children">
                            <div class="workload-tree-actions">
                                <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}"><x-icon name="folder-open" /> Abrir grupo</a>
                                <a class="btn btn-ghost btn-sm" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a>
                                <a class="btn btn-ghost btn-sm" href="{{ route('groups.calendar', $group) }}"><x-icon name="calendar-days" /> Calendario</a>
                                <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}#group-roster"><x-icon name="users" /> Alumnos</a>
                                @if($group->students_count > 0)
                                    <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}?talks=1#group-talks"><x-icon name="message-circle" /> Charla 5 min</a>
                                @endif
                            </div>

                            @forelse($group->assignments as $assignment)
                                @php($courseModalities = $assignment->modalities)
                                @php($curriculum = $assignment->curriculum)
                                <details class="workload-tree-course">
                                    <summary class="workload-tree-summary workload-course-summary">
                                        <span class="tree-branch-mark"></span>
                                        <span class="tree-node-icon tree-course-icon"><x-icon name="book-open" size="14" /></span>
                                        <span class="tree-node-copy">
                                            <strong>{{ $assignment->course->name }}</strong>
                                            <small>{{ $assignment->course->code ?: 'Sin código' }} · {{ $courseModalities->count() }} modalidades</small>
                                        </span>
                                        <span class="tree-course-status {{ $curriculum && $curriculum->is_active ? 'is-ready' : 'is-pending' }}">
                                            <x-icon name="{{ $curriculum && $curriculum->is_active ? 'book-check' : 'book-x' }}" size="12" />
                                            {{ $curriculum && $curriculum->is_active ? 'Malla activa' : 'Malla pendiente' }}
                                        </span>
                                        <span class="tree-chevron"><x-icon name="chevron-right" size="13" /></span>
                                    </summary>

                                    <div class="workload-tree-children workload-course-children">
                                        <div class="workload-tree-actions">
                                            <a class="btn btn-ghost btn-sm" href="{{ route('assignments.quick.edit', [$group, $assignment]) }}"><x-icon name="pencil" /> Editar curso y horario</a>
                                            <a class="btn btn-ghost btn-sm" href="{{ route('curricula.show', $assignment) }}"><x-icon name="file-up" /> {{ $curriculum && $curriculum->is_active ? 'Actualizar malla' : 'Subir malla PDF' }}</a>
                                        </div>

                                        @if($curriculum && $curriculum->is_active)
                                            <div class="tree-curriculum-note">
                                                <x-icon name="book-check" size="13" />
                                                <strong>{{ $curriculum->course_name ?: $assignment->course->name }}</strong>
                                                <span>{{ $curriculum->weeks->count() }} semanas · Versión {{ $curriculum->version }}</span>
                                            </div>
                                        @endif

                                        @forelse($courseModalities as $modality)
                                            @php($classSchedules = $modality->schedules->where('block_type', 'CLASS'))
                                            @php($daysLabel = $classSchedules->pluck('day_of_week')->unique()->sort()->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? '')->filter()->implode(' · '))
                                            @php($scheduleLabel = $classSchedules->map(fn ($item) => substr((string) $item->start_time, 0, 5).'–'.substr((string) $item->end_time, 0, 5))->unique()->implode(', '))
                                            @php($modalityReady = $modality->start_date && $modality->end_date && $classSchedules->isNotEmpty())
                                            <div class="workload-tree-modality">
                                                <span class="tree-branch-mark"></span>
                                                <span class="tree-modality-tag {{ $modality->modality === 'TEC' ? 'is-tec' : 'is-tall' }}">{{ $modality->modality }}</span>
                                                <div class="tree-modality-copy">
                                                    <strong>{{ $modality->modality === 'TEC' ? 'Teoría' : 'Taller / práctica' }} · NRC {{ $modality->nrc ?: 'pendiente' }}</strong>
                                                    <small>{{ $modality->start_date?->format('d/m/Y') ?: 'Sin inicio' }} – {{ $modality->end_date?->format('d/m/Y') ?: 'Sin fin' }}</small>
                                                    <small>
                                                        @if($classSchedules->isNotEmpty())
                                                            {{ $daysLabel }} · {{ $scheduleLabel }} · {{ number_format((float) $modality->total_hours, 2) }} h/sem.
                                                        @else
                                                            Horario pendiente
                                                        @endif
                                                        @if($modality->block_code) · Bloque {{ $modality->block_code }}@endif
                                                        @if($modality->block_reference) · Ref. {{ $modality->block_reference }}@endif
                                                    </small>
                                                    @if($modality->documents->isNotEmpty())
                                                        <small>{{ $modality->documents->count() }} documentos generados</small>
                                                    @endif
                                                </div>
                                                <span class="tree-course-status {{ $modalityReady ? 'is-ready' : 'is-pending' }}">{{ $modalityReady ? 'Configurada' : 'Pendiente' }}</span>
                                                <a class="btn btn-secondary btn-sm tree-open-modality" href="{{ route('modalities.show', $modality) }}"><x-icon name="arrow-up-right" /> Abrir</a>
                                            </div>
                                        @empty
                                            <div class="tree-empty-child">Aún no hay modalidades. Edita el curso para agregar TEC o TALL.</div>
                                        @endforelse
                                    </div>
                                </details>
                            @empty
                                <div class="tree-empty-child">
                                    Este grupo todavía no tiene cursos.
                                    <a href="{{ route('assignments.quick', $group) }}">Agregar el primer curso <x-icon name="arrow-right" size="12" /></a>
                                </div>
                            @endforelse
                        </div>
                    </details>
                @empty
                    <div class="tree-empty-child">Este ciclo todavía no tiene grupos. <a href="{{ route('cycles.show', $cycle) }}">Abrir ciclo para crear el primer grupo <x-icon name="arrow-right" size="12" /></a></div>
                @endforelse
            </div>
        </details>
    @empty
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
