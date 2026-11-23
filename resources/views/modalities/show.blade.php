@extends('layouts.app')
@section('title','Modalidad '.$modality->modality)
@section('content')
@php($assignment = $modality->assignment)
<div class="page-head"><div><p class="muted small"><a href="{{ route('groups.show',$assignment->group) }}">{{ $assignment->group->name }}</a> / {{ $assignment->course->name }}</p><h1>{{ $assignment->course->name }}</h1><p class="subhead">{{ $modality->modality }} · NRC {{ $modality->nrc ?: 'Pendiente' }} · {{ $assignment->group->cycle->academic_year }} / {{ $assignment->group->cycle->cycle_code }}</p></div><a class="btn btn-secondary" href="{{ route('groups.show',$assignment->group) }}">Volver al grupo</a></div>
<div class="grid grid-3">
<div class="card"><div class="stat-label">Alumnos matriculados</div><div class="stat">{{ $modality->enrollments->count() }}</div></div>
<div class="card"><div class="stat-label">Sesiones calculadas</div><div class="stat">{{ $modality->sessions->where('status','scheduled')->count() }}</div></div>
<div class="card"><div class="stat-label">Documentos generados</div><div class="stat">{{ $modality->documents->count() }}</div></div>
</div>
<section class="card"><div class="page-head"><div><h2>Configuración del calendario</h2><p class="muted small">Modifica inicio, fin y horarios. El sistema vuelve a calcular únicamente esta modalidad.</p></div><span class="badge {{ $modality->modality==='TEC'?'badge-blue':'badge-green' }}">{{ $modality->label }}</span></div>
<form method="POST" action="{{ route('modalities.update',$modality) }}">@csrf @method('PUT')
<div class="form-row-3">
<div><label>NRC</label><input class="field" name="nrc" value="{{ old('nrc',$modality->nrc) }}"></div>
<div><label>Fecha de inicio</label><input class="field" type="date" name="start_date" value="{{ old('start_date',$modality->start_date?->format('Y-m-d')) }}"></div>
<div><label>Fecha de fin</label><input class="field" type="date" name="end_date" value="{{ old('end_date',$modality->end_date?->format('Y-m-d')) }}"></div>
</div>
<label>Horas semanales declaradas</label><input class="field" style="max-width:220px" type="number" step=".5" min="0" name="total_hours" value="{{ old('total_hours',$modality->total_hours) }}">
<div class="grid grid-2 mt-16">
<div><label>Días de clase</label><div class="form-row-3">
@foreach([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $day=>$label)
<label class="checkline"><input type="checkbox" name="days[]" value="{{ $day }}" {{ $modality->schedules->contains('day_of_week',$day)?'checked':'' }}> {{ $label }}</label>
@endforeach
</div></div>
<div class="form-row"><div><label>Hora inicio (horario común)</label><input class="field" type="time" name="start_time" value="{{ old('start_time',$modality->schedules->first()?->start_time) }}"></div><div><label>Hora fin</label><input class="field" type="time" name="end_time" value="{{ old('end_time',$modality->schedules->first()?->end_time) }}"></div></div>
</div>
<label>Aula / ambiente</label><input class="field" name="classroom" value="{{ old('classroom',$modality->schedules->first()?->classroom) }}">
<p class="help">Este formulario configura un horario común para los días seleccionados. Para horarios diferentes por día, se puede ampliar la pantalla de horarios.</p>
<div class="actions mt-16"><button class="btn" type="submit">Guardar y recalcular fechas</button></div>
</form>
<form method="POST" action="{{ route('modalities.rebuild',$modality) }}" style="margin-top:12px">@csrf<button class="btn btn-secondary" type="submit">Recalcular calendario ahora</button></form>
</section>
<section class="card"><h2>Importar alumnos del campus</h2><p class="muted">Carga directamente el archivo que te entrega la plataforma, sin crear otra plantilla. El sistema detecta la columna de calificación aunque el encabezado tenga un código variable.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('students.preview',$modality) }}">@csrf
<label>Archivo original del campus (.xls, .xlsx o .csv)</label><input class="field" type="file" name="roster" accept=".xls,.xlsx,.csv,.txt" required>
<button class="btn mt-16" type="submit">Revisar archivo antes de importar</button></form>
@if($import)<p class="help">Última importación: {{ $import->original_filename }} · {{ $import->created_count }} nuevos · {{ $import->updated_count }} actualizados · {{ $import->skipped_count }} omitidos.</p>@endif
</section>
<section class="card"><div class="page-head"><h2>Documentos Excel de esta modalidad</h2><a class="btn btn-secondary" href="{{ route('evaluations.index',$modality) }}">Evaluaciones y notas</a></div>
<div class="actions">
@foreach(['plan'=>'Generar plan de sesión','registro'=>'Generar registro de formación','evaluacion'=>'Generar formato de evaluación','charla'=>'Generar charla de 5 minutos'] as $type=>$label)
<form method="POST" action="{{ route('documents.generate',[$modality,$type]) }}">@csrf<button class="btn btn-secondary" type="submit">{{ $label }}</button></form>
@endforeach
</div>
@if($modality->documents->isNotEmpty())
<div class="table-wrap mt-16"><table><thead><tr><th>Documento</th><th>Versión</th><th>Estado</th><th>Generado</th><th></th></tr></thead><tbody>
@foreach($modality->documents as $document)<tr><td>{{ $document->filename }}</td><td>{{ $document->version }}</td><td>@if(($document->metadata['stale']??false))<span class="badge badge-orange">Requiere regenerar</span>@else<span class="badge badge-green">Vigente</span>@endif</td><td>{{ $document->created_at?->format('d/m/Y H:i') }}</td><td><a href="{{ route('documents.download',$document) }}">Descargar</a></td></tr>@endforeach
</tbody></table></div>
@endif
</section>
<section class="card"><h2>Sesiones calculadas y contenido</h2>
@if($modality->sessions->isEmpty())<div class="empty">Completa las fechas y los días de clase para generar las sesiones.</div>@else
@foreach($modality->sessions as $session)
<div style="border-top:1px solid #e8edf3;padding:14px 0">
<div class="page-head" style="margin-bottom:8px"><div><strong>{{ $session->session_date?->format('d/m/Y') }}</strong> · Sesión {{ $session->session_number ?: '—' }} · {{ $session->duration_minutes }} min
@if($session->curriculum_week_number)<div class="muted small">Semana del curso {{ $session->curriculum_week_number }} @if($session->curriculumWeek?->source_week_number)· Semana fuente {{ $session->curriculumWeek->source_week_number }}@endif</div>@endif</div>
<span class="badge {{ $session->status==='holiday'?'badge-orange':($session->status==='cancelled'?'badge-red':'badge-green') }}">{{ $session->status }}</span></div>
@if($session->system_note)<p class="muted small">{{ $session->system_note }}</p>@endif
@if($session->status!=='holiday')
<form method="POST" action="{{ route('sessions.update',$session) }}">@csrf @method('PATCH')
<div class="form-row"><div><label>Tema</label><input class="field" name="topic" value="{{ $session->topic }}"></div><div><label>Objetivo</label><input class="field" name="objective" value="{{ $session->objective }}"></div></div>
<label>Actividades, operaciones, ejercicios y materiales</label><textarea class="field" name="activities" rows="3">{{ $session->activities }}</textarea>
<label>Evidencias</label><input class="field" name="evidence" value="{{ $session->evidence }}">
<button class="btn btn-secondary mt-16" type="submit">Guardar contenido manual</button>
</form>
@endif
</div>
@endforeach
@endif
</section>
@endsection
