@extends('layouts.app')
@section('title','Prompt para analizar malla')
@section('content')
<div class="page-head"><div><h1>Prompt preparado para ChatGPT</h1><p class="subhead">Adjunta el PDF original de la malla a ChatGPT, copia este prompt y pégalo en el mismo chat.</p></div><a class="btn btn-secondary" href="{{ route('curricula.show',$assignment) }}"><x-icon name="arrow-left" /> Volver</a></div>
<div class="grid grid-3">
<div class="card"><div class="stat-label">Versión del prompt</div><div class="stat" style="font-size:20px">{{ $promptVersion }}</div></div>
<div class="card"><div class="stat-label">Fecha y hora de generación</div><strong>{{ $promptGeneratedAt->format('d/m/Y H:i:s P') }}</strong></div>
<div class="card"><div class="stat-label">Curso de destino</div><strong>{{ $assignment->course->name }}</strong></div>
</div>
<section class="card"><h2>Pasos para usar ChatGPT</h2>
<ol style="line-height:1.9">
<li>Descarga o conserva el PDF original que acabas de subir.</li>
<li>Abre ChatGPT y adjunta ese mismo PDF.</li>
<li>Copia el prompt completo de abajo y envíalo en el chat donde adjuntaste la malla.</li>
<li>Solicita que devuelva únicamente el JSON, sin explicaciones ni bloques Markdown.</li>
<li>Cuando recibas la respuesta, copia el JSON completo y pégalo en el formulario de esta página.</li>
</ol>
<div class="alert alert-info">La malla sirve para TEC y TALL. ChatGPT debe extraer una sola vez los contenidos oficiales y proponer planes separados para teoría/conocimientos tecnológicos y práctica de taller. Las propuestas de IA se importan pendientes de tu aprobación.</div>
</section>
<section class="card"><div class="page-head"><h2>Prompt completo</h2><button class="btn btn-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('promptText').value).then(()=>this.innerText='Copiado')"><x-icon name="file-text" /> Copiar prompt</button></div>
<textarea id="promptText" class="field mono" rows="22" readonly>{{ $prompt }}</textarea>
<p class="help">Versión: {{ $promptVersion }} · Generado: {{ $promptGeneratedAt->toIso8601String() }}</p>
</section>
<section class="card"><h2>Pegar resultado JSON de ChatGPT</h2>
<form method="POST" enctype="multipart/form-data" action="{{ route('curricula.preview',$assignment) }}">@csrf
<input type="hidden" name="source_token" value="{{ $token }}">
<label>JSON generado por ChatGPT</label><textarea class="field mono" name="json_content" rows="10" placeholder='Pega aquí el objeto JSON completo, desde la primera llave hasta la última.'></textarea>
<label>O selecciona un archivo .json (opcional)</label><input class="field" type="file" name="json_file" accept=".json,.txt">
<button class="btn mt-16" type="submit"><x-icon name="check" /> Validar y revisar resultado</button>
</form></section>
@endsection
