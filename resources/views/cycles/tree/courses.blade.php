@forelse($assignments as $assignment)
    @php($components = $assignment->modalities->keyBy('modality'))
    <details class="workload-tree-course" data-tree-course-item data-assignment-id="{{ $assignment->id }}"
             data-tree-load="{{ route('workload-tree.modalities', $assignment) }}">
        <summary class="workload-tree-summary workload-course-summary" data-course-label="{{ $assignment->course->name }}">
            <span class="tree-branch-mark"></span>
            <span class="tree-node-icon tree-course-icon"><x-icon name="book-open" size="15" /></span>
            <span class="tree-node-copy">
                <strong data-course-name>{{ $assignment->course->name }}</strong>
                <small>{{ $assignment->course->code ?: 'Sin código' }}</small>
            </span>
            <span class="tree-course-components" aria-label="Componentes del curso">
                <span class="tree-component-chip {{ $components->has('TEC') ? 'is-present' : 'is-missing' }}" title="{{ $components->has('TEC') ? 'Teoría registrada' : 'Teoría pendiente' }}">TEC</span>
                <span class="tree-component-chip {{ $components->has('TALL') ? 'is-present' : 'is-missing' }}" title="{{ $components->has('TALL') ? 'Taller registrado' : 'Taller pendiente' }}">TAL</span>
            </span>
            <span class="tree-chevron"><x-icon name="chevron-right" size="13" /></span>
        </summary>

        <div class="workload-tree-children workload-course-children">
            <div class="tree-course-actions">
                <details class="tree-course-action-inline">
                    <summary><x-icon name="pencil" size="13" /> Editar nombre</summary>
                    <form method="POST" action="{{ route('workload-tree.courses.name', $assignment) }}" data-tree-course-name-form>
                        @csrf
                        @method('PATCH')
                        <p class="tree-shared-course-note">Este nombre pertenece al catálogo del curso y se actualizará también en los otros grupos donde esté asignado.</p>
                        <label class="tree-entry-field">
                            <span>Nombre del curso</span>
                            <input class="field" name="name" value="{{ $assignment->course->name }}" maxlength="255" required>
                        </label>
                        <div class="tree-inline-form-actions">
                            <button class="btn btn-sm" type="submit"><x-icon name="check" /> Guardar</button>
                            <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
                        </div>
                    </form>
                </details>

                <details class="tree-course-action-inline tree-course-remove">
                    <summary><x-icon name="x" size="13" /> Eliminar</summary>
                    <div class="tree-remove-confirm">
                        <p>Se retirará este curso de este grupo y sus modalidades. El historial curricular se conservará.</p>
                        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-tree-delete-course>
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Confirmar retiro</button>
                            <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
                        </form>
                    </div>
                </details>
            </div>
            <p class="tree-course-hint">Doble clic en NRC o fechas para editarlos.</p>
            <div data-tree-content>
                <div class="tree-loading-state">Despliega el curso para consultar sus modalidades y NRC.</div>
            </div>
        </div>
    </details>
@empty
    <div class="tree-empty-child">Este grupo todavía no tiene cursos. Usa «Agregar curso» en la cabecera del grupo para comenzar.</div>
@endforelse
