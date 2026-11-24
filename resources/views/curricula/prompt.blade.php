@extends('layouts.app')
@section('title', 'Generar programación curricular')
@section('content')
<div class="page-head">
    <div>
        <h1>Generar programación con ChatGPT</h1>
        <p class="subhead">Prepara el chat, genera el JSON y tráelo aquí para revisarlo e importarlo.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('curricula.show', $assignment) }}"><x-icon name="arrow-left" /> Volver</a>
</div>

<ol class="curriculum-stepper" aria-label="Pasos de importación">
    <li class="is-complete"><span class="step-number"><x-icon name="check" size="13" /></span><span><strong>PDF</strong><small>Subido</small></span></li>
    <li class="is-active" aria-current="step"><span class="step-number">2</span><span><strong>ChatGPT</strong><small>Generar JSON</small></span></li>
    <li><span class="step-number">3</span><span><strong>Importar</strong><small>Revisar datos</small></span></li>
</ol>

@if($textExtractionWarning)
    <div class="alert alert-warning" role="alert">
        El PDF contiene poco texto extraíble. Adjunta el PDF original a ChatGPT para que también pueda leer sus tablas e imágenes.
    </div>
@endif

<div class="card prompt-wizard" id="prompt-wizard" data-initial-step="{{ $errors->any() || filled(old('json_content')) ? 'result' : 'prepare' }}">
    <div class="wizard-heading">
        <div>
            <span class="eyebrow">Paso 2 de 3</span>
            <h2>Prepara tu respuesta</h2>
            <p class="card-description">Completa estos dos pasos en orden. Puedes volver al anterior cuando lo necesites.</p>
        </div>
        <span class="wizard-progress-label" id="wizard-progress-label">1 de 2</span>
    </div>

    <div class="wizard-substeps" aria-label="Pasos de ChatGPT">
        <div class="wizard-substep is-active" data-wizard-indicator="prepare">
            <span>1</span><strong>Preparar ChatGPT</strong>
        </div>
        <div class="wizard-substep" data-wizard-indicator="result">
            <span>2</span><strong>Traer resultado</strong>
        </div>
    </div>

    <section class="prompt-wizard-panel" data-wizard-panel="prepare" aria-labelledby="prepare-chatgpt-title">
        <h3 id="prepare-chatgpt-title" tabindex="-1">1. Adjunta el PDF y copia el prompt</h3>
        <p>Abre ChatGPT en otra pestaña, adjunta el mismo PDF que subiste a Gestión Docente y después copia este prompt en ese chat.</p>
        <a class="btn btn-secondary" href="https://chatgpt.com/" target="_blank" rel="noopener noreferrer"><x-icon name="external-link" /> Abrir ChatGPT</a>

        <div class="prompt-copy-area">
            <div class="prompt-copy-heading">
                <label for="promptText">Prompt para analizar la malla</label>
                <button class="btn btn-sm" id="copyPromptButton" type="button"><x-icon name="copy" /> Copiar prompt</button>
            </div>
            <textarea id="promptText" class="field mono prompt-textarea" rows="16" readonly>{{ $prompt }}</textarea>
            <p class="help" id="copyPromptStatus" role="status" aria-live="polite">Copia el texto completo y pégalo en el chat donde adjuntaste el PDF.</p>
        </div>

        <div class="wizard-actions">
            <span class="help">Cuando ChatGPT te devuelva el objeto JSON, continúa al siguiente paso.</span>
            <button class="btn" type="button" data-wizard-next><x-icon name="arrow-right" /> Continuar al resultado</button>
        </div>
    </section>

    <section class="prompt-wizard-panel" data-wizard-panel="result" aria-labelledby="paste-result-title">
        <h3 id="paste-result-title" tabindex="-1">2. Pega o selecciona el JSON</h3>
        <p>Vuelve a Gestión Docente y pega la respuesta completa de ChatGPT o carga el archivo .json.</p>

        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>No se pudo validar el resultado:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('curricula.preview', $assignment) }}" id="curriculumJsonForm">
            @csrf
            <input type="hidden" name="source_token" value="{{ $token }}">
            <label for="curriculumJsonContent">Resultado JSON</label>
            <textarea id="curriculumJsonContent" class="field mono" name="json_content" rows="10" placeholder='Pega aquí el objeto JSON completo.'>{{ old('json_content') }}</textarea>
            @error('json_content')<p class="field-error">{{ $message }}</p>@enderror

            <label for="curriculumJsonFile">O selecciona un archivo .json</label>
            <input class="field" id="curriculumJsonFile" type="file" name="json_file" accept=".json,.txt">
            @error('json_file')<p class="field-error">{{ $message }}</p>@enderror

            <div class="wizard-actions">
                <button class="btn btn-secondary" type="button" data-wizard-back><x-icon name="arrow-left" /> Volver al prompt</button>
                <button class="btn" type="submit"><x-icon name="check" /> Validar JSON y revisar</button>
            </div>
        </form>
    </section>
</div>
@endsection

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
.prompt-wizard{max-width:920px}
.wizard-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.wizard-heading h2{margin:4px 0}
.wizard-progress-label{white-space:nowrap;color:var(--accent);font-size:12px;font-weight:750}
.wizard-substeps{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:16px 0 20px;padding-bottom:12px;border-bottom:1px solid var(--line)}
.wizard-substep{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:11px}
.wizard-substep>span{display:grid;place-items:center;flex:0 0 24px;width:24px;height:24px;border-radius:50%;background:var(--surface-soft);font-size:10px;font-weight:750}
.wizard-substep.is-active{color:var(--accent)}
.wizard-substep.is-active>span{background:var(--accent);color:#fff}
.wizard-substep.is-complete>span{background:var(--success-bg);color:var(--success)}
.prompt-wizard-panel h3{margin:0 0 6px}
.prompt-wizard-panel>p{margin:0 0 14px;color:var(--muted);font-size:12px}
.prompt-copy-area{margin-top:18px}
.prompt-copy-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:7px}
.prompt-copy-heading label{margin:0}
.prompt-textarea{min-height:300px;resize:vertical;line-height:1.5}
.wizard-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px;padding-top:14px;border-top:1px solid var(--line)}
.wizard-actions form{display:contents}
.wizard-actions .help{margin:0}
.prompt-wizard-panel[hidden]{display:none!important}
@media(max-width:650px){
    .curriculum-stepper li{align-items:flex-start;padding:8px}
    .curriculum-stepper small{display:none}
    .wizard-heading{align-items:center}
    .wizard-actions{align-items:stretch;flex-direction:column-reverse}
    .wizard-actions .btn{width:100%}
    .prompt-copy-heading{align-items:stretch;flex-direction:column}
    .prompt-copy-heading .btn{width:100%}
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const wizard = document.getElementById('prompt-wizard');
    if (!wizard) return;

    const panels = [...wizard.querySelectorAll('[data-wizard-panel]')];
    const indicators = [...wizard.querySelectorAll('[data-wizard-indicator]')];
    const progressLabel = document.getElementById('wizard-progress-label');
    const initialStep = wizard.dataset.initialStep === 'result' ? 'result' : 'prepare';

    const showStep = step => {
        panels.forEach(panel => {
            panel.hidden = panel.dataset.wizardPanel !== step;
        });
        indicators.forEach(indicator => {
            const active = indicator.dataset.wizardIndicator === step;
            const complete = indicator.dataset.wizardIndicator === 'prepare' && step === 'result';
            indicator.classList.toggle('is-active', active);
            indicator.classList.toggle('is-complete', complete);
            if (active) indicator.setAttribute('aria-current', 'step');
            else indicator.removeAttribute('aria-current');
        });
        if (progressLabel) progressLabel.textContent = step === 'prepare' ? '1 de 2' : '2 de 2';
    };

    wizard.querySelector('[data-wizard-next]')?.addEventListener('click', () => {
        showStep('result');
        document.getElementById('paste-result-title')?.focus();
    });
    wizard.querySelector('[data-wizard-back]')?.addEventListener('click', () => {
        showStep('prepare');
        document.getElementById('prepare-chatgpt-title')?.focus();
    });

    const copyButton = document.getElementById('copyPromptButton');
    const textarea = document.getElementById('promptText');
    const status = document.getElementById('copyPromptStatus');

    copyButton?.addEventListener('click', async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(textarea.value);
            } else {
                textarea.focus();
                textarea.select();
                if (!document.execCommand('copy')) throw new Error('No se pudo copiar automáticamente.');
            }
            if (status) status.textContent = 'Prompt copiado. Pégalo en el chat de ChatGPT donde adjuntaste el PDF.';
        } catch (error) {
            if (status) status.textContent = 'No se pudo copiar automáticamente. Selecciona el texto y usa Ctrl+C.';
        }
    });

    showStep(initialStep);
})();
</script>
@endpush
