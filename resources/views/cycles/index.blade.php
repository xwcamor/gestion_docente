@extends('layouts.app')
@section('title', 'Ciclos académicos')
@section('content')
<div class="page-head">
    <div><p class="eyebrow">Organización académica</p><h1>Ciclos académicos</h1><p class="subhead">Importa la pestaña Cursos de tu Excel institucional y deja que el sistema registre cursos, modalidades, NRC y fechas.</p></div>
    <div class="actions"><a class="btn" href="{{ route('distribution.create') }}">⇧ Importar Excel</a><a class="btn btn-secondary" href="{{ route('cycles.create') }}">Crear manualmente</a></div>
</div>
<section class="card" style="border-color:#d7e4f1;background:#fbfdff">
    <div class="card-head"><div><h2>Tu flujo simplificado</h2><p class="card-description">Ya no necesitas crear cada curso uno por uno.</p></div><span class="badge badge-green">Recomendado</span></div>
    <div class="grid grid-4">
        <div><span class="badge badge-blue">01</span><h3 style="margin-top:9px">Sube el Excel</h3><p class="help">Se lee la pestaña Cursos.</p></div>
        <div><span class="badge badge-blue">02</span><h3 style="margin-top:9px">Revisa datos</h3><p class="help">Comprueba cursos, NRC y periodos.</p></div>
        <div><span class="badge badge-blue">03</span><h3 style="margin-top:9px">Confirma</h3><p class="help">Se crean ciclo, grupo y modalidades.</p></div>
        <div><span class="badge badge-blue">04</span><h3 style="margin-top:9px">Completa horarios</h3><p class="help">Solo días, horas y ambientes faltantes.</p></div>
    </div>
</section>
<div class="grid grid-4" style="margin-bottom:18px">
    <div class="stat-card"><div class="stat-label">Ciclos registrados</div><div class="stat">{{ $cycles->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Años disponibles</div><div class="stat">{{ $years->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Grupos en pantalla</div><div class="stat">{{ $cycles->sum('groups_count') }}</div></div>
    <div class="stat-card"><div class="stat-label">Siguiente paso</div><div style="font-size:14px;font-weight:700;margin-top:9px;color:#17365d">Completar horarios</div></div>
</div>
<section class="card">
    <div class="card-head"><div><h2>Mis ciclos</h2><p class="card-description">Busca por año o código para encontrar rápidamente el periodo que necesitas.</p></div><span class="badge">{{ $cycles->count() }} resultados</span></div>
    <form method="GET" action="{{ route('cycles.index') }}" class="filter-bar">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por año o código…" aria-label="Buscar ciclo">
        <select class="filter-input" name="year" aria-label="Filtrar por año"><option value="">Todos los años</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>@endforeach</select>
        <button class="btn btn-secondary btn-sm" type="submit">Aplicar filtros</button>
        @if(request()->hasAny(['q','year']))<a class="btn btn-ghost btn-sm" href="{{ route('cycles.index') }}">Limpiar</a>@endif
    </form>
    @forelse($cycles as $cycle)
        <div class="list-row"><div><div class="list-title">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</div><div class="list-meta">{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }} · {{ $cycle->groups_count }} {{ $cycle->groups_count === 1 ? 'grupo' : 'grupos' }}</div></div><div class="actions"><span class="badge {{ $cycle->status === 'active' ? 'badge-green' : '' }}">{{ $cycle->status ?: 'Registrado' }}</span><a class="btn btn-secondary btn-sm" href="{{ route('cycles.show', $cycle) }}">Abrir ciclo →</a></div></div>
    @empty
        <div class="empty"><strong>{{ request()->hasAny(['q','year']) ? 'No encontramos ciclos con esos filtros.' : 'Todavía no tienes ciclos registrados.' }}</strong><span>{{ request()->hasAny(['q','year']) ? 'Prueba con otro año o código.' : 'Sube tu Excel institucional para empezar.' }}</span><div class="mt-16"><a class="btn" href="{{ route('distribution.create') }}">Importar mi Excel</a></div></div>
    @endforelse
</section>
@endsection