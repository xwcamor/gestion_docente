@extends('layouts.app')
@section('title','Grupo')
@section('content')
<div class="page-head"><div><p class="muted small"><a href="{{ route('cycles.show',$group->cycle) }}">Ciclo {{ $group->cycle->cycle_code }}</a> / Grupo</p><h1>{{ $group->name }}</h1><p class="subhead">{{ $group->cycle->academic_year }} · Asignado {{ $group->assigned_at?->format('d/m/Y') ?? 'sin fecha' }}</p></div><a class="btn btn-secondary" href="{{ route('cycles.show',$group->cycle) }}">Volver al ciclo</a></div>
<section class="card"><h2>Asignar curso</h2><p class="muted">No existe un límite de cursos por grupo. Activa TEC, TALL o ambas modalidades; cada una tendrá su NRC, fechas, horario y documentos propios.</p>
<form method="POST" action="{{ route('assignments.store',$group) }}">@csrf
<div class="form-row-3">
<div><label>Nombre del curso</label><input class="field" name="course_name" required></div>
<div><label>Código del curso</label><input class="field" name="course_code" placeholder="Ej. PIAD-224"></div>
<div><label>Fecha de asignación</label><input class="field" type="date" name="assigned_at"></div>
</div>
<div class="form-row"><div><label>Carrera</label><input class="field" name="career"></div><div><label>Módulo</label><input class="field" name="module"></div></div>
<div class="grid grid-2 mt-16">
<div style="border:1px solid #dbe5f1;border-radius:10px;padding:14px"><label class="checkline"><input type="checkbox" name="has_tec" value="1" checked> Activar TEC (Conocimientos tecnológicos)</label>
<div class="form-row"><div><label>NRC TEC</label><input class="field" name="tec_nrc"></div><div><label>Horas por semana</label><input class="field" type="number" step=".5" min="0" name="tec_total_hours"></div></div>
<div class="form-row"><div><label>Inicio TEC</label><input class="field" type="date" name="tec_start_date"></div><div><label>Fin TEC</label><input class="field" type="date" name="tec_end_date"></div></div>
<label>Días de clase TEC</label><div class="form-row-3">@foreach([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $day=>$label)<label class="checkline"><input type="checkbox" name="tec_days[]" value="{{ $day }}"> {{ $label }}</label>@endforeach</div>
<div class="form-row"><div><label>Hora de inicio</label><input class="field" type="time" name="tec_start_time"></div><div><label>Hora de fin</label><input class="field" type="time" name="tec_end_time"></div></div>
<label>Ambiente</label><input class="field" name="tec_classroom">
</div>
<div style="border:1px solid #dbe5f1;border-radius:10px;padding:14px"><label class="checkline"><input type="checkbox" name="has_tall" value="1"> Activar TALL (Práctica de taller)</label>
<div class="form-row"><div><label>NRC TALL</label><input class="field" name="tall_nrc"></div><div><label>Horas por semana</label><input class="field" type="number" step=".5" min="0" name="tall_total_hours"></div></div>
<div class="form-row"><div><label>Inicio TALL</label><input class="field" type="date" name="tall_start_date"></div><div><label>Fin TALL</label><input class="field" type="date" name="tall_end_date"></div></div>
<label>Días de clase TALL</label><div class="form-row-3">@foreach([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $day=>$label)<label class="checkline"><input type="checkbox" name="tall_days[]" value="{{ $day }}"> {{ $label }}</label>@endforeach</div>
<div class="form-row"><div><label>Hora de inicio</label><input class="field" type="time" name="tall_start_time"></div><div><label>Hora de fin</label><input class="field" type="time" name="tall_end_time"></div></div>
<label>Ambiente</label><input class="field" name="tall_classroom">
</div>
</div>
<label>Observaciones</label><textarea class="field" name="notes" rows="2"></textarea>
<button class="btn mt-16" type="submit">Guardar curso y modalidades</button>
</form></section>
<section class="card"><h2>Cursos asignados ({{ $group->assignments->count() }})</h2>
@if($group->assignments->isEmpty())<div class="empty">Todavía no hay cursos asignados a este grupo.</div>@else
@foreach($group->assignments as $assignment)
<div style="padding:15px 0;border-bottom:1px solid #e8edf3"><div class="page-head" style="margin:0"><div><strong>{{ $assignment->course->name }}</strong><div class="muted small">{{ $assignment->course->code }}</div></div><a href="{{ route('curricula.show',$assignment) }}">Malla curricular</a></div>
<div class="actions mt-16">@forelse($assignment->modalities as $modality)<a class="btn btn-secondary" href="{{ route('modalities.show',$modality) }}">{{ $modality->modality }} · NRC {{ $modality->nrc ?: 'Pendiente' }}</a>@empty<span class="muted small">Sin modalidad configurada</span>@endforelse</div></div>
@endforeach @endif
</section>
@endsection
