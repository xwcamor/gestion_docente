@extends('layouts.app')
@section('title', 'Modalidad '.$modality->modality)
@section('content')
@php($assignment = $modality->assignment)
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $assignment->group->cycle) }}">{{ $assignment->group->cycle->academic_year }} · {{ $assignment->group->cycle->cycle_code }}</a><span>›</span><a href="{{ route('groups.show', $assignment->group) }}">{{ $assignment->group->name }}</a><span>›</span><span class="current">{{ $modality->modality }}</span></div>
<div class="page-head">
    <div><p class="eyebrow">Modalidad {{ $modality->label }}</p><h1>{{ $assignment->course->name }}</h1><p class="subhead">{{ $modality->modality }} · NRC {{ $modality->nrc ?: 'Pendiente' }} · Bloque {{ $modality->block_code ?: 'Pendiente' }} @if($modality->block_reference) · Ref. {{ $modality->block_reference }} @endif · {{ $assignment->group->name }} · {{ $assignment->group->cycle->academic_year }}</p></div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $assignment->group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>
<div class="quick-nav">
    <a href="#calendar"><x-icon name="calendar-days" /> Calendario</a><a href="#students"><x-icon name="users" /> Alumnos</a><a href="#documents"><x-icon name="file-text" /> Documentos Excel</a><a href="#sessions"><x-icon name="clipboard-check" /> Sesiones</a>
    <a href="{{ route('evaluations.index', $modality) }}"><x-icon name="arrow-right" /> Evaluaciones y notas</a>
</div>
<div class="grid grid-3" style="margin-bottom:14px">
    <div class="stat-card"><div class="stat-label">Alumnos matriculados</div><div class="stat">{{ $enrollmentsCount }}</div></div>
    <div class="stat-card"><div class="stat-label">Sesiones programadas</div><div class="stat">{{ $scheduledSessionCount }}</div></div>
    <div class="stat-card"><div class="stat-label">Documentos generados</div><div class="stat">{{ $documentCount }}</div></div>
</div>

<section class="card detail-progress-card" aria-labelledby="modality-progress-title">
    <div class="detail-progress-heading">
        <div><p class="eyebrow">Seguimiento del curso</p><h2 id="modality-progress-title">Avance de preparación</h2></div>
        <strong>{{ $configurationSteps }} / 4</strong>
    </div>
    <div class="detail-progress-row">
        <div class="detail-progress-label"><span>Configuración del curso</span><strong>{{ $configurationPercent }}%</strong></div>
        <div class="detail-progress-track" role="progressbar" aria-label="Avance de configuración del curso" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $configurationPercent }}"><span style="width:{{ $configurationPercent }}%"></span></div>
        <small>NRC · fechas de dictado · horario · calendario de clases cuando aplica</small>
    </div>
    @if($hasClassSchedule)
        <div class="detail-progress-row">
            <div class="detail-progress-label"><span>Sesiones con contenido preparado</span><strong>{{ $sessionsWithContentCount }} / {{ $scheduledSessionCount }} · {{ $sessionContentPercent }}%</strong></div>
            <div class="detail-progress-track" role="progressbar" aria-label="Sesiones programadas con contenido" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $sessionContentPercent }}"><span style="width:{{ $sessionContentPercent }}%"></span></div>
            <small>Una sesión cuenta cuando tiene tema, objetivo, actividades o evidencias.</small>
        </div>
    @elseif($hasAnySchedule)
        <div class="detail-progress-row">
            <small>Esta modalidad utiliza bloques asincrónicos. No requiere sesiones de clase en el calendario.</small>
        </div>
    @else
        <div class="detail-progress-row">
            <small>Falta configurar el horario de esta modalidad. Elige los días y bloques correspondientes en Calendario y horario.</small>
        </div>
    @endif
</section>

<section class="card" id="calendar">
    <div class="card-head"><div><h2>Calendario y horario</h2><p class="card-description">Configura el periodo y los días de clase. Las sesiones se calculan automáticamente.</p></div><span class="badge {{ $modality->modality === 'TEC' ? 'badge-blue' : 'badge-green' }}">{{ $modality->label }}</span></div>
    <details class="panel">
        <summary>Ver o modificar configuración del calendario</summary>
        <div class="panel-body">
            <form method="POST" action="{{ route('modalities.update', $modality) }}">
                @csrf @method('PUT')
                <p class="help">Estas son las fechas reales de dictado de este curso/modalidad. Pueden terminar antes del fin del rango general del ciclo; el calendario solo generará sesiones dentro de estas fechas.</p>
            <div class="form-row-3">
                    <div><label for="nrc">NRC de esta modalidad</label><input class="field" id="nrc" name="nrc" value="{{ old('nrc', $modality->nrc) }}"></div>
                    <div><label for="start_date">Fecha de inicio</label><input class="field" id="start_date" type="date" name="start_date" value="{{ old('start_date', $modality->start_date?->format('Y-m-d')) }}"></div>
                    <div><label for="end_date">Fecha de fin</label><input class="field" id="end_date" type="date" name="end_date" value="{{ old('end_date', $modality->end_date?->format('Y-m-d')) }}"></div>
                </div>
                <div class="form-row">
                    <div><label for="total_hours">Horas semanales declaradas</label><input class="field" style="max-width:240px" id="total_hours" type="number" step=".5" min="0" name="total_hours" value="{{ old('total_hours', $modality->total_hours) }}"></div>
                    <div><label>Aula / ambiente</label><input class="field" name="classroom" value="{{ old('classroom', $modality->schedules->first()?->classroom) }}" placeholder="Ej. Laboratorio 204"></div>
                </div>
                <label>Días de clase</label>
                <div class="weekday-list">
                    @foreach([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $day=>$label)
                        <label class="weekday"><input type="checkbox" name="days[]" value="{{ $day }}" {{ $modality->schedules->contains('day_of_week', $day) ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="form-row" style="margin-top:12px">
                    <div><label for="start_time">Hora de inicio (común)</label><input class="field" id="start_time" type="time" name="start_time" value="{{ old('start_time', $modality->schedules->first()?->start_time ? substr($modality->schedules->first()->start_time, 0, 5) : '') }}"></div>
                    <div><label for="end_time">Hora de fin</label><input class="field" id="end_time" type="time" name="end_time" value="{{ old('end_time', $modality->schedules->first()?->end_time ? substr($modality->schedules->first()->end_time, 0, 5) : '') }}"></div>
                </div>
                <p class="help">Esta pantalla utiliza un horario común para los días seleccionados. Al guardar, el sistema recalcula las sesiones de esta modalidad.</p>
<div class="calendar-preview" id="calendar-preview" aria-live="polite">
<div class="calendar-preview-head"><div><strong>Vista previa automática</strong><p class="help">Solo cuenta tus días de clase y excluye feriados.</p></div><span class="badge badge-blue" id="preview-count">Pendiente</span></div>
<div class="calendar-preview-stats"><div><small>Primera clase</small><strong id="preview-first">—</strong></div><div><small>Última clase efectiva</small><strong id="preview-last">—</strong></div><div><small>Feriados excluidos</small><strong id="preview-holidays">0</strong></div></div>
<p class="help" id="preview-note">Selecciona las fechas y los días para calcular.</p>
<button class="btn btn-secondary btn-sm" type="button" id="adjust-end-date" disabled><x-icon name="calendar-check" /> Usar última clase como fecha final</button>
<details class="preview-details"><summary>Ver fechas calculadas</summary><div id="preview-dates" class="preview-date-list"></div></details>
</div>
                <div class="form-actions"><span class="help">Los documentos anteriores se marcarán para regeneración si cambian los datos.</span><button class="btn" type="submit"><x-icon name="check" /> Guardar y recalcular calendario</button></div>
            </form>
        </div>
    </details>
    @if($modality->start_date && $modality->end_date && $modality->schedules->isEmpty())
        <div class="alert alert-warning mt-16">Ya hay fechas registradas, pero falta al menos un día y horario de clase para calcular las sesiones.</div>
    @endif
    <form method="POST" action="{{ route('modalities.rebuild', $modality) }}" class="mt-16" data-confirm="Se volverán a calcular las sesiones de esta modalidad con la configuración y los feriados actuales. ¿Deseas continuar?">@csrf<button class="btn btn-secondary btn-sm" type="submit"><x-icon name="layers" /> Recalcular calendario ahora</button></form>
</section>

<section class="card" id="students">
    <div class="card-head">
        <div>
            <h2>Padrón compartido del grupo</h2>
            <p class="card-description">Los alumnos se cargan una sola vez desde el grupo. Este curso mantiene su propia matrícula, notas y evaluaciones.</p>
        </div>
        <span class="badge">{{ $enrollmentsCount }} matrículas en este curso</span>
    </div>
    <div class="grid grid-2">
        <div class="stat-card">
            <div class="stat-label">Alumnos registrados en el grupo</div>
            <div class="stat">{{ $assignment->group->students()->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Alumnos vinculados a este curso</div>
            <div class="stat">{{ $enrollmentsCount }}</div>
        </div>
    </div>
    <p class="help mt-16">Para agregar o actualizar estudiantes, abre el padrón del grupo. El sistema crea las matrículas que falten sin copiar calificaciones entre cursos.</p>
    <div class="actions mt-16">
        <a class="btn" href="{{ route('groups.show', $assignment->group) }}#group-roster"><x-icon name="users" /> Administrar padrón del grupo</a>
    </div>
</section>

<section class="card" id="documents">
    <div class="card-head"><div><h2>Documentos Excel</h2><p class="card-description">Genera archivos Excel con la información actual de esta modalidad.</p></div><a class="btn btn-secondary btn-sm" href="{{ route('evaluations.index', $modality) }}"><x-icon name="arrow-right" /> Evaluaciones y notas</a></div>
    <div class="grid grid-2">
        @foreach(['plan'=>'Plan de sesión','registro'=>'Registro de formación','evaluacion'=>'Formato de evaluación'] as $type=>$label)
            <form class="course-card" method="POST" action="{{ route('documents.generate', [$modality, $type]) }}">
                @csrf
                <strong style="display:block;margin-bottom:8px">{{ $label }}</strong>
                <span class="help" style="display:block;margin-bottom:12px">Crear archivo Excel con los datos actuales.</span>
                <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="file-text" /> Generar Excel</button>
            </form>
        @endforeach
    </div>
    @if($documentCount > 0)
        <h3 style="margin-top:20px">Archivos generados</h3>
        <div class="table-wrap"><table><thead><tr><th>Documento</th><th>Versión</th><th>Estado</th><th>Fecha</th><th></th></tr></thead><tbody>
            @foreach($documents as $document)
                <tr><td>{{ $document->filename }}</td><td>{{ $document->version }}</td><td>@if(($document->metadata['stale'] ?? false))<span class="badge badge-orange">Regenerar</span>@else<span class="badge badge-green">Vigente</span>@endif</td><td>{{ $document->created_at?->format('d/m/Y H:i') }}</td><td><a href="{{ route('documents.download', $document) }}"><x-icon name="arrow-right" /> Descargar</a></td></tr>
            @endforeach
        </tbody></table></div>
        @if($documents->hasPages())
            <x-pagination :paginator="$documents" />
        @endif
    @endif
</section>

<section class="card" id="sessions">
    <div class="card-head"><div><h2>Sesiones del curso</h2><p class="card-description">El calendario se calcula con las fechas, días de clase y feriados. Abre una sesión solo cuando necesites editar su contenido.</p></div><span class="badge">{{ $sessionCount }} sesiones</span></div>
    @if($sessionCount === 0)
        <div class="empty"><strong>Aún no se han calculado sesiones.</strong><span>Configura el periodo y el horario en la sección Calendario.</span><div class="mt-16"><a class="btn btn-secondary btn-sm" href="#calendar"><x-icon name="calendar-days" /> Ir a calendario</a></div></div>
    @else
        <div class="filter-bar"><input class="filter-input" id="session-search" type="search" placeholder="Buscar por fecha, semana o tema…" aria-label="Buscar sesiones"><span class="help">{{ $scheduledSessionCount }} sesiones programadas</span></div>
        <div id="session-list">
            @foreach($sessions as $session)
                <details class="panel session-item" style="margin-bottom:9px" data-search="{{ strtolower(($session->session_date?->format('d/m/Y') ?? '').' '.$session->session_number.' '.$session->topic.' '.$session->curriculum_week_number) }}">
                    <summary><span><strong>{{ $session->session_date?->format('d/m/Y') }}</strong> · Sesión {{ $session->session_number ?: '—' }} <span class="muted">· {{ $session->duration_minutes }} min</span></span><span class="badge {{ $session->status === 'holiday' ? 'badge-orange' : ($session->status === 'cancelled' ? 'badge-red' : 'badge-green') }}">{{ $session->status }}</span></summary>
                    <div class="panel-body">
                        @if($session->curriculum_week_number)<p class="help">Semana del curso {{ $session->curriculum_week_number }} @if($session->curriculumWeek?->source_week_number) · Semana fuente {{ $session->curriculumWeek->source_week_number }}@endif</p>@endif
                        @if($session->system_note)<div class="alert alert-info">{{ $session->system_note }}</div>@endif
                        @if($session->status !== 'holiday')
                            <form method="POST" action="{{ route('sessions.update', $session) }}">
                                @csrf @method('PATCH')
                                <label for="topic-{{ $session->id }}">Tema</label><input class="field" id="topic-{{ $session->id }}" name="topic" value="{{ $session->topic }}">
                                <label for="objective-{{ $session->id }}">Objetivo</label><input class="field" id="objective-{{ $session->id }}" name="objective" value="{{ $session->objective }}">
                                <label for="activities-{{ $session->id }}">Actividades, ejercicios y materiales</label><textarea class="field" id="activities-{{ $session->id }}" name="activities" rows="3">{{ $session->activities }}</textarea>
                                <label for="evidence-{{ $session->id }}">Evidencias</label><input class="field" id="evidence-{{ $session->id }}" name="evidence" value="{{ $session->evidence }}">
                                <div class="form-actions"><span class="help">Se guarda solo el contenido de esta sesión.</span><button class="btn btn-secondary btn-sm" type="submit"><x-icon name="check" /> Guardar sesión</button></div>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
        @if($sessions->hasPages())
            <x-pagination :paginator="$sessions" />
        @endif
        <div class="empty" id="session-empty" hidden><strong>No hay sesiones con ese criterio.</strong><span>Prueba con otra fecha o tema.</span></div>
    @endif
</section>
@endsection

@push('styles')
<style>
.detail-progress-card{padding:16px 18px;margin-bottom:14px}
.detail-progress-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
.detail-progress-heading h2{margin:2px 0 0;font-size:17px}
.detail-progress-heading>strong{font-size:15px;color:#347c91}
.detail-progress-row+.detail-progress-row{margin-top:14px;padding-top:13px;border-top:1px solid #edf1f3}
.detail-progress-label{display:flex;justify-content:space-between;gap:8px;margin-bottom:7px;font-size:12px;color:#4d626e}
.detail-progress-label strong{color:#274d61;white-space:nowrap}
.detail-progress-track{height:8px;overflow:hidden;border-radius:99px;background:#e8eef1}
.detail-progress-track span{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#3a8cab,#47a88e)}
.detail-progress-row small{display:block;margin-top:6px;color:#76868e;font-size:10px}
@media(max-width:600px){.detail-progress-card{padding:14px}.detail-progress-label{align-items:flex-start;flex-direction:column;gap:3px}}
</style>
@endpush

@push('styles')
<style>
.calendar-preview{margin-top:18px;padding:16px;border:1px solid #e2e7e0;border-radius:9px;background:#f8faf7}
.calendar-preview-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.calendar-preview-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:13px 0}
.calendar-preview-stats>div{padding:11px;background:#fff;border:1px solid #e7ebe5;border-radius:7px}
.calendar-preview-stats small{display:block;color:#7b867d;font-size:10px;margin-bottom:5px}
.calendar-preview-stats strong{font-size:14px;color:#40584a}
.preview-details{margin-top:12px;border-top:1px solid #e3e8e1;padding-top:10px}
.preview-details summary{cursor:pointer;font-size:12px;font-weight:700;color:#465249}
.preview-date-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(135px,1fr));gap:6px;margin-top:10px}
.preview-date{font-size:11px;padding:7px 8px;border-radius:6px;background:#fff;border:1px solid #e6ebe5}
.preview-date.holiday{color:#806338;background:#f8f3e9;border-color:#e9dfca}
@media(max-width:600px){.calendar-preview-stats{grid-template-columns:1fr}.calendar-preview-head{display:block}}

/* Modalidad: calendario, alumnado y documentos con contraste */
.calendar-preview{border-color:#cfe1e9;background:linear-gradient(135deg,#eff7fa,#f5faf9)}
.calendar-preview-stats>div{border-color:#d9e7ed;background:#fff}
.calendar-preview-stats small{color:#668392}
.calendar-preview-stats strong{color:#28677d}
.preview-details{border-top-color:#d8e6ec}
.preview-details summary{color:#35657a}
.preview-date{border-color:#dce8ed;background:#fff;color:#456b7c}
.preview-date.holiday{color:#956326;background:#fff4e3;border-color:#efd8b1}

</style>
@endpush

@push('scripts')
<script>
(() => {
    const form = document.querySelector('#calendar .panel-body form');
    if (!form) return;
    const holidays = new Set(@json($holidayDates));
    const startInput = form.querySelector('[name="start_date"]');
    const endInput = form.querySelector('[name="end_date"]');
    const dayInputs = [...form.querySelectorAll('[name="days[]"]')];
    const first = document.getElementById('preview-first');
    const last = document.getElementById('preview-last');
    const count = document.getElementById('preview-count');
    const holidayCount = document.getElementById('preview-holidays');
    const note = document.getElementById('preview-note');
    const list = document.getElementById('preview-dates');
    const adjust = document.getElementById('adjust-end-date');
    let lastTeachingDate = null;
    const fmt = date => new Intl.DateTimeFormat('es-PE',{day:'2-digit',month:'2-digit',year:'numeric'}).format(date);
    const parse = value => value ? new Date(value+'T12:00:00') : null;
    const calculate = () => {
        list.innerHTML = ''; lastTeachingDate = null;
        const from = parse(startInput.value), to = parse(endInput.value);
        const days = new Set(dayInputs.filter(input=>input.checked).map(input=>Number(input.value)));
        if (!from || !to || from > to || !days.size) {
            first.textContent='—';last.textContent='—';count.textContent='Pendiente';holidayCount.textContent='0';
            note.textContent='Indica un rango válido y selecciona al menos un día de clase.';adjust.disabled=true;return;
        }
        let sessions=0,blocked=0,firstTeachingDate=null;
        const cursor=new Date(from);
        while(cursor<=to){
            const isoDay = cursor.getDay() === 0 ? 7 : cursor.getDay();
            if(days.has(isoDay)){
                const key=[cursor.getFullYear(),String(cursor.getMonth()+1).padStart(2,'0'),String(cursor.getDate()).padStart(2,'0')].join('-');
                const item=document.createElement('div');item.className='preview-date';
                if(holidays.has(key)){blocked++;item.classList.add('holiday');item.textContent=fmt(cursor)+' · Feriado';}
                else{sessions++;if(!firstTeachingDate)firstTeachingDate=new Date(cursor);lastTeachingDate=new Date(cursor);item.textContent=fmt(cursor)+' · Clase';}
                list.appendChild(item);
            }
            cursor.setDate(cursor.getDate()+1);
        }
        first.textContent=firstTeachingDate?fmt(firstTeachingDate):'—';last.textContent=lastTeachingDate?fmt(lastTeachingDate):'—';
        count.textContent=sessions+' clases';holidayCount.textContent=String(blocked);
        const endKey=[to.getFullYear(),String(to.getMonth()+1).padStart(2,'0'),String(to.getDate()).padStart(2,'0')].join('-');
        const endIsClassDay=days.has(to.getDay())&&!holidays.has(endKey);
        note.textContent=sessions+' sesiones dentro del rango; '+blocked+' feriados excluidos.'+(!endIsClassDay&&lastTeachingDate?' La fecha final no es lectiva: la última clase efectiva será '+fmt(lastTeachingDate)+'.':'');
        adjust.disabled=!lastTeachingDate||lastTeachingDate.getTime()===to.getTime();
    };
    [startInput,endInput,...dayInputs].forEach(input=>input.addEventListener('change',calculate));
    adjust.addEventListener('click',()=>{if(lastTeachingDate){endInput.value=[lastTeachingDate.getFullYear(),String(lastTeachingDate.getMonth()+1).padStart(2,'0'),String(lastTeachingDate.getDate()).padStart(2,'0')].join('-');calculate();}});
    calculate();
})();

(() => {
    const search = document.getElementById('session-search');
    if (!search) return;
    const rows = [...document.querySelectorAll('.session-item')];
    const empty = document.getElementById('session-empty');
    search.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        rows.forEach(row => {
            const show = row.dataset.search.includes(term);
            row.hidden = !show;
            if (show) visible++;
        });
        empty.hidden = visible > 0;
    });
})();
</script>
@endpush
