@extends('layouts.app')
@section('title', 'Importar carga laboral')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Cargas laborales</a><span>›</span><span class="current">Importar</span></div>
<div class="page-head"><div><p class="eyebrow">Paso 1 · Archivo maestro</p><h1>Importar carga laboral</h1><p class="subhead">Sube el archivo maestro con cursos, NRC, fechas y bloques horarios.</p></div></div>
<section class="card">
    <div class="card-head workload-template-head">
        <div>
            <h2>Archivo de distribución</h2>
            <p class="card-description">Completa Cursos y Horarios en el formato de la plantilla. No cambies los encabezados ni los nombres de las hojas.</p>
        </div>
        <a class="btn btn-secondary btn-sm" href="{{ route('distribution.template') }}"><x-icon name="download" /> Descargar plantilla Excel</a>
    </div>
    <form method="POST" action="{{ route('distribution.preview') }}" enctype="multipart/form-data">
        @csrf
        <label for="file">Archivo Excel (.xlsx o .xls)</label>
        <input class="field" type="file" id="file" name="file" accept=".xlsx,.xls" required>
        @error('file')<p class="field-error">{{ $message }}</p>@enderror
        <p class="help">La plantilla incluye las hojas Cursos, Horarios e Instrucciones. Los ejemplos están separados y no se importan como datos reales.</p>
        <div class="alert alert-info mt-16"><strong>Horarios:</strong> registra una fila por cada día y bloque en la hoja Horarios. El sistema los relacionará por CODIGO y modalidad (TEC/TALL), calculará las horas semanales y generará el calendario con los feriados excluidos.</div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('cycles.index') }}"><x-icon name="arrow-left" /> Cancelar</a><button class="btn" type="submit"><x-icon name="file-up" /> Leer carga laboral</button></div>
    </form>
</section>
@push('styles')
<style>
@media(max-width:640px){
    .workload-template-head{align-items:flex-start;flex-direction:column}
    .workload-template-head>.btn{width:100%}
}
</style>
@endpush
@endsection