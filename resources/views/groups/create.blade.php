@extends('layouts.app')
@section('title', 'Crear grupo')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $cycle) }}">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</a><span>›</span><span class="current">Nuevo grupo</span></div>
<div class="page-head"><div><p class="eyebrow">Paso 2 de 4 · Organización académica</p><h1>Agregar grupo</h1><p class="subhead">Un grupo representa la sección o aula que te han asignado dentro de este ciclo.</p></div></div>
<section class="card">
    <div class="card-head"><div><h2>Datos del grupo</h2><p class="card-description">No necesitas registrar aquí los cursos; los agregarás en la siguiente pantalla.</p></div><span class="badge badge-blue">{{ $cycle->academic_year }} · {{ $cycle->cycle_code }}</span></div>
    <form method="POST" action="{{ route('groups.store', $cycle) }}">
        @csrf
        <div class="form-row">
            <div><label for="name">Nombre o código del grupo</label><input class="field" id="name" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Ej. Grupo 01 / NRC grupo"><p class="help">Usa un nombre que reconozcas fácilmente.</p></div>
            <div><label for="assigned_at">Fecha de asignación</label><input class="field" id="assigned_at" type="date" name="assigned_at" value="{{ old('assigned_at') }}"><p class="help">Puedes dejarlo vacío si aún no tienes la fecha.</p></div>
        </div>
        <label for="notes">Observaciones (opcional)</label><textarea class="field" id="notes" name="notes" rows="3" placeholder="Información de la asignación que quieras recordar…">{{ old('notes') }}</textarea>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}">← Volver al ciclo</a><button class="btn" type="submit">Guardar grupo →</button></div>
    </form>
</section>
@endsection
