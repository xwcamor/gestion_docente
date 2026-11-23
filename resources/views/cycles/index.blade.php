@extends('layouts.app')
@section('title', 'Ciclos académicos')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Organización académica</p>
        <h1>Ciclos académicos</h1>
        <p class="subhead">Primero crea el ciclo. Dentro de cada ciclo podrás registrar todos tus grupos y sus cursos.</p>
    </div>
    <a class="btn" href="{{ route('cycles.create') }}">＋ Crear ciclo</a>
</div>

<div class="grid grid-4" style="margin-bottom:18px">
    <div class="stat-card"><div class="stat-label">Ciclos registrados</div><div class="stat">{{ $cycles->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Años disponibles</div><div class="stat">{{ $years->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Grupos en pantalla</div><div class="stat">{{ $cycles->sum('groups_count') }}</div></div>
    <div class="stat-card"><div class="stat-label">Siguiente paso</div><div style="font-size:14px;font-weight:700;margin-top:9px;color:#17365d">Abrir un ciclo</div></div>
</div>

<section class="card">
    <div class="card-head">
        <div><h2>Mis ciclos</h2><p class="card-description">Busca por año o código para encontrar rápidamente el periodo que necesitas.</p></div>
        <span class="badge">{{ $cycles->count() }} resultados</span>
    </div>
    <form method="GET" action="{{ route('cycles.index') }}" class="filter-bar">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por año o código…" aria-label="Buscar ciclo">
        <select class="filter-input" name="year" aria-label="Filtrar por año">
            <option value="">Todos los años</option>
            @foreach($years as $year)<option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>@endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit">Aplicar filtros</button>
        @if(request()->hasAny(['q','year']))<a class="btn btn-ghost btn-sm" href="{{ route('cycles.index') }}">Limpiar</a>@endif
    </form>

    @forelse($cycles as $cycle)
        <div class="list-row">
            <div>
                <div class="list-title">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</div>
                <div class="list-meta">{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }} · {{ $cycle->groups_count }} {{ $cycle->groups_count === 1 ? 'grupo' : 'grupos' }}</div>
            </div>
            <div class="actions">
                <span class="badge {{ $cycle->status === 'active' ? 'badge-green' : '' }}">{{ $cycle->status ?: 'Registrado' }}</span>
                <a class="btn btn-secondary btn-sm" href="{{ route('cycles.show', $cycle) }}">Abrir ciclo →</a>
            </div>
        </div>
    @empty
        <div class="empty">
            <strong>{{ request()->hasAny(['q','year']) ? 'No encontramos ciclos con esos filtros.' : 'Todavía no tienes ciclos registrados.' }}</strong>
            <span>{{ request()->hasAny(['q','year']) ? 'Prueba con otro año o código.' : 'Empieza creando tu primer ciclo académico.' }}</span>
            @if(!request()->hasAny(['q','year']))<div class="mt-16"><a class="btn" href="{{ route('cycles.create') }}">Crear mi primer ciclo</a></div>@endif
        </div>
    @endforelse
</section>

<section class="card">
    <h2>Cómo se organiza la información</h2>
    <div class="grid grid-4" style="margin-top:14px">
        <div><span class="badge badge-blue">01</span><h3 style="margin-top:9px">Ciclo</h3><p class="help">Año y periodo académico.</p></div>
        <div><span class="badge badge-blue">02</span><h3 style="margin-top:9px">Grupo</h3><p class="help">Cada aula o grupo asignado dentro del ciclo.</p></div>
        <div><span class="badge badge-blue">03</span><h3 style="margin-top:9px">Curso</h3><p class="help">Cada curso puede tener TEC, TALL o ambas.</p></div>
        <div><span class="badge badge-blue">04</span><h3 style="margin-top:9px">Gestión</h3><p class="help">Alumnos, calendario, evaluaciones y Excel.</p></div>
    </div>
</section>
@endsection
