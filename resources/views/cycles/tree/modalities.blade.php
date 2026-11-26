@php($activeCount = $modalities->where('active', true)->count())
@php($hasAlternativeModality = $modalities->count() > 1)
@foreach(['TEC' => 'Teoría', 'TALL' => 'Taller'] as $type => $label)
    @php($modality = $modalities->firstWhere('modality', $type))
    @if($modality)
        @php($classSchedules = $modality->schedules->where('block_type', 'CLASS'))
        @php($daysLabel = $classSchedules->pluck('day_of_week')->unique()->sort()->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? '')->filter()->implode(' · '))
        @php($scheduleLabel = $classSchedules->map(fn ($item) => substr((string) $item->start_time, 0, 5).'–'.substr((string) $item->end_time, 0, 5))->unique()->implode(', '))
        @php($modalityReady = $modality->start_date && $modality->end_date && $classSchedules->isNotEmpty())
        @php($enabledDescription = $classSchedules->isNotEmpty() ? $daysLabel.' · '.$scheduleLabel.' · '.number_format((float) $modality->total_hours, 2).' h/sem.' : 'Programación horaria')
        <article class="workload-tree-modality {{ $modality->active ? 'is-active' : 'is-disabled' }}"
                 data-modality-row data-modality-type="{{ $type }}" data-modality-id="{{ $modality->id }}" data-active="{{ $modality->active ? '1' : '0' }}" data-active-description="{{ $enabledDescription }}">
            <span class="tree-branch-mark"></span>
            <div class="tree-modality-copy">
                <strong>{{ $label }} · NRC
                    @if($modality->active)
                        <span class="tree-inline-nrc"
                              data-inline-nrc
                              data-nrc-url="{{ route('workload-tree.modalities.nrc', $modality) }}"
                              data-nrc="{{ $modality->nrc }}" data-modality-id="{{ $modality->id }}"
                              title="Doble clic para editar">{{ $modality->nrc ?: 'Falta' }}</span>
                    @else
                        <span class="tree-disabled-value">{{ $modality->nrc ?: 'Falta' }}</span>
                    @endif
                </strong>
                <div class="tree-modality-dates"
                     @if($modality->active)
                         data-modality-dates
                         data-dates-url="{{ route('workload-tree.modalities.dates', $modality) }}"
                         data-start-date="{{ $modality->start_date?->toDateString() }}"
                         data-end-date="{{ $modality->end_date?->toDateString() }}"
                         data-modality-id="{{ $modality->id }}"
                     @endif>
                    <span class="tree-date-field">
                        <span class="tree-date-label">Inicio</span>
                        @if($modality->active)
                            <span class="tree-inline-date" data-inline-date="start_date" title="Clic para editar la fecha">{{ $modality->start_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                        @else
                            <span class="tree-disabled-value">{{ $modality->start_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                        @endif
                    </span>
                    <span class="tree-date-field">
                        <span class="tree-date-label">Fin</span>
                        @if($modality->active)
                            <span class="tree-inline-date" data-inline-date="end_date" title="Clic para editar la fecha">{{ $modality->end_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                        @else
                            <span class="tree-disabled-value">{{ $modality->end_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                        @endif
                    </span>
                </div>
                <small class="tree-modality-schedule-summary {{ !$modality->active ? 'is-disabled' : ($classSchedules->isNotEmpty() ? 'is-ready' : 'is-pending') }}"
                       data-modality-description
                       data-active-description="{{ $enabledDescription }}">
                    @if(!$modality->active)
                        Desactivada · datos conservados
                    @elseif($classSchedules->isNotEmpty())
                        {{ $daysLabel }} · {{ $scheduleLabel }} · {{ number_format((float) $modality->total_hours, 2) }} h/sem.
                    @else
                        Programación horaria
                    @endif
                    @if($modality->block_code)
                        <span class="tree-modality-extra-meta">Bloque {{ $modality->block_code }}</span>
                    @endif
                    @if($modality->block_reference)
                        <span class="tree-modality-extra-meta">Ref. {{ $modality->block_reference }}</span>
                    @endif
                </small>
                @if($modality->active && $modality->documents_count > 0)
                    <small>{{ $modality->documents_count }} documentos generados</small>
                @endif
            </div>

            <div class="tree-modality-actions">
                <span class="tree-course-status {{ !$modality->active ? 'is-disabled' : ($modalityReady ? 'is-ready' : 'is-pending') }}" data-modality-status>
                    {{ !$modality->active ? 'Desactivada' : ($modalityReady ? 'Configurada' : 'Pendiente') }}
                </span>
                <div class="tree-modality-buttons">
                    <button class="tree-modality-toggle {{ $modality->active ? 'is-on' : 'is-off' }}"
                            type="button"
                            data-toggle-modality
                            data-modality-url="{{ route('workload-tree.modalities.active', $modality) }}"
                            data-active="{{ $modality->active ? '1' : '0' }}"
                            aria-pressed="{{ $modality->active ? 'true' : 'false' }}"
                            @disabled($modality->active && $activeCount <= 1 && !$hasAlternativeModality)
                            title="{{ $modality->active && $activeCount <= 1 && $hasAlternativeModality ? 'Cambiar al otro componente sin dejar el curso vacío' : ($modality->active && $activeCount <= 1 ? 'El curso debe conservar un componente activo' : ($modality->active ? 'Desactivar '.$type : 'Activar '.$type)) }}">
                        <span class="tree-toggle-dot"></span>
                        <span data-toggle-label>
                            @if($modality->active && $activeCount <= 1 && $hasAlternativeModality)
                                Cambiar a {{ $type === 'TEC' ? 'TAL' : 'TEC' }}
                            @elseif($modality->active && $activeCount <= 1)
                                Requerida
                            @else
                                {{ $modality->active ? 'Desactivar' : 'Activar' }}
                            @endif
                        </span>
                    </button>
                    @if($modality->active)
                        <a class="btn btn-secondary btn-sm tree-open-modality" href="{{ route('modalities.show', $modality) }}"><x-icon name="calendar-days" size="14" /> Horario</a>
                    @endif
                </div>
            </div>
        </article>
    @else
        <div class="workload-tree-modality is-missing" data-modality-row data-modality-type="{{ $type }}">
            <span class="tree-branch-mark"></span>
            <div class="tree-modality-copy">
                <strong>{{ $label }}</strong>
                <small>Componente no agregado</small>
            </div>
            <div class="tree-modality-actions is-missing-actions">
                <span class="tree-course-status is-pending">No agregado</span>
                <button class="btn btn-secondary tree-add-modality"
                        type="button"
                        data-open-inline-course-entry
                        data-group-id="{{ $assignment->group->id }}"
                        data-course-code="{{ $assignment->course->code }}"
                        data-course-name="{{ $assignment->course->name }}"
                        data-component="{{ $type }}"
                        data-store-url="{{ route('workload-tree.modalities.store', ['assignment' => $assignment, 'type' => $type]) }}">
                    <x-icon name="plus" size="13" /> Agregar {{ $type === 'TEC' ? 'TEC' : 'TAL' }}
                </button>
            </div>
        </div>
    @endif
@endforeach
