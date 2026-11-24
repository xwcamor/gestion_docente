@extends('layouts.app')
@section('title', 'Grupo '.$group->name)
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.index') }}">Ciclos</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">{{ $group->name }}</span>
</div>
<a class="cycle-back" href="{{ route('cycles.show', $group->cycle) }}"><x-icon name="arrow-left" size="15" /> Volver a los grupos</a>

<div class="page-head group-page-head">
    <div>
        <p class="eyebrow"><x-icon name="users" size="12" /> {{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</p>
        <h1>{{ $group->name }}</h1>
        <div class="cycle-item-meta">
            <span><x-icon name="calendar-range" size="12" /> Carga: {{ $group->cycle->start_date?->format('d/m/Y') }} – {{ $group->cycle->end_date?->format('d/m/Y') }}</span>
            @if($workloadDaysLabels->isNotEmpty() && filled($group->workload_start_time) && filled($group->workload_end_time))
                <span>·</span>
                <span><x-icon name="clock" size="12" /> {{ $workloadDaysLabels->map(fn ($day) => mb_substr($day, 0, 3))->join(', ') }} · {{ substr((string) $group->workload_start_time, 0, 5) }}–{{ substr((string) $group->workload_end_time, 0, 5) }}</span>
            @endif
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('cycles.show', $group->cycle) }}"><x-icon name="arrow-left" /> Grupos</a>
        <a class="btn btn-secondary" href="{{ route('groups.calendar', $group) }}"><x-icon name="calendar-days" /> Calendario</a>
        <button class="btn btn-secondary" type="button" data-modal-open="group-edit-dialog"><x-icon name="edit-3" /> Editar grupo</button>
        <a class="btn" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any() && request('dialog') !== 'edit-group')
    <div class="alert alert-error" role="alert">
        <strong>No se pudieron guardar los cambios.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<dialog class="ui-dialog sheet-dialog" id="group-edit-dialog" aria-labelledby="group-edit-title" @if(request('dialog') === 'edit-group') data-auto-open @endif>
    <div class="dialog-head">
        <div><h2 id="group-edit-title">Editar grupo</h2><p>Actualiza el nombre de este grupo dentro del ciclo.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('groups.update', $group) }}" data-loading-form="1">
        @csrf
        @method('PATCH')
        <div class="dialog-body">
            @if($errors->any() && request('dialog') === 'edit-group')
                <div class="dialog-error" role="alert">
                    <strong>No se pudo actualizar el grupo.</strong>
                    @error('name')<p>{{ $message }}</p>@enderror
                </div>
            @endif
            <label for="edit_group_name">Nombre del grupo</label>
            <input class="field" id="edit_group_name" name="name" value="{{ old('name', $group->name) }}" required maxlength="100" autofocus>
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close><x-icon name="x" /> Cancelar</button>
            <button class="btn" type="submit"><x-icon name="check" /> Guardar nombre</button>
        </div>
    </form>
</dialog>

<details class="card group-panel group-workload-panel" id="group-workload" data-group-panel @if($workloadStatus !== 'complete' || request()->boolean('workload') || $errors->has('workload_type') || $errors->has('workload_start_date') || $errors->has('workload_end_date') || $errors->has('workload_days') || $errors->has('workload_start_time') || $errors->has('workload_end_time') || $errors->has('supervisor_theory_hours') || $errors->has('supervisor_lab_hours')) open @endif>
    <summary class="group-panel-summary">
        <span class="group-panel-icon group-workload-icon"><x-icon name="clock" /></span>
        <span class="group-panel-title">
            <strong>Carga horaria</strong>
            <small>Asignación del supervisor frente a las horas reales de tus cursos</small>
        </span>
        <span class="group-panel-count">{{ number_format($supervisorTheoryHours + $supervisorLabHours, 2) }} h asignadas · {{ number_format($actualTheoryHours + $actualLabHours, 2) }} h dictadas</span>
        <x-section-status :status="$workloadStatus" />
        <x-icon name="chevron-right" class="group-panel-chevron" />
    </summary>
    <div class="group-panel-body">
        <div class="workload-comparison">
            @foreach([
                ['label' => 'Teoría', 'assigned' => $supervisorTheoryHours, 'actual' => $actualTheoryHours, 'diff' => $theoryHoursDifference, 'configured' => $group->supervisor_theory_hours !== null],
                ['label' => 'Taller', 'assigned' => $supervisorLabHours, 'actual' => $actualLabHours, 'diff' => $labHoursDifference, 'configured' => $group->supervisor_lab_hours !== null],
            ] as $comparison)
                <div class="workload-comparison-item">
                    <div class="workload-comparison-title">
                        <strong>{{ $comparison['label'] }}</strong>
                        @if($comparison['configured'])
                            <span class="workload-difference {{ abs($comparison['diff']) <= 0.01 ? 'is-equal' : ($comparison['diff'] > 0 ? 'is-below' : 'is-above') }}">
                                @if(abs($comparison['diff']) <= 0.01)
                                    Coincide
                                @elseif($comparison['diff'] > 0)
                                    {{ number_format($comparison['diff'], 2) }} h por debajo
                                @else
                                    {{ number_format(abs($comparison['diff']), 2) }} h por encima
                                @endif
                            </span>
                        @else
                            <span class="workload-difference is-pending">Sin asignación</span>
                        @endif
                    </div>
                    <div class="workload-comparison-values">
                        <div><small>Supervisor</small><strong>{{ $comparison['configured'] ? number_format($comparison['assigned'], 2).' h' : '—' }}</strong></div>
                        <div><small>Horario real</small><strong>{{ number_format($comparison['actual'], 2) }} h</strong></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="alert alert-info workload-note">
            Las horas del supervisor son la carga autorizada. Las horas reales se calculan automáticamente sumando los bloques semanales de teoría y taller que configures en cada curso. No se reemplaza el horario de las modalidades.
        </div>

        <form method="POST" action="{{ route('groups.workload.update', $group) }}" class="workload-form" data-loading-form="1">
            @csrf
            @method('PATCH')
            <div class="form-row-3">
                <div>
                    <label for="workload_type">Tipo de asignación</label>
                    <select class="field" id="workload_type" name="workload_type">
                        <option value="">Seleccionar</option>
                        <option value="PARTIAL" @selected(old('workload_type', $group->workload_type) === 'PARTIAL')>Parcial</option>
                        <option value="FULL" @selected(old('workload_type', $group->workload_type) === 'FULL')>Completa</option>
                    </select>
                </div>
                <div>
                    <label for="workload_start_date">Fecha de inicio</label>
                    <input class="field" id="workload_start_date" type="date" name="workload_start_date" value="{{ old('workload_start_date', $group->workload_start_date?->format('Y-m-d') ?? $group->cycle->start_date?->format('Y-m-d')) }}">
                    @error('workload_start_date')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div>
                    <label for="workload_end_date">Fecha de término</label>
                    <input class="field" id="workload_end_date" type="date" name="workload_end_date" value="{{ old('workload_end_date', $group->workload_end_date?->format('Y-m-d') ?? $group->cycle->end_date?->format('Y-m-d')) }}">
                    @error('workload_end_date')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="workload-days-field">
                <label>Días asignados por el supervisor</label>
                <div class="weekday-list">
                    @foreach([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $day=>$dayLabel)
                        <label class="weekday">
                            <input type="checkbox" name="workload_days[]" value="{{ $day }}" @checked(in_array($day, array_map('intval', (array) old('workload_days', $group->workload_days ?? [])), true))>
                            {{ $dayLabel }}
                        </label>
                    @endforeach
                </div>
                @error('workload_days')<small class="field-error">{{ $message }}</small>@enderror
            </div>

            <div class="form-row-3">
                <div>
                    <label for="workload_start_time">Hora inicial de disponibilidad</label>
                    <input class="field" id="workload_start_time" type="time" step="900" name="workload_start_time" value="{{ old('workload_start_time', $group->workload_start_time ? substr($group->workload_start_time, 0, 5) : '') }}">
                    @error('workload_start_time')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div>
                    <label for="workload_end_time">Hora final de disponibilidad</label>
                    <input class="field" id="workload_end_time" type="time" step="900" name="workload_end_time" value="{{ old('workload_end_time', $group->workload_end_time ? substr($group->workload_end_time, 0, 5) : '') }}">
                    @error('workload_end_time')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="workload-date-summary">
                    <small>Rango de disponibilidad</small>
                    <strong>{{ $workloadDaysLabels->isNotEmpty() ? $workloadDaysLabels->map(fn ($day) => mb_substr($day, 0, 3))->join(', ') : 'Días pendientes' }}</strong>
                </div>
            </div>

            <div class="workload-hours-fields">
                <div>
                    <label for="supervisor_theory_hours">Horas de teoría asignadas por el supervisor</label>
                    <div class="input-with-suffix">
                        <input class="field" id="supervisor_theory_hours" type="number" name="supervisor_theory_hours" min="0" max="9999" step="0.25" value="{{ old('supervisor_theory_hours', $group->supervisor_theory_hours) }}" placeholder="Ej. 5">
                        <span>h/semana</span>
                    </div>
                    @error('supervisor_theory_hours')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div>
                    <label for="supervisor_lab_hours">Horas de taller asignadas por el supervisor</label>
                    <div class="input-with-suffix">
                        <input class="field" id="supervisor_lab_hours" type="number" name="supervisor_lab_hours" min="0" max="9999" step="0.25" value="{{ old('supervisor_lab_hours', $group->supervisor_lab_hours) }}" placeholder="Ej. 10">
                        <span>h/semana</span>
                    </div>
                    @error('supervisor_lab_hours')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </div>

            @if($errors->has('workload_type') || $errors->has('workload_start_date') || $errors->has('workload_end_date') || $errors->has('workload_start_time') || $errors->has('workload_end_time'))
                <div class="alert alert-error">Revisa los datos de la asignación de carga horaria.</div>
            @endif

            <div class="form-actions">
                <span class="help">La disponibilidad del grupo es referencial. Los días y las horas efectivas de cada curso se detallan en su modalidad.</span>
                <button class="btn" type="submit"><x-icon name="check" /> Guardar carga horaria</button>
            </div>
        </form>
    </div>
</details>

<details class="card group-panel" id="group-roster" data-group-panel @if(($studentCount === 0 && !request('workload')) || request('edit_student') || request('dialog') === 'student-create') open @endif>
    <summary class="group-panel-summary">
        <span class="group-panel-icon"><x-icon name="users" /></span>
        <span class="group-panel-title">
            <strong>Padrón de alumnos</strong>
            <small>Una sola lista para todos los cursos del grupo</small>
        </span>
        <span class="group-panel-count">{{ number_format($studentCount) }} alumnos</span>
        <x-section-status :status="$rosterStatus" />
        <x-icon name="chevron-right" class="group-panel-chevron" />
    </summary>
    <div class="group-panel-body">
        <div class="roster-toolbar">
            <div>
                <strong>Importar o actualizar padrón</strong>
                <p class="help">La importación actualiza los datos existentes y evita duplicados por ID o usuario. La ficha del alumno se comparte donde esté vinculado; las notas se conservan por curso.</p>
            </div>
            <form method="POST" enctype="multipart/form-data" action="{{ route('groups.students.preview', $group) }}" class="roster-upload">
                @csrf
                <input class="field" id="group-roster-file" type="file" name="roster" accept=".xls,.xlsx,.csv,.txt" required>
                <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="file-text" /> Revisar archivo</button>
            </form>
        </div>

        @if($lastRosterImport)
            <div class="import-summary">
                <span><x-icon name="file-text" /> Última carga: <strong>{{ $lastRosterImport->original_filename }}</strong></span>
                <span>{{ $lastRosterImport->created_count }} nuevos</span>
                <span>{{ $lastRosterImport->updated_count }} actualizados</span>
                <span>{{ $lastRosterImport->skipped_count }} omitidos</span>
            </div>
        @endif
            <div class="roster-list-head">
                <div>
                    <strong>Alumnos registrados</strong>
                    <small>{{ $studentCount > 0 ? 'Agrega un alumno, edita sus datos o retíralo de este grupo.' : 'Todavía no hay alumnos. Puedes registrar el primero aquí.' }}</small>
                </div>
                <div class="roster-head-actions">
                    @if($students->isNotEmpty())
                        <input class="filter-input roster-search" id="student-search" type="search" placeholder="Buscar en esta página…" aria-label="Buscar alumno en esta página">
                    @endif
                    <button class="btn btn-sm" type="button" data-modal-open="student-create-dialog">
                        <x-icon name="plus" /> Agregar alumno
                    </button>
                </div>
            </div>


        @if($students->isNotEmpty())
            <div class="table-wrap roster-table-wrap">
                <table class="roster-table">
                    <thead><tr><th>ID institucional</th><th>Apellidos</th><th>Nombres</th><th>Usuario</th><th class="roster-actions-heading">Acciones</th></tr></thead>
                    <tbody id="student-rows">
                    @foreach($students as $student)
                        @php($editing = (string) request('edit_student') === (string) $student->id)
                        @php($useOldValues = (int) old('editing_student_id') === (int) $student->id)
                        @php($formId = 'student-edit-'.$student->id)
                        <tr data-student-row data-search="{{ mb_strtolower($student->institutional_id.' '.$student->surnames.' '.$student->given_names.' '.$student->username) }}" data-editing="{{ $editing ? 'true' : 'false' }}">
                            <td>
                                <span data-student-display @if($editing) hidden @endif>{{ $student->institutional_id ?: '—' }}</span>
                                <input class="field student-edit-input mono" form="{{ $formId }}" name="institutional_id" maxlength="80" data-original-value="{{ $student->institutional_id }}" value="{{ $useOldValues ? old('institutional_id') : $student->institutional_id }}" placeholder="ID institucional" @unless($editing) hidden disabled @endunless>
                                @if($editing) @error('institutional_id')<small class="field-error">{{ $message }}</small>@enderror @endif
                            </td>
                            <td>
                                <span data-student-display @if($editing) hidden @endif>{{ $student->surnames }}</span>
                                <input class="field student-edit-input" form="{{ $formId }}" name="surnames" maxlength="255" data-original-value="{{ $student->surnames }}" value="{{ $useOldValues ? old('surnames') : $student->surnames }}" required @unless($editing) hidden disabled @endunless>
                                @if($editing) @error('surnames')<small class="field-error">{{ $message }}</small>@enderror @endif
                            </td>
                            <td>
                                <span data-student-display @if($editing) hidden @endif>{{ $student->given_names }}</span>
                                <input class="field student-edit-input" form="{{ $formId }}" name="given_names" maxlength="255" data-original-value="{{ $student->given_names }}" value="{{ $useOldValues ? old('given_names') : $student->given_names }}" required @unless($editing) hidden disabled @endunless>
                                @if($editing) @error('given_names')<small class="field-error">{{ $message }}</small>@enderror @endif
                            </td>
                            <td>
                                <span data-student-display @if($editing) hidden @endif>{{ $student->username ?: '—' }}</span>
                                <input class="field student-edit-input mono" form="{{ $formId }}" name="username" maxlength="255" data-original-value="{{ $student->username }}" value="{{ $useOldValues ? old('username') : $student->username }}" placeholder="Usuario" @unless($editing) hidden disabled @endunless>
                                @if($editing) @error('username')<small class="field-error">{{ $message }}</small>@enderror @endif
                            </td>
                            <td class="roster-actions">
                                <form id="{{ $formId }}" method="POST" action="{{ route('groups.students.update', [$group, $student]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="editing_student_id" value="{{ $student->id }}">
                                    <input type="hidden" name="students_page" value="{{ request('students_page', 1) }}">
                                    <button class="btn btn-ghost btn-sm" type="button" data-student-edit @if($editing) hidden @endif><x-icon name="file-text" /> Editar</button>
                                    <button class="btn btn-sm" type="submit" data-student-save @unless($editing) hidden @endunless><x-icon name="check" /> Guardar</button>
                                    <button class="btn btn-ghost btn-sm" type="button" data-student-cancel @unless($editing) hidden @endunless><x-icon name="x" /> Cancelar</button>
                                </form>
                                @if($studentCount > 1)
                                    <form method="POST" action="{{ route('groups.students.destroy', [$group, $student]) }}"
                                          data-confirm="Se retirará a {{ $student->given_names }} {{ $student->surnames }} de este grupo. Sus calificaciones y su ficha se conservarán; solo quedará inactivo en los cursos de este grupo.">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="students_page" value="{{ request('students_page', 1) }}">
                                        <button class="btn btn-danger btn-sm" type="submit" title="Retirar del grupo">
                                            <x-icon name="x" /> Quitar
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-danger btn-sm" type="button" disabled
                                            title="Cada grupo debe conservar al menos un alumno"
                                            aria-label="No se puede retirar al último alumno">
                                        <x-icon name="x" /> Quitar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="empty" id="student-empty" hidden><strong>No se encontraron alumnos.</strong><span>Prueba con otro nombre, ID o usuario.</span></div>
            @if($students->hasPages())
                <x-pagination :paginator="$students" />
            @endif
        @else
            <div class="empty roster-empty">
                <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="users" size="20" /></span>
                <strong>Este grupo todavía no tiene alumnos.</strong>
                <span>Agrega el primer alumno manualmente o importa el padrón oficial del campus.</span>
            </div>
        @endif
    </div>
</details>


<dialog class="ui-dialog sheet-dialog" id="student-create-dialog" aria-labelledby="student-create-title" @if(request('dialog') === 'student-create') data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="student-create-title">Agregar alumno</h2>
            <p>{{ $group->name }} · El padrón se comparte con todos los cursos del grupo.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('groups.students.store', $group) }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any() && request('dialog') === 'student-create')
                <div class="dialog-error" role="alert">
                    <strong>Revisa estos datos:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="form-row">
                <div>
                    <label for="new_student_id">ID institucional</label>
                    <input class="field" id="new_student_id" name="institutional_id" maxlength="80" value="{{ old('institutional_id') }}" placeholder="Código del alumno">
                </div>
                <div>
                    <label for="new_student_username">Usuario</label>
                    <input class="field" id="new_student_username" name="username" maxlength="255" value="{{ old('username') }}" placeholder="Usuario del campus">
                </div>
            </div>
            <p class="help">Registra al menos uno: ID institucional o usuario.</p>
            <div class="form-row">
                <div>
                    <label for="new_student_surnames">Apellidos <span class="muted">*</span></label>
                    <input class="field" id="new_student_surnames" name="surnames" maxlength="255" value="{{ old('surnames') }}" required>
                </div>
                <div>
                    <label for="new_student_names">Nombres <span class="muted">*</span></label>
                    <input class="field" id="new_student_names" name="given_names" maxlength="255" value="{{ old('given_names') }}" required>
                </div>
            </div>
            <p class="help">Si el ID o el usuario ya existe en tu padrón general, se reutilizará esa ficha y se conservarán sus notas.</p>
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close><x-icon name="x" /> Cancelar</button>
            <button class="btn" type="submit"><x-icon name="plus" /> Agregar al grupo</button>
        </div>
    </form>
</dialog>

<details class="card group-panel" id="group-courses" data-group-panel @if($courseCount === 0) open @endif>
    <summary class="group-panel-summary">
        <span class="group-panel-icon"><x-icon name="book-open" /></span>
        <span class="group-panel-title">
            <strong>Cursos del grupo</strong>
            <small>Consulta NRC, modalidad, malla y avance de configuración</small>
        </span>
        <span class="group-panel-count">{{ number_format($courseCount) }} cursos</span>
        <x-section-status :status="$coursesStatus" />
        <x-icon name="chevron-right" class="group-panel-chevron" />
    </summary>
    <div class="group-panel-body">
        @if($assignments->isNotEmpty())
        <div class="course-list" id="course-list">
                @foreach($assignments as $assignment)
                    @php($modalityTotal = $assignment->modalities->count())
                    @php($modalityReady = $assignment->modalities->filter(fn ($item) => $item->start_date && $item->end_date && $item->schedules->where('block_type', 'CLASS')->isNotEmpty())->count())
                    @php($courseScheduleReady = $modalityTotal > 0 && $modalityReady === $modalityTotal)
                    <article class="course-row-card">
                        <div class="course-row-main">
                            <div class="course-row-title">
                                <strong>{{ $assignment->course->name }}</strong>
                                <small>{{ $assignment->course->code ?: 'Sin código' }} · {{ $modalityTotal }} modalidades</small>
                            </div>
                            <div class="course-row-actions">
                                <a class="btn btn-ghost btn-sm" href="{{ route('curricula.show', $assignment) }}"><x-icon name="book-open" /> Malla</a>
                                <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-confirm="Se retirará este curso de {{ $group->name }} junto con sus modalidades, matrículas, evaluaciones y documentos. El curso del catálogo general se conservará para usarlo en otros grupos. Esta acción no se puede deshacer.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <div class="course-row-status" aria-label="Estado de configuración">
                            @if($modalityTotal > 0)
                                <span class="course-status-chip {{ $courseScheduleReady ? 'is-ready' : 'is-pending' }}">
                                    <x-icon name="{{ $courseScheduleReady ? 'check-circle' : 'clock' }}" size="12" />
                                    Horarios {{ $modalityReady }}/{{ $modalityTotal }}
                                </span>
                            @else
                                <span class="course-status-chip is-pending">Modalidades pendientes</span>
                            @endif
                            <span class="course-status-chip {{ $assignment->curriculum ? 'is-ready' : 'is-pending' }}">
                                <x-icon name="{{ $assignment->curriculum ? 'book-check' : 'book-open' }}" size="12" />
                                {{ $assignment->curriculum ? 'Malla vinculada' : 'Malla pendiente' }}
                            </span>
                        </div>
                        <div class="course-row-modalities">
                            @forelse($assignment->modalities as $modality)
                                <a class="modality-link {{ $modality->modality === 'TEC' ? 'modality-tec' : 'modality-tall' }}" href="{{ route('modalities.show', $modality) }}">
                                    <strong>{{ $modality->modality }}</strong>
                                    <span>NRC {{ $modality->nrc ?: 'Pendiente' }}</span>
                                    <span>{{ $modality->start_date?->format('d/m/Y') ?: 'Sin fecha' }} – {{ $modality->end_date?->format('d/m/Y') ?: 'Sin fecha' }}</span>
                                    <x-icon name="arrow-up-right" size="13" />
                                </a>
                            @empty
                                <span class="badge badge-orange">Modalidad pendiente</span>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty">
                <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="book-open" size="20" /></span>
                <strong>Aún no hay cursos en este grupo.</strong>
                <span>Registra varios cursos desde una sola tabla.</span>
                <div class="mt-16"><a class="btn" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a></div>
            </div>
        @endif
    </div>
</details>

<details class="card group-panel group-talk-panel" id="group-talks" data-group-panel @if(request()->boolean('talks')) open @endif>
    <summary class="group-panel-summary">
        <span class="group-panel-icon group-talk-icon"><x-icon name="clipboard-check" /></span>
        <span class="group-panel-title">
            <strong>Charlas de 5 minutos</strong>
            <small>Fechas calculadas automáticamente según la carga horaria del grupo</small>
        </span>
        <span class="group-panel-count">{{ $talkDoneCount }} realizadas · {{ $talkPendingCount }} pendientes</span>
        <x-section-status :status="$talkStatus" />
        <x-icon name="chevron-right" class="group-panel-chevron" />
    </summary>
    <div class="group-panel-body">
        <div class="talks-intro">
            <div>
                <strong>Catálogo institucional</strong>
                <p>Se programa una charla por cada día disponible del grupo y se excluyen los feriados. Al terminar los 96 temas oficiales, el catálogo vuelve automáticamente al primero. Marca el check al realizarla y ajusta los participantes de cada charla; el valor inicial corresponde al padrón del grupo.</p>
            </div>
            @if($groupTalks->isNotEmpty())
                <form method="POST" action="{{ route('documents.group-talks', $group) }}">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="file-text" /> Descargar Excel del grupo</button>
                </form>
            @endif
        </div>

        <div class="talks-period">
            <x-icon name="calendar-range" size="15" />
            @if($talkScheduleSummary['range_configured'])
                <strong>
                    {{ \Illuminate\Support\Carbon::parse($talkScheduleSummary['range_start'])->format('d/m/Y') }}
                    – {{ \Illuminate\Support\Carbon::parse($talkScheduleSummary['range_end'])->format('d/m/Y') }}
                </strong>
                <span>{{ $talkScheduleSummary['eligible_dates_count'] }} fechas disponibles</span>
                <span>Días: {{ implode(', ', $talkScheduleSummary['weekday_labels']) }}</span>
                @if($talkScheduleSummary['uses_cycle_range'])
                    <span>Se usa el rango del ciclo porque el rango propio de carga no está completo</span>
                @endif
                @if($talkScheduleSummary['uses_default_weekdays'])
                    <span>Se usa lunes a viernes como días predeterminados</span>
                @endif
            @else
                <strong>Periodo sin configurar</strong>
                <span>Completa las fechas Desde/Hasta del ciclo o de Carga horaria; por defecto se usan lunes a viernes</span>
            @endif
        </div>

        <div class="talks-summary">
            <span><strong>{{ $talkScheduleSummary['catalogue_topic_count'] }}</strong> temas oficiales</span>
            <span><strong>{{ $talkAssignedCount }}</strong> charlas programadas</span>
            <span><strong>{{ $talkDoneCount }}</strong> realizadas</span>
            <span><strong>{{ $talkPendingCount }}</strong> pendientes</span>
        </div>

        @if($groupTalks->isNotEmpty())
            <div class="table-wrap talks-table-wrap">
                <table class="talks-table">
                    <thead>
                        <tr><th>Fecha</th><th>Tema institucional</th><th>Estado</th><th>Participantes</th><th>Observaciones</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        @foreach($groupTalks as $talk)
                            <tr>
                                <td>
                                    <span class="talk-date">
                                        <small>{{ $talk->scheduled_date->copy()->locale('es')->translatedFormat('l') }}</small>
                                        <strong>{{ $talk->scheduled_date->format('d/m/Y') }}</strong>
                                    </span>
                                </td>
                                <td class="talk-topic">{{ $talk->topic }}</td>
                                <td colspan="4">
                                    <form method="POST" action="{{ route('groups.talks.update', [$group, $talk]) }}" class="talk-execution-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="pending">
                                        <label class="talk-status-toggle">
                                            <input
                                                type="checkbox"
                                                name="status"
                                                value="done"
                                                @checked($talk->status === 'done')
                                                data-auto-submit-change
                                                aria-label="{{ $talk->status === 'done' ? 'Marcar charla como pendiente' : 'Marcar charla como realizada' }}"
                                            >
                                            <span class="talk-status-mark" aria-hidden="true"></span>
                                            <span class="talk-status-label">{{ $talk->status === 'done' ? 'Realizada' : 'Pendiente' }}</span>
                                        </label>
                                        <div class="talk-participants">
                                            <input
                                                class="field talk-participant-input"
                                                type="number"
                                                name="participant_count"
                                                min="0"
                                                max="9999"
                                                step="1"
                                                required
                                                value="{{ $talk->participant_count ?? $studentCount }}"
                                                aria-label="Cantidad de participantes de la charla"
                                                title="Puedes editar la cantidad de participantes de esta charla"
                                            >
                                            <small>alumnos</small>
                                        </div>
                                        <input class="field" name="observations" value="{{ $talk->observations }}" maxlength="2000" placeholder="Observaciones del grupo" aria-label="Observaciones">
                                        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="check" /> Guardar cambios</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(!$talkScheduleSummary['range_configured'])
                <div class="alert alert-info mt-16">
                    Para programar las charlas, completa en <strong>Carga horaria</strong> el inicio, el término y los días de disponibilidad.
                </div>
            @elseif($talkScheduleSummary['eligible_dates_count'] === 0)
                <div class="alert alert-warning mt-16">
                    No se encontraron fechas disponibles dentro del rango del grupo. Revisa las fechas de carga horaria, los días asignados y los feriados.
                </div>
            @endif
        @else
            @if(!$talkScheduleSummary['range_configured'])
                <div class="empty"><strong>Configura la carga horaria del grupo.</strong><span>Al guardar las fechas y los días disponibles, el sistema programará automáticamente las charlas.</span></div>
            @elseif($talkScheduleSummary['eligible_dates_count'] === 0)
                <div class="empty"><strong>No hay días disponibles para programar charlas.</strong><span>Revisa el rango de carga horaria, los días seleccionados y los feriados.</span></div>
            @else
                <div class="empty"><strong>Las charlas del grupo están al día.</strong><span>No hay temas adicionales sin fecha. El catálogo se repetirá automáticamente cuando existan más días disponibles.</span></div>
            @endif
        @endif
    </div>
</details>
@endsection

@push('styles')
<style>
.section-status{display:inline-flex;align-items:center;justify-content:center;gap:5px;flex:none;padding:5px 9px;border:1px solid;border-radius:999px;font-size:10px;font-weight:750;line-height:1.2;white-space:nowrap}
.section-status-symbol{font-size:11px;font-weight:900}
.section-status-complete{color:#24734b;background:#edf8f0;border-color:#cfe8d6}
.section-status-partial{color:#98651d;background:#fff7e8;border-color:#f1dfb8}
.section-status-pending{color:#68747c;background:#f3f5f6;border-color:#dce2e5}


.group-workload-panel{border-color:#d9e6e1}
.group-workload-icon{background:#edf6f1;color:#35765b}
.group-workload-panel[open]>.group-panel-summary{background:#f7fbf8}
.workload-comparison{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:14px 0}
.workload-comparison-item{padding:12px;border:1px solid #e3ebe6;border-radius:8px;background:#fbfdfb}
.workload-comparison-title{display:flex;align-items:center;justify-content:space-between;gap:8px;color:#3d594c;font-size:12px}
.workload-difference{padding:4px 7px;border:1px solid;border-radius:20px;font-size:9px;font-weight:750;white-space:nowrap}
.workload-difference.is-equal{color:#24734b;background:#edf8f0;border-color:#cfe8d6}
.workload-difference.is-below{color:#98651d;background:#fff7e8;border-color:#f1dfb8}
.workload-difference.is-above{color:#a0443d;background:#fff1ef;border-color:#f0d5d1}
.workload-difference.is-pending{color:#68747c;background:#f3f5f6;border-color:#dce2e5}
.workload-comparison-values{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px}
.workload-comparison-values div{display:flex;flex-direction:column;gap:3px}
.workload-comparison-values small{color:#819089;font-size:10px}
.workload-comparison-values strong{color:#2c493b;font-size:18px;font-weight:800;font-variant-numeric:tabular-nums}
.workload-note{margin:12px 0}
.workload-form{display:grid;gap:15px;padding-top:13px;border-top:1px solid #edf1ee}
.workload-days-field>label,.workload-form label{display:block;margin-bottom:6px;color:#52625a;font-size:11px;font-weight:700}
.workload-days-field{margin-top:13px}
.workload-days-field .weekday-list{display:flex;flex-wrap:wrap;gap:6px}
.workload-date-summary{display:flex;flex-direction:column;justify-content:center;gap:5px;padding:8px 10px;border:1px solid #e4ebe6;border-radius:7px;background:#f8fbf9}
.workload-date-summary small{color:#819089;font-size:10px}
.workload-date-summary strong{color:#365c49;font-size:12px}
.workload-hours-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.input-with-suffix{display:flex;align-items:center;gap:7px}
.input-with-suffix .field{min-width:0;flex:1}
.input-with-suffix span{color:#728179;font-size:10px;white-space:nowrap}
html[data-theme="dark"] .section-status-complete{color:#b9e9ca;background:#173a29;border-color:#285a40}
html[data-theme="dark"] .section-status-partial{color:#f1d49a;background:#3b2e17;border-color:#65502a}
html[data-theme="dark"] .section-status-pending{color:#c0cbd0;background:#26343a;border-color:#40515a}
html[data-theme="dark"] .workload-comparison-item{background:#172820;border-color:#355144}
html[data-theme="dark"] .workload-comparison-title,html[data-theme="dark"] .workload-comparison-values strong{color:#d0e6d8}
html[data-theme="dark"] .workload-date-summary{background:#172820;border-color:#355144}
html[data-theme="dark"] .workload-date-summary strong{color:#c4e6d0}
html[data-theme="dark"] .workload-date-summary small,html[data-theme="dark"] .workload-comparison-values small{color:#9eb7aa}
html[data-theme="dark"] .group-workload-panel[open]>.group-panel-summary{background:#172820}
@media(max-width:760px){.workload-comparison{grid-template-columns:1fr}.workload-hours-fields{grid-template-columns:1fr}}

.group-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:11px;margin-bottom:14px}
.stat-hint{display:block;margin-top:3px;color:#71808a;font-size:11px}
.group-progress{margin-bottom:14px;padding:17px 19px}
.group-progress-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
.group-progress-heading h2{margin:2px 0 0;font-size:17px}
.progress-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.progress-item{min-width:0}
.progress-label{display:flex;justify-content:space-between;gap:8px;margin-bottom:7px;font-size:12px;color:#4d626e}
.progress-label strong{color:#274d61;white-space:nowrap}
.progress-track{height:8px;overflow:hidden;border-radius:99px;background:#e8eef1}
.progress-track span{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#3a8cab,#47a88e);transition:width .25s ease}
.progress-item small{display:block;margin-top:6px;color:#76868e;font-size:10px}
.group-panel{padding:0;overflow:hidden;scroll-margin-top:18px}
.group-panel+.group-panel{margin-top:12px}
.group-panel-summary{display:flex;align-items:center;gap:12px;padding:15px 18px;cursor:pointer;list-style:none}
.group-panel-summary::-webkit-details-marker{display:none}
.group-panel-icon{display:grid;place-items:center;width:37px;height:37px;flex:0 0 37px;border-radius:10px;background:#eaf4f8;color:#347992}
.group-panel-title{display:flex;flex:1;min-width:0;flex-direction:column;gap:3px}
.group-panel-title strong{font-size:14px;color:#273f4c}
.group-panel-title small{font-size:11px;color:#7b8990}
.group-panel-count{padding:5px 9px;border-radius:20px;background:#edf4f7;color:#3d7187;font-size:11px;font-weight:750;white-space:nowrap}
.group-panel-chevron{color:#78909a;transition:transform .2s ease}
.group-panel[open] .group-panel-chevron{transform:rotate(90deg)}
.group-panel-body{padding:0 18px 18px;border-top:1px solid #edf1f3}
.roster-toolbar{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding:15px 0}
.roster-toolbar>div{max-width:480px}
.roster-toolbar>div>strong{font-size:12px;color:#344f5d}
.roster-upload{display:flex;align-items:center;gap:8px;max-width:480px}
.roster-upload .field{min-width:210px;font-size:11px}
.import-summary{display:flex;flex-wrap:wrap;gap:8px 15px;padding:9px 11px;margin-bottom:12px;border-radius:8px;background:#f3f8fa;color:#5f7782;font-size:10px}
.import-summary span{display:inline-flex;align-items:center;gap:5px}
.import-summary strong{color:#34596a}
.roster-list-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:16px 0 9px}
.roster-list-head>div{display:flex;flex-direction:column;gap:3px}
.roster-list-head strong{font-size:12px}
.roster-list-head small{font-size:10px;color:#819098}
.roster-search{max-width:250px}
.roster-head-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}
.roster-table .roster-actions{min-width:175px}
.roster-table .roster-actions form{display:inline-flex;gap:5px;align-items:center}
@media(max-width:600px){.roster-head-actions{align-items:stretch;flex-direction:column}.roster-head-actions .btn{width:100%}.roster-head-actions .roster-search{max-width:none;width:100%}}

.roster-table-wrap{border:1px solid #e7edf0;border-radius:9px}
/* Encabezados fijos: cada tabla conserva su cabecera mientras se recorren sus filas. */
.roster-table-wrap,.talks-table-wrap{
    max-height:min(65vh,560px);
    overflow:auto;
    overscroll-behavior:contain;
}
.roster-table-wrap .roster-table thead th,
.talks-table-wrap .talks-table thead th{
    position:sticky;
    top:0;
    z-index:4;
}
.roster-table{min-width:800px}
.roster-table th{background:#f2f7f9;color:#4d6d7b;font-size:10px}
.roster-table td{font-size:11px;vertical-align:middle}
.roster-table .field{min-width:130px;padding:7px 8px;font-size:11px}
.roster-table td:nth-child(2) .field,.roster-table td:nth-child(3) .field{min-width:150px}
.roster-table .roster-actions-heading{text-align:right}
.roster-table .roster-actions{text-align:right;white-space:nowrap}
.roster-table .roster-actions form{display:inline-flex;gap:5px;align-items:center}
.roster-table tr[data-editing="true"]{background:#f5fafc}
.roster-table tr[data-editing="true"] td{padding-top:8px;padding-bottom:8px}
.roster-empty{padding:26px 12px}
.course-list{display:grid;gap:9px;margin-top:12px}
.course-row-card{padding:14px 15px;border:1px solid var(--line);border-radius:9px;background:var(--surface)}
.course-row-main{display:flex;align-items:center;justify-content:space-between;gap:12px}
.course-row-title{display:flex;flex-direction:column;gap:4px;min-width:0}
.course-row-title strong{font-size:13px;color:var(--ink)}
.course-row-title small{font-size:10px;color:var(--muted)}
.course-row-actions{flex:0 0 auto;display:flex;align-items:center;gap:6px}
.course-row-status{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin:9px 0}
.course-status-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid var(--line);border-radius:6px;font-size:10px;font-weight:650}
.course-status-chip.is-ready{border-color:color-mix(in srgb,var(--success) 24%,var(--line));background:var(--success-bg);color:var(--success)}
.course-status-chip.is-pending{border-color:color-mix(in srgb,var(--warning) 24%,var(--line));background:var(--warning-bg);color:var(--warning)}
.course-row-modalities{display:flex;flex-wrap:wrap;gap:7px}
.modality-link{display:flex;align-items:center;gap:7px;max-width:100%;padding:7px 9px;border:1px solid #dfebf0;border-radius:7px;text-decoration:none;color:#456473;font-size:10px}
.modality-link strong{font-size:10px}
.modality-tec{background:#f1f8fb}
.modality-tall{background:#f5f5fb}
.modality-link span{white-space:nowrap}
.modality-link:hover{border-color:#8cb8c8;background:#f7fbfc}
.field-error{display:block;margin-top:4px;font-size:10px;color:#b42318}
 
.group-talk-panel{border-color:color-mix(in srgb,var(--info) 24%,var(--line))}
.group-talk-icon{background:var(--info-bg);color:var(--info)}
.group-talk-panel[open]>.group-panel-summary{background:var(--info-bg)}
.talks-intro{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:14px 0;padding:12px 14px;border:1px solid color-mix(in srgb,var(--info) 22%,var(--line));border-radius:8px;background:var(--info-bg)}
.talks-intro strong{display:block;color:var(--info);font-size:12px}
.talks-intro p{max-width:650px;margin:4px 0 0;color:var(--muted);font-size:10px;line-height:1.55}
.talks-summary{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 13px}
.talks-period{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 11px;padding:9px 11px;border:1px solid color-mix(in srgb,var(--info) 22%,var(--line));border-radius:8px;background:var(--info-bg);color:var(--muted);font-size:10px}
.talks-period strong{color:var(--info);font-size:11px}
.talks-period>span{color:var(--muted)}
.talks-summary span{padding:6px 9px;border:1px solid color-mix(in srgb,var(--info) 20%,var(--line));border-radius:6px;background:var(--info-bg);color:var(--muted);font-size:10px}
.talks-summary strong{color:var(--info);font-size:11px}
.talks-table-wrap{border:1px solid var(--line);border-radius:8px}
.talks-table{min-width:1050px}
.talks-table th{background:var(--info-bg);color:var(--info)}
.talks-table td{vertical-align:middle}
.talk-date{display:grid;gap:2px;white-space:nowrap;color:var(--info)}.talk-date small{color:var(--muted);font-size:9px;font-weight:650;text-transform:capitalize}.talk-date strong{font-size:11px;font-weight:800}
.talk-topic{min-width:260px;color:var(--ink);font-size:11px;font-weight:600}
.talk-execution-form{display:grid;grid-template-columns:132px 120px minmax(170px,1fr) auto;align-items:center;gap:7px;min-width:580px}
.talk-execution-form .field{min-height:33px;padding:6px 8px;font-size:10px}
.talk-participant-input{width:64px;min-width:64px;text-align:center;font-variant-numeric:tabular-nums}
.talk-status-toggle{position:relative;display:inline-flex;align-items:center;gap:8px;min-height:36px;color:var(--muted);font-size:10px;font-weight:700;cursor:pointer;white-space:nowrap}
.talk-status-toggle input{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.talk-status-mark{display:grid;place-items:center;width:19px;height:19px;flex:none;border:1.5px solid var(--line-strong);border-radius:5px;background:var(--surface);color:#fff;transition:background .14s ease,border-color .14s ease}
.talk-status-mark::after{content:"✓";font-size:13px;font-weight:900;line-height:1;opacity:0;transform:scale(.7);transition:opacity .14s ease,transform .14s ease}
.talk-status-toggle input:checked+.talk-status-mark{border-color:var(--success);background:var(--success)}
.talk-status-toggle input:checked+.talk-status-mark::after{opacity:1;transform:scale(1)}
.talk-status-toggle input:focus-visible+.talk-status-mark{outline:3px solid color-mix(in srgb,var(--success) 28%,transparent);outline-offset:2px}
.talk-status-toggle:has(input:checked){color:var(--success)}
.talk-participants{display:flex;align-items:baseline;justify-content:center;gap:4px;min-height:34px;padding:7px 8px;border:1px solid color-mix(in srgb,var(--info) 22%,var(--line));border-radius:7px;background:var(--info-bg);color:var(--info);font-variant-numeric:tabular-nums;white-space:nowrap}
.talk-participants strong{font-size:12px;font-weight:800}
.talk-participants small{color:var(--muted);font-size:9px}
html[data-theme="dark"] .talks-intro strong,
html[data-theme="dark"] .talks-period strong,
html[data-theme="dark"] .talks-summary strong,
html[data-theme="dark"] .talks-table th,
html[data-theme="dark"] .talk-date,
html[data-theme="dark"] .talk-participants{color:#b6d9ee}
html[data-theme="dark"] .talk-participants{border-color:#355a70}
html[data-theme="dark"] .talk-status-mark{background:var(--surface);border-color:var(--line-strong)}
html[data-theme="dark"] .talk-status-toggle input:checked+.talk-status-mark{border-color:var(--success);background:var(--success)}
html[data-theme="dark"] .talk-topic{color:var(--ink)}
@media(max-width:760px){.talks-intro{align-items:stretch;flex-direction:column}.talks-intro form,.talks-intro .btn{width:100%}.talk-execution-form{grid-template-columns:repeat(2,minmax(0,1fr));min-width:0}.talk-execution-form input[name="observations"],.talk-execution-form>button{grid-column:1/-1}.talk-execution-form>button{width:100%}}

@media(max-width:850px){.progress-grid{grid-template-columns:1fr;gap:13px}.roster-toolbar{flex-direction:column}.roster-upload{width:100%;max-width:none}.roster-upload .field{flex:1}.course-row-status{grid-template-columns:1fr}}
@media(max-width:600px){.group-page-head{align-items:flex-start}.group-page-head .actions{flex-wrap:wrap}.group-progress{padding:14px}.group-panel-summary{padding:13px}.group-panel-count{font-size:10px}.roster-upload{align-items:stretch;flex-direction:column}.roster-upload .field{width:100%;min-width:0}.roster-list-head{align-items:stretch;flex-direction:column}.roster-search{max-width:none;width:100%}.course-row-main{align-items:flex-start}.course-row-status{gap:8px}.modality-link{width:100%;flex-wrap:wrap}.modality-link span:nth-of-type(2){white-space:normal}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const workloadPanel = document.getElementById('group-workload');
    if (workloadPanel && location.hash === '#group-workload') workloadPanel.open = true;

    const panel = document.getElementById('group-roster');
    if (panel && (location.hash === '#group-roster' || document.querySelector('[data-student-row][data-editing="true"]'))) {
        panel.open = true;
    }
    const coursesPanel = document.getElementById('group-courses');
    if (coursesPanel && location.hash === '#group-courses') coursesPanel.open = true;
    const talksPanel = document.getElementById('group-talks');
    if (talksPanel && (location.hash === '#group-talks' || new URLSearchParams(location.search).get('talks') === '1')) talksPanel.open = true;

    document.querySelectorAll('[data-auto-submit-change]').forEach(control => {
        control.addEventListener('change', () => {
            const form = control.form;
            if (form && typeof form.requestSubmit === 'function') form.requestSubmit();
        });
    });

    document.querySelectorAll('[data-student-row]').forEach(row => {
        const inputs = [...row.querySelectorAll('.student-edit-input')];
        const displays = [...row.querySelectorAll('[data-student-display]')];
        const editButton = row.querySelector('[data-student-edit]');
        const saveButton = row.querySelector('[data-student-save]');
        const cancelButton = row.querySelector('[data-student-cancel]');

        const setEditing = (editing, discardChanges = false) => {
            row.dataset.editing = editing ? 'true' : 'false';
            inputs.forEach(input => {
                if (!editing && discardChanges) input.value = input.dataset.originalValue || '';
                input.hidden = !editing;
                input.disabled = !editing;
            });
            displays.forEach(display => { display.hidden = editing; });
            if (editButton) editButton.hidden = editing;
            if (saveButton) saveButton.hidden = !editing;
            if (cancelButton) cancelButton.hidden = !editing;
            if (editing) inputs[0]?.focus();
        };

        editButton?.addEventListener('click', () => {
            document.querySelectorAll('[data-student-row][data-editing="true"]').forEach(other => {
                if (other !== row) other.querySelector('[data-student-cancel]')?.click();
            });
            setEditing(true);
        });
        cancelButton?.addEventListener('click', () => setEditing(false, true));
    });

    const studentSearch = document.getElementById('student-search');
    const studentRows = [...document.querySelectorAll('#student-rows [data-student-row]')];
    const studentEmpty = document.getElementById('student-empty');
    studentSearch?.addEventListener('input', () => {
        const term = studentSearch.value.trim().toLocaleLowerCase();
        let visible = 0;
        studentRows.forEach(row => {
            const show = row.dataset.search.includes(term);
            row.hidden = !show;
            if (show) visible++;
        });
        if (studentEmpty) studentEmpty.hidden = visible > 0;
    });


})();
</script>
@endpush
