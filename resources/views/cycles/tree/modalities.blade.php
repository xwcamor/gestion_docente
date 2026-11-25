@forelse($modalities as $modality)
    @php($classSchedules = $modality->schedules->where('block_type', 'CLASS'))
    @php($daysLabel = $classSchedules->pluck('day_of_week')->unique()->sort()->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? '')->filter()->implode(' · '))
    @php($scheduleLabel = $classSchedules->map(fn ($item) => substr((string) $item->start_time, 0, 5).'–'.substr((string) $item->end_time, 0, 5))->unique()->implode(', '))
    @php($modalityReady = $modality->start_date && $modality->end_date && $classSchedules->isNotEmpty())
    <div class="workload-tree-modality">
        <span class="tree-branch-mark"></span>
        <span class="tree-modality-tag {{ $modality->modality === 'TEC' ? 'is-tec' : 'is-tall' }}">{{ $modality->modality }}</span>
        <div class="tree-modality-copy">
            <strong>{{ $modality->modality === 'TEC' ? 'Teoría' : 'Taller / práctica' }} · NRC {{ $modality->nrc ?: 'pendiente' }}</strong>
            <small>{{ $modality->start_date?->format('d/m/Y') ?: 'Sin inicio' }} – {{ $modality->end_date?->format('d/m/Y') ?: 'Sin fin' }}</small>
            <small>
                @if($classSchedules->isNotEmpty())
                    {{ $daysLabel }} · {{ $scheduleLabel }} · {{ number_format((float) $modality->total_hours, 2) }} h/sem.
                @else
                    Horario pendiente
                @endif
                @if($modality->block_code) · Bloque {{ $modality->block_code }}@endif
                @if($modality->block_reference) · Ref. {{ $modality->block_reference }}@endif
            </small>
            @if($modality->documents_count > 0)
                <small>{{ $modality->documents_count }} documentos generados</small>
            @endif
        </div>
        <span class="tree-course-status {{ $modalityReady ? 'is-ready' : 'is-pending' }}">{{ $modalityReady ? 'Configurada' : 'Pendiente' }}</span>
        <a class="btn btn-secondary btn-sm tree-open-modality" href="{{ route('modalities.show', $modality) }}"><x-icon name="arrow-up-right" /> Abrir</a>
    </div>
@empty
    <div class="tree-empty-child">Este curso todavía no tiene modalidades. Edita el curso para agregar TEC o TALL.</div>
@endforelse
