@forelse($groups as $group)
    <div class="workload-tree-group-shell" data-group-shell="{{ $group->id }}">
        <details class="workload-tree-group"
                 id="tree-group-{{ $group->id }}"
                 data-tree-group-id="{{ $group->id }}"
                 data-group-label="Grupo {{ $group->name }}"
                 data-tree-load="{{ route('workload-tree.courses', $group) }}">
            <summary class="workload-tree-summary workload-group-summary">
                <span class="tree-branch-mark"></span>
                <span class="tree-node-icon tree-group-icon"><x-icon name="users" size="16" /></span>
                <span class="tree-node-copy">
                    <strong>Grupo {{ $group->name }}</strong>
                    <small><span data-group-course-count>{{ $group->assignments_count }}</span> cursos · {{ $group->students_count }} alumnos</small>
                </span>
                <span class="tree-chevron"><x-icon name="chevron-right" size="14" /></span>
            </summary>

            <div class="workload-tree-children workload-group-children">
                <div class="workload-tree-group-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', ['group' => $group, 'panel' => 'courses']) }}#group-courses">
                        <x-icon name="graduation-cap" /> Gestión académica
                    </a>
                </div>
                <div data-tree-content>
                    <div class="tree-loading-state">Despliega el grupo para consultar sus cursos.</div>
                </div>
            </div>
        </details>

        <details class="workload-tree-add-course-inline" id="tree-course-entry-{{ $group->id }}">
            <summary aria-label="Agregar curso al grupo {{ $group->name }}">
                <x-icon name="plus" size="13" /><span>Agregar curso</span>
            </summary>
            <form class="tree-course-entry-form" method="POST"
                  action="{{ route('workload-tree.courses.store', $group) }}"
                  data-tree-course-form
                  data-group-id="{{ $group->id }}">
                @csrf
                <div class="tree-course-entry-heading">
                    <div><strong>Nuevo curso</strong><small>Se agregará directamente a Grupo {{ $group->name }}</small></div>
                    <span class="tree-inline-step">01 · CURSO</span>
                </div>
                <div class="tree-course-entry-error" data-course-form-error hidden role="alert"></div>

                <div class="tree-entry-grid tree-entry-grid-course">
                    <label class="tree-entry-field">
                        <span>Código base institucional</span>
                        <input class="field mono" name="course_code" maxlength="80" required placeholder="202620-PIAD-222">
                        <small>Sin sufijo TEC, TAL ni NRC.</small>
                    </label>
                    <label class="tree-entry-field">
                        <span>Nombre del curso</span>
                        <input class="field" name="course_name" maxlength="255" required placeholder="Ej. Frontend Developer Web">
                    </label>
                </div>

                <fieldset class="tree-entry-components">
                    <legend>Componentes del curso</legend>
                    <label><input type="checkbox" name="components[]" value="TEC" data-course-component> <span class="tree-component-choice is-tec">TEC</span> Teoría</label>
                    <label><input type="checkbox" name="components[]" value="TALL" data-course-component> <span class="tree-component-choice is-tall">TAL</span> Taller / práctica</label>
                    <small>Selecciona uno o ambos. Cada componente tendrá su propio NRC y periodo.</small>
                </fieldset>

                <section class="tree-entry-component" data-course-config="TEC" hidden>
                    <div class="tree-entry-component-title"><span class="tree-component-choice is-tec">TEC</span><strong>Datos de teoría</strong></div>
                    <div class="tree-entry-grid tree-entry-grid-three">
                        <label class="tree-entry-field"><span>NRC</span><input class="field" name="tec_nrc" inputmode="numeric" maxlength="12" placeholder="11518"></label>
                        <label class="tree-entry-field"><span>Fecha de inicio</span><input class="field" type="date" name="tec_start_date"></label>
                        <label class="tree-entry-field"><span>Fecha de fin</span><input class="field" type="date" name="tec_end_date"></label>
                    </div>
                    <details class="tree-entry-schedule">
                        <summary>Configurar horario TEC (opcional)</summary>
                        <div class="tree-entry-days">
                            @foreach([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'] as $day => $label)
                                <label><input type="checkbox" name="tec_days[]" value="{{ $day }}"> {{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="tree-entry-grid tree-entry-grid-three">
                            <label class="tree-entry-field"><span>Desde</span><input class="field" type="time" name="tec_start_time"></label>
                            <label class="tree-entry-field"><span>Hasta</span><input class="field" type="time" name="tec_end_time"></label>
                            <label class="tree-entry-field"><span>Aula</span><input class="field" name="tec_classroom" maxlength="100" placeholder="Opcional"></label>
                        </div>
                    </details>
                </section>

                <section class="tree-entry-component" data-course-config="TALL" hidden>
                    <div class="tree-entry-component-title"><span class="tree-component-choice is-tall">TAL</span><strong>Datos de taller</strong></div>
                    <div class="tree-entry-grid tree-entry-grid-three">
                        <label class="tree-entry-field"><span>NRC</span><input class="field" name="tall_nrc" inputmode="numeric" maxlength="12" placeholder="11520"></label>
                        <label class="tree-entry-field"><span>Fecha de inicio</span><input class="field" type="date" name="tall_start_date"></label>
                        <label class="tree-entry-field"><span>Fecha de fin</span><input class="field" type="date" name="tall_end_date"></label>
                    </div>
                    <details class="tree-entry-schedule">
                        <summary>Configurar horario TAL (opcional)</summary>
                        <div class="tree-entry-days">
                            @foreach([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'] as $day => $label)
                                <label><input type="checkbox" name="tall_days[]" value="{{ $day }}"> {{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="tree-entry-grid tree-entry-grid-three">
                            <label class="tree-entry-field"><span>Desde</span><input class="field" type="time" name="tall_start_time"></label>
                            <label class="tree-entry-field"><span>Hasta</span><input class="field" type="time" name="tall_end_time"></label>
                            <label class="tree-entry-field"><span>Aula</span><input class="field" name="tall_classroom" maxlength="100" placeholder="Opcional"></label>
                        </div>
                    </details>
                </section>

                <div class="tree-course-entry-footer">
                    <span>Los cursos se pueden configurar por completo y ajustar después desde el árbol.</span>
                    <button class="btn" type="submit" data-course-submit><x-icon name="check" /> Guardar curso</button>
                </div>
            </form>
        </details>
    </div>
@empty
    <div class="tree-empty-child">No hay grupos que coincidan con esta búsqueda.</div>
@endforelse
