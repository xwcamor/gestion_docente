@forelse($assignments as $assignment)
    @php($components = $assignment->modalities->keyBy('modality'))
    @php($tecModality = $components->get('TEC'))
    @php($tallModality = $components->get('TALL'))
    @php($periods = $assignment->modalities->filter(fn ($modality) => $modality->active && $modality->start_date && $modality->end_date)->map(fn ($modality) => $modality->start_date->format('d/m/Y').' – '.$modality->end_date->format('d/m/Y'))->unique()->values())
    @php($periodLabel = $periods->count() === 1 ? 'Inicio y fin: '.$periods->first() : ($periods->isEmpty() ? 'Inicio y fin pendientes' : 'Fechas diferentes por TEC/TAL'))
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
                <span class="tree-expand-symbol" aria-hidden="true">
                    <span data-tree-collapsed><x-icon name="plus" size="16" /></span>
                    <span data-tree-expanded><x-icon name="minus" size="16" /></span>
                </span>
                <span class="tree-course-node"><x-icon name="book-open" size="24" /></span>
                <span class="tree-node-copy">
                    <strong><span data-inline-text data-edit-field="name" data-edit-label="Nombre del curso" data-edit-url="{{ route('workload-tree.courses.name', $assignment) }}" data-edit-value="{{ $assignment->course->name }}" data-edit-kind="course-name">{{ $assignment->course->name }}</span></strong>
                    <small>
                        <span data-inline-text data-edit-on-click data-edit-field="code" data-edit-kind="course-code" data-edit-label="Código institucional" data-edit-url="{{ route('workload-tree.courses.code', $assignment) }}" data-edit-value="{{ $assignment->course->code }}" data-edit-kind="course-code">{{ $assignment->course->code ?: 'Código pendiente (doble clic para agregar)' }}</span>
                    </small>
                    <small data-course-period>{{ $periodLabel }}</small>
                </span>
                <span class="tree-course-components" aria-label="Componentes">
                    <span class="tree-component-chip {{ !$tecModality ? 'is-missing' : ($tecModality->active ? 'is-present' : 'is-disabled') }}">TEC</span>
                    <span class="tree-component-chip {{ !$tallModality ? 'is-missing' : ($tallModality->active ? 'is-present' : 'is-disabled') }}">TAL</span>
                </span>
            </summary>

            <div class="tree-course-children">
                <div class="tree-course-tools">
                    <span><x-icon name="git-branch" size="15" /> Componentes del curso</span>
                    <button class="tree-icon-action tree-course-remove-trigger" type="button"
                            data-toggle-course-delete
                            @disabled($group->assignments_count <= 1)
                            aria-label="Retirar curso"
                            title="{{ $group->assignments_count <= 1 ? 'El grupo debe conservar al menos un curso' : 'Retirar curso de este grupo' }}">
                        <x-icon name="x" size="17" />
                    </button>
                </div>

                <div class="tree-delete-confirm tree-course-delete-confirm" data-course-delete-confirm hidden>
                    <p><strong>¿Retirar {{ $assignment->course->name }} de este grupo?</strong> Se retirarán sus modalidades y horarios del grupo. El historial curricular se conserva.</p>
                    <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-tree-delete-course>
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit" @disabled($group->assignments_count <= 1)>Confirmar eliminación</button>
                        <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
                    </form>
                </div>

                @include('cycles.tree.modalities', ['assignment' => $assignment, 'modalities' => $assignment->modalities])
            </div>
        </details>
    </article>
@empty
    <div class="tree-empty-child tree-empty-course">
        <x-icon name="book-open" size="30" />
        <strong>Este grupo todavía no tiene cursos</strong>
        <span>Escribe el nombre de un curso en el campo superior para agregarlo.</span>
    </div>
@endforelse
