@extends('layouts.app')
@section('title','Previsualizar malla')
@section('content')
<div class="page-head"><div><h1>Previsualización de malla curricular</h1><p class="subhead">Revisa los datos extraídos y las propuestas antes de incorporarlas al curso.</p></div><a class="btn btn-secondary" href="{{ route('curricula.show',$assignment) }}"><x-icon name="arrow-left" /> Cancelar</a></div>
<div class="grid grid-3">
<div class="card"><div class="stat-label">Versión del prompt</div><strong>{{ $promptInfo['prompt_version'] ?? 'No indicada' }}</strong></div>
<div class="card"><div class="stat-label">Generado el</div><strong>{{ $promptInfo['prompt_generated_at'] ?? 'No indicado' }}</strong></div>
<div class="card"><div class="stat-label">Bloques encontrados</div><div class="stat">{{ count($payload['weeks']) }}</div></div>
</div>
<section class="card"><h2>Datos generales</h2><div class="grid grid-2">
@foreach($payload['metadata'] as $key=>$value)<div><div class="muted small">{{ str_replace('_',' ',ucfirst($key)) }}</div><strong>{{ is_scalar($value)?$value:'—' }}</strong></div>@endforeach
</div></section>
<section class="card"><h2>Relación de tareas y operaciones</h2>
@forelse($payload['operation_matrix'] as $task)
<div style="padding:10px 0;border-bottom:1px solid #e8edf3"><strong>{{ $task['task_code'] }} · {{ $task['task_title'] }}</strong><ul>@foreach($task['operations'] as $operation)<li>{{ $operation['code'] }} — {{ $operation['description'] }} <span class="badge">{{ $operation['type'] ?? 'UNSPECIFIED' }}</span></li>@endforeach</ul></div>
@empty <div class="empty">No se identificaron tareas HT.</div> @endforelse
</section>
<section class="card"><h2>Bloques curriculares</h2>
@foreach($payload['weeks'] as $week)
<div style="padding:14px 0;border-bottom:1px solid #e8edf3">
<div class="page-head"><div><strong>Semana del curso {{ $week['week_number'] }}</strong> @if($week['source_week_number'])<span class="badge badge-blue">Semana fuente {{ $week['source_week_number'] }}</span>@endif
<div class="muted small">Horas T/P/V: {{ $week['hours']['T'] ?? '—' }} / {{ $week['hours']['P'] ?? '—' }} / {{ $week['hours']['V'] ?? '—' }}</div></div>
@if($week['is_evaluation'])<span class="badge badge-orange">Evaluación</span>@endif</div>
@if($week['task_code'] ?? false)<p><strong>{{ $week['task_code'] }}</strong> · {{ $week['task_title'] ?? '' }}</p>@endif
@if(!empty($week['programming_topic']))<p class="curriculum-week-topic"><strong>Tema oficial · Hoja de Programación:</strong> {{ $week['programming_topic'] }}</p>@endif
<div class="grid grid-2">
<div><h3>Conocimientos tecnológicos (fuente)</h3><ul>@foreach($week['technological_knowledge'] as $item)<li>{{ $item }}</li>@endforeach</ul>
<h3>Conocimientos complementarios</h3><ul>@foreach($week['complementary_knowledge'] as $item)<li>{{ $item }}</li>@endforeach</ul>
<h3>Formación virtual</h3><ul>@foreach($week['virtual_activities'] as $item)<li>{{ $item }}</li>@endforeach</ul></div>
<div><h3>Propuesta TEC</h3><strong>{{ $week['tec_plan']['topic'] ?? '' }}</strong><p>{{ $week['tec_plan']['objective'] ?? '' }}</p><div class="small muted">Actividades: {{ count($week['tec_plan']['activities'] ?? []) }} · Ejercicios: {{ count($week['tec_plan']['exercises'] ?? []) }} · Materiales: {{ count($week['tec_plan']['materials'] ?? []) }}</div>
<h3 style="margin-top:14px">Propuesta TALL</h3><strong>{{ $week['tall_plan']['topic'] ?? '' }}</strong><p>{{ $week['tall_plan']['objective'] ?? '' }}</p><div class="small muted">Actividades: {{ count($week['tall_plan']['activities'] ?? []) }} · Ejercicios: {{ count($week['tall_plan']['exercises'] ?? []) }} · Materiales: {{ count($week['tall_plan']['materials'] ?? []) }}</div></div>
</div>
@if($week['notes'])<p class="muted small">Nota: {{ $week['notes'] }}</p>@endif
</div>
@endforeach
</section>
<section class="card"><h2>Confirmar importación</h2><div class="alert alert-info">Los datos oficiales de la malla se guardarán como fuente. Las propuestas TEC/TALL de IA quedarán pendientes de aprobación. Podrás revisarlas y aprobarlas después.</div>
<form method="POST" action="{{ route('curricula.confirm',$assignment) }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><button class="btn" type="submit"><x-icon name="check" /> Confirmar e importar esta malla</button></form>
</section>
@push('styles')
<style>
.curriculum-week-topic{margin:7px 0;padding:8px 10px;border-left:3px solid var(--info);border-radius:5px;background:var(--info-bg);color:var(--ink);font-size:11px;line-height:1.5}
.curriculum-week-topic strong{color:var(--info)}
</style>
@endpush
@endsection
