@forelse($groups as $group)
    @php($daysLabel = collect($group->workload_days ?? [])->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'][(int) $day] ?? null)->filter()->implode(' · '))
    @php($startDate = $group->workload_start_date?->toDateString() ?: '')
    @php($endDate = $group->workload_end_date?->toDateString() ?: '')
    <div class="workload-tree-group-shell" data-group-shell="{{ $group->id }}" data-group-id="{{ $group->id }}">
        <details class="workload-tree-group"
                 id="tree-group-{{ $group->id }}"
                 data-tree-group-id="{{ $group->id }}"
                 data-group-label="Grupo {{ $group->name }}"
                 data-group-name="{{ $group->name }}"
                 data-group-code="{{ $group->code }}"
                 data-group-career="{{ $group->career }}"
                 data-group-semester="{{ $group->semester }}"
                 data-group-name-url="{{ route('groups.update', $group) }}"
                 data-tree-load="{{ route('workload-tree.courses', $group) }}"
                 data-tree-loaded="0">
            <summary class="workload-tree-summary workload-group-summary" data-select-group>
                <span class="tree-expand-symbol" aria-hidden="true"></span>
                
                <span class="tree-node-copy">
                    <span class="tree-group-title-row">
                        <strong><span class="tree-node-kind">Grupo:</span> <span data-inline-text data-edit-field="name" data-edit-label="Nombre del grupo" data-edit-url="{{ route('groups.update', $group) }}" data-edit-value="{{ $group->name }}" data-edit-kind="group">{{ $group->name }}</span></strong>
                        <span class="tree-group-counts">
                            <span class="tree-meta-pill"><span data-group-course-count>{{ $group->assignments_count }}</span> <span data-group-course-label>{{ (int) $group->assignments_count === 1 ? 'curso' : 'cursos' }}</span></span>
                            <span class="tree-meta-pill">{{ $group->students_count }} {{ (int) $group->students_count === 1 ? 'alumno' : 'alumnos' }}</span>
                        </span>
                    </span>
                    <span class="tree-group-inline-details" aria-label="Carrera, semestre y horario">
                        <span class="tree-group-inline-field">
                            <span class="tree-group-inline-label">Carrera</span>
                            <span class="tree-inline-editable {{ filled($group->career) ? '' : 'is-empty' }}"
                                  data-inline-text data-edit-on-click
                                  data-edit-field="career" data-edit-kind="group-career"
                                  data-edit-label="Carrera del grupo"
                                  data-edit-url="{{ route('workload-tree.groups.context', $group) }}"
                                  data-edit-value="{{ $group->career }}"
                                  data-edit-response="career"
                                  data-edit-empty-label="Falta"
                                  data-group-career-display
                                  title="Clic para editar carrera">{{ $group->career ?: 'Falta' }}</span>
                        </span>
                        <span class="tree-group-inline-field">
                            <span class="tree-group-inline-label">Semestre</span>
                            <span class="tree-inline-editable {{ filled($group->semester) ? '' : 'is-empty' }}"
                                  data-inline-text data-edit-on-click
                                  data-edit-field="semester" data-edit-kind="group-semester"
                                  data-edit-options="I|II|III|IV|V|VI"
                                  data-edit-label="Semestre del grupo"
                                  data-edit-url="{{ route('workload-tree.groups.context', $group) }}"
                                  data-edit-value="{{ $group->semester }}"
                                  data-edit-response="semester"
                                  data-edit-empty-label="Falta"
                                  data-group-semester-display
                                  title="Clic para editar semestre">{{ $group->semester ?: 'Falta' }}</span>
                        </span>
                        <span class="tree-group-inline-field tree-group-inline-schedule"
                              data-group-dates
                              data-dates-url="{{ route('workload-tree.groups.context', $group) }}"
                              data-start-date="{{ $startDate }}"
                              data-end-date="{{ $endDate }}"
                              data-start-field="workload_start_date"
                              data-end-field="workload_end_date">
                            @if($daysLabel)
                                <span class="tree-group-inline-days">{{ $daysLabel }}</span>
                            @endif
                            <span class="tree-group-inline-time">
                                <span class="tree-group-inline-sub-label">Inicio</span>
                                <span class="tree-inline-date {{ $startDate ? '' : 'is-empty' }}"
                                      data-inline-date="workload_start_date" data-empty-label="Falta"
                                      title="Clic para seleccionar la fecha de inicio">{{ $group->workload_start_date?->format('d/m/Y') ?: 'Falta' }}</span>
                            </span>
                            <span class="tree-group-inline-time">
                                <span class="tree-group-inline-sub-label">Fin</span>
                                <span class="tree-inline-date {{ $endDate ? '' : 'is-empty' }}"
                                      data-inline-date="workload_end_date" data-empty-label="Falta"
                                      title="Clic para seleccionar la fecha de fin">{{ $group->workload_end_date?->format('d/m/Y') ?: 'Falta' }}</span>
                            </span>
                        </span>
                    </span>
                </span>
            </summary>

            <div class="workload-tree-children workload-group-children">
                <form method="POST" action="{{ route('groups.destroy', $group) }}" data-tree-delete-group hidden>
                    @csrf
                    @method('DELETE')
                </form>

                <div data-tree-content>
                    <div class="tree-loading-state">Despliega el grupo para cargar sus cursos.</div>
                </div>

                <form class="tree-course-draft-form" method="POST"
                      action="{{ route('workload-tree.courses.store', $group) }}"
                      data-tree-course-form
                      data-group-id="{{ $group->id }}"
                      hidden>
                    @csrf
                    <div class="tree-draft-heading">
                        <strong><x-icon name="book-open" size="16" /> Nuevo curso</strong>
                        <span>TEC + TAL activos por defecto</span>
                    </div>
                    <div class="tree-draft-fields">
                        <label class="tree-course-name-input">
                            <span>Nombre del curso</span>
                            <input class="field" name="course_name" data-uppercase maxlength="255" required placeholder="Ej. Desarrollo de Aplicaciones Web">
                        </label>
                    </div>
                    <input type="hidden" name="components[]" value="TEC">
                    <input type="hidden" name="components[]" value="TALL">
                    <div class="tree-draft-footer">
                        <span class="tree-draft-note">Se crearán ambos componentes activos. Puedes desactivar uno después.</span>
                        <div>
                            <button class="btn btn-ghost" type="button" data-cancel-course-draft>Cancelar</button>
                            <button class="btn" type="submit" data-course-submit><x-icon name="check" size="16" /> Guardar curso</button>
                        </div>
                    </div>
                    <div class="tree-course-entry-error" data-course-form-error hidden role="alert"></div>
                </form>

                <div class="tree-add-row tree-add-course-row" data-course-add-row>
                    <button type="button" data-show-course-draft><x-icon name="plus" size="16" /> Agregar curso</button>
                </div>
            </div>
        </details>
        <button class="tree-group-delete-trigger" type="button"
                data-toggle-group-delete
                @disabled($cycleGroupCount <= 1)
                aria-label="Eliminar grupo"
                title="{{ $cycleGroupCount <= 1 ? 'El ciclo debe conservar al menos un grupo' : 'Eliminar grupo' }}">
            <x-icon name="trash-2" size="15" />
            <span>Eliminar grupo</span>
        </button>
    </div>
@empty
    <div class="tree-empty-child">
        <x-icon name="folder-open" size="34" />
        <strong>Todavía no hay grupos</strong>
        <span>Usa la fila ＋ Agregar grupo que aparece debajo del árbol.</span>
    </div>
@endforelse
