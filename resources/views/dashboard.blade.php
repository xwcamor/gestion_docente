@extends('layouts.app')
@section('title', 'Resumen')
@section('content')
<div class="page-head">
    <div><p class="eyebrow">Tu espacio de trabajo</p><h1>Hola, {{ auth()->user()->name }}</h1><p class="subhead">Desde aquí puedes continuar con tus ciclos, grupos, cursos y documentos.</p></div>
    <div class="actions"><a class="btn btn-secondary" href="{{ route('cycles.index') }}">Ver mis ciclos</a><a class="btn" href="{{ route('cycles.create') }}">＋ Crear ciclo</a></div>
</div>

<div class="grid grid-4 dashboard-stats" style="margin-bottom:19px">
    <div class="stat-card"><div class="stat-label">Ciclos académicos</div><div class="stat">{{ $cycles->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Grupos registrados</div><div class="stat">{{ $groupCount }}</div></div>
    <div class="stat-card"><div class="stat-label">Modalidades</div><div class="stat">{{ $modalities->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Feriados próximos</div><div class="stat">{{ $upcoming->count() }}</div></div>
</div>

<section class="card dashboard-guide">
    <div class="card-head"><div><h2>Cómo trabajar en el sistema</h2><p class="card-description">Sigue este orden. Cada pantalla te lleva al siguiente paso.</p></div><span class="badge badge-blue">Flujo de trabajo</span></div>
    <div class="grid grid-4">
        <a class="course-card" href="{{ route('cycles.index') }}"><span class="badge badge-blue">01</span><h3 style="margin-top:10px">Ciclos</h3><p class="help">Registra el año y periodo académico.</p></a>
        <a class="course-card" href="{{ route('cycles.index') }}"><span class="badge badge-blue">02</span><h3 style="margin-top:10px">Grupos</h3><p class="help">Agrega los grupos que te asignen a cada ciclo.</p></a>
        <a class="course-card" href="{{ route('cycles.index') }}"><span class="badge badge-blue">03</span><h3 style="margin-top:10px">Cursos</h3><p class="help">Registra cada curso y elige TEC, TALL o ambos.</p></a>
        <a class="course-card" href="{{ route('cycles.index') }}"><span class="badge badge-blue">04</span><h3 style="margin-top:10px">Gestión</h3><p class="help">Importa alumnos, administra notas y genera Excel.</p></a>
    </div>
</section>

<div class="grid grid-2">
    <section class="card">
        <div class="card-head"><div><h2>Mis ciclos</h2><p class="card-description">Tus periodos académicos más recientes.</p></div><a href="{{ route('cycles.index') }}">Ver todos →</a></div>
        @forelse($cycles->take(5) as $cycle)
            <div class="list-row"><div><div class="list-title">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</div><div class="list-meta">{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }} · {{ $cycle->groups_count }} grupos</div></div><a class="btn btn-secondary btn-sm" href="{{ route('cycles.show', $cycle) }}">Abrir</a></div>
        @empty
            <div class="empty"><strong>Comienza por crear tu primer ciclo.</strong><span>Luego podrás agregar grupos y cursos.</span><div class="mt-16"><a class="btn" href="{{ route('cycles.create') }}">Crear ciclo</a></div></div>
        @endforelse
    </section>
    <section class="card">
        <div class="card-head"><div><h2>Feriados próximos</h2><p class="card-description">Días que pueden modificar el calendario de clases.</p></div><a href="{{ route('holidays.index') }}">Administrar →</a></div>
        @forelse($upcoming as $holiday)
            <div class="list-row"><div><div class="list-title">{{ $holiday->name }}</div><div class="list-meta">{{ $holiday->holiday_date->format('d/m/Y') }} · {{ $holiday->scope }}</div></div><span class="badge badge-orange">Bloquea clases</span></div>
        @empty <div class="empty"><strong>No hay feriados próximos registrados.</strong><span>El calendario se mantiene según los días de clase definidos.</span></div> @endforelse
    </section>
</div>

<section class="card">
    <div class="card-head"><div><h2>Grupos recientes</h2><p class="card-description">Abre un grupo para continuar donde lo dejaste.</p></div></div>
    @if($groups->isEmpty())
        <div class="empty"><strong>Aún no tienes grupos.</strong><span>Cuando crees tu primer ciclo, podrás registrar los grupos asignados.</span></div>
    @else
        <div class="table-wrap"><table><thead><tr><th>Grupo</th><th>Ciclo</th><th>Cursos</th><th></th></tr></thead><tbody>
            @foreach($groups as $group)
                <tr><td><strong>{{ $group->name }}</strong></td><td>{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</td><td>{{ $group->assignments->count() }}</td><td><a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}">Abrir grupo →</a></td></tr>
            @endforeach
        </tbody></table></div>
    @endif
</section>
@push('styles')
<style>
@media(max-width:680px){
    .dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
    .dashboard-stats .stat-card{min-width:0}
    .dashboard-guide{display:none}
    .dashboard-stats+.dashboard-guide{display:none}
    .dashboard-stats .stat-label{min-height:26px;line-height:1.25}
    .dashboard-stats .stat{font-size:23px}
}
</style>
@endpush
@endsection
