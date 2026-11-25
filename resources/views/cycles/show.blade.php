@extends('layouts.app')
@section('title', $cycle->academic_year.' · Ciclo '.$cycle->cycle_code)
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.index') }}">Ciclos académicos</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span>
</div>
<a class="cycle-back" href="{{ route('cycles.index') }}"><x-icon name="arrow-left" size="15" /> Volver a todos los ciclos</a>

<div class="page-head cycle-detail-head">
    <div>
        <p class="eyebrow"><x-icon name="calendar-days" size="12" /> Ciclo académico</p>
        <h1>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h1>
        <div class="cycle-item-meta">
            <span><x-icon name="graduation-cap" size="12" /> Carrera inicial: {{ $cycle->career ?: 'Pendiente' }}</span>
            <span>Semestre inicial: {{ $cycle->semester ?: '—' }}</span>
            <span><x-icon name="calendar-range" size="12" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            <span class="badge badge-green">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
        </div>
    </div>
    <div class="actions cycle-detail-actions">
        <button class="btn btn-secondary" type="button" data-modal-open="cycle-edit-dialog"><x-icon name="edit-3" /> Editar ciclo</button>
        @if($groupCount > 0)
            <button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Agregar grupo</button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" role="alert">{{ session('error') }}</div>
@endif

<section class="card cycle-groups-card">
    <div class="card-head cycle-groups-heading">
        <h2>Grupos</h2>
        <span class="badge badge-blue">{{ $groupCount }} {{ $groupCount === 1 ? 'grupo' : 'grupos' }}</span>
    </div>

    @if($groups->isNotEmpty())
        <div class="cycle-group-list">
            @foreach($groups as $group)
                @php
                    $hasCourses = $group->assignments_count > 0;
                    $allCoursesHaveModalities = $hasCourses
                        && $group->assignments_with_modalities_count >= $group->assignments_count;
                    $hasModalities = $group->modalities_count > 0;
                    $allSchedulesConfigured = $hasModalities
                        && $group->configured_modalities_count >= $group->modalities_count;
                    $groupReady = $allCoursesHaveModalities && $allSchedulesConfigured;

                    $groupState = !$hasCourses
                        ? 'pending'
                        : ($groupReady ? 'complete' : 'partial');
                    $groupStateLabel = !$hasCourses
                        ? 'Sin cursos'
                        : ($groupReady ? 'Configurado' : 'En progreso');

                    $coursePercent = $hasCourses
                        ? (int) round(($group->assignments_with_modalities_count / $group->assignments_count) * 100)
                        : 0;
                    $schedulePercent = $hasModalities
                        ? (int) round(($group->configured_modalities_count / $group->modalities_count) * 100)
                        : 0;
                    $dayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
                    $groupDays = collect($group->workload_days ?? [])
                        ->map(fn ($day) => $dayNames[(int) $day] ?? null)
                        ->filter()
                        ->implode(', ');
                    $hasGroupSchedule = $groupDays !== ''
                        && filled($group->workload_start_time)
                        && filled($group->workload_end_time);
                    $groupScheduleLabel = $hasGroupSchedule
                        ? $groupDays.' · '.substr((string) $group->workload_start_time, 0, 5).'–'.substr((string) $group->workload_end_time, 0, 5)
                        : 'Horario pendiente';
                    $groupWorkloadPeriod = $group->workload_start_date && $group->workload_end_date
                        ? $group->workload_start_date->format('d/m/Y').' – '.$group->workload_end_date->format('d/m/Y')
                        : null;
                @endphp

                <details class="cycle-group" open>
                    <summary class="cycle-group-summary">
                        <span class="cycle-group-icon"><x-icon name="users" size="17" /></span>
                        <span class="cycle-group-title">
                            <strong>{{ $group->name }}</strong>
                            <small class="cycle-group-meta">
                                {{ $group->assignments_count }} {{ $group->assignments_count === 1 ? 'curso' : 'cursos' }}
                                <span class="cycle-group-schedule {{ $hasGroupSchedule ? 'is-configured' : 'is-pending' }}">
                                    <x-icon name="clock" size="11" /> {{ $groupScheduleLabel }}
                                    @if($groupWorkloadPeriod)<span class="cycle-group-schedule-period">· {{ $groupWorkloadPeriod }}</span>@endif
                                </span>
                            </small>
                        </span>
                        <span class="cycle-group-state is-{{ $groupState }}">{{ $groupStateLabel }}</span>
                        <x-icon name="chevron-right" class="cycle-group-chevron" size="17" />
                    </summary>

                    <div class="cycle-group-body">
                        <div class="cycle-group-progress">
                            <div class="cycle-group-progress-item">
                                <div class="cycle-group-progress-label">
                                    <span>Cursos con modalidad</span>
                                    <strong>{{ $group->assignments_with_modalities_count }} / {{ $group->assignments_count }}</strong>
                                </div>
                                <div class="cycle-group-progress-track" role="progressbar"
                                     aria-label="Cursos con modalidad en {{ $group->name }}"
                                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $coursePercent }}">
                                    <span style="width:{{ $coursePercent }}%"></span>
                                </div>
                            </div>

                            <div class="cycle-group-progress-item">
                                <div class="cycle-group-progress-label">
                                    <span>Horarios configurados</span>
                                    <strong>{{ $group->configured_modalities_count }} / {{ $group->modalities_count }}</strong>
                                </div>
                                <div class="cycle-group-progress-track" role="progressbar"
                                     aria-label="Horarios configurados en {{ $group->name }}"
                                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $schedulePercent }}">
                                    <span style="width:{{ $schedulePercent }}%"></span>
                                </div>
                            </div>
                        </div>

                        <div class="cycle-group-actions">
                            <a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}">
                                Abrir grupo <x-icon name="arrow-right" />
                            </a>

                            @if($groupCount > 1)
                                <form method="POST" action="{{ route('groups.destroy', $group) }}"
                                      data-confirm="Se eliminará este grupo, sus cursos dentro del grupo, modalidades, matrículas y sesiones. Los alumnos y cursos del catálogo que se utilicen en otros grupos se conservarán. Esta acción no se puede deshacer.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Eliminar grupo</button>
                                </form>
                            @else
                                <button class="btn btn-danger btn-sm" type="button" disabled
                                        title="Cada ciclo debe conservar al menos un grupo"
                                        aria-label="No se puede eliminar el único grupo del ciclo">
                                    <x-icon name="x" /> Eliminar grupo
                                </button>
                            @endif
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        @if($groups->hasPages())
            <x-pagination :paginator="$groups" />
        @endif
    @else
        <div class="empty cycle-groups-empty">
            <span class="stat-icon"><x-icon name="users" size="20" /></span>
            <strong>Este ciclo necesita un grupo.</strong>
            <span>Agrega el primer grupo para continuar con la carga académica.</span>
            <button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Crear primer grupo</button>
        </div>
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="cycle-edit-dialog" aria-labelledby="cycle-edit-title" @if(request('dialog') === 'edit-cycle') data-auto-open @endif>
    <div class="dialog-head">
        <div><h2 id="cycle-edit-title">Editar ciclo</h2><p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('cycles.update', $cycle) }}" data-loading-form="1">
        @csrf
        @method('PATCH')
        <div class="dialog-body">
            @if($errors->any() && request('dialog') === 'edit-cycle')
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="form-row">
                <div>
                    <label for="edit_academic_year">Año académico <span class="muted">*</span></label>
                    <input class="field" id="edit_academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', $cycle->academic_year) }}" required>
                    @error('academic_year')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="edit_cycle_code">Código del ciclo <span class="muted">*</span></label>
                    <input class="field" id="edit_cycle_code" name="cycle_code" maxlength="20" value="{{ old('cycle_code', $cycle->cycle_code) }}" required placeholder="Ej. 10 o 20">
                    @error('cycle_code')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label for="edit_cycle_career">Carrera predeterminada para grupos nuevos <span class="muted">*</span></label>
                    <input class="field" id="edit_cycle_career" name="career" maxlength="255" value="{{ old('career', $cycle->career) }}" required placeholder="Ej. Desarrollo de Software">
                    @error('career')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="edit_cycle_semester">Semestre predeterminado para grupos nuevos <span class="muted">*</span></label>
                    @php($selectedCycleSemester = strtoupper(trim(str_ireplace('semestre', '', (string) old('semester', $cycle->semester)))))
                    <select class="field" id="edit_cycle_semester" name="semester" required>
                        <option value="">Seleccionar semestre</option>
                        @foreach(['I', 'II', 'III', 'IV', 'V', 'VI'] as $semesterOption)
                            <option value="{{ $semesterOption }}" @selected($selectedCycleSemester === $semesterOption)>{{ $semesterOption }}</option>
                        @endforeach
                    </select>
                    @error('semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label for="edit_cycle_start">Fecha de inicio <span class="muted">*</span></label>
                    <input class="field" id="edit_cycle_start" type="date" name="start_date" value="{{ old('start_date', $cycle->start_date?->format('Y-m-d')) }}" required data-cycle-start>
                    @error('start_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="edit_cycle_end">Fecha de fin <span class="muted">*</span></label>
                    <input class="field" id="edit_cycle_end" type="date" name="end_date" value="{{ old('end_date', $cycle->end_date?->format('Y-m-d')) }}" min="{{ old('start_date', $cycle->start_date?->format('Y-m-d')) }}" required data-cycle-end>
                    @error('end_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="help mt-16"><x-icon name="info" size="12" /> Carrera y semestre se copian a los grupos nuevos. Cada grupo puede modificarlos por separado sin alterar a los demás.</p>
            <p class="help"><x-icon name="calendar-range" size="12" /> La fecha de fin debe ser igual o posterior al inicio.</p>
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close><x-icon name="x" /> Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="group-create-dialog" aria-labelledby="group-create-title"
        data-mandatory="{{ $groupCount === 0 ? 'true' : 'false' }}"
        @if($groupCount === 0 || request()->boolean('nuevo_grupo') || ($errors->any() && !request('dialog'))) data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="group-create-title">{{ $groupCount === 0 ? 'Crear primer grupo' : 'Agregar grupos' }}</h2>
            <p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p>
        </div>
        @if($groupCount > 0)
            <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
        @endif
    </div>
    <form method="POST" action="{{ route('groups.store', $cycle) }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any() && !request('dialog'))
                <div class="dialog-error" role="alert">
                    <strong>Revisa los nombres de los grupos:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if($groupCount === 0)
                <div class="modal-context">
                    <x-icon name="info" size="16" />
                    <span>Este ciclo necesita al menos un grupo. Registra el primero para continuar; luego podrás agregar cursos y configurar su carga horaria.</span>
                </div>
            @else
                <p class="card-description">Escribe el nombre de cada grupo. La carga horaria se configura después, dentro de cada grupo.</p>
            @endif
            <div class="group-count-control">
                <label for="group-count">¿Cuántos grupos vas a crear?</label>
                <input class="field" id="group-count" type="number" min="1" max="30" value="{{ max(1, count(old('groups', [])) ?: 1) }}">
                <p class="help">Puedes crear hasta 30 grupos de una sola vez.</p>
            </div>
            <div class="group-fields" id="group-fields"></div>
        </div>
        <div class="dialog-footer">
            @if($groupCount > 0)
                <button class="btn btn-secondary" type="button" data-modal-close><x-icon name="x" /> Cancelar</button>
            @endif
            <button class="btn" type="submit"><x-icon name="check" /> {{ $groupCount === 0 ? 'Crear primer grupo' : 'Guardar grupos' }}</button>
        </div>
    </form>
</dialog>

@push('styles')
<style>
.cycle-detail-head{align-items:flex-end}
.cycle-detail-actions{justify-content:flex-end}
.cycle-groups-card{padding:17px}
.cycle-groups-heading{margin-bottom:4px}
.cycle-groups-heading h2{margin:0}
.cycle-group-list{display:grid}
.cycle-group{border-bottom:1px solid var(--line)}
.cycle-group:last-child{border-bottom:0}
.cycle-group-summary{display:flex;align-items:center;gap:12px;min-height:72px;padding:12px 3px;list-style:none;cursor:pointer}
.cycle-group-summary::-webkit-details-marker{display:none}
.cycle-group-summary:focus-visible{outline-offset:2px;border-radius:7px}
.cycle-group-icon{display:grid;place-items:center;width:38px;height:38px;flex:0 0 38px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft);color:var(--accent)}
.cycle-group-title{display:flex;flex:1;min-width:0;flex-direction:column;gap:3px}
.cycle-group-title strong{color:var(--ink);font-size:13px;font-weight:700}
.cycle-group-title small{display:grid;gap:5px;color:var(--muted);font-size:10px}
.cycle-group-meta{line-height:1.5}
.cycle-group-schedule{display:inline-flex;align-items:center;gap:4px;flex-wrap:wrap}
.cycle-group-schedule.is-configured{color:var(--info)}
.cycle-group-schedule.is-pending{color:var(--warning)}
.cycle-group-schedule-period{color:var(--muted)}

.cycle-group-state{flex:none;padding:5px 9px;border:1px solid var(--line);border-radius:20px;font-size:10px;font-weight:700}
.cycle-group-state.is-pending{background:var(--surface-soft);color:var(--muted)}
.cycle-group-state.is-partial{border-color:#eadfc9;background:var(--warning-bg);color:var(--warning)}
.cycle-group-state.is-complete{border-color:#d5e5d8;background:var(--success-bg);color:var(--success)}
.cycle-group-chevron{flex:none;color:var(--muted);transition:transform .16s ease}
.cycle-group[open] .cycle-group-chevron{transform:rotate(90deg)}
.cycle-group-body{padding:3px 0 15px 50px}
.cycle-group-progress{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.cycle-group-progress-item{min-width:0}
.cycle-group-progress-label{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px;color:var(--muted);font-size:10px}
.cycle-group-progress-label strong{color:var(--ink);font-size:10px;font-variant-numeric:tabular-nums;white-space:nowrap}
.cycle-group-progress-track{height:7px;overflow:hidden;border-radius:99px;background:var(--line)}
.cycle-group-progress-track span{display:block;height:100%;border-radius:99px;background:var(--accent);transition:width .18s ease}
.cycle-group-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap;margin-top:15px}
.cycle-group-actions form{margin:0}
.cycle-groups-empty{padding:32px 14px}
.cycle-groups-empty .stat-icon{display:grid;place-items:center;width:44px;height:44px;margin:0 auto 10px;border-radius:10px;background:var(--accent-soft);color:var(--accent)}
.cycle-groups-empty .btn{margin-top:7px}
.group-create-item{padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface)}
.group-create-item+.group-create-item{margin-top:10px}
.group-count-control{display:grid;grid-template-columns:minmax(0,1fr) 110px;align-items:center;gap:10px}
.group-count-control label{margin:0}
.group-count-control .help{grid-column:1/-1;margin:0}
.group-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:16px}
.group-name-field{padding:11px;border:1px solid var(--line);border-radius:10px;background:var(--surface-soft)}
.group-name-field label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;color:var(--ink-soft)}
@media(max-width:760px){
    .cycle-detail-head{align-items:flex-start;flex-direction:column}
    .cycle-detail-actions{width:100%;justify-content:stretch}
    .cycle-detail-actions>*{flex:1}
    .cycle-detail-actions .btn{width:100%}
    .cycle-group-progress{grid-template-columns:1fr;gap:12px}
}
@media(max-width:650px){
    .cycle-group-summary{gap:9px}
    .cycle-group-icon{width:34px;height:34px;flex-basis:34px}
    .cycle-group-body{padding-left:0}
    .cycle-group-actions{justify-content:stretch}
    .cycle-group-actions>*{flex:1}
    .cycle-group-actions .btn{width:100%}
    .cycle-group-state{font-size:9px;padding:5px 7px}
}
@media(max-width:420px){
    .group-fields{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){
    .cycle-group-chevron,.cycle-group-progress-track span{transition:none}
}
html[data-theme="dark"] .cycle-group-state.is-partial{border-color:#5f5239}
html[data-theme="dark"] .cycle-group-state.is-complete{border-color:#365845}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const cycleStart = document.querySelector('#cycle-edit-dialog [data-cycle-start]');
    const cycleEnd = document.querySelector('#cycle-edit-dialog [data-cycle-end]');

    if (cycleStart && cycleEnd) {
        const syncCycleDates = () => {
            cycleEnd.min = cycleStart.value || '';
            cycleEnd.setCustomValidity(
                cycleStart.value && cycleEnd.value && cycleEnd.value < cycleStart.value
                    ? 'La fecha de fin debe ser igual o posterior al inicio.'
                    : ''
            );
        };

        cycleStart.addEventListener('change', syncCycleDates);
        cycleEnd.addEventListener('change', syncCycleDates);
        syncCycleDates();
    }

    const countInput = document.getElementById('group-count');
    const groupFields = document.getElementById('group-fields');

    if (countInput && groupFields) {
        const oldGroups = @json(array_values(old('groups', [])));
        const errorMessages = @json($errors->getMessages());
        const values = oldGroups.map(group => group || {});

        const render = () => {
            const count = Math.max(1, Math.min(30, Number.parseInt(countInput.value, 10) || 1));
            countInput.value = count;
            groupFields.replaceChildren();

            for (let index = 0; index < count; index++) {
                const wrapper = document.createElement('section');
                wrapper.className = 'group-create-item';

                const label = document.createElement('label');
                label.htmlFor = 'new-group-name-' + index;
                label.textContent = 'Nombre del grupo ' + (index + 1);

                const input = document.createElement('input');
                input.className = 'field';
                input.type = 'text';
                input.id = label.htmlFor;
                input.name = 'groups[' + index + '][name]';
                input.maxLength = 100;
                input.required = true;
                input.placeholder = 'Ej. IA 202';
                input.value = values[index]?.name || '';
                input.addEventListener('input', () => {
                    values[index] = {...(values[index] || {}), name: input.value};
                });

                wrapper.append(label, input);

                const messages = errorMessages['groups.' + index + '.name'] || [];
                messages.forEach(message => {
                    const error = document.createElement('small');
                    error.className = 'field-error';
                    error.textContent = message;
                    wrapper.appendChild(error);
                });

                groupFields.appendChild(wrapper);
            }
        };

        countInput.addEventListener('input', render);
        countInput.addEventListener('change', render);
        render();
    }

    const requiredGroupDialog = document.getElementById('group-create-dialog');
    if (requiredGroupDialog?.dataset.mandatory === 'true') {
        requiredGroupDialog.addEventListener('cancel', event => event.preventDefault());
    }
})();
</script>
@endpush
@endsection
