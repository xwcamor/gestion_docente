@extends('layouts.app')

@section('title', 'Prueba de layout')

@section('content')
    <div class="breadcrumbs">
        <a href="{{ route('cycles.index') }}">Cargas laborales</a>
        <span class="current">Prueba de layout</span>
    </div>

    <header class="page-head">
        <div>
            <p class="eyebrow">DIAGNÓSTICO VISUAL</p>
            <h1>Prueba de layout</h1>
            <p class="subhead">Vista mínima para comprobar el comportamiento del menú lateral y el espacio de contenido.</p>
        </div>
    </header>

    <section class="card" aria-labelledby="layout-test-card-title">
        <div class="card-head">
            <div>
                <h2 id="layout-test-card-title">Tarjeta de prueba</h2>
                <p class="card-description">Este es el único contenido de la vista.</p>
            </div>
        </div>
        <p class="muted mb-0">Si esta tarjeta se muestra correctamente junto al sidebar, el layout global funciona en una vista limpia.</p>
    </section>
@endsection
