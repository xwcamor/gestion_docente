@extends('layouts.app')
@section('title','Resumen')
@section('content')
<div class="page-head"><div><h1>Resumen académico</h1><p class="subhead">Bienvenido, {{ auth()->user()->name }}. Aquí tienes el estado de tus ciclos, grupos y cursos.</p></div><a class="btn" href="{{ route('cycles.index') }}">Gestionar ciclos</a></div>
<div class="grid grid-4">
    <div class="card"><div class="stat-label">Ciclos registrados</div><div class="stat">{{ $cycles->count() }}</div></div>
    <div class="card"><div class="stat-label">Grupos recientes</div><div class="stat">{{ $groups->count() }}</div></div>
    <div class="card"><div class="stat-label">Cursos / modalidades</div><div class="stat">{{ $modalities->count() }}</div></div>
    <div class="card"><div class="stat-label">Feriados próximos (45 días)</div><div class="stat">{{ $upcoming->count() }}</div></div>
</div>
<div class="grid grid-2">
    <section class="card"><div class="page-head"><h2>Mis ciclos</h2><a href="{{ route('cycles.index') }}">Ver todos</a></div>
        @forelse($cycles as $cycle)
            <div style="padding:12px 0;border-bottom:1px solid #edf0f4">
                <strong>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</strong>
                <div class="muted small">{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }} · {{ $cycle->groups_count }} grupos</div>
                <a href="{{ route('cycles.show',$cycle) }}">Abrir ciclo</a>
            </div>
        @empty <div class="empty">Todavía no has registrado ciclos.</div> @endforelse
    </section>
    <section class="card"><h2>Feriados próximos</h2>
        @forelse($upcoming as $holiday)
            <div style="padding:10px 0;border-bottom:1px solid #edf0f4"><strong>{{ $holiday->holiday_date->format('d/m/Y') }}</strong> · {{ $holiday->name }}<div class="muted small">{{ $holiday->scope }}</div></div>
        @empty <div class="empty">No hay feriados o cierres próximos registrados.</div> @endforelse
        <p class="mt-16 mb-0"><a href="{{ route('holidays.index') }}">Administrar calendario</a></p>
    </section>
</div>
<section class="card"><h2>Grupos recientes</h2>
    @if($groups->isEmpty())<div class="empty">Crea un ciclo y luego registra uno o más grupos.</div>
    @else<div class="table-wrap"><table><thead><tr><th>Grupo</th><th>Ciclo</th><th>Cursos asignados</th><th></th></tr></thead><tbody>
    @foreach($groups as $group)<tr><td><strong>{{ $group->name }}</strong></td><td>{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</td><td>{{ $group->assignments->count() }}</td><td><a href="{{ route('groups.show',$group) }}">Abrir grupo</a></td></tr>@endforeach
    </tbody></table></div>@endif
</section>
@endsection
