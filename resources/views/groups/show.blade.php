@extends('layouts.app')
@section('title', 'Grupo '.$group->name)
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</a><span>›</span><span class="current">{{ $group->name }}</span></div>
<div class="page-head">
    <div><p class="eyebrow">Paso 3 de 4 · Cursos</p><h1>{{ $group->name }}</h1><p class="subhead">{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }} · {{ $group->assigned_at?->format('d/m/Y') ?? 'Fecha de asignación no indicada' }}</p></div>
    <div class="actions"><a class="btn btn-secondary" href="{{ route('cycles.show', $group->cycle) }}">← Volver al ciclo</a><a class="btn" href="{{ route('assignments.quick', $group) }}">＋ Carga rápida de cursos</a></div>
</div>

<div class="grid grid-3" style="margin-bottom:17px">
    <div class="stat-card"><div class="stat-label">Cursos de este grupo</div><div class="stat">{{ $group->assignments->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Modalidades TEC</div><div class="stat">{{ $group->assignments->sum(fn($a) => $a->modalities->where('modality', 'TEC')->count()) }}</div></div>
    <div class="stat-card"><div class="stat-label">Modalidades TALL</div><div class="stat">{{ $group->assignments->sum(fn($a) => $a->modalities->where('modality', 'TALL')->count()) }}</div></div>
</div>

<section class="card">
    <div class="card-head"><div><h2>Cursos asignados</h2><p class="card-description">Selecciona una modalidad para trabajar con su calendario, alumnos, notas y documentos.</p></div><span class="badge">{{ $group->assignments->count() }} cursos</span></div>
    @if($group->assignments->isNotEmpty())
        <div class="filter-bar">
            <input class="filter-input" id="course-search" type="search" placeholder="Buscar por nombre o código…" aria-label="Buscar cursos">
            <select class="filter-input" id="modality-filter" aria-label="Filtrar por modalidad">
                <option value="">Todas las modalidades</option><option value="TEC">Solo con TEC</option><option value="TALL">Solo con TALL</option><option value="BOTH">TEC y TALL</option>
            </select>
            <span class="help" id="course-count">{{ $group->assignments->count() }} cursos</span>
        </div>
        <div class="grid grid-2" id="course-list">
            @foreach($group->assignments as $assignment)
                @php($types = $assignment->modalities->pluck('modality')->all())
                <article class="course-card" data-search="{{ strtolower($assignment->course->name.' '.$assignment->course->code) }}" data-modalities="{{ implode(',', $types) }}">
                    <div class="course-card-head">
                        <div><div class="list-title">{{ $assignment->course->name }}</div><div class="list-meta">{{ $assignment->course->code ?: 'Sin código' }}</div></div>
                        <a class="btn btn-ghost btn-sm" href="{{ route('curricula.show', $assignment) }}">Malla curricular</a>
                    </div>
                    <div class="actions mt-16">
                        @forelse($assignment->modalities as $modality)
                            <a class="btn btn-secondary btn-sm" href="{{ route('modalities.show', $modality) }}"><span class="badge {{ $modality->modality === 'TEC' ? 'badge-blue' : 'badge-green' }}">{{ $modality->modality }}</span> NRC {{ $modality->nrc ?: 'Pendiente' }} →</a>
                        @empty
                            <span class="badge badge-orange">Modalidad pendiente</span>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
        <div class="empty" id="course-empty" hidden><strong>No hay cursos que coincidan.</strong><span>Prueba con otro nombre o filtro.</span></div>
    @else
        <div class="empty"><strong>Aún no has agregado cursos a este grupo.</strong><span>Registra el primer curso y selecciona si tendrá TEC, TALL o ambas.</span><div class="mt-16"><a class="btn" href="{{ route('assignments.quick', $group) }}">Registrar primeros cursos</a></div></div>
    @endif
</section>
@endsection

@push('scripts')
<script>
(() => {
    const search = document.getElementById('course-search');
    const filter = document.getElementById('modality-filter');
    if (!search || !filter) return;
    const cards = [...document.querySelectorAll('.course-card')];
    const empty = document.getElementById('course-empty');
    const count = document.getElementById('course-count');
    const apply = () => {
        const term = search.value.trim().toLocaleLowerCase();
        const selected = filter.value;
        let visible = 0;
        cards.forEach(card => {
            const textMatch = card.dataset.search.includes(term);
            const types = card.dataset.modalities.split(',').filter(Boolean);
            const typeMatch = !selected || (selected === 'BOTH' ? types.includes('TEC') && types.includes('TALL') : types.includes(selected));
            const show = textMatch && typeMatch;
            card.hidden = !show;
            if (show) visible++;
        });
        empty.hidden = visible !== 0;
        count.textContent = visible + (visible === 1 ? ' curso' : ' cursos');
    };
    search.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>
@endpush
