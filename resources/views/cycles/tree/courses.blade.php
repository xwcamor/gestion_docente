@forelse($assignments as $assignment)
    @php($components = $assignment->modalities->keyBy('modality'))
    @php($tecModality = $components->get('TEC'))
    @php($tallModality = $components->get('TALL'))
    @php($courseStartDate = $assignment->course_start_date?->toDateString() ?: '')
    @php($courseEndDate = $assignment->course_end_date?->toDateString() ?: '')
    <article class="workload-tree-course" data-tree-course-item
             data-assignment-id="{{ $assignment->id }}"
             data-course-id="{{ $assignment->course_id }}"
             data-course-name="{{ $assignment->course->name }}"
             data-course-code="{{ $assignment->course->code }}"
             data-course-name-url="{{ route('workload-tree.courses.name', $assignment) }}"
             data-course-code-url="{{ route('workload-tree.courses.code', $assignment) }}"
             data-group-id="{{ $group->id }}">
        <details class="tree-course-branch" data-course-branch>
            <summary class="tree-course-summary" data-select-course>
                <span class="tree-expand-symbol" aria-hidden="true"><x-icon name="chevron-right" size="16" /></span>
                <span class="tree-course-node"><x-icon name="book-open" size="21" /></span>
                <span class="tree-node-copy">
                    <strong><span data-inline-text data-edit-field="name" data-edit-label="Nombre del curso" data-edit-url="{{ route('workload-tree.courses.name', $assignment) }}" data-edit-value="{{ $assignment->course->name }}" data-edit-kind="course-name">{{ $assignment->course->name }}</span></strong>
                    <span class="tree-course-code-row">
                        <span class="tree-meta-label">Código PIAD-</span>
                        <span class="tree-inline-editable" data-inline-text data-edit-on-click data-edit-field="code" data-edit-kind="course-code" data-edit-label="Código institucional" data-edit-url="{{ route('workload-tree.courses.code', $assignment) }}" data-edit-value="{{ $assignment->course->code }}" title="Clic para editar código">{{ $assignment->course->code ?: 'Falta' }}</span>
                    </span>
                    <div class="tree-course-period" data-course-dates
                         data-dates-url="{{ route('workload-tree.courses.dates', $assignment) }}"
                         data-start-date="{{ $courseStartDate }}"
                         data-end-date="{{ $courseEndDate }}"
                         data-start-field="course_start_date"
                         data-end-field="course_end_date"
                         aria-label="Periodo del curso">
                        <span class="tree-date-field">
                            <span class="tree-date-label">Inicio</span>
                            <span class="tree-inline-date {{ $courseStartDate ? '' : 'is-empty' }}"
                                  data-inline-date="course_start_date" data-empty-label="Falta"
                                  title="Clic para agregar o editar la fecha de inicio">{{ $assignment->course_start_date?->format('d/m/Y') ?: 'Falta' }}</span>
                        </span>
                        <span class="tree-date-field">
                            <span class="tree-date-label">Fin</span>
                            <span class="tree-inline-date {{ $courseEndDate ? '' : 'is-empty' }}"
                                  data-inline-date="course_end_date" data-empty-label="Agregar fecha"
                                  title="Clic para agregar o editar la fecha de fin">{{ $assignment->course_end_date?->format('d/m/Y') ?: 'Falta' }}</span>
                        </span>
                    </div>
                </span>
                <span class="tree-course-components" aria-label="Componentes">
                    <span class="tree-component-chip {{ !$tecModality ? 'is-missing' : ($tecModality->active ? 'is-present' : 'is-disabled') }}" title="{{ !$tecModality ? 'TEC no agregado' : ($tecModality->active ? 'TEC activo' : 'TEC desactivado') }}">TEC</span>
                    <span class="tree-component-chip {{ !$tallModality ? 'is-missing' : ($tallModality->active ? 'is-present' : 'is-disabled') }}" title="{{ !$tallModality ? 'TAL no agregado' : ($tallModality->active ? 'TAL activo' : 'TAL desactivado') }}">TAL</span>
                </span>
            </summary>

            <div class="tree-course-children">
                @include('cycles.tree.modalities', ['assignment' => $assignment, 'modalities' => $assignment->modalities])
            </div>
        </details>

        <button class="tree-icon-action tree-course-delete-trigger" type="button"
                data-toggle-course-delete
                @disabled($group->assignments_count <= 1)
                aria-label="Retirar curso del grupo"
                title="{{ $group->assignments_count <= 1 ? 'El grupo debe conservar al menos un curso' : 'Retirar curso de este grupo' }}">
            <x-icon name="trash-2" size="15" />
        </button>
        <div class="tree-delete-confirm tree-course-delete-confirm" data-course-delete-confirm hidden>
            <p><strong>¿Quitar este curso del grupo?</strong> Se quitarán sus horarios y modalidades. La malla curricular se conserva.</p>
            <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-tree-delete-course>
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit" @disabled($group->assignments_count <= 1)>Quitar curso</button>
                <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
            </form>
        </div>
    </article>
@empty
    <div class="tree-empty-child tree-empty-course">
        <x-icon name="book-open" size="30" />
        <strong>Este grupo todavía no tiene cursos</strong>
        <span>Usa la fila ＋ Agregar curso que aparece debajo del listado.</span>
    </div>
@endforelse
