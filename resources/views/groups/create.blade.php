@extends('layouts.app')
@section('title', 'Crear grupo')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $cycle) }}">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</a><span>›</span><span class="current">Nuevo grupo</span></div>
<div class="page-head"><div><p class="eyebrow">Organización académica</p><h1>Agregar grupo</h1><p class="subhead">Crea un grupo solo cuando tengas una sección distinta. Si tu Excel contiene todos los cursos de una misma asignación, impórtalo desde la pantalla de ciclos.</p></div></div>
<section class="card">
    <div class="alert alert-info"><strong>¿Tienes tu Excel institucional?</strong> No registres los cursos manualmente. La importación puede crear el grupo y todos sus cursos a partir de la pestaña Cursos.</div>
    <div class="actions"><a class="btn" href="{{ route('distribution.create') }}">Importar Excel institucional</a><a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}">Volver al ciclo</a></div>
</section>
<section class="card">
    <h2>Crear grupo manualmente</h2><p class="card-description">Usa esta opción únicamente si necesitas separar cursos en otro grupo.</p>
    <form method="POST" action="{{ route('groups.store', $cycle) }}">@csrf
        <div class="form-row"><div><label for="name">Nombre o código del grupo</label><input class="field" id="name" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Ej. Grupo 01"></div><div><label for="assigned_at">Fecha de asignación (opcional)</label><input class="field" id="assigned_at" type="date" name="assigned_at" value="{{ old('assigned_at') }}"></div></div>
        <label for="notes">Observaciones (opcional)</label><textarea class="field" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}">Cancelar</a><button class="btn" type="submit">Guardar grupo</button></div>
    </form>
</section>
@endsection