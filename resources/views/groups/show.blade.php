@extends('layouts.app')
@section('title', 'Grupo '.$group->name)
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.index') }}">Ciclos</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">{{ $group->name }}</span>
</div>
<a class="cycle-back" href="{{ route('cycles.show', $group->cycle) }}"><x-icon name="arrow-left" size="15" /> Volver a los grupos</a>

<div class="page-head">
    <div>
        <p class="eyebrow"><x-icon name="users" size="12" /> {{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</p>
        <h1>{{ $group->name }}</h1>
        <div class="cycle-item-meta">
            <span><x-icon name="calendar-range" size="12" /> {{ $group->cycle->start_date?->format('d/m/Y') }} – {{ $group->cycle->end_date?->format('d/m/Y') }}</span>
            <span>·</span>
            <span><x-icon name="clock" size="12" /> {{ $group->assigned_at?->format('d/m/Y') ?? 'Sin fecha de asignación' }}</span>
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('cycles.show', $group->cycle) }}"><x-icon name="arrow-left" /> Grupos</a>
        <a class="btn" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a>
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:17px">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Cursos</span><span class="stat-icon"><x-icon name="book-open" /></span></div>
        <div class="stat">{{ $courseCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Modalidades TEC</span><span class="stat-icon"><x-icon name="layers" /></span></div>
        <div class="stat">{{ $tecCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Modalidades TALL</span><span class="stat-icon"><x-icon name="graduation-cap" /></span></div>
        <div class="stat">{{ $tallCount }}</div>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div><h2>Cursos del grupo</h2></div>
        <span class="badge badge-blue">{{ $courseCount }} {{ $courseCount === 1 ? 'curso' : 'cursos' }}</span>
    </div>
    @if($assignments->isNotEmpty())
        <div class="filter-bar">
            <input class="filter-input" id="course-search" type="search" placeholder="Buscar curso…" aria-label="Buscar cursos">
            <select class="filter-input" id="modality-filter" aria-label="Filtrar por modalidad">
                <option value="">Todas las modalidades</option>
                <option value="TEC">Solo TEC</option>
                <option value="TALL">Solo TALL</option>
                <option value="BOTH">TEC y TALL</option>
            </select>
            <span class="help" id="course-count">{{ $assignments->count() }} en esta página</span>
        </div>
        <div class="grid grid-2" id="course-list">
            @foreach($assignments as $assignment)
                @php($types = $assignment->modalities->pluck('modality')->all())
                <article class="course-card" data-search="{{ mb_strtolower($assignment->course->name.' '.$assignment->course->code) }}" data-modalities="{{ implode(',', $types) }}">
                    <div class="course-card-head">
                        <div>
                            <div class="list-title">{{ $assignment->course->name }}</div>
                            <div class="list-meta"><x-icon name="file-text" size="12" /> {{ $assignment->course->code ?: 'Sin código' }}</div>
                        </div>
                        <a class="btn btn-ghost btn-sm" href="{{ route('curricula.show', $assignment) }}"><x-icon name="book-open" /> Malla</a>
                    </div>
                    <div class="actions mt-16">
                        @forelse($assignment->modalities as $modality)
                            <a class="btn btn-secondary btn-sm" href="{{ route('modalities.show', $modality) }}">
                                <span class="badge {{ $modality->modality === 'TEC' ? 'badge-blue' : 'badge-purple' }}">{{ $modality->modality }}</span>
                                NRC {{ $modality->nrc ?: 'Pendiente' }} <x-icon name="arrow-up-right" size="13" />
                            </a>
                        @empty
                            <span class="badge badge-orange">Modalidad pendiente</span>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
        <div class="empty" id="course-empty" hidden><strong>No hay cursos que coincidan.</strong><span>Prueba con otro nombre o modalidad.</span></div>
        @if($assignments->hasPages())
            <x-pagination :paginator="$assignments" />
        @endif
    @else
        <div class="empty">
            <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="book-open" size="20" /></span>
            <strong>Aún no hay cursos en este grupo.</strong>
            <span>Registra varios cursos desde una sola tabla.</span>
            <div class="mt-16"><a class="btn" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a></div>
        </div>
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
            const typeMatch = !selected || (
                selected === 'BOTH'
                    ? types.includes('TEC') && types.includes('TALL')
                    : types.includes(selected)
            );
            const show = textMatch && typeMatch;
            card.hidden = !show;
            if (show) visible++;
        });
        empty.hidden = visible !== 0;
        count.textContent = visible + (visible === 1 ? ' visible' : ' visibles en esta página');
    };

    search.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>
@endpush
