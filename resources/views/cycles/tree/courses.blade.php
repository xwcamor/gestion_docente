@forelse($assignments as $assignment)
    @php($components = $assignment->modalities->keyBy('modality'))
    @php($tecModality = $components->get('TEC'))
    @php($tallModality = $components->get('TALL'))
    <article class="workload-tree-course" data-tree-course-item data-assignment-id="{{ $assignment->id }}"
             data-course-name="{{ $assignment->course->name }}"
             data-course-code="{{ $assignment->course->code }}"
             data-course-name-url="{{ route('workload-tree.courses.name', $assignment) }}"
             data-course-code-url="{{ route('workload-tree.courses.code', $assignment) }}"
             data-group-id="{{ $group->id }}">
        <button class="tree-course-head" type="button" data-select-course>
            <span class="tree-course-node"><x-icon name="book-open" size="15" /></span>
            <span class="tree-node-copy">
                <strong>{{ $assignment->course->name }}</strong>
                <small>{{ $assignment->course->code ?: 'Código opcional' }}</small>
            </span>
            <span class="tree-course-components">
                <span class="tree-component-chip {{ !$tecModality ? 'is-missing' : ($tecModality->active ? 'is-present' : 'is-disabled') }}">TEC</span>
                <span class="tree-component-chip {{ !$tallModality ? 'is-missing' : ($tallModality->active ? 'is-present' : 'is-disabled') }}">TAL</span>
            </span>
            <span class="tree-course-selected-label">Editar <x-icon name="chevron-right" size="13" /></span>
        </button>

        <div class="tree-delete-confirm tree-course-delete-confirm" data-course-delete-confirm hidden>
            <p><strong>¿Retirar {{ $assignment->course->name }} de este grupo?</strong> Se retirarán sus modalidades y horarios del grupo.</p>
            <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-tree-delete-course>
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Confirmar</button>
                <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
            </form>
        </div>

        <button class="tree-course-remove-trigger" type="button" data-toggle-course-delete title="Retirar curso">
            <x-icon name="x" size="13" />
        </button>

        <div hidden data-editor-modalities>
            @foreach(['TEC', 'TALL'] as $type)
                @php($modality = $components->get($type))
                @php($classSchedules = $modality ? $modality->schedules->where('block_type', 'CLASS') : collect())
                @php($daysLabel = $classSchedules->pluck('day_of_week')->unique()->sort()->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? '')->filter()->implode(' · '))
                @php($scheduleLabel = $classSchedules->map(fn ($item) => substr((string) $item->start_time, 0, 5).'–'.substr((string) $item->end_time, 0, 5))->unique()->implode(', '))
                <div data-editor-modality
                     data-type="{{ $type }}"
                     data-exists="{{ $modality ? '1' : '0' }}"
                     data-modality-id="{{ $modality?->id }}"
                     data-active="{{ $modality?->active ? '1' : '0' }}"
                     data-nrc="{{ $modality?->nrc }}"
                     data-nrc-url="{{ $modality ? route('workload-tree.modalities.nrc', $modality) : '' }}"
                     data-start-date="{{ $modality?->start_date?->toDateString() }}"
                     data-end-date="{{ $modality?->end_date?->toDateString() }}"
                     data-dates-url="{{ $modality ? route('workload-tree.modalities.dates', $modality) : '' }}"
                     data-active-url="{{ $modality ? route('workload-tree.modalities.active', $modality) : '' }}"
                     data-schedule-label="{{ $classSchedules->isNotEmpty() ? $daysLabel.' · '.$scheduleLabel.' · '.number_format((float) $modality->total_hours, 2).' h/sem.' : 'Horario pendiente' }}"
                     data-has-schedule="{{ $classSchedules->isNotEmpty() ? '1' : '0' }}"
                     data-modality-url="{{ $modality ? route('modalities.show', $modality) : '' }}"
                     data-store-url="{{ route('workload-tree.modalities.store', ['assignment' => $assignment, 'type' => $type]) }}">
                </div>
            @endforeach
        </div>
    </article>
@empty
    <div class="tree-empty-child">Este grupo todavía no tiene cursos. Agrégalos desde el campo de arriba.</div>
@endforelse
