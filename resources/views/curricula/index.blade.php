@extends('layouts.app')
@section('title', 'Programación curricular')
@section('content')
<div class="page-head">
    <div>
        <p class="muted small">
            <a href="{{ route('groups.show', $assignment->group) }}">{{ $assignment->group->name }}</a>
            / {{ $assignment->course->name }}
        </p>
        <h1>Programación curricular</h1>
        <p class="subhead">Sube el PDF oficial de la malla para iniciar la importación.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $assignment->group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>

<ol class="curriculum-stepper" aria-label="Pasos de importación">
    <li class="is-active" aria-current="step"><span class="step-number">1</span><span><strong>PDF</strong><small>Subir documento</small></span></li>
    <li><span class="step-number">2</span><span><strong>ChatGPT</strong><small>Generar JSON</small></span></li>
    <li><span class="step-number">3</span><span><strong>Importar</strong><small>Revisar datos</small></span></li>
</ol>

<section class="card curriculum-upload-card">
    <form method="POST" enctype="multipart/form-data" action="{{ route('curricula.extract', $assignment) }}" data-loading-form="1">
        @csrf
        <label for="curriculum_pdf">PDF de la malla curricular <span class="muted">*</span></label>
        <input
            class="field"
            id="curriculum_pdf"
            type="file"
            name="curriculum_pdf"
            accept=".pdf,application/pdf"
            required
            aria-describedby="curriculum-pdf-help"
        >
        @error('curriculum_pdf')
            <p class="field-error">{{ $message }}</p>
        @enderror
        <p class="help" id="curriculum-pdf-help">Selecciona el documento oficial en formato PDF (máximo 20 MB).</p>
        <div class="form-actions">
            <button class="btn" type="submit"><x-icon name="file-up" /> Analizar PDF y continuar</button>
        </div>
    </form>
</section>
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
.curriculum-upload-card{max-width:760px}
.curriculum-upload-card .field{margin-top:7px}
.curriculum-upload-card .form-actions{margin-top:18px}
@media(max-width:600px){
    .curriculum-stepper li{align-items:flex-start;padding:8px}
    .curriculum-stepper small{display:none}
    .curriculum-upload-card .form-actions,.curriculum-upload-card .form-actions .btn{width:100%}
}
</style>
@endpush
