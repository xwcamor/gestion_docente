@forelse($groups as $group)
    @php($daysLabel = collect($group->workload_days ?? [])->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? null)->filter()->implode(' · '))
    @php($startTime = filled($group->workload_start_time) ? substr((string) $group->workload_start_time, 0, 5) : '')
    @php($endTime = filled($group->workload_end_time) ? substr((string) $group->workload_end_time, 0, 5) : '')
    @php($timeLabel = $startTime && $endTime ? $startTime.'–'.$endTime : '')
    @php($scheduleLabel = trim($daysLabel.($daysLabel && $timeLabel ? ' · ' : '').$timeLabel))
    <div class="workload-tree-group-shell" data-group-shell="{{ $group->id }}" data-group-id="{{ $group->id }}">
        <details class="workload-tree-group"
                 name="workload-groups"
                 id="tree-group-{{ $group->id }}"
                 data-tree-group-id="{{ $group->id }}"
                 data-group-label="Grupo {{ $group->name }}"
                 data-group-name="{{ $group->name }}"
                 data-group-code="{{ $group->code }}"
                 data-group-name-url="{{ route('groups.update', $group) }}"
                 data-tree-load="{{ route('workload-tree.courses', $group) }}"
                 data-tree-loaded="0">
            <summary class="workload-tree-summary workload-group-summary" data-select-group>
                <span class="tree-expand-symbol" aria-hidden="true"><x-icon name="chevron-right" size="17" /></span>
                <span class="tree-node-icon tree-group-icon"><x-icon name="folder-open" size="25" /></span>
                <span class="tree-node-copy">
                    <strong><span data-inline-text data-edit-field="name" data-edit-label="Nombre del grupo" data-edit-url="{{ route('groups.update', $group) }}" data-edit-value="{{ $group->name }}" data-edit-kind="group">{{ $group->name }}</span></strong>
                    <small>
                        <span class="tree-meta-item">Código: <span class="tree-inline-editable" data-inline-text data-edit-on-click data-edit-field="code" data-edit-kind="group-code" data-edit-label="Código del grupo" data-edit-url="{{ route('groups.update', $group) }}" data-edit-value="{{ $group->code }}" title="Clic para editar código">{{ $group->code ?: 'Código pendiente (clic para agregar)' }}</span></span>
                        <span class="tree-meta-separator">·</span>
                        <span><span data-group-course-count>{{ $group->assignments_count }}</span> cursos</span>
                        <span class="tree-meta-separator">·</span>
                        <span>{{ $group->students_count }} alumnos</span>
                    </small>
                    <small>
                        <x-icon name="graduation-cap" size="13" />
                        Carrera: <span data-cycle-career-display>{{ $cycle->career ?: 'Pendiente' }}</span>
                        <span class="tree-meta-separator">·</span>
                        Semestre: <span data-cycle-semester-display>{{ $cycle->semester ?: '—' }}</span>
                    </small>
                    <small>
                        <x-icon name="clock" size="13" />
                        Horario: {{ $scheduleLabel ?: 'Pendiente de configurar' }}
                    </small>
                </span>
                <span class="tree-group-row-actions">
                    <button class="tree-icon-action tree-group-delete-trigger" type="button"
                            data-toggle-group-delete
                            @disabled($cycleGroupCount <= 1)
                            aria-label="Eliminar grupo"
                            title="{{ $cycleGroupCount <= 1 ? 'El ciclo debe conservar al menos un grupo' : 'Eliminar grupo' }}">
                        <x-icon name="x" size="18" />
                    </button>
                </span>
            </summary>

            <div class="workload-tree-children workload-group-children">
                <div class="tree-delete-confirm tree-group-delete-confirm" data-group-delete-confirm hidden>
                    <p><strong>¿Eliminar Grupo {{ $group->name }}?</strong> Se retirarán sus cursos, modalidades, horarios y documentos asociados.</p>
                    <form method="POST" action="{{ route('groups.destroy', $group) }}" data-tree-delete-group>
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit" @disabled($cycleGroupCount <= 1)>Confirmar eliminación</button>
                        <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
                    </form>
                </div>

                <form class="tree-course-entry-form" method="POST"
                      action="{{ route('workload-tree.courses.store', $group) }}"
                      data-tree-course-form
                      data-group-id="{{ $group->id }}">
                    @csrf
                    <label class="tree-course-name-input">
                        <span><x-icon name="plus" size="17" /> Agregar curso al grupo</span>
                        <input class="field" name="course_name" maxlength="255" required placeholder="Escribe el nombre del curso…">
                    </label>
                    <fieldset class="tree-entry-components">
                        <legend>Componentes del curso</legend>
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
                        <small>Selecciona al menos uno. Después podrás completar NRC, fechas y horario en cada componente.</small>
                        <button class="btn tree-course-create-button" type="submit" data-course-submit><x-icon name="plus" size="17" /> Agregar curso</button>
                    </div>
                    <div class="tree-course-entry-error" data-course-form-error hidden role="alert"></div>
                </form>

                <div data-tree-content>
                    <div class="tree-loading-state">Despliega el grupo para cargar sus cursos.</div>
                </div>
            </div>
        </details>
    </div>
@empty
    <div class="tree-empty-child">
        <x-icon name="folder-open" size="34" />
        <strong>Todavía no hay grupos</strong>
        <span>Crea el primero desde el campo superior.</span>
    </div>
@endforelse
