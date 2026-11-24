@extends('layouts.app')
@section('title', 'Importar carga laboral')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Cargas laborales</a><span>›</span><span class="current">Importar</span></div>
<div class="page-head"><div><p class="eyebrow">Paso 1 · Archivo maestro</p><h1>Importar carga laboral</h1><p class="subhead">Sube la distribución que recibiste. El sistema identificará el ciclo, grupo, cursos, NRC y fechas disponibles.</p></div></div>
<section class="card">
    <div class="card-head">
        <div>
            <h2>Archivo de distribución</h2>
            <p class="card-description">Completa una fila por curso en la hoja Cursos. No cambies los encabezados.</p>
        </div>
        <a class="btn btn-secondary btn-sm" href="{{ route('distribution.template') }}"><x-icon name="download" /> Descargar plantilla Excel</a>
    </div>
    <form method="POST" action="{{ route('distribution.preview') }}" enctype="multipart/form-data">
        @csrf
        <label for="file">Archivo Excel (.xlsx o .xls)</label>
        <input class="field" type="file" id="file" name="file" accept=".xlsx,.xls" required>
        <p class="help">La plantilla incluye una hoja Instrucciones con ejemplos y el formato correcto de cada columna.</p>
        <div class="alert alert-warning" style="margin-top:17px"><strong>Horario:</strong> esta versión todavía no extrae días, horas ni ambientes de otras pestañas del archivo. Después de importar, esos bloques deben revisarse en cada modalidad TEC/TALL.</div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('cycles.index') }}"><x-icon name="arrow-left" /> Cancelar</a><button class="btn" type="submit"><x-icon name="file-up" /> Leer carga laboral</button></div>
    </form>
</section>
@endsection