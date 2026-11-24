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

<div class="page-head">
    <div>
        <p class="eyebrow"><x-icon name="calendar-days" size="12" /> Ciclo académico</p>
        <h1>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h1>
        <div class="cycle-item-meta">
            <span><x-icon name="calendar-range" size="12" /> Rango de carga: {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            <span class="badge badge-green">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
            <button class="inline-edit" type="button" data-modal-open="cycle-edit-dialog"><x-icon name="file-text" size="13" /> Editar ciclo</button>
        </div>
    </div>
    <div class="actions">
        <form method="POST" action="{{ route('documents.distribution', $cycle) }}">
            @csrf
            <button class="btn btn-secondary" type="submit"><x-icon name="file-text" /> Exportar carga laboral</button>
        </form>
        <button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Agregar grupo</button>
        <form method="POST" action="{{ route('cycles.destroy', $cycle) }}" data-confirm="Se eliminará este ciclo y toda su planificación asociada. Los cursos del catálogo general y los alumnos reutilizados en otros grupos se conservarán. Esta acción no se puede deshacer.">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit"><x-icon name="x" /> Eliminar ciclo</button>
        </form>
    </div>
</div>


<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:17px">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Grupos</span><span class="stat-icon"><x-icon name="users" /></span></div>
        <div class="stat">{{ $groupCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Cursos</span><span class="stat-icon"><x-icon name="book-open" /></span></div>
        <div class="stat">{{ $courseCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Modalidades</span><span class="stat-icon"><x-icon name="layers" /></span></div>
        <div class="stat">{{ $modalityCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Horas presenciales / semana</span><span class="stat-icon"><x-icon name="clock" /></span></div>
        <div class="stat">{{ number_format($declaredWeeklyHours, 1) }} h</div>
    </div>
</div>

@if($loadGridRows)
<section class="card cycle-weekly-load" id="weekly-load">
    <div class="card-head">
        <div>
            <h2>Distribución semanal de carga horaria</h2>
            <p class="card-description">Vista consolidada del ciclo en intervalos de 15 minutos. Solo incluye bloques presenciales de teoría y laboratorio.</p>
        </div>
        <span class="weekly-hours-total">{{ number_format($declaredWeeklyHours, 2) }} h/semana</span>
    </div>
    @if($loadConflictCount > 0)
        <div class="alert alert-error load-conflict-alert"><x-icon name="alert-triangle" /> Hay bloques que coinciden en el mismo día y horario. Revisa las modalidades marcadas en rojo.</div>
    @endif
    <div class="table-wrap weekly-timetable-wrap">
        <table class="weekly-timetable">
            <thead>
                <tr>
                    <th>Inicio</th>
                    <th>Fin</th>
                    @foreach($loadWeekdays as $dayLabel)
                        <th>{{ $dayLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($loadGridRows as $gridRow)
                    <tr>
                        <th>{{ $gridRow['start'] }}</th>
                        <th>{{ $gridRow['end'] }}</th>
                        @foreach($loadWeekdays as $day => $dayLabel)
                            @php $cell = $gridRow['cells'][$day]; @endphp
                            <td class="{{ !$cell['active'] ? 'is-empty' : ($cell['conflict'] ? 'is-conflict' : (($cell['type'] ?? 'THEORY') === 'LAB' ? 'is-lab' : 'is-theory')) }}">
                                @if($cell['show'])
                                    @foreach($cell['blocks'] as $block)
                                        <div class="weekly-timetable-block">
                                            <strong>{{ $block['course'] }}</strong>
                                            <small>{{ $block['instruction_type'] === 'LAB' ? 'LAB' : 'TEORÍA' }} · {{ $block['room'] }}</small>
                                        </div>
                                    @endforeach
                                @elseif($cell['conflict'])
                                    <span class="conflict-label">HORARIO CRUZADO</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="weekly-load-legend">
        <span><i class="legend-theory"></i> Teoría</span>
        <span><i class="legend-lab"></i> Laboratorio</span>
        <span><i class="legend-conflict"></i> Cruce de horarios</span>
    </div>
</section>
@elseif($modalityCount === 0)
<section class="card">
    <div class="empty"><strong>Aún no hay horarios para mostrar.</strong><span>Agrega cursos y registra sus bloques de clase para construir la carga semanal.</span></div>
</section>
@endif

<section class="card cycle-progress">
    <div class="cycle-progress-heading">
        <div><p class="eyebrow">Seguimiento del periodo</p><h2>Avance de configuración</h2></div>
        <span class="muted small">Según cursos y modalidades registrados</span>
    </div>
    <div class="cycle-progress-grid">
        <div>
            <div class="cycle-progress-label"><span>Mallas curriculares vinculadas</span><strong>{{ $curriculumAssignedCount }} / {{ $courseCount }}</strong></div>
            <div class="cycle-progress-track" role="progressbar" aria-label="Cursos con malla vinculada" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $curriculumPercent }}"><span style="width:{{ $curriculumPercent }}%"></span></div>
            <small>{{ $curriculumPercent }}% de cursos</small>
        </div>
        <div>
            <div class="cycle-progress-label"><span>Horarios configurados</span><strong>{{ $configuredModalityCount }} / {{ $modalityCount }}</strong></div>
            <div class="cycle-progress-track" role="progressbar" aria-label="Modalidades con horario configurado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $schedulePercent }}"><span style="width:{{ $schedulePercent }}%"></span></div>
            <small>{{ $schedulePercent }}% de modalidades con fechas y horario de clase</small>
        </div>
    </div>
</section>



<section class="card">
    <div class="card-head">
        <div><h2>Grupos</h2></div>
        <span class="badge badge-blue">{{ $groupCount }} {{ $groupCount === 1 ? 'grupo' : 'grupos' }}</span>
    </div>
    @forelse($groups as $group)
        @php
            $groupWorkloadHasDetails = filled($group->workload_type)
                || !empty($group->workload_days)
                || filled($group->workload_start_time)
                || filled($group->workload_end_time)
                || $group->supervisor_theory_hours !== null
                || $group->supervisor_lab_hours !== null;
            $groupWorkloadComplete = in_array($group->workload_type, ['PARTIAL', 'FULL'], true)
                && $group->workload_start_date !== null
                && $group->workload_end_date !== null
                && !empty($group->workload_days)
                && filled($group->workload_start_time)
                && filled($group->workload_end_time)
                && $group->supervisor_theory_hours !== null
                && $group->supervisor_lab_hours !== null;
            $groupWorkloadStatus = $groupWorkloadComplete ? 'complete' : ($groupWorkloadHasDetails ? 'partial' : 'pending');
        @endphp
        <article class="cycle-item">
            <div class="cycle-item-main">
                <span class="cycle-item-icon"><x-icon name="users" size="18" /></span>
                <div>
                    <h3 class="cycle-item-title">{{ $group->name }}</h3>
                    <div class="cycle-item-meta">
                        <span><x-icon name="clock" size="12" /> {{ $group->assigned_at?->format('d/m/Y') ?? 'Sin fecha de asignación' }}</span>
                        <span>·</span>
                        <span><x-icon name="book-open" size="12" /> {{ $group->assignments_count }} {{ $group->assignments_count === 1 ? 'curso' : 'cursos' }}</span>
                        <span>·</span>
                        <span><x-icon name="users" size="12" /> {{ $group->students_count }} alumnos</span>
                    </div>
                    <div class="cycle-group-workload">
                        <x-section-status :status="$groupWorkloadStatus" />
                        @if($groupWorkloadHasDetails)
                            <span>{{ $group->workload_type === 'FULL' ? 'Completa' : 'Parcial' }}</span>
                            <span>Supervisor: {{ number_format((float) $group->supervisor_theory_hours, 2) }} h teoría · {{ number_format((float) $group->supervisor_lab_hours, 2) }} h taller</span>
                        @else
                            <span>Falta registrar la carga asignada</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="cycle-item-actions">
                <button
                    class="btn btn-ghost btn-sm"
                    type="button"
                    data-edit-group
                    data-group-id="{{ $group->id }}"
                    data-update-url="{{ route('groups.update', $group) }}"
                    data-name="{{ $group->name }}"
                    data-assigned-at="{{ $group->assigned_at?->format('Y-m-d') }}"
                ><x-icon name="file-text" /> Editar</button>
                <a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}">Abrir grupo <x-icon name="arrow-right" /></a>
                <form method="POST" action="{{ route('groups.destroy', $group) }}" data-confirm="Se eliminará este grupo, sus cursos dentro del grupo, modalidades, matrículas y sesiones. Los alumnos y cursos del catálogo que se utilicen en otros grupos se conservarán. Esta acción no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Eliminar grupo</button>
                </form>
            </div>
        </article>
    @empty
        <div class="empty">
            <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="users" size="20" /></span>
            <strong>Este ciclo todavía no tiene grupos.</strong>
            <span>Agrega el primer grupo para registrar después sus cursos.</span>
            <div class="mt-16"><button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Crear grupo</button></div>
        </div>
    @endforelse
    @if($groups->hasPages())
        <x-pagination :paginator="$groups" />
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
.group-create-item{padding:12px;border:1px solid #e5e9e5;border-radius:9px;background:#fff}
.group-create-item+.group-create-item{margin-top:10px}
.group-workload-inline{margin-top:10px;border:1px solid #e2e9e6;border-radius:8px;background:#f8fbf9}
.group-workload-inline>summary{padding:9px 11px;color:#426052;font-size:11px;font-weight:750;cursor:pointer}
.group-workload-inline[open]>summary{border-bottom:1px solid #e2e9e6}
.group-workload-inline>.help{margin:9px 11px 0}
.group-workload-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding:10px 11px}
.group-workload-fields label,.group-workload-days>strong{display:block;margin-bottom:5px;color:#52625a;font-size:10px;font-weight:700}
.group-workload-fields .field{min-width:0}
.group-workload-days{padding:0 11px 11px}
.group-workload-day-options{display:flex;flex-wrap:wrap;gap:6px}
.group-workload-day-options .weekday{padding:5px 8px}
@media(max-width:650px){.group-workload-fields{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:420px){.group-workload-fields{grid-template-columns:1fr}}
</style>
<style>
.cycle-progress{margin-bottom:15px}
.cycle-progress-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
.cycle-progress-heading h2{margin:2px 0 0;font-size:17px}
.cycle-progress-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.cycle-progress-label{display:flex;justify-content:space-between;gap:8px;margin-bottom:7px;font-size:12px;color:#4d626e}
.cycle-progress-label strong{color:#274d61;white-space:nowrap}
.cycle-progress-track{height:8px;overflow:hidden;border-radius:99px;background:#e8eef1}
.cycle-progress-track span{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#3a8cab,#47a88e)}
.cycle-progress-grid small{display:block;margin-top:6px;color:#76868e;font-size:10px}
@media(max-width:650px){.cycle-progress-grid{grid-template-columns:1fr}.cycle-progress-heading{align-items:flex-start;flex-direction:column}}

.cycle-weekly-load{padding:16px}
.weekly-hours-total{display:inline-flex;align-items:center;padding:7px 10px;border:1px solid #c6dce5;border-radius:7px;background:#f4fafc;color:#23657d;font-size:12px;font-weight:750;white-space:nowrap}
.weekly-timetable-wrap{max-height:620px;overflow:auto;border:1px solid #dfe6e1;border-radius:7px}
.weekly-timetable{min-width:900px;table-layout:fixed;border-collapse:separate;border-spacing:0;font-size:10px}
.weekly-timetable th,.weekly-timetable td{padding:0;border-right:1px solid #e1e7e2;border-bottom:1px solid #e1e7e2;text-align:center}
.weekly-timetable thead th{position:sticky;top:0;z-index:2;height:32px;background:#e8eef0;color:#405d68;font-size:10px}
.weekly-timetable tbody th{width:58px;height:26px;background:#f7f9f7;color:#66756c;font-size:9px;font-weight:600}
.weekly-timetable td{height:26px;background:#fff}
.weekly-timetable td.is-theory{background:#edf5fa;color:#285e77}
.weekly-timetable td.is-lab{background:#edf6ef;color:#326747}
.weekly-timetable td.is-conflict{background:#fff0ef;color:#a53832}
.weekly-timetable td.is-empty{background:#fff}
.weekly-timetable-block{display:grid;gap:1px;padding:3px;line-height:1.2;overflow-wrap:anywhere}
.weekly-timetable-block strong{font-size:9px;font-weight:800}
.weekly-timetable-block small{font-size:8px;font-weight:650}
.conflict-label{font-size:8px;font-weight:800}
.load-conflict-alert{margin-bottom:12px}
.weekly-load-legend{display:flex;gap:15px;flex-wrap:wrap;margin-top:10px;color:#748179;font-size:9px}
.weekly-load-legend span{display:inline-flex;align-items:center;gap:5px}
.weekly-load-legend i{width:9px;height:9px;border-radius:2px;display:inline-block}
.legend-theory{background:#cfe2eb}.legend-lab{background:#cfe4d5}.legend-conflict{background:#f0c1bd}
@media(max-width:760px){.cycle-weekly-load{padding:11px}.weekly-timetable-wrap{max-height:500px}}
</style>
@endpush

@push('styles')
<style>
</style>
@endpush

@endsection

@push('styles')
<style>
.cycle-group-workload{display:flex;align-items:center;flex-wrap:wrap;gap:7px;margin-top:8px;color:#687970;font-size:10px;line-height:1.4}
.cycle-group-workload>span:not(.section-status){padding:4px 7px;border:1px solid #e5ebe6;border-radius:6px;background:#f8faf8}
html[data-theme="dark"] .cycle-group-workload{color:#b5c7bc}
html[data-theme="dark"] .cycle-group-workload>span:not(.section-status){background:#17251f;border-color:#34483c}
@media(max-width:650px){.cycle-group-workload{align-items:flex-start;flex-direction:column}}
.group-count-control{display:grid;grid-template-columns:minmax(0,1fr) 110px;align-items:center;gap:10px}
.group-count-control label{margin:0}
.group-count-control .help{grid-column:1/-1;margin:0}
.group-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:16px}
.group-name-field{padding:11px;border:1px solid #dce7ed;border-radius:10px;background:#f9fcfd}
.group-name-field label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;color:#477085}
@media(max-width:560px){.group-fields{grid-template-columns:1fr}}
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
