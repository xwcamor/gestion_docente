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

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any() && request()->boolean('talks'))
    <div class="alert alert-error" role="alert">
        <strong>No se pudo distribuir las charlas:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

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
        <div class="stat-top"><span class="stat-label">Horas declaradas / semana</span><span class="stat-icon"><x-icon name="clock" /></span></div>
        <div class="stat">{{ number_format($declaredWeeklyHours, 1) }} h</div>
    </div>
</div>

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

<details class="card cycle-talk-panel" id="cycle-talks" @if($cycleTalks->isEmpty() || request()->boolean('talks')) open @endif>
    <summary class="cycle-talk-summary">
        <span class="cycle-talk-icon"><x-icon name="clipboard-check" /></span>
        <span class="cycle-talk-title">
            <strong>Charlas de 5 minutos</strong>
            <small>Catálogo reutilizable y distribución automática por fecha del ciclo</small>
        </span>
        <span class="cycle-talk-count">{{ $talkAssignedCount }} asignadas · {{ $talkPendingCount }} pendientes</span>
        <x-icon name="chevron-right" class="cycle-talk-chevron" />
    </summary>
    <div class="cycle-talk-body">
        <div class="alert alert-info">
            Los temas se guardan como catálogo reutilizable del profesor. Cada ciclo conserva su propia distribución. El sistema usa los días y rangos reales de cada curso/modalidad, evita repetir fechas y excluye feriados.
        </div>

        <form method="POST" action="{{ route('cycles.five-minute-talks.update', $cycle) }}" enctype="multipart/form-data" data-confirm="Se volverán a distribuir los temas en este ciclo. Las fechas y asignaciones anteriores de charlas de este ciclo serán reemplazadas. ¿Deseas continuar?">
            @csrf
            @method('PUT')
            <div class="cycle-talk-form-grid">
                <div>
                    <label for="talk-topics">Temas del documento fijo</label>
                    <textarea class="field" id="talk-topics" name="topics" rows="10" placeholder="Pega un tema por línea, respetando el orden del documento original.">{{ old('topics', $talkTopics->pluck('title')->implode("\n")) }}</textarea>
                    <p class="help">Un tema por línea. Al guardar, esta lista se convierte en el catálogo reutilizable para tus siguientes ciclos.</p>
                    <label for="talk-topics-file" class="mt-16">O importar el Excel con los temas</label>
                    <input class="field" id="talk-topics-file" type="file" name="topics_file" accept=".xlsx,.xls,.csv">
                    <p class="help">Se lee la columna llamada “Tema” o, si no existe, la primera columna.</p>
                </div>
                <div>
                    <label>Días permitidos para programar charlas</label>
                    <div class="cycle-talk-days">
                        @foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $day=>$label)
                            <label class="cycle-talk-day">
                                <input type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, $selectedTalkDays, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if(!$availableTalkDays)
                        <div class="alert alert-warning mt-16">Todavía no se detectan días con horario en este ciclo. Primero configura las fechas y los días de tus cursos.</div>
                    @else
                        <p class="help mt-16">Días detectados en tus horarios: {{ collect($availableTalkDays)->map(fn ($day) => [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'][$day])->join(', ') }}.</p>
                    @endif
                    <button class="btn mt-16" type="submit"><x-icon name="calendar-check" /> Guardar y distribuir charlas</button>
                </div>
            </div>
        </form>

        <div class="cycle-talk-results">
            <div class="cycle-talk-results-head">
                <div><strong>Distribución de este ciclo</strong><small>{{ $cycleTalks->count() }} temas en el catálogo de este ciclo</small></div>
                @if($cycleTalks->isNotEmpty())
                    <form method="POST" action="{{ route('documents.cycle-talks', $cycle) }}">
                        @csrf
                        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="file-text" /> Generar Excel de charlas</button>
                    </form>
                @endif
            </div>
            @if($cycleTalks->isNotEmpty())
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>N.º</th><th>Fecha asignada</th><th>Tema</th><th>Estado</th></tr></thead>
                        <tbody>
                            @foreach($cycleTalks as $talk)
                                <tr>
                                    <td>{{ $talk->position }}</td>
                                    <td>{{ $talk->scheduled_date?->format('d/m/Y') ?? 'Pendiente' }}</td>
                                    <td>{{ $talk->topic }}</td>
                                    <td>@if($talk->scheduled_date)<span class="badge badge-green">Programada</span>@else<span class="badge badge-orange">Sin fecha disponible</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty"><strong>Aún no hay charlas distribuidas.</strong><span>Importa o pega los temas y selecciona los días para asignar una fecha a cada tema.</span></div>
            @endif
        </div>
    </div>
</details>

<section class="card">
    <div class="card-head">
        <div><h2>Grupos</h2></div>
        <span class="badge badge-blue">{{ $groupCount }} {{ $groupCount === 1 ? 'grupo' : 'grupos' }}</span>
    </div>
    @forelse($groups as $group)
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

<dialog class="ui-dialog sheet-dialog" id="cycle-edit-dialog" aria-labelledby="cycle-edit-title" data-backdrop-close @if(request('dialog') === 'edit-cycle') data-auto-open @endif>
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

<dialog class="ui-dialog sheet-dialog" id="group-create-dialog" aria-labelledby="group-create-title" data-backdrop-close @if(request()->boolean('nuevo_grupo') || ($errors->any() && !request('dialog') && !request()->boolean('talks'))) data-auto-open @endif>
    <div class="dialog-head">
        <div><h2 id="group-create-title">Agregar grupo</h2><p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('groups.store', $cycle) }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any() && !request('dialog') && !request()->boolean('talks'))
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

<dialog class="ui-dialog sheet-dialog" id="group-edit-dialog" aria-labelledby="group-edit-title" data-backdrop-close>
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
</style>
@endpush

@push('styles')
<style>
.cycle-talk-panel{padding:0;overflow:hidden;margin-bottom:15px}
.cycle-talk-summary{display:flex;align-items:center;gap:12px;padding:15px 18px;cursor:pointer;list-style:none}
.cycle-talk-summary::-webkit-details-marker{display:none}
.cycle-talk-icon{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:10px;background:#edf6f8;color:#32788c}
.cycle-talk-title{display:flex;flex:1;min-width:0;flex-direction:column;gap:3px}
.cycle-talk-title strong{font-size:14px;color:#294554}
.cycle-talk-title small{font-size:11px;color:#7b8990}
.cycle-talk-count{font-size:10px;font-weight:750;color:#45768a;white-space:nowrap}
.cycle-talk-chevron{color:#78909a;transition:transform .2s ease}
.cycle-talk-panel[open] .cycle-talk-chevron{transform:rotate(90deg)}
.cycle-talk-body{padding:0 18px 18px;border-top:1px solid #edf1f3}
.cycle-talk-form-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(260px,.75fr);gap:22px;padding:16px 0}
.cycle-talk-form-grid textarea{font-family:inherit;line-height:1.5}
.cycle-talk-days{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:9px}
.cycle-talk-day{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid #e1eaee;border-radius:8px;background:#fbfdfe;font-size:12px}
.cycle-talk-day input{accent-color:#327f91}
.cycle-talk-results{border-top:1px solid #e9eff2;padding-top:14px}
.cycle-talk-results-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
.cycle-talk-results-head>div{display:flex;flex-direction:column;gap:3px}
.cycle-talk-results-head strong{font-size:12px}
.cycle-talk-results-head small{font-size:10px;color:#7b8990}
@media(max-width:750px){.cycle-talk-form-grid{grid-template-columns:1fr}.cycle-talk-count{display:none}}
@media(max-width:520px){.cycle-talk-summary{padding:13px}.cycle-talk-results-head{align-items:flex-start;flex-direction:column}.cycle-talk-results-head form,.cycle-talk-results-head .btn{width:100%}}
</style>
@endpush

@endsection

@push('styles')
<style>
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
        const values = oldGroups.map(group => group?.name || '');
        const render = () => {
            const count = Math.max(1, Math.min(30, Number.parseInt(countInput.value, 10) || 1));
            countInput.value = count;
            groupFields.replaceChildren();

            for (let index = 0; index < count; index++) {
                const wrapper = document.createElement('div');
                wrapper.className = 'group-name-field';

                const label = document.createElement('label');
                label.htmlFor = 'group-name-' + index;
                label.textContent = 'Grupo ' + (index + 1);

                const input = document.createElement('input');
                input.className = 'field';
                input.id = 'group-name-' + index;
                input.name = 'groups[' + index + '][name]';
                input.maxLength = 100;
                input.required = true;
                input.placeholder = 'Ej. IA 20' + (index + 1);
                input.value = values[index] || '';
                input.addEventListener('input', () => { values[index] = input.value; });

                wrapper.append(label, input);
                groupFields.append(wrapper);
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
