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
                <span class="tree-expand-symbol" aria-hidden="true"></span>
                
                <span class="tree-node-copy">
                    <span class="tree-course-title-row">
                        <strong><span class="tree-node-kind">Curso:</span> <span data-inline-text data-edit-field="name" data-edit-label="Nombre del curso" data-edit-url="{{ route('workload-tree.courses.name', $assignment) }}" data-edit-value="{{ $assignment->course->name }}" data-edit-kind="course-name">{{ $assignment->course->name }}</span></strong>
                        @if(($tecModality && $tecModality->active) || ($tallModality && $tallModality->active))
                            <span class="tree-course-components" aria-label="Componentes activos">
                                @if($tecModality && $tecModality->active)
                                    <span class="tree-component-chip is-present" title="TEC activo">TEC</span>
                                @endif
                                @if($tallModality && $tallModality->active)
                                    <span class="tree-component-chip is-present" title="TAL activo">TAL</span>
                                @endif
                            </span>
                        @endif
                    </span>
                    <span class="tree-course-code-row">
                        <span class="tree-meta-label">Código PIAD-</span>
                        <span class="tree-inline-editable {{ filled($assignment->course->code) ? '' : 'is-empty' }}" data-inline-text data-edit-on-click data-edit-field="code" data-edit-kind="course-code" data-edit-label="Código institucional" data-edit-url="{{ route('workload-tree.courses.code', $assignment) }}" data-edit-value="{{ $assignment->course->code }}" data-edit-empty-label="Falta" title="Clic para editar código">{{ $assignment->course->code ?: 'Falta' }}</span>
                        <span class="tree-course-period" data-course-dates
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
                                      data-inline-date="course_end_date" data-empty-label="Falta"
                                      title="Clic para agregar o editar la fecha de fin">{{ $assignment->course_end_date?->format('d/m/Y') ?: 'Falta' }}</span>
                            </span>
                        </span>
                    </span>
                </span>
            </summary>

            <div class="tree-course-children">
                @include('cycles.tree.modalities', ['assignment' => $assignment, 'modalities' => $assignment->modalities])
            </div>
        </details>

        <button class="tree-course-delete-trigger" type="button"
                data-toggle-course-delete
                @disabled($group->assignments_count <= 1)
                aria-label="Eliminar curso"
                title="{{ $group->assignments_count <= 1 ? 'El grupo debe conservar al menos un curso' : 'Eliminar curso' }}">
            <x-icon name="trash-2" size="14" />
            <span>Eliminar curso</span>
        </button>
        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-tree-delete-course hidden>
            @csrf
            @method('DELETE')
        </form>
    </article>
@empty
    <div class="tree-empty-child tree-empty-course">
        <x-icon name="book-open" size="30" />
        <strong>Este grupo todavía no tiene cursos</strong>
        <span>Usa la fila ＋ Agregar curso que aparece debajo del listado.</span>
    </div>
@endforelse
