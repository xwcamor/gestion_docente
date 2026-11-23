@extends('layouts.app')
@section('title', 'Detalle del ciclo')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos académicos</a><span>›</span><span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span></div>
<div class="page-head">
    <div><p class="eyebrow">Paso 2 de 4 · Grupos</p><h1>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h1><p class="subhead">{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}. Aquí se organizan los grupos que pertenecen a este periodo.</p></div>
    <div class="actions"><a class="btn btn-secondary" href="{{ route('cycles.index') }}">← Todos los ciclos</a><a class="btn" href="{{ route('groups.create', $cycle) }}">＋ Agregar grupo</a></div>
</div>
<div class="grid grid-3" style="margin-bottom:17px">
    <div class="stat-card"><div class="stat-label">Grupos registrados</div><div class="stat">{{ $cycle->groups->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Cursos asignados</div><div class="stat">{{ $cycle->groups->sum(fn($group) => $group->assignments->count()) }}</div></div>
    <div class="stat-card"><div class="stat-label">Modalidades activas</div><div class="stat">{{ $cycle->groups->sum(fn($group) => $group->assignments->sum(fn($assignment) => $assignment->modalities->count())) }}</div></div>
</div>

<section class="card">
    <div class="card-head"><div><h2>Grupos de este ciclo</h2><p class="card-description">Abre un grupo para asignar cursos y organizar TEC/TALL.</p></div><span class="badge">{{ $cycle->groups->count() }} grupos</span></div>
    @forelse($cycle->groups as $group)
        <div class="list-row">
            <div><div class="list-title">{{ $group->name }}</div><div class="list-meta">Asignado {{ $group->assigned_at?->format('d/m/Y') ?? 'sin fecha' }} · {{ $group->assignments->count() }} cursos · {{ $group->assignments->sum(fn($a) => $a->modalities->count()) }} modalidades</div></div>
            <a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}">Administrar grupo →</a>
        </div>
    @empty
        <div class="empty"><strong>Este ciclo todavía no tiene grupos.</strong><span>Cuando recibas una asignación, crea aquí el grupo correspondiente.</span><div class="mt-16"><a class="btn" href="{{ route('groups.create', $cycle) }}">Crear primer grupo</a></div></div>
    @endforelse
</section>
@endsection
