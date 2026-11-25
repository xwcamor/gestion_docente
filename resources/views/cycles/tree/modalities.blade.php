@foreach(['TEC' => 'Teoría', 'TALL' => 'Taller'] as $type => $label)
    @php($modality = $modalities->firstWhere('modality', $type))
    @if($modality)
        @php($classSchedules = $modality->schedules->where('block_type', 'CLASS'))
        @php($daysLabel = $classSchedules->pluck('day_of_week')->unique()->sort()->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? '')->filter()->implode(' · '))
        @php($scheduleLabel = $classSchedules->map(fn ($item) => substr((string) $item->start_time, 0, 5).'–'.substr((string) $item->end_time, 0, 5))->unique()->implode(', '))
        @php($modalityReady = $modality->start_date && $modality->end_date && $classSchedules->isNotEmpty())
        <div class="workload-tree-modality">
            <span class="tree-branch-mark"></span>
            <span class="tree-modality-tag {{ $type === 'TEC' ? 'is-tec' : 'is-tall' }}">{{ $type === 'TEC' ? 'TEC' : 'TAL' }}</span>
            <div class="tree-modality-copy">
                <strong>{{ $label }} · NRC
                    <span class="tree-inline-nrc"
                          data-inline-nrc
                          data-nrc-url="{{ route('workload-tree.modalities.nrc', $modality) }}"
                          data-nrc="{{ $modality->nrc }}"
                          title="Doble clic para editar el NRC">{{ $modality->nrc ?: 'NRC pendiente' }}</span>
                </strong>
                <small class="tree-modality-dates"
                       data-modality-dates
                       data-dates-url="{{ route('workload-tree.modalities.dates', $modality) }}"
                       data-start-date="{{ $modality->start_date?->toDateString() }}"
                       data-end-date="{{ $modality->end_date?->toDateString() }}"
                       title="Doble clic sobre una fecha para editarla">
                    Inicio:
                    <span class="tree-inline-date" data-inline-date="start_date">{{ $modality->start_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                    <span class="tree-date-separator">·</span>
                    Fin:
                    <span class="tree-inline-date" data-inline-date="end_date">{{ $modality->end_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                    <span class="tree-inline-hint">doble clic para editar</span>
                </small>
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
            <a class="btn btn-secondary btn-sm tree-open-modality" href="{{ route('modalities.show', $modality) }}"><x-icon name="arrow-up-right" /> Horario</a>
        </div>
    @else
        <div class="workload-tree-modality is-missing">
            <span class="tree-branch-mark"></span>
            <span class="tree-modality-tag {{ $type === 'TEC' ? 'is-tec' : 'is-tall' }}">{{ $type === 'TEC' ? 'TEC' : 'TAL' }}</span>
            <div class="tree-modality-copy">
                <strong>{{ $label }} todavía no agregada</strong>
                <small>Agrega este componente sin salir del árbol.</small>
            </div>
            <button class="btn btn-secondary tree-add-modality"
                    type="button"
                    data-open-course-dialog
                    data-group-id="{{ $assignment->group->id }}"
                    data-create-url="{{ route('workload-tree.courses.store', $assignment->group) }}"
                    data-course-code="{{ $assignment->course->code }}"
                    data-course-name="{{ $assignment->course->name }}"
                    data-component="{{ $type }}">
                <x-icon name="plus" size="13" /> Agregar {{ $type === 'TEC' ? 'TEC' : 'TAL' }}
            </button>
        </div>
    @endif
@endforeach
