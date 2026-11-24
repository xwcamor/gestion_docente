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
            <span>·</span>
            <span><x-icon name="clock" size="12" /> Asignado: {{ $group->assigned_at?->format('d/m/Y') ?? 'Sin fecha' }}</span>
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('cycles.show', $group->cycle) }}"><x-icon name="arrow-left" /> Grupos</a>
        <a class="btn" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>No se pudieron guardar los cambios.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="group-stats">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Alumnos</span><span class="stat-icon"><x-icon name="users" /></span></div>
        <div class="stat">{{ number_format($studentCount) }}</div>
        <span class="stat-hint">Padrón del grupo</span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Cursos</span><span class="stat-icon"><x-icon name="book-open" /></span></div>
        <div class="stat">{{ number_format($courseCount) }}</div>
        <span class="stat-hint">{{ $modalityCount }} modalidades</span>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Carga semanal</span><span class="stat-icon"><x-icon name="clock" /></span></div>
        <div class="stat">{{ number_format($declaredWeeklyHours, 1) }} h</div>
        <span class="stat-hint">Horas declaradas</span>
    </div>
</div>

<section class="group-progress card" aria-labelledby="group-progress-title">
    <div class="group-progress-heading">
        <div><p class="eyebrow">Resumen de configuración</p><h2 id="group-progress-title">Avance académico del grupo</h2></div>
        <span class="muted small">Datos actuales</span>
    </div>
    <div class="progress-grid">
        <div class="progress-item">
            <div class="progress-label"><span>Cursos con modalidad</span><strong>{{ $coursesWithModalityCount }} / {{ $courseCount }}</strong></div>
            <div class="progress-track" role="progressbar" aria-label="Cursos con modalidad asignada" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseModalityPercent }}"><span style="width:{{ $courseModalityPercent }}%"></span></div>
            <small>{{ $courseModalityPercent }}% de cursos con modalidad</small>
        </div>
        <div class="progress-item">
            <div class="progress-label"><span>Horarios configurados</span><strong>{{ $configuredModalityCount }} / {{ $modalityCount }}</strong></div>
            <div class="progress-track" role="progressbar" aria-label="Modalidades con fechas y horario configurados" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $schedulePercent }}"><span style="width:{{ $schedulePercent }}%"></span></div>
            <small>{{ $schedulePercent }}% de modalidades con fechas y horario</small>
        </div>
        <div class="progress-item">
            <div class="progress-label"><span>Mallas vinculadas</span><strong>{{ $curriculumCount }} / {{ $courseCount }}</strong></div>
            <div class="progress-track" role="progressbar" aria-label="Cursos con malla curricular vinculada" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $curriculumPercent }}"><span style="width:{{ $curriculumPercent }}%"></span></div>
            <small>{{ $curriculumPercent }}% de cursos con malla curricular</small>
        </div>
    </div>
</section>

<details class="card group-panel" id="group-roster" data-group-panel @if($studentCount === 0 || request('edit_student')) open @endif>
    <summary class="group-panel-summary">
        <span class="group-panel-icon"><x-icon name="users" /></span>
        <span class="group-panel-title">
            <strong>Padrón de alumnos</strong>
            <small>Una sola lista para todos los cursos del grupo</small>
        </span>
        <span class="group-panel-count">{{ number_format($studentCount) }} alumnos</span>
        <x-icon name="chevron-down" class="group-panel-chevron" />
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
                <span><x-icon name="file-check" /> Última carga: <strong>{{ $lastRosterImport->original_filename }}</strong></span>
                <span>{{ $lastRosterImport->created_count }} nuevos</span>
                <span>{{ $lastRosterImport->updated_count }} actualizados</span>
                <span>{{ $lastRosterImport->skipped_count }} omitidos</span>
            </div>
        @endif

        @if($students->isNotEmpty())
            <div class="roster-list-head">
                <div><strong>Alumnos registrados</strong><small>Usa Editar para cambiar los datos sin salir de esta tabla.</small></div>
                <input class="filter-input roster-search" id="student-search" type="search" placeholder="Buscar en esta página…" aria-label="Buscar alumno en esta página">
            </div>
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
                                    <button class="btn btn-ghost btn-sm" type="button" data-student-edit @if($editing) hidden @endif><x-icon name="pencil" /> Editar</button>
                                    <button class="btn btn-sm" type="submit" data-student-save @unless($editing) hidden @endunless><x-icon name="check" /> Guardar</button>
                                    <button class="btn btn-ghost btn-sm" type="button" data-student-cancel @unless($editing) hidden @endunless>Cancelar</button>
                                </form>
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
                <span>Selecciona el archivo oficial del campus y revisa la vista previa antes de importarlo.</span>
            </div>
        @endif
    </div>
</details>

<details class="card group-panel" id="group-courses" data-group-panel @if($courseCount === 0) open @endif>
    <summary class="group-panel-summary">
        <span class="group-panel-icon"><x-icon name="book-open" /></span>
        <span class="group-panel-title">
            <strong>Cursos del grupo</strong>
            <small>Consulta NRC, modalidad, malla y avance de configuración</small>
        </span>
        <span class="group-panel-count">{{ number_format($courseCount) }} cursos</span>
        <x-icon name="chevron-down" class="group-panel-chevron" />
    </summary>
    <div class="group-panel-body">
        @if($assignments->isNotEmpty())
            <div class="filter-bar course-toolbar">
                <input class="filter-input" id="course-search" type="search" placeholder="Buscar curso…" aria-label="Buscar cursos">
                <select class="filter-input" id="modality-filter" aria-label="Filtrar por modalidad">
                    <option value="">Todas las modalidades</option>
                    <option value="TEC">Solo TEC</option>
                    <option value="TALL">Solo TALL</option>
                    <option value="BOTH">TEC y TALL</option>
                </select>
                <span class="help" id="course-count">{{ $assignments->count() }} en esta página</span>
            </div>
            <div class="course-list" id="course-list">
                @foreach($assignments as $assignment)
                    @php($types = $assignment->modalities->pluck('modality')->all())
                    @php($modalityTotal = $assignment->modalities->count())
                    @php($modalityReady = $assignment->modalities->filter(fn ($item) => $item->start_date && $item->end_date && $item->schedules->isNotEmpty())->count())
                    @php($courseProgress = $modalityTotal > 0 ? (int) round(($modalityReady / $modalityTotal) * 100) : 0)
                    <article class="course-row-card" data-search="{{ mb_strtolower($assignment->course->name.' '.$assignment->course->code) }}" data-modalities="{{ implode(',', $types) }}">
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
                                    <button class="btn btn-danger btn-sm" type="submit"><x-icon name="trash-2" /> Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <div class="course-row-status">
                            <div class="course-row-progress">
                                <div class="progress-label"><span>Horario de modalidades</span><strong>{{ $modalityReady }}/{{ $modalityTotal }} · {{ $courseProgress }}%</strong></div>
                                <div class="progress-track" role="progressbar" aria-label="Avance de horarios para {{ $assignment->course->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseProgress }}"><span style="width:{{ $courseProgress }}%"></span></div>
                            </div>
                            <span class="badge {{ $assignment->curriculum ? 'badge-green' : 'badge-orange' }}">{{ $assignment->curriculum ? 'Malla vinculada' : 'Malla pendiente' }}</span>
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
            <div class="empty" id="course-empty" hidden><strong>No hay cursos que coincidan.</strong><span>Prueba con otro nombre o modalidad.</span></div>
            @if($assignments->hasPages())
                <x-pagination :paginator="$assignments" />
            @endif
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
@endsection

@push('styles')
<style>
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
.group-panel[open] .group-panel-chevron{transform:rotate(180deg)}
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
.roster-table-wrap{border:1px solid #e7edf0;border-radius:9px}
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
.course-toolbar{display:flex;align-items:center;gap:10px;margin:13px 0}
.course-toolbar .filter-input{max-width:260px}
.course-list{display:grid;gap:9px}
.course-row-card{padding:13px 14px;border:1px solid #e5edf1;border-radius:9px;background:#fff}
.course-row-main{display:flex;align-items:center;justify-content:space-between;gap:10px}
.course-row-title{display:flex;flex-direction:column;gap:4px;min-width:0}
.course-row-title strong{font-size:13px;color:#2f4c5b}
.course-row-title small{font-size:10px;color:#7e8d94}
.course-row-actions{flex:0 0 auto;display:flex;align-items:center;gap:5px}
.course-row-status{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:15px;margin:11px 0}
.course-row-progress{max-width:420px;min-width:0}
.course-row-progress .progress-label{font-size:10px;margin-bottom:5px}
.course-row-progress .progress-track{height:6px}
.course-row-modalities{display:flex;flex-wrap:wrap;gap:7px}
.modality-link{display:flex;align-items:center;gap:7px;max-width:100%;padding:7px 9px;border:1px solid #dfebf0;border-radius:7px;text-decoration:none;color:#456473;font-size:10px}
.modality-link strong{font-size:10px}
.modality-tec{background:#f1f8fb}
.modality-tall{background:#f5f5fb}
.modality-link span{white-space:nowrap}
.modality-link:hover{border-color:#8cb8c8;background:#f7fbfc}
.field-error{display:block;margin-top:4px;font-size:10px;color:#b42318}
@media(max-width:850px){.progress-grid{grid-template-columns:1fr;gap:13px}.roster-toolbar{flex-direction:column}.roster-upload{width:100%;max-width:none}.roster-upload .field{flex:1}.course-row-status{grid-template-columns:1fr}}
@media(max-width:600px){.group-page-head{align-items:flex-start}.group-page-head .actions{flex-wrap:wrap}.group-progress{padding:14px}.group-panel-summary{padding:13px}.group-panel-count{font-size:10px}.roster-upload{align-items:stretch;flex-direction:column}.roster-upload .field{width:100%;min-width:0}.roster-list-head{align-items:stretch;flex-direction:column}.roster-search{max-width:none;width:100%}.course-toolbar{align-items:stretch;flex-direction:column}.course-toolbar .filter-input{max-width:none;width:100%}.course-row-main{align-items:flex-start}.course-row-status{gap:8px}.modality-link{width:100%;flex-wrap:wrap}.modality-link span:nth-of-type(2){white-space:normal}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const panel = document.getElementById('group-roster');
    if (panel && (location.hash === '#group-roster' || document.querySelector('[data-student-row][data-editing="true"]'))) {
        panel.open = true;
    }
    const coursesPanel = document.getElementById('group-courses');
    if (coursesPanel && location.hash === '#group-courses') coursesPanel.open = true;

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

    const search = document.getElementById('course-search');
    const filter = document.getElementById('modality-filter');
    if (!search || !filter) return;
    const cards = [...document.querySelectorAll('.course-row-card')];
    const empty = document.getElementById('course-empty');
    const count = document.getElementById('course-count');

    const apply = () => {
        const term = search.value.trim().toLocaleLowerCase();
        const selected = filter.value;
        let visible = 0;
        cards.forEach(card => {
            const textMatch = card.dataset.search.includes(term);
            const types = card.dataset.modalities.split(',').filter(Boolean);
            const typeMatch = !selected || (
                selected === 'BOTH'
                    ? types.includes('TEC') && types.includes('TALL')
                    : types.includes(selected)
            );
            const show = textMatch && typeMatch;
            card.hidden = !show;
            if (show) visible++;
        });
        if (empty) empty.hidden = visible !== 0;
        if (count) count.textContent = visible + (visible === 1 ? ' visible' : ' visibles en esta página');
    };

    search.addEventListener('input', apply);
    filter.addEventListener('change', apply);
})();
</script>
@endpush
