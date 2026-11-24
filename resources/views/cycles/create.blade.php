@extends('layouts.app')
@section('title', 'Crear ciclo')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><span class="current">Nuevo ciclo</span></div>
<div class="page-head">
    <div><p class="eyebrow">Datos del periodo</p><h1>Crear ciclo académico</h1><p class="subhead">Registra el periodo una sola vez. Después podrás agregar todos los grupos que te asignen.</p></div>
</div>

<section class="card">
    <div class="card-head"><div><h2>Datos del ciclo</h2><p class="card-description">Usa el año y el código que aparecen en tu programación académica.</p></div><span class="badge badge-blue">Requerido</span></div>
    <form method="POST" action="{{ route('cycles.store') }}">
        @csrf
        <div class="form-row">
            <div><label for="academic_year">Año académico</label><input class="field" id="academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', date('Y')) }}" required><p class="help">Ejemplo: 2027.</p></div>
            <div><label for="cycle_code">Código del ciclo</label><input class="field" id="cycle_code" name="cycle_code" value="{{ old('cycle_code', '10') }}" maxlength="20" required placeholder="10 o 20"><p class="help">Ejemplo: ciclo 10 o ciclo 20.</p></div>
        </div>
        <div class="form-row">
            <div><label for="start_date">Inicio del rango de carga laboral</label><input class="field" id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required></div>
            <div><label for="end_date">Fin del rango de carga laboral</label><input class="field" id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" min="{{ old('start_date') }}" required></div>
        </div>
        <div class="alert alert-info mt-16">Este es el rango general de la carga laboral. No obliga a que todos los cursos terminen el mismo día. Cada curso tendrá sus propias fechas reales de dictado y su horario.</div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('cycles.index') }}">← Cancelar y volver</a>
            <button class="btn" type="submit">Guardar ciclo y continuar →</button>
        </div>
    </form>
</section>

<script>
(() => {
    const start = document.getElementById('start_date');
    const end = document.getElementById('end_date');
    if (!start || !end) return;
    const sync = () => {
        end.min = start.value || '';
        end.setCustomValidity(start.value && end.value && end.value < start.value
            ? 'La fecha de fin debe ser igual o posterior a la fecha de inicio.'
            : '');
    };
    start.addEventListener('change', sync);
    end.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
