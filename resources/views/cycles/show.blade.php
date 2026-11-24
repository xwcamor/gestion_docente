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
            <span><x-icon name="calendar-range" size="12" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            <span class="badge badge-green">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
        </div>
    </div>
    <div class="actions cycle-detail-actions">
        <button class="btn btn-secondary" type="button" data-modal-open="cycle-edit-dialog"><x-icon name="file-text" /> Editar ciclo</button>
        <button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Agregar grupo</button>
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
                @endphp

                <details class="cycle-group">
                    <summary class="cycle-group-summary">
                        <span class="cycle-group-icon"><x-icon name="users" size="17" /></span>
                        <span class="cycle-group-title">
                            <strong>{{ $group->name }}</strong>
                            <small>{{ $group->assignments_count }} {{ $group->assignments_count === 1 ? 'curso' : 'cursos' }}</small>
                        </span>
                        <span class="cycle-group-state is-{{ $groupState }}">{{ $groupStateLabel }}</span>
                        <x-icon name="chevron-down" class="cycle-group-chevron" size="17" />
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
                            <button class="btn btn-ghost btn-sm"
                                    type="button"
                                    data-edit-group
                                    data-group-id="{{ $group->id }}"
                                    data-update-url="{{ route('groups.update', $group) }}"
                                    data-name="{{ $group->name }}"
                                    data-assigned-at="{{ $group->assigned_at?->format('Y-m-d') }}">
                                <x-icon name="file-text" /> Editar
                            </button>
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
                <div><label for="edit_academic_year">Año académico</label><input class="field" id="edit_academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', $cycle->academic_year) }}" required></div>
                <div><label for="edit_cycle_code">Código del ciclo</label><input class="field" id="edit_cycle_code" name="cycle_code" maxlength="20" value="{{ old('cycle_code', $cycle->cycle_code) }}" required></div>
            </div>
            <div class="form-row">
                <div><label for="edit_cycle_start">Inicio del rango de carga</label><input class="field" id="edit_cycle_start" type="date" name="start_date" value="{{ old('start_date', $cycle->start_date?->format('Y-m-d')) }}" required></div>
                <div><label for="edit_cycle_end">Fin del rango de carga</label><input class="field" id="edit_cycle_end" type="date" name="end_date" value="{{ old('end_date', $cycle->end_date?->format('Y-m-d')) }}" required></div>
            </div>
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="group-create-dialog" aria-labelledby="group-create-title" @if(request()->boolean('nuevo_grupo') || ($errors->any() && !request('dialog'))) data-auto-open @endif>
    <div class="dialog-head">
        <div><h2 id="group-create-title">Agregar grupo</h2><p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('groups.store', $cycle) }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any() && !request('dialog'))
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="group-count-control">
                <label for="group-count">¿Cuántos grupos vas a crear?</label>
                <input class="field" id="group-count" type="number" min="1" max="30" value="{{ max(1, count(old('groups', [])) ?: 1) }}">
                <p class="help">Indica la cantidad y escribe el nombre de cada grupo. Puedes crear hasta 30 de una sola vez.</p>
            </div>
            @if($errors->has('groups') || $errors->has('groups.*.name'))
                <div class="dialog-error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            <div class="group-fields" id="group-fields"></div>
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar grupo</button></div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="group-edit-dialog" aria-labelledby="group-edit-title">
    <div class="dialog-head">
        <div><h2 id="group-edit-title">Editar grupo</h2><p>Actualiza el nombre o la fecha de asignación.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="#" data-loading-form="1" id="group-edit-form">
        @csrf
        @method('PATCH')
        <div class="dialog-body">
            @if($errors->any() && request('dialog') === 'edit-group')
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <label for="edit_group_name">Nombre del grupo</label>
            <input type="hidden" name="page" value="{{ request('page', 1) }}">
            <input class="field" id="edit_group_name" name="name" required maxlength="100">
            <label for="edit_group_assigned">Fecha de asignación <span class="muted">(opcional)</span></label>
            <input class="field" id="edit_group_assigned" type="date" name="assigned_at">
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
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
.cycle-group-title small{color:var(--muted);font-size:10px}
.cycle-group-state{flex:none;padding:5px 9px;border:1px solid var(--line);border-radius:20px;font-size:10px;font-weight:700}
.cycle-group-state.is-pending{background:var(--surface-soft);color:var(--muted)}
.cycle-group-state.is-partial{border-color:#eadfc9;background:var(--warning-bg);color:var(--warning)}
.cycle-group-state.is-complete{border-color:#d5e5d8;background:var(--success-bg);color:var(--success)}
.cycle-group-chevron{flex:none;color:var(--muted);transition:transform .16s ease}
.cycle-group[open] .cycle-group-chevron{transform:rotate(180deg)}
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
.group-workload-inline{margin-top:10px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft)}
.group-workload-inline>summary{padding:9px 11px;color:var(--ink-soft);font-size:11px;font-weight:750;cursor:pointer}
.group-workload-inline[open]>summary{border-bottom:1px solid var(--line)}
.group-workload-inline>.help{margin:9px 11px 0}
.group-workload-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding:10px 11px}
.group-workload-fields label,.group-workload-days>strong{display:block;margin-bottom:5px;color:var(--ink-soft);font-size:10px;font-weight:700}
.group-workload-fields .field{min-width:0}
.group-workload-days{padding:0 11px 11px}
.group-workload-day-options{display:flex;flex-wrap:wrap;gap:6px}
.group-workload-day-options .weekday{padding:5px 8px}
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
    .group-workload-fields{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cycle-group-state{font-size:9px;padding:5px 7px}
}
@media(max-width:420px){
    .group-workload-fields,.group-fields{grid-template-columns:1fr}
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
    const countInput = document.getElementById('group-count');
    const groupFields = document.getElementById('group-fields');
    if (countInput && groupFields) {
        const oldGroups = @json(array_values(old('groups', [])));
        const cycleStart = @json($cycle->start_date?->format('Y-m-d'));
        const cycleEnd = @json($cycle->end_date?->format('Y-m-d'));
        const values = oldGroups.map(group => group || {});
        const weekdays = [
            [1, 'Lun'], [2, 'Mar'], [3, 'Mié'], [4, 'Jue'],
            [5, 'Vie'], [6, 'Sáb'], [7, 'Dom']
        ];

        const makeField = (labelText, type, name, value, options = {}) => {
            const wrapper = document.createElement('div');
            wrapper.className = options.className || '';

            const label = document.createElement('label');
            label.textContent = labelText;
            const id = name.replace(/[^a-zA-Z0-9]+/g, '-') + '-field';
            label.htmlFor = id;

            let field;
            if (type === 'select') {
                field = document.createElement('select');
                field.className = 'field';
                (options.choices || []).forEach(choice => {
                    const option = document.createElement('option');
                    option.value = choice.value;
                    option.textContent = choice.label;
                    field.appendChild(option);
                });
                field.value = value ?? '';
            } else {
                field = document.createElement('input');
                field.className = 'field';
                field.type = type;
                field.value = value ?? '';
                if (type === 'number') {
                    field.min = '0';
                    field.max = '9999';
                    field.step = '.25';
                }
            }
            field.id = id;
            field.name = name;
            if (type === 'time') field.step = '900';
            const match = name.match(/^groups\[(\d+)\]\[([^\]]+)\]/);
            if (match) {
                const groupIndex = Number(match[1]);
                const fieldName = match[2];
                const syncValue = () => {
                    values[groupIndex] = {...(values[groupIndex] || {}), [fieldName]: field.value};
                };
                field.addEventListener('input', syncValue);
                field.addEventListener('change', syncValue);
            }
            wrapper.append(label, field);
            return wrapper;
        };

        const render = () => {
            const count = Math.max(1, Math.min(30, Number.parseInt(countInput.value, 10) || 1));
            countInput.value = count;
            groupFields.replaceChildren();

            for (let index = 0; index < count; index++) {
                const data = values[index] || {};
                const prefix = 'groups[' + index + ']';
                const wrapper = document.createElement('section');
                wrapper.className = 'group-create-item';

                const nameField = makeField(
                    'Nombre del grupo ' + (index + 1),
                    'text',
                    prefix + '[name]',
                    data.name || ''
                );
                const nameInput = nameField.querySelector('input');
                nameInput.maxLength = 100;
                nameInput.required = true;
                nameInput.placeholder = 'Ej. IA 202';
                nameInput.addEventListener('input', () => { values[index] = {...(values[index] || {}), name: nameInput.value}; });

                const details = document.createElement('details');
                details.className = 'group-workload-inline';
                const summary = document.createElement('summary');
                summary.textContent = 'Configurar carga horaria (opcional)';
                details.appendChild(summary);

                const help = document.createElement('p');
                help.className = 'help';
                help.textContent = 'Registra la asignación del supervisor. Las horas realmente dictadas se calcularán desde los horarios de cada curso.';
                details.appendChild(help);

                const grid = document.createElement('div');
                grid.className = 'group-workload-fields';
                const configFields = [
                    makeField('Tipo de carga', 'select', prefix + '[workload_type]', data.workload_type || '', {
                        className: 'workload-type-field',
                        choices: [
                            {value: '', label: 'Seleccionar tipo'},
                            {value: 'PARTIAL', label: 'Parcial'},
                            {value: 'FULL', label: 'Completa'}
                        ]
                    }),
                    makeField('Fecha de inicio', 'date', prefix + '[workload_start_date]', data.workload_start_date || cycleStart),
                    makeField('Fecha de término', 'date', prefix + '[workload_end_date]', data.workload_end_date || cycleEnd),
                    makeField('Hora inicial', 'time', prefix + '[workload_start_time]', data.workload_start_time || ''),
                    makeField('Hora final', 'time', prefix + '[workload_end_time]', data.workload_end_time || ''),
                    makeField('Horas asignadas · Teoría', 'number', prefix + '[supervisor_theory_hours]', data.supervisor_theory_hours ?? ''),
                    makeField('Horas asignadas · Taller', 'number', prefix + '[supervisor_lab_hours]', data.supervisor_lab_hours ?? '')
                ];
                configFields.forEach(field => grid.appendChild(field));
                details.appendChild(grid);

                const daysBlock = document.createElement('div');
                daysBlock.className = 'group-workload-days';
                const daysTitle = document.createElement('strong');
                daysTitle.textContent = 'Días asignados';
                daysBlock.appendChild(daysTitle);
                const daysOptions = document.createElement('div');
                daysOptions.className = 'group-workload-day-options';
                const selectedDays = new Set((data.workload_days || []).map(String));
                weekdays.forEach(([day, labelText]) => {
                    const label = document.createElement('label');
                    label.className = 'weekday';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = prefix + '[workload_days][]';
                    checkbox.value = String(day);
                    checkbox.checked = selectedDays.has(String(day));
                    checkbox.addEventListener('change', () => {
                        values[index] = {
                            ...(values[index] || {}),
                            workload_days: [...daysOptions.querySelectorAll('input:checked')].map(input => input.value)
                        };
                    });
                    label.append(checkbox, document.createTextNode(' ' + labelText));
                    daysOptions.appendChild(label);
                });
                daysBlock.appendChild(daysOptions);
                details.appendChild(daysBlock);

                wrapper.append(nameField, details);
                groupFields.appendChild(wrapper);
            }
        };

        countInput.addEventListener('input', render);
        countInput.addEventListener('change', render);
        render();
    }
})();
</script>
<script>
(() => {
    const dialog = document.getElementById('group-edit-dialog');
    const form = document.getElementById('group-edit-form');
    const name = document.getElementById('edit_group_name');
    const assigned = document.getElementById('edit_group_assigned');
    if (!dialog || !form || !name || !assigned) return;

    const prepare = trigger => {
        form.action = trigger.dataset.updateUrl;
        name.value = trigger.dataset.name || '';
        assigned.value = trigger.dataset.assignedAt || '';
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-edit-group]');
        if (!trigger) return;
        prepare(trigger);
        dialog.showModal();
        name.focus();
    });

    @if(request('dialog') === 'edit-group')
        const previousTrigger = document.querySelector('[data-edit-group][data-group-id="{{ (int) request('group_id') }}"]');
        if (previousTrigger) {
            prepare(previousTrigger);
            name.value = @json(old('name', ''));
            assigned.value = @json(old('assigned_at', ''));
            dialog.showModal();
            name.focus();
        }
    @endif
})();
</script>
@endpush
@endsection
