@extends('layouts.app')
@section('title', 'Ciclos académicos')
@section('content')
<div class="page-head">
    <div><p class="eyebrow">Organización académica</p><h1>Ciclos académicos</h1><p class="subhead">Organiza tus periodos, crea tus grupos y registra los cursos en una sola tabla rápida. La única importación será la lista de alumnos.</p></div>
    <div class="actions"><a class="btn" href="{{ route('cycles.create') }}">＋ Crear ciclo</a></div>
</div>
<section class="card">
    <div class="card-head"><div><h2>Cómo trabajarás</h2><p class="card-description">Sin importar hojas de distribución ni llenar un formulario por curso.</p></div><span class="badge badge-green">Flujo simplificado</span></div>
    <div class="grid grid-4">
        <div><span class="badge badge-blue">01</span><h3 style="margin-top:9px">Crea el ciclo</h3><p class="help">Indica año, periodo y fechas generales.</p></div>
        <div><span class="badge badge-blue">02</span><h3 style="margin-top:9px">Crea el grupo</h3><p class="help">Organiza la sección o aula.</p></div>
        <div><span class="badge badge-blue">03</span><h3 style="margin-top:9px">Digita los cursos</h3><p class="help">Tabla rápida con código, TEC/TALL, bloques, NRC y fechas.</p></div>
        <div><span class="badge badge-blue">04</span><h3 style="margin-top:9px">Configura horarios</h3><p class="help">Calcula sesiones, excluye feriados e importa alumnos por modalidad.</p></div>
    </div>
</section>
<div class="grid grid-4" style="margin-bottom:16px">
    <div class="stat-card"><div class="stat-label">Ciclos registrados</div><div class="stat">{{ $cycles->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Años disponibles</div><div class="stat">{{ $years->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Grupos en pantalla</div><div class="stat">{{ $cycles->sum('groups_count') }}</div></div>
    <div class="stat-card"><div class="stat-label">Siguiente paso</div><div class="stat" style="font-size:14px;margin-top:9px">Registrar cursos</div></div>
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
        <div class="empty"><strong>{{ request()->hasAny(['q','year']) ? 'No encontramos ciclos con esos filtros.' : 'Todavía no tienes ciclos registrados.' }}</strong><span>{{ request()->hasAny(['q','year']) ? 'Prueba con otro año o código.' : 'Crea tu primer ciclo para comenzar.' }}</span><div class="mt-16"><a class="btn" href="{{ route('cycles.create') }}">Crear primer ciclo</a></div></div>
    @endforelse
</section>
@endsection
