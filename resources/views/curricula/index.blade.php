@extends('layouts.app')
@section('title','Malla curricular')
@section('content')
<div class="page-head"><div><p class="muted small"><a href="{{ route('groups.show',$assignment->group) }}">{{ $assignment->group->name }}</a> / {{ $assignment->course->name }}</p><h1>Malla curricular</h1><p class="subhead">Una sola malla oficial alimenta TEC y TALL. Las propuestas didácticas de cada modalidad se revisan y aprueban antes de usarse.</p></div><a class="btn btn-secondary" href="{{ route('groups.show',$assignment->group) }}">Volver al grupo</a></div>
<section class="card"><h2>Importar malla curricular PDF</h2>
<p>Sube el documento oficial. El sistema extraerá su texto y preparará un prompt fechado para ChatGPT. Después podrás pegar aquí el JSON generado y revisar lo que se importará.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('curricula.extract',$assignment) }}">@csrf
<label>Documento PDF de la malla</label><input class="field" type="file" name="curriculum_pdf" accept=".pdf,application/pdf" required>
<button class="btn mt-16" type="submit">Analizar PDF y generar prompt</button>
</form>
<p class="help">El PDF original es la fuente de verdad. Revisa el resultado de la IA antes de confirmar la importación.</p>
<div class="actions mt-16"><a class="btn btn-secondary" href="{{ route('curricula.example',$assignment) }}">Descargar JSON de ejemplo PIAD-224</a></div>
</section>
<section class="card"><h2>Importar JSON ya generado</h2>
<form method="POST" action="{{ route('curricula.preview',$assignment) }}">@csrf
<label>Pega el JSON completo</label><textarea class="field mono" name="json_content" rows="8" placeholder='{"schema_version":2,"processing":{...},"metadata":{...},"operation_matrix":[],"weeks":[]}'></textarea>
<button class="btn mt-16" type="submit">Validar y previsualizar JSON</button>
</form></section>
<section class="card"><h2>Versiones de malla importadas</h2>
@if($curricula->isEmpty())<div class="empty">Todavía no hay mallas curriculares asociadas a este curso.</div>@else
@foreach($curricula as $curriculum)
<div style="border-top:1px solid #e8edf3;padding:16px 0">
<div class="page-head"><div><strong>{{ $curriculum->course_name ?: $assignment->course->name }}</strong><div class="muted small">Versión {{ $curriculum->version }} · {{ $curriculum->source_filename }} · {{ $curriculum->created_at?->format('d/m/Y H:i') }}</div>
<div class="muted small">Prompt: {{ data_get($curriculum->source_metadata,'prompt_version','—') }} · Generado: {{ data_get($curriculum->source_metadata,'prompt_generated_at','—') }}</div></div><span class="badge {{ $curriculum->is_active?'badge-green':'' }}">{{ $curriculum->is_active?'Activa':'Histórica' }}</span></div>
<p class="small">{{ $curriculum->weeks->count() }} bloques · {{ $curriculum->tasks->count() }} tareas HT · Objetivo: {{ $curriculum->general_objective ?: 'No indicado' }}</p>
@if($curriculum->is_active)
<form method="POST" action="{{ route('curricula.approve',$curriculum) }}">@csrf
<h3>Revisar sugerencias pedagógicas de IA</h3>
<p class="help">Marca solo las sugerencias que aprobaste. Las propuestas TEC y TALL permanecen separadas.</p>
@foreach($curriculum->weeks as $week)
<div style="background:#f8fafc;padding:12px;border-radius:9px;margin:10px 0">
<strong>Bloque {{ $week->week_number }} @if($week->source_week_number)(semana de la malla {{ $week->source_week_number }})@endif</strong>
<div class="muted small">T/P/V: {{ $week->hours_theory ?? '—' }} / {{ $week->hours_practice ?? '—' }} / {{ $week->hours_virtual ?? '—' }}</div>
@foreach($week->contentItems->where('source_kind','AI_SUGGESTION') as $item)
<label class="checkline" style="align-items:flex-start;font-weight:500"><input type="checkbox" name="approved_items[]" value="{{ $item->id }}" {{ $item->approved?'checked':'' }}>
<span><span class="badge">{{ $item->metadata['target_modality'] ?? 'BOTH' }}</span> <strong>{{ $item->category }}</strong> · {{ $item->content }}</span></label>
@endforeach
</div>
@endforeach
<button class="btn" type="submit">Guardar aprobación de sugerencias</button>
</form>
@endif
<details style="margin-top:14px"><summary style="cursor:pointer;font-weight:700">Ver tareas y operaciones oficiales de esta malla</summary>
@foreach($curriculum->tasks as $task)
<div style="padding:8px 0"><strong>{{ $task->task_code }} · {{ $task->title }}</strong>
<ul>@foreach($task->operations as $operation)<li>{{ $operation->operation_code }} — {{ $operation->description }} <span class="badge">{{ $operation->operation_type }}</span></li>@endforeach</ul></div>
@endforeach
</details>
</div>
@endforeach @endif
</section>
@endsection
