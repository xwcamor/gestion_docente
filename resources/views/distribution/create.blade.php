@extends('layouts.app')
@section('title', 'Importar distribución académica')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos y cursos</a><span>›</span><span class="current">Importar Excel</span></div>
<div class="page-head"><div><p class="eyebrow">Carga académica · Importación guiada</p><h1>Importar desde tu Excel</h1><p class="subhead">Sube uno de tus formatos institucionales. Leeremos la pestaña Cursos para registrar los cursos, bloques, NRC y fechas sin volver a escribirlos.</p></div></div>
<section class="card">
    <div class="card-head"><div><h2>Selecciona el archivo institucional</h2><p class="card-description">Debe contener una pestaña llamada Cursos con las columnas CURSO, CODIGO, BLOQUE TEC, NRC TEC, BLOQUE TALLER, BLOQUE, NRC TALLE y FECHA.</p></div><span class="badge badge-green">Lectura automática</span></div>
    <form method="POST" action="{{ route('distribution.preview') }}" enctype="multipart/form-data">
        @csrf
        <label for="file">Archivo Excel (.xlsx o .xls)</label>
        <input class="field" type="file" id="file" name="file" accept=".xlsx,.xls" required>
        <p class="help">Se leerá únicamente la pestaña Cursos para esta importación. Las demás hojas se conservan en el archivo original.</p>
        <div class="alert alert-info" style="margin-top:17px"><strong>Importante:</strong> la pestaña Cursos contiene NRC y fechas, pero no los días ni las horas de clase. Esos horarios se configurarán después, sin inventar información.</div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('cycles.index') }}"><x-icon name="arrow-left" /> Cancelar</a><button class="btn" type="submit"><x-icon name="file-text" /> Leer Excel y mostrar vista previa</button></div>
    </form>
</section>
@endsection