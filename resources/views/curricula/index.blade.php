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
.curriculum-upload-card{max-width:760px}
.curriculum-upload-card .field{margin-top:7px}
.curriculum-upload-card .form-actions{margin-top:18px}
@media(max-width:600px){.curriculum-upload-card .form-actions,.curriculum-upload-card .form-actions .btn{width:100%}}
</style>
@endpush
