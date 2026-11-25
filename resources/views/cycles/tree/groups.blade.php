@forelse($groups as $group)
    <div class="workload-tree-group-shell" data-group-shell="{{ $group->id }}" data-group-id="{{ $group->id }}">
        <details class="workload-tree-group"
                 id="tree-group-{{ $group->id }}"
                 data-tree-group-id="{{ $group->id }}"
                 data-group-label="Grupo {{ $group->name }}"
                 data-group-name="{{ $group->name }}"
                 data-group-name-url="{{ route('groups.update', $group) }}"
                 data-tree-load="{{ route('workload-tree.courses', $group) }}">
            <summary class="workload-tree-summary workload-group-summary" data-select-group>
                <span class="tree-chevron"><x-icon name="chevron-right" size="14" /></span>
                <span class="tree-node-icon tree-group-icon"><x-icon name="users" size="16" /></span>
                <span class="tree-node-copy">
                    <strong>Grupo {{ $group->name }}</strong>
                    <small><span data-group-course-count>{{ $group->assignments_count }}</span> cursos · {{ $group->students_count }} alumnos</small>
                </span>
                <span class="tree-group-selected-label">Seleccionar</span>
            </summary>

            <div class="workload-tree-children workload-group-children">
                <div class="workload-tree-group-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', ['group' => $group, 'panel' => 'courses']) }}#group-courses">
                        <x-icon name="graduation-cap" /> Gestión académica
                    </a>
                    <button class="btn btn-ghost btn-sm tree-group-delete-trigger" type="button" data-toggle-group-delete
                            @disabled($cycleGroupCount <= 1)
                            title="{{ $cycleGroupCount <= 1 ? 'Cada ciclo debe conservar al menos un grupo' : 'Eliminar este grupo' }}">
                        <x-icon name="x" size="13" /> <span data-group-delete-label>{{ $cycleGroupCount <= 1 ? 'Único grupo' : 'Eliminar grupo' }}</span>
                    </button>
                </div>

                <div class="tree-delete-confirm tree-group-delete-confirm" data-group-delete-confirm hidden>
                    <p><strong>¿Eliminar Grupo {{ $group->name }}?</strong> Se retirarán sus cursos, modalidades, horarios y documentos asociados.</p>
                    <form method="POST" action="{{ route('groups.destroy', $group) }}" data-tree-delete-group>
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit" @disabled($cycleGroupCount <= 1)><x-icon name="x" /> Confirmar</button>
                        <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
                    </form>
                </div>

                <form class="tree-course-entry-form" method="POST"
                      action="{{ route('workload-tree.courses.store', $group) }}"
                      data-tree-course-form
                      data-group-id="{{ $group->id }}">
                    @csrf
                    <label class="tree-course-name-input">
                        <span>Agregar curso</span>
                        <input class="field" name="course_name" maxlength="255" required placeholder="Escribe el nombre del curso…">
                    </label>
                    <fieldset class="tree-entry-components">
                        <legend>¿Qué componentes tiene?</legend>
                        <label class="tree-component-option is-tec">
                            <input type="checkbox" name="components[]" value="TEC" data-course-component checked>
                            <span>TEC</span><small>Teoría</small>
                        </label>
                        <label class="tree-component-option is-tall">
                            <input type="checkbox" name="components[]" value="TALL" data-course-component>
                            <span>TAL</span><small>Taller</small>
                        </label>
                    </fieldset>
                    <div class="tree-course-create-footer">
                        <small>Deja al menos uno seleccionado. El código, NRC y horario se completan después.</small>
                        <button class="btn tree-course-create-button" type="submit" data-course-submit><x-icon name="plus" size="14" /> Crear curso</button>
                    </div>
                    <div class="tree-course-entry-error" data-course-form-error hidden role="alert"></div>
                </form>

                <div data-tree-content>
                    <div class="tree-loading-state">Abre el grupo para ver sus cursos.</div>
                </div>
            </div>
        </details>
    </div>
@empty
    <div class="tree-empty-child">Todavía no hay grupos. Crea el primero para empezar.</div>
@endforelse
