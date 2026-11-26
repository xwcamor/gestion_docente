@extends('layouts.app')
@section('title','Previsualizar malla')
@section('content')
<div class="page-head"><div><h1>Revisar programación curricular</h1><p class="subhead">Verifica la información de ChatGPT antes de importarla al curso.</p></div><a class="btn btn-secondary" href="{{ route('curricula.show',$assignment) }}"><x-icon name="arrow-left" /> Cancelar</a></div>
<ol class="curriculum-stepper" aria-label="Pasos de importación">
    <li class="is-complete"><span class="step-number"><x-icon name="check" size="13" /></span><span><strong>PDF</strong><small>Subido</small></span></li>
    <li class="is-complete"><span class="step-number"><x-icon name="check" size="13" /></span><span><strong>ChatGPT</strong><small>JSON recibido</small></span></li>
    <li class="is-active" aria-current="step"><span class="step-number">3</span><span><strong>Importar</strong><small>Revisar datos</small></span></li>
</ol>
<div class="import-context-row" aria-label="Origen de la programación">
    <span><small>Versión del prompt</small><strong>{{ $promptInfo['prompt_version'] ?? 'No indicada' }}</strong></span>
    <span><small>Generado</small><strong>{{ $promptInfo['prompt_generated_at'] ?? 'No indicado' }}</strong></span>
    <span><small>Bloques curriculares</small><strong>{{ count($payload['weeks']) }}</strong></span>
</div>
<section class="card"><h2>Datos generales</h2><div class="grid grid-2">
@foreach($payload['metadata'] as $key=>$value)<div><div class="muted small">{{ str_replace('_',' ',ucfirst($key)) }}</div><strong>{{ is_scalar($value)?$value:'—' }}</strong></div>@endforeach
</div></section>
<section class="card"><h2>Relación de tareas y operaciones</h2>
@forelse($payload['operation_matrix'] as $task)
<div class="curriculum-operation-row"><strong>{{ $task['task_code'] }} · {{ $task['task_title'] }}</strong><ul>@foreach($task['operations'] as $operation)<li>{{ $operation['code'] }} — {{ $operation['description'] }} <span class="badge">{{ $operation['type'] ?? 'UNSPECIFIED' }}</span></li>@endforeach</ul></div>
@empty <div class="empty">No se identificaron tareas HT.</div> @endforelse
</section>
<section class="card"><h2>Bloques curriculares</h2>
@foreach($payload['weeks'] as $week)
<div class="curriculum-week-block">
<div class="curriculum-week-head"><div><strong>Semana del curso {{ $week['week_number'] }}</strong> @if($week['source_week_number'])<span class="badge badge-blue">Semana fuente {{ $week['source_week_number'] }}</span>@endif
<div class="muted small">Horas T/P/V: {{ $week['hours']['T'] ?? '—' }} / {{ $week['hours']['P'] ?? '—' }} / {{ $week['hours']['V'] ?? '—' }}</div></div>
@if($week['is_evaluation'])<span class="badge badge-orange">Evaluación</span>@endif</div>
@if($week['task_code'] ?? false)<p><strong>{{ $week['task_code'] }}</strong> · {{ $week['task_title'] ?? '' }}</p>@endif
@if(!empty($week['programming_topic']))<p class="curriculum-week-topic"><strong>Tema oficial · Hoja de Programación:</strong> {{ $week['programming_topic'] }}</p>@endif
<div class="grid grid-2">
<div><h3>Conocimientos tecnológicos (fuente)</h3><ul>@foreach($week['technological_knowledge'] as $item)<li>{{ $item }}</li>@endforeach</ul>
<h3>Conocimientos complementarios</h3><ul>@foreach($week['complementary_knowledge'] as $item)<li>{{ $item }}</li>@endforeach</ul>
<h3>Formación virtual</h3><ul>@foreach($week['virtual_activities'] as $item)<li>{{ $item }}</li>@endforeach</ul></div>
<div><h3>Propuesta TEC</h3><strong>{{ $week['tec_plan']['topic'] ?? '' }}</strong><p>{{ $week['tec_plan']['objective'] ?? '' }}</p><div class="small muted">Actividades: {{ count($week['tec_plan']['activities'] ?? []) }} · Ejercicios: {{ count($week['tec_plan']['exercises'] ?? []) }} · Materiales: {{ count($week['tec_plan']['materials'] ?? []) }}</div>
<h3 class="curriculum-subheading-break">Propuesta TALL</h3><strong>{{ $week['tall_plan']['topic'] ?? '' }}</strong><p>{{ $week['tall_plan']['objective'] ?? '' }}</p><div class="small muted">Actividades: {{ count($week['tall_plan']['activities'] ?? []) }} · Ejercicios: {{ count($week['tall_plan']['exercises'] ?? []) }} · Materiales: {{ count($week['tall_plan']['materials'] ?? []) }}</div></div>
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
.curriculum-stepper{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:0 0 16px;padding:0;list-style:none}
.curriculum-stepper li{display:flex;align-items:center;gap:9px;min-width:0;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:var(--surface)}
.curriculum-stepper .step-number{display:grid;place-items:center;flex:0 0 27px;width:27px;height:27px;border-radius:50%;background:var(--surface-soft);color:var(--muted);font-size:11px;font-weight:750}
.curriculum-stepper li>span:last-child{display:grid;gap:2px;min-width:0}
.curriculum-stepper strong{font-size:11px;color:var(--ink)}
.curriculum-stepper small{font-size:10px;color:var(--muted)}
.curriculum-stepper li.is-complete{border-color:var(--success);background:var(--success-bg)}
.curriculum-stepper li.is-complete .step-number{background:var(--success);color:#fff}
.curriculum-stepper li.is-active{border-color:var(--accent);background:var(--surface)}
.curriculum-stepper li.is-active .step-number{background:var(--accent);color:#fff}
@media(max-width:600px){.curriculum-stepper li{align-items:flex-start;padding:8px}.curriculum-stepper small{display:none}}
.curriculum-week-topic{margin:7px 0;padding:8px 10px;border-left:3px solid var(--info);border-radius:5px;background:var(--info-bg);color:var(--ink);font-size:11px;line-height:1.5}
.curriculum-week-topic strong{color:var(--info)}
</style>
@endpush
@endsection
