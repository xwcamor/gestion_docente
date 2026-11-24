@extends('layouts.app')
@section('title', 'Prompt para analizar malla')
@section('content')
<div class="page-head">
    <div>
        <h1>Prompt preparado para ChatGPT</h1>
        <p class="subhead">Copia el prompt, adjunta el mismo PDF en ChatGPT y pega aquí el JSON que te devuelva.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('curricula.show', $assignment) }}"><x-icon name="arrow-left" /> Volver a la malla</a>
</div>

@if($textExtractionWarning)
    <div class="alert alert-warning" role="alert">
        El PDF contiene poco texto extraíble. Adjunta el PDF original a ChatGPT para que también pueda leer sus tablas e imágenes.
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>No se pudo validar el resultado:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section class="card prompt-card">
    <div class="card-head">
        <div>
            <h2>Prompt</h2>
            <p class="card-description">Pégalo en el mismo chat donde adjuntaste el PDF.</p>
        </div>
        <button class="btn" id="copyPromptButton" type="button"><x-icon name="copy" /> Copiar prompt</button>
    </div>
    <textarea id="promptText" class="field mono prompt-textarea" rows="18" readonly>{{ $prompt }}</textarea>
    <p class="help" id="copyPromptStatus" role="status" aria-live="polite"></p>
</section>

<section class="card prompt-result-card">
    <h2>Importar resultado de ChatGPT</h2>
    <p class="card-description">Pega el JSON completo o selecciona el archivo .json que generó ChatGPT.</p>
    <form method="POST" enctype="multipart/form-data" action="{{ route('curricula.preview', $assignment) }}" id="curriculumJsonForm">
        @csrf
        <input type="hidden" name="source_token" value="{{ $token }}">
        <label for="curriculumJsonContent">Resultado JSON</label>
        <textarea id="curriculumJsonContent" class="field mono" name="json_content" rows="8" placeholder='Pega aquí el objeto JSON completo.'>{{ old('json_content') }}</textarea>
        @error('json_content')<p class="field-error">{{ $message }}</p>@enderror
        <label for="curriculumJsonFile">O selecciona un archivo .json</label>
        <input class="field" id="curriculumJsonFile" type="file" name="json_file" accept=".json,.txt">
        @error('json_file')<p class="field-error">{{ $message }}</p>@enderror
        <div class="form-actions">
            <span class="help">Revisarás los datos antes de confirmar la importación.</span>
            <button class="btn" type="submit"><x-icon name="check" /> Validar y revisar resultado</button>
        </div>
    </form>
</section>
@endsection

@push('styles')
<style>
.prompt-textarea{min-height:360px;resize:vertical;line-height:1.5}
.prompt-card .card-head{align-items:center}
.prompt-result-card .form-actions{margin-top:14px}
@media(max-width:600px){
    .prompt-card .card-head{align-items:stretch;flex-direction:column}
    .prompt-card .card-head .btn{width:100%}
    .prompt-result-card .form-actions{align-items:stretch;flex-direction:column}
    .prompt-result-card .form-actions .btn{width:100%}
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const button = document.getElementById('copyPromptButton');
    const textarea = document.getElementById('promptText');
    const status = document.getElementById('copyPromptStatus');

    button?.addEventListener('click', async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(textarea.value);
            } else {
                textarea.focus();
                textarea.select();
                if (!document.execCommand('copy')) {
                    throw new Error('No se pudo copiar automáticamente.');
                }
            }
            if (status) status.textContent = 'Prompt copiado. Ya puedes pegarlo en ChatGPT.';
        } catch (error) {
            if (status) status.textContent = 'No se pudo copiar automáticamente. Selecciona el texto y usa Ctrl+C.';
        }
    });
})();
</script>
@endpush
