@extends('layouts.app')
@section('title', 'Modalidad '.$modality->modality)
@section('content')
@php($assignment = $modality->assignment)
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $assignment->group->cycle) }}">{{ $assignment->group->cycle->academic_year }} · {{ $assignment->group->cycle->cycle_code }}</a><span>›</span><a href="{{ route('groups.show', $assignment->group) }}">{{ $assignment->group->name }}</a><span>›</span><span class="current">{{ $modality->modality }}</span></div>
<div class="page-head">
    <div><p class="eyebrow">Paso 4 de 4 · Gestión de modalidad</p><h1>{{ $assignment->course->name }}</h1><p class="subhead">{{ $modality->modality }} · NRC {{ $modality->nrc ?: 'Pendiente' }} · {{ $assignment->group->name }} · {{ $assignment->group->cycle->academic_year }}</p></div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $assignment->group) }}">← Volver al grupo</a>
</div>
<div class="quick-nav">
    <a href="#calendar">Calendario</a><a href="#students">Alumnos</a><a href="#documents">Documentos Excel</a><a href="#sessions">Sesiones</a>
    <a href="{{ route('evaluations.index', $modality) }}">Evaluaciones y notas →</a>
</div>
<div class="grid grid-3" style="margin-bottom:17px">
    <div class="stat-card"><div class="stat-label">Alumnos matriculados</div><div class="stat">{{ $modality->enrollments->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Sesiones calculadas</div><div class="stat">{{ $modality->sessions->where('status', 'scheduled')->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Documentos generados</div><div class="stat">{{ $modality->documents->count() }}</div></div>
</div>

<section class="card" id="calendar">
    <div class="card-head"><div><h2>1. Calendario y horario</h2><p class="card-description">Configura el periodo y los días de clase. Las sesiones se calculan automáticamente.</p></div><span class="badge {{ $modality->modality === 'TEC' ? 'badge-blue' : 'badge-green' }}">{{ $modality->label }}</span></div>
    <details class="panel">
        <summary>Ver o modificar configuración del calendario</summary>
        <div class="panel-body">
            <form method="POST" action="{{ route('modalities.update', $modality) }}">
                @csrf @method('PUT')
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
                <div class="form-actions"><span class="help">Los documentos anteriores se marcarán para regeneración si cambian los datos.</span><button class="btn" type="submit">Guardar y recalcular calendario</button></div>
            </form>
        </div>
    </details>
    @if($modality->start_date && $modality->end_date && $modality->schedules->isEmpty())
        <div class="alert alert-warning mt-16">Ya hay fechas registradas, pero falta al menos un día y horario de clase para calcular las sesiones.</div>
    @endif
    <form method="POST" action="{{ route('modalities.rebuild', $modality) }}" class="mt-16">@csrf<button class="btn btn-secondary btn-sm" type="submit">↻ Recalcular calendario ahora</button></form>
</section>

<section class="card" id="students">
    <div class="card-head"><div><h2>2. Alumnos del campus</h2><p class="card-description">Importa el archivo original que recibes de la plataforma. Primero revisarás una vista previa.</p></div><span class="badge">{{ $modality->enrollments->count() }} alumnos</span></div>
    <form method="POST" enctype="multipart/form-data" action="{{ route('students.preview', $modality) }}">
        @csrf
        <label for="roster">Archivo de alumnos (.xls, .xlsx o .csv)</label>
        <input class="field" id="roster" type="file" name="roster" accept=".xls,.xlsx,.csv,.txt" required>
        <div class="actions mt-16"><button class="btn" type="submit">Seleccionar archivo y revisar →</button>@if($import)<span class="help">Última importación: {{ $import->original_filename }} · {{ $import->created_count }} nuevos · {{ $import->updated_count }} actualizados · {{ $import->skipped_count }} omitidos.</span>@endif</div>
    </form>
</section>

<section class="card" id="documents">
    <div class="card-head"><div><h2>3. Documentos Excel</h2><p class="card-description">Genera los formatos oficiales usando los datos de esta modalidad.</p></div><a class="btn btn-secondary btn-sm" href="{{ route('evaluations.index', $modality) }}">Evaluaciones y notas →</a></div>
    <div class="grid grid-2">
        @foreach(['plan'=>'Plan de sesión','registro'=>'Registro de formación','evaluacion'=>'Formato de evaluación','charla'=>'Charla de 5 minutos'] as $type=>$label)
            <form class="course-card" method="POST" action="{{ route('documents.generate', [$modality, $type]) }}">
                @csrf
                <strong style="display:block;margin-bottom:8px">{{ $label }}</strong>
                <span class="help" style="display:block;margin-bottom:12px">Crear archivo Excel con los datos actuales.</span>
                <button class="btn btn-secondary btn-sm" type="submit">Generar Excel</button>
            </form>
        @endforeach
    </div>
    @if($modality->documents->isNotEmpty())
        <h3 style="margin-top:20px">Archivos generados</h3>
        <div class="table-wrap"><table><thead><tr><th>Documento</th><th>Versión</th><th>Estado</th><th>Fecha</th><th></th></tr></thead><tbody>
            @foreach($modality->documents as $document)
                <tr><td>{{ $document->filename }}</td><td>{{ $document->version }}</td><td>@if(($document->metadata['stale'] ?? false))<span class="badge badge-orange">Regenerar</span>@else<span class="badge badge-green">Vigente</span>@endif</td><td>{{ $document->created_at?->format('d/m/Y H:i') }}</td><td><a href="{{ route('documents.download', $document) }}">Descargar</a></td></tr>
            @endforeach
        </tbody></table></div>
    @endif
</section>

<section class="card" id="sessions">
    <div class="card-head"><div><h2>4. Sesiones del curso</h2><p class="card-description">El calendario se calcula con las fechas, días de clase y feriados. Abre una sesión solo cuando necesites editar su contenido.</p></div><span class="badge">{{ $modality->sessions->count() }} sesiones</span></div>
    @if($modality->sessions->isEmpty())
        <div class="empty"><strong>Aún no se han calculado sesiones.</strong><span>Configura el periodo y el horario en la sección Calendario.</span><div class="mt-16"><a class="btn btn-secondary btn-sm" href="#calendar">Ir a calendario</a></div></div>
    @else
        <div class="filter-bar"><input class="filter-input" id="session-search" type="search" placeholder="Buscar por fecha, semana o tema…" aria-label="Buscar sesiones"><span class="help">{{ $modality->sessions->where('status', 'scheduled')->count() }} sesiones programadas</span></div>
        <div id="session-list">
            @foreach($modality->sessions as $session)
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
                                <div class="form-actions"><span class="help">Se guarda solo el contenido de esta sesión.</span><button class="btn btn-secondary btn-sm" type="submit">Guardar sesión</button></div>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
        <div class="empty" id="session-empty" hidden><strong>No hay sesiones con ese criterio.</strong><span>Prueba con otra fecha o tema.</span></div>
    @endif
</section>
@endsection

@push('scripts')
<script>
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
