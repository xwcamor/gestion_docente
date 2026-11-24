@extends('layouts.app')
@section('title', 'Modalidad '.$modality->modality)
@section('content')
@php $assignment = $modality->assignment; @endphp
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $assignment->group->cycle) }}">{{ $assignment->group->cycle->academic_year }} · {{ $assignment->group->cycle->cycle_code }}</a><span>›</span><a href="{{ route('groups.show', $assignment->group) }}">{{ $assignment->group->name }}</a><span>›</span><span class="current">{{ $modality->modality }}</span></div>
<div class="page-head">
    <div><p class="eyebrow">Modalidad {{ $modality->label }}</p><h1>{{ $assignment->course->name }}</h1><p class="subhead">{{ $modality->modality }} · NRC {{ $modality->nrc ?: 'Pendiente' }} · Bloque {{ $modality->block_code ?: 'Pendiente' }} @if($modality->block_reference) · Ref. {{ $modality->block_reference }} @endif · {{ $assignment->group->name }} · {{ $assignment->group->cycle->academic_year }}</p></div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $assignment->group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>
@php
    $scheduleWeekdays = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    $scheduleRows = old('schedules');
    if (!is_array($scheduleRows)) {
        $scheduleRows = $modality->schedules->where('block_type', 'CLASS')
            ->groupBy(fn ($schedule) => implode('|', [
                $schedule->start_time,
                $schedule->end_time,
                $schedule->classroom ?? '',
                $schedule->instruction_type ?? 'THEORY',
            ]))
            ->map(fn ($blocks) => [
                'days' => $blocks->pluck('day_of_week')->map(fn ($day) => (int) $day)->unique()->sort()->values()->all(),
                'start_time' => substr($blocks->first()->start_time, 0, 5),
                'end_time' => substr($blocks->first()->end_time, 0, 5),
                'classroom' => $blocks->first()->classroom,
                'instruction_type' => $blocks->first()->instruction_type ?? 'THEORY',
            ])->values()->all();
    } else {
        $scheduleRows = array_map(function ($row) {
            $row['days'] = $row['days'] ?? (!empty($row['day_of_week']) ? [(int) $row['day_of_week']] : []);
            return $row;
        }, $scheduleRows);
    }
    if (!$scheduleRows) {
        $scheduleRows = [['days' => [], 'start_time' => '', 'end_time' => '', 'classroom' => '', 'instruction_type' => 'THEORY']];
    }
@endphp
<details class="card modality-section" id="calendar" @if($errors->any()) open @endif>
    <summary class="modality-section-summary">
        <span class="modality-section-icon"><x-icon name="calendar-days" /></span>
        <span class="modality-section-title">
            <strong>Horario y calendario</strong>
            <small>Fechas de dictado: {{ $modality->start_date?->format('d/m/Y') ?: 'Pendiente' }} – {{ $modality->end_date?->format('d/m/Y') ?: 'Pendiente' }}</small>
        </span>
        <span class="modality-section-count">{{ number_format($weeklyHoursCalculated, 2) }} h/semana · {{ $scheduledSessionCount }} sesiones</span>
        <x-section-status :status="$calendarStatus" />
        <x-icon name="chevron-right" class="modality-section-chevron" />
    </summary>
    <div class="modality-section-body">
    @if($hasAnySchedule)
        <div class="modality-weekly-overview">
            <strong>{{ number_format($weeklyHoursCalculated, 2) }} h/semana</strong>
            <div class="modality-day-chips">
                @foreach($scheduleWeekdays as $day => $dayLabel)
                    @if($schedulesByDay->has($day))
                        <span class="modality-day-chip">
                            <b>{{ $dayLabel }}</b>
                            @foreach($schedulesByDay->get($day) as $schedule)
                                <small>{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }} · {{ ($schedule->instruction_type ?? 'THEORY') === 'LAB' ? 'Lab.' : 'Teoría' }}</small>
                            @endforeach
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <details class="panel schedule-config-panel" @if($errors->any()) open @endif>
        <summary>Configurar fechas y bloques de clase</summary>
        <div class="panel-body">
            <form id="modality-schedule-form" method="POST" action="{{ route('modalities.update', $modality) }}" data-loading-form="1">
                @csrf
                @method('PUT')
                <input type="hidden" name="schedules_present" value="1">
                <div class="form-row-3">
                    <div><label for="nrc">NRC</label><input class="field" id="nrc" name="nrc" value="{{ old('nrc', $modality->nrc) }}" maxlength="30"></div>
                    <div><label for="start_date">Fecha de inicio</label><input class="field" id="start_date" type="date" name="start_date" value="{{ old('start_date', $modality->start_date?->format('Y-m-d')) }}"></div>
                    <div><label for="end_date">Fecha de fin</label><input class="field" id="end_date" type="date" name="end_date" value="{{ old('end_date', $modality->end_date?->format('Y-m-d')) }}"></div>
                </div>

                <div class="schedule-editor-heading">
                    <div><h3>Bloques semanales</h3><p class="help">Agrega una fila por cada bloque. Cada día puede tener varias horas, ambientes y tipos diferentes.</p></div>
                    <span class="schedule-hours-chip">Total calculado: <strong id="weekly-hours-live">{{ number_format($weeklyHoursCalculated, 2) }} h</strong></span>
                </div>
                <div id="schedule-editor" class="schedule-editor">
                    @foreach($scheduleRows as $index => $scheduleRow)
                        <div class="schedule-row" data-schedule-row>
                            <div class="schedule-days-field">
                                <label>Días</label>
                                <div class="schedule-days-options">
                                    @foreach($scheduleWeekdays as $day => $dayLabel)
                                        <label class="weekday"><input type="checkbox" name="schedules[{{ $index }}][days][]" value="{{ $day }}" data-schedule-input data-schedule-day @checked(in_array($day, array_map('intval', $scheduleRow['days'] ?? []), true))> {{ mb_substr($dayLabel, 0, 3) }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label for="schedule-type-{{ $index }}">Tipo</label>
                                <select class="field" id="schedule-type-{{ $index }}" name="schedules[{{ $index }}][instruction_type]" data-schedule-input>
                                    <option value="THEORY" @selected(($scheduleRow['instruction_type'] ?? 'THEORY') === 'THEORY')>Teoría</option>
                                    <option value="LAB" @selected(($scheduleRow['instruction_type'] ?? 'THEORY') === 'LAB')>Laboratorio</option>
                                </select>
                            </div>
                            <div>
                                <label for="schedule-start-{{ $index }}">Desde</label>
                                <input class="field" id="schedule-start-{{ $index }}" type="time" step="900" name="schedules[{{ $index }}][start_time]" value="{{ $scheduleRow['start_time'] ?? '' }}" data-schedule-input data-schedule-start>
                            </div>
                            <div>
                                <label for="schedule-end-{{ $index }}">Hasta</label>
                                <input class="field" id="schedule-end-{{ $index }}" type="time" step="900" name="schedules[{{ $index }}][end_time]" value="{{ $scheduleRow['end_time'] ?? '' }}" data-schedule-input data-schedule-end>
                            </div>
                            <div>
                                <label for="schedule-room-{{ $index }}">Aula / ambiente</label>
                                <input class="field" id="schedule-room-{{ $index }}" name="schedules[{{ $index }}][classroom]" value="{{ $scheduleRow['classroom'] ?? '' }}" maxlength="100" placeholder="Ej. LAB 103" data-schedule-input>
                            </div>
                            <div class="schedule-remove-cell">
                                <label aria-hidden="true">&nbsp;</label>
                                <button class="btn btn-danger btn-sm" type="button" data-remove-schedule title="Quitar bloque"><x-icon name="x" /> Quitar</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="actions schedule-editor-actions">
                    <button class="btn btn-secondary btn-sm" id="add-schedule" type="button"><x-icon name="plus" /> Agregar bloque</button>
                </div>

                <div class="calendar-preview" id="calendar-preview" aria-live="polite">
                    <div class="calendar-preview-head">
                        <div><strong>Resumen de fechas calculadas</strong><p class="help">Aquí solo se muestran fechas, horas calculadas y feriados excluidos; no se repite el nombre del curso.</p></div>
                        <span class="badge badge-blue" id="preview-count">Pendiente</span>
                    </div>
                    <div class="calendar-preview-stats">
                        <div><small>Primera clase</small><strong id="preview-first">—</strong></div>
                        <div><small>Última clase efectiva</small><strong id="preview-last">—</strong></div>
                        <div><small>Horas por semana</small><strong id="preview-weekly-hours">{{ number_format($weeklyHoursCalculated, 2) }} h</strong></div>
                        <div><small>Feriados excluidos</small><strong id="preview-holidays">0</strong></div>
                    </div>
                    <p class="help" id="preview-note">Indica las fechas y registra los bloques para calcular.</p>
                    <div id="preview-holidays-list" class="preview-holidays-list"></div>
                    <button class="btn btn-secondary btn-sm" type="button" id="adjust-end-date" disabled><x-icon name="calendar-check" /> Usar última clase como fecha final</button>
                    <details class="preview-details"><summary>Ver fechas programadas</summary><div id="preview-dates" class="preview-date-list"></div></details>
                </div>
                <div class="form-actions">
                    <span class="help">Las horas se calculan automáticamente. Los feriados bloqueantes se marcarán en rojo y no generarán clase.</span>
                    <button class="btn" type="submit"><x-icon name="check" /> Guardar y recalcular</button>
                </div>
            </form>
        </div>
    </details>

    @if($modality->start_date && $modality->end_date && !$hasClassSchedule)
        <div class="alert alert-warning mt-16">Las fechas de dictado están registradas, pero esta modalidad todavía no tiene días ni bloques de clase. Agrégalos para generar sus sesiones académicas.</div>
    @endif
    @if($hasCourseDates && $hasClassSchedule)
        <form method="POST" action="{{ route('modalities.rebuild', $modality) }}" class="mt-16" data-loading-form="1" data-confirm="Se volverán a calcular las sesiones con los bloques de teoría/laboratorio y los feriados actuales. ¿Deseas continuar?">
            @csrf
            <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="layers" /> Recalcular calendario</button>
        </form>
    @endif
</div>
</details>

<details class="card modality-section" id="students">
    <summary class="modality-section-summary">
        <span class="modality-section-icon"><x-icon name="users" /></span>
        <span class="modality-section-title">
            <strong>Padrón de alumnos</strong>
            <small>Lista compartida del grupo; matrícula y notas independientes por curso</small>
        </span>
        <span class="modality-section-count">{{ $enrollmentsCount }} matriculados</span>
        <x-section-status :status="$studentsStatus" />
        <x-icon name="chevron-right" class="modality-section-chevron" />
    </summary>
    <div class="modality-section-body">
    <p class="help mt-16">Para agregar o actualizar estudiantes, abre el padrón del grupo. El sistema crea las matrículas que falten sin copiar calificaciones entre cursos.</p>
    <div class="actions mt-16">
        <a class="btn" href="{{ route('groups.show', $assignment->group) }}#group-roster"><x-icon name="users" /> Administrar padrón del grupo</a>
    </div>
</div>
</details>

<details class="card modality-section" id="documents">
    <summary class="modality-section-summary">
        <span class="modality-section-icon"><x-icon name="file-spreadsheet" /></span>
        <span class="modality-section-title">
            <strong>Documentos Excel</strong>
            <small>Genera y descarga los formatos de esta modalidad</small>
        </span>
        <span class="modality-section-count">{{ $documentCount }} archivos</span>
        <x-section-status :status="$documentsStatus" />
        <x-icon name="chevron-right" class="modality-section-chevron" />
    </summary>
    <div class="modality-section-body">
        <div class="modality-section-toolbar">
            <a class="btn btn-secondary btn-sm" href="{{ route('evaluations.index', $modality) }}"><x-icon name="arrow-right" /> Evaluaciones y notas</a>
        </div>
    <div class="document-generator-list">
        @foreach(['plan'=>'Plan de sesión','registro'=>'Registro de formación','evaluacion'=>'Formato de evaluación'] as $type=>$label)
            <form class="document-generator-row" method="POST" action="{{ route('documents.generate', [$modality, $type]) }}" data-loading-form="1">
                @csrf
                <span><strong>{{ $label }}</strong><small>Archivo Excel con los datos actuales</small></span>
                <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="file-text" /> Generar</button>
            </form>
        @endforeach
    </div>
    @if($documentCount > 0)
        <h3 style="margin-top:20px">Archivos generados</h3>
        <div class="table-wrap"><table><thead><tr><th>Documento</th><th>Versión</th><th>Estado</th><th>Fecha</th><th></th></tr></thead><tbody>
            @foreach($documents as $document)
                <tr><td>{{ $document->filename }}</td><td>{{ $document->version }}</td><td>@if(($document->metadata['stale'] ?? false))<span class="badge badge-orange">Regenerar</span>@else<span class="badge badge-green">Vigente</span>@endif</td><td>{{ $document->created_at?->format('d/m/Y H:i') }}</td><td><a href="{{ route('documents.download', $document) }}"><x-icon name="arrow-right" /> Descargar</a></td></tr>
            @endforeach
        </tbody></table></div>
        @if($documents->hasPages())
            <x-pagination :paginator="$documents" />
        @endif
    @endif
</div>
</details>

<details class="card modality-section" id="sessions">
    <summary class="modality-section-summary">
        <span class="modality-section-icon"><x-icon name="clipboard-check" /></span>
        <span class="modality-section-title">
            <strong>Sesiones del curso</strong>
            <small>Contenido, actividades y evidencias de cada clase</small>
        </span>
        <span class="modality-section-count">{{ $scheduledSessionCount }} programadas</span>
        <x-section-status :status="$sessionsStatus" />
        <x-icon name="chevron-right" class="modality-section-chevron" />
    </summary>
    <div class="modality-section-body">
    @if($sessionCount === 0)
        <div class="empty"><strong>Aún no se han calculado sesiones.</strong><span>Configura el periodo y el horario en la sección Calendario.</span><div class="mt-16"><a class="btn btn-secondary btn-sm" href="#calendar"><x-icon name="calendar-days" /> Ir a calendario</a></div></div>
    @else
        <div class="filter-bar"><input class="filter-input" id="session-search" type="search" placeholder="Buscar por fecha, semana o tema…" aria-label="Buscar sesiones"><span class="help">{{ $scheduledSessionCount }} sesiones programadas</span></div>
        <div id="session-list">
            @foreach($sessions as $session)
                <details class="panel session-item" style="margin-bottom:9px" data-search="{{ strtolower(($session->session_date?->format('d/m/Y') ?? '').' '.$session->session_number.' '.$session->topic.' '.$session->curriculum_week_number) }}">
                    <summary><span><strong>{{ $session->session_date?->format('d/m/Y') }}</strong> · Sesión {{ $session->session_number ?: '—' }} <span class="muted">· {{ $session->duration_minutes }} min</span></span><span class="badge {{ $session->status === 'holiday' ? 'badge-orange' : ($session->status === 'cancelled' ? 'badge-red' : 'badge-green') }}">{{ $session->status }}</span></summary>
                    <div class="panel-body">
                        @if($session->curriculum_week_number)<p class="help">Semana del curso {{ $session->curriculum_week_number }} @if($session->curriculumWeek?->source_week_number) · Semana fuente {{ $session->curriculumWeek->source_week_number }}@endif</p>@endif
                        @if($session->system_note)<div class="alert alert-info">{{ $session->system_note }}</div>@endif
                        @if($session->status !== 'holiday')
                            <form method="POST" action="{{ route('sessions.update', $session) }}" data-loading-form="1">
                                @csrf @method('PATCH')
                                <label for="topic-{{ $session->id }}">Tema</label><input class="field" id="topic-{{ $session->id }}" name="topic" value="{{ $session->topic }}">
                                <label for="objective-{{ $session->id }}">Objetivo</label><input class="field" id="objective-{{ $session->id }}" name="objective" value="{{ $session->objective }}">
                                <label for="activities-{{ $session->id }}">Actividades, ejercicios y materiales</label><textarea class="field" id="activities-{{ $session->id }}" name="activities" rows="3">{{ $session->activities }}</textarea>
                                <label for="evidence-{{ $session->id }}">Evidencias</label><input class="field" id="evidence-{{ $session->id }}" name="evidence" value="{{ $session->evidence }}">
                                <div class="form-actions"><span class="help">Se guarda solo el contenido de esta sesión.</span><button class="btn btn-secondary btn-sm" type="submit"><x-icon name="check" /> Guardar sesión</button></div>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
        @if($sessions->hasPages())
            <x-pagination :paginator="$sessions" />
        @endif
        <div class="empty" id="session-empty" hidden><strong>No hay sesiones con ese criterio.</strong><span>Prueba con otra fecha o tema.</span></div>
    @endif
</div>
</details>
@endsection

@push('styles')
<style>
.detail-progress-card{padding:16px 18px;margin-bottom:14px}
.detail-progress-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
.detail-progress-heading h2{margin:2px 0 0;font-size:17px}
.detail-progress-heading>strong{font-size:15px;color:#347c91}
.detail-progress-row+.detail-progress-row{margin-top:14px;padding-top:13px;border-top:1px solid #edf1f3}
.detail-progress-label{display:flex;justify-content:space-between;gap:8px;margin-bottom:7px;font-size:12px;color:#4d626e}
.detail-progress-label strong{color:#274d61;white-space:nowrap}
.detail-progress-track{height:8px;overflow:hidden;border-radius:99px;background:#e8eef1}
.detail-progress-track span{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#3a8cab,#47a88e)}
.detail-progress-row small{display:block;margin-top:6px;color:#76868e;font-size:10px}
@media(max-width:600px){.detail-progress-card{padding:14px}.detail-progress-label{align-items:flex-start;flex-direction:column;gap:3px}}

/* Carga semanal: bloques independientes por día */
.weekly-load-summary{margin:0 0 15px;padding:14px;border:1px solid #d7e6ec;border-radius:9px;background:linear-gradient(135deg,#f2f8fa,#fbfdfd)}
.weekly-load-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:12px}
.weekly-load-head strong{display:block;color:#274b5b;font-size:13px}
.weekly-load-head p{margin:4px 0 0;color:#66808b;font-size:10px;line-height:1.45}
.weekly-hours-total,.schedule-hours-chip{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border:1px solid #c6dce5;border-radius:7px;background:#fff;color:#23657d;font-size:12px;font-weight:750;white-space:nowrap}
.weekly-load-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:7px}
.weekly-load-day{min-width:0;min-height:78px;padding:8px;border:1px solid #deeaee;border-radius:7px;background:#fff}
.weekly-load-day>strong{display:block;margin-bottom:7px;color:#486b7a;font-size:10px}
.weekly-load-block{display:grid;gap:2px;margin:0 0 5px;padding:7px;border-radius:6px;font-size:9px;line-height:1.35}
.weekly-load-block:last-child{margin-bottom:0}
.weekly-load-block span{font-weight:750}
.weekly-load-block b{font-size:9px}
.weekly-load-block small{color:inherit;opacity:.83;overflow-wrap:anywhere}
.weekly-load-block.is-theory{border:1px solid #cfe2eb;background:#edf6fa;color:#2e667d}
.weekly-load-block.is-lab{border:1px solid #cfe4d5;background:#eff7f1;color:#326b49}
.weekly-load-empty{color:#a0aaa4;font-size:9px}
.schedule-config-panel{margin-top:8px}
.schedule-editor-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:12px 0 7px}
.schedule-editor-heading h3{margin:0 0 2px}
.schedule-editor-heading .help{margin:0}
.schedule-editor{display:grid;gap:6px}
.schedule-row{display:grid;grid-template-columns:minmax(300px,1.7fr) minmax(125px,.8fr) minmax(110px,.7fr) minmax(110px,.7fr) minmax(145px,1fr) auto;align-items:end;gap:7px;padding:8px;border:1px solid #e0e8e5;border-radius:8px;background:#fbfcfb}
.schedule-days-field{min-width:0}
.schedule-days-options{display:flex;align-items:center;flex-wrap:wrap;gap:4px}
.schedule-days-options .weekday{margin:0;padding:4px 5px;font-size:9px}
.schedule-days-options .weekday input{width:13px;height:13px}
.schedule-row label{margin-top:0;font-size:9px}
.schedule-row .field{min-height:35px;font-size:11px}
.schedule-remove-cell .btn{min-height:35px;padding:6px 8px}
.schedule-editor-actions{margin:7px 0 0}
@media(min-width:1051px){.schedule-days-options{flex-wrap:nowrap}}
.calendar-preview{margin-top:18px;padding:16px;border:1px solid #cfe1e9;border-radius:9px;background:linear-gradient(135deg,#eff7fa,#f8fbfc)}
.calendar-preview-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.calendar-preview-head strong{color:#274b5b;font-size:13px}
.calendar-preview-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin:13px 0}
.calendar-preview-stats>div{padding:11px;background:#fff;border:1px solid #d9e7ed;border-radius:7px}
.calendar-preview-stats small{display:block;color:#668392;font-size:10px;margin-bottom:5px}
.calendar-preview-stats strong{font-size:14px;color:#28677d}
.preview-details{margin-top:12px;border-top:1px solid #d8e6ec;padding-top:10px}
.preview-details summary{cursor:pointer;font-size:11px;font-weight:700;color:#35657a}
.preview-date-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:6px;margin-top:10px}
.preview-date{font-size:10px;padding:7px 8px;border-radius:6px;background:#fff;border:1px solid #dce8ed;color:#456b7c}
.preview-holidays-list{display:grid;gap:6px;margin:10px 0}
.preview-holiday-warning{padding:9px 11px;border:1px solid #efc4c0;border-left:3px solid #c84840;border-radius:6px;background:#fff1f0;color:#963b35;font-size:10px;line-height:1.45}
.preview-holiday-warning strong{font-weight:800}
.preview-no-holidays{padding:8px 10px;border:1px solid #cfe4d5;border-radius:6px;background:#eff8f1;color:#38694a;font-size:10px}
@media(max-width:1050px){.weekly-load-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.schedule-row{grid-template-columns:repeat(3,minmax(0,1fr))}.schedule-days-field{grid-column:1/-1}.schedule-remove-cell{grid-column:3}}
@media(max-width:650px){.weekly-load-head,.schedule-editor-heading{align-items:stretch;flex-direction:column}.weekly-load-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.schedule-row{grid-template-columns:repeat(2,minmax(0,1fr))}.schedule-days-field{grid-column:1/-1}.schedule-remove-cell{grid-column:2}.calendar-preview-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:390px){.weekly-load-grid{grid-template-columns:1fr}.schedule-row{grid-template-columns:1fr}.schedule-days-field{grid-column:1}.schedule-remove-cell{grid-column:1}.calendar-preview-stats{grid-template-columns:1fr}}

.modality-section{padding:0;overflow:hidden}
.modality-section-summary{display:flex;align-items:center;gap:12px;min-height:70px;padding:13px 16px;list-style:none;cursor:pointer}
.modality-section-summary::-webkit-details-marker{display:none}
.modality-section-summary:focus-visible{outline-offset:-3px}
.modality-section-icon{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:9px;background:var(--surface-soft);color:var(--accent)}
.modality-section-title{display:grid;gap:4px;flex:1;min-width:0}
.modality-section-title strong{color:var(--ink);font-size:13px}
.modality-section-title small{color:var(--muted);font-size:10px;line-height:1.35}
.modality-section-count{color:var(--ink-soft);font-size:10px;font-weight:700;white-space:nowrap}
.modality-section-chevron{color:var(--muted);transition:transform .16s ease;flex:none}
.modality-section[open] .modality-section-chevron{transform:rotate(90deg)}
.modality-section-body{padding:0 16px 16px;border-top:1px solid var(--line)}
.modality-section-toolbar{display:flex;justify-content:flex-end;margin:0 0 12px}
.document-generator-list{display:grid;gap:7px}
.document-generator-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:var(--surface)}
.document-generator-row>span{display:grid;gap:3px;min-width:0}
.document-generator-row strong{color:var(--ink);font-size:11px}
.document-generator-row small{color:var(--muted);font-size:10px}
@media(max-width:520px){.document-generator-row{align-items:stretch;flex-direction:column}.document-generator-row .btn{width:100%}}

.modality-weekly-overview{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:12px 0;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft)}
.modality-weekly-overview>strong{color:var(--ink);font-size:11px;white-space:nowrap}
.modality-day-chips{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.modality-day-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border:1px solid var(--line);border-radius:6px;background:var(--surface);font-size:10px}
.modality-day-chip b{color:var(--ink-soft)}
.modality-day-chip small{color:var(--muted)}
.modality-section .talks-summary,.modality-section .calendar-preview{margin-top:12px}
@media(max-width:700px){.modality-section-summary{gap:8px;padding:11px}.modality-section-count{display:none}.modality-section-body{padding:0 11px 12px}.modality-day-chip{align-items:flex-start;flex-direction:column;gap:3px}}
html[data-theme="dark"] .modality-section-icon{background:#24343b}
</style>
@endpush

@push('styles')
<style>
.calendar-preview{margin-top:18px;padding:16px;border:1px solid #e2e7e0;border-radius:9px;background:#f8faf7}
.calendar-preview-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.calendar-preview-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:13px 0}
.calendar-preview-stats>div{padding:11px;background:#fff;border:1px solid #e7ebe5;border-radius:7px}
.calendar-preview-stats small{display:block;color:#7b867d;font-size:10px;margin-bottom:5px}
.calendar-preview-stats strong{font-size:14px;color:#40584a}
.preview-details{margin-top:12px;border-top:1px solid #e3e8e1;padding-top:10px}
.preview-details summary{cursor:pointer;font-size:12px;font-weight:700;color:#465249}
.preview-date-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(135px,1fr));gap:6px;margin-top:10px}
.preview-date{font-size:11px;padding:7px 8px;border-radius:6px;background:#fff;border:1px solid #e6ebe5}
.preview-date.holiday{color:#806338;background:#f8f3e9;border-color:#e9dfca}
@media(max-width:600px){.calendar-preview-stats{grid-template-columns:1fr}.calendar-preview-head{display:block}}

/* Modalidad: calendario, alumnado y documentos con contraste */
.calendar-preview{border-color:#cfe1e9;background:linear-gradient(135deg,#eff7fa,#f5faf9)}
.calendar-preview-stats>div{border-color:#d9e7ed;background:#fff}
.calendar-preview-stats small{color:#668392}
.calendar-preview-stats strong{color:#28677d}
.preview-details{border-top-color:#d8e6ec}
.preview-details summary{color:#35657a}
.preview-date{border-color:#dce8ed;background:#fff;color:#456b7c}
.preview-date.holiday{color:#956326;background:#fff4e3;border-color:#efd8b1}

</style>
@endpush

@push('scripts')
<script>
(() => {
    const form = document.getElementById('modality-schedule-form');
    if (!form) return;
    const editor = document.getElementById('schedule-editor');
    const addButton = document.getElementById('add-schedule');
    const holidays = new Map(@json($blockedHolidays).map(item => [item.date, item.name]));
    const startInput = form.querySelector('[name="start_date"]');
    const endInput = form.querySelector('[name="end_date"]');
    const first = document.getElementById('preview-first');
    const last = document.getElementById('preview-last');
    const count = document.getElementById('preview-count');
    const holidayCount = document.getElementById('preview-holidays');
    const weeklyLive = document.getElementById('weekly-hours-live');
    const weeklyPreview = document.getElementById('preview-weekly-hours');
    const note = document.getElementById('preview-note');
    const list = document.getElementById('preview-dates');
    const holidayList = document.getElementById('preview-holidays-list');
    const adjust = document.getElementById('adjust-end-date');
    let lastTeachingDate = null;
    let nextIndex = Math.max(-1, ...[...editor.querySelectorAll('[name^="schedules["]')].map(input => {
        const match = input.name.match(/^schedules\[(\d+)\]/);
        return match ? Number(match[1]) : -1;
    })) + 1;

    const fmt = date => new Intl.DateTimeFormat('es-PE',{day:'2-digit',month:'2-digit',year:'numeric'}).format(date);
    const parse = value => value ? new Date(value+'T12:00:00') : null;
    const dateKey = date => [date.getFullYear(),String(date.getMonth()+1).padStart(2,'0'),String(date.getDate()).padStart(2,'0')].join('-');
    const formatHours = minutes => (minutes/60).toLocaleString('es-PE',{minimumFractionDigits:0,maximumFractionDigits:2})+' h';

    const blocks = () => [...editor.querySelectorAll('[data-schedule-row]')].flatMap(row => {
        const days = [...row.querySelectorAll('[data-schedule-day]:checked')].map(input => Number(input.value));
        const start = row.querySelector('[data-schedule-start]')?.value || '';
        const end = row.querySelector('[data-schedule-end]')?.value || '';
        if (!days.length || !start || !end || end <= start) return [];
        const [sh,sm] = start.split(':').map(Number);
        const [eh,em] = end.split(':').map(Number);
        const minutes = (eh*60+em)-(sh*60+sm);
        return days.map(day => ({day,start,end,minutes}));
    });

    const rowTemplate = index => `
        <div class="schedule-row" data-schedule-row>
            <div class="schedule-days-field"><label>Días</label><div class="schedule-days-options">
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="1" data-schedule-input data-schedule-day> Lun</label>
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="2" data-schedule-input data-schedule-day> Mar</label>
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="3" data-schedule-input data-schedule-day> Mié</label>
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="4" data-schedule-input data-schedule-day> Jue</label>
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="5" data-schedule-input data-schedule-day> Vie</label>
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="6" data-schedule-input data-schedule-day> Sáb</label>
                <label class="weekday"><input type="checkbox" name="schedules[${index}][days][]" value="7" data-schedule-input data-schedule-day> Dom</label>
            </div></div>
            <div><label for="schedule-type-${index}">Tipo</label><select class="field" id="schedule-type-${index}" name="schedules[${index}][instruction_type]" data-schedule-input><option value="THEORY">Teoría</option><option value="LAB">Laboratorio</option></select></div>
            <div><label for="schedule-start-${index}">Desde</label><input class="field" id="schedule-start-${index}" type="time" step="900" name="schedules[${index}][start_time]" data-schedule-input data-schedule-start></div>
            <div><label for="schedule-end-${index}">Hasta</label><input class="field" id="schedule-end-${index}" type="time" step="900" name="schedules[${index}][end_time]" data-schedule-input data-schedule-end></div>
            <div><label for="schedule-room-${index}">Aula / ambiente</label><input class="field" id="schedule-room-${index}" name="schedules[${index}][classroom]" maxlength="100" placeholder="Ej. LAB 103" data-schedule-input></div>
            <div class="schedule-remove-cell"><label aria-hidden="true">&nbsp;</label><button class="btn btn-danger btn-sm" type="button" data-remove-schedule>× Quitar</button></div>
        </div>`;

    addButton?.addEventListener('click', () => {
        editor.insertAdjacentHTML('beforeend', rowTemplate(nextIndex++));
        calculate();
    });
    editor.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-schedule]');
        if (!remove) return;
        remove.closest('[data-schedule-row]')?.remove();
        calculate();
    });
    form.addEventListener('input', event => {
        if (event.target.matches('[data-schedule-input], [name="start_date"], [name="end_date"]')) calculate();
    });
    form.addEventListener('change', event => {
        if (event.target.matches('[data-schedule-input], [name="start_date"], [name="end_date"]')) calculate();
    });

    function calculate() {
        list.innerHTML = '';
        holidayList.innerHTML = '';
        lastTeachingDate = null;
        const from = parse(startInput.value);
        const to = parse(endInput.value);
        const currentBlocks = blocks();
        const weeklyMinutes = currentBlocks.reduce((sum, block) => sum + block.minutes, 0);
        weeklyLive.textContent = formatHours(weeklyMinutes);
        weeklyPreview.textContent = formatHours(weeklyMinutes);

        if (!from || !to || from > to || !currentBlocks.length) {
            first.textContent = '—'; last.textContent = '—'; count.textContent = 'Pendiente'; holidayCount.textContent = '0';
            note.textContent = 'Indica un rango válido y agrega bloques completos con día y horas.';
            adjust.disabled = true;
            return;
        }

        const scheduledDates = new Map();
        const excluded = new Map();
        let sessions = 0;
        const cursor = new Date(from);
        while (cursor <= to) {
            const key = dateKey(cursor);
            const day = cursor.getDay() === 0 ? 7 : cursor.getDay();
            const dayBlocks = currentBlocks.filter(block => block.day === day);
            if (dayBlocks.length) {
                if (holidays.has(key)) excluded.set(key,{date:new Date(cursor),name:holidays.get(key),blocks:dayBlocks.length});
                else { scheduledDates.set(key,{date:new Date(cursor),blocks:dayBlocks.length}); sessions += dayBlocks.length; }
            }
            cursor.setDate(cursor.getDate()+1);
        }

        const dates = [...scheduledDates.values()];
        const excludedDates = [...excluded.values()];
        const firstDate = dates[0]?.date || null;
        const lastDate = dates[dates.length-1]?.date || null;
        first.textContent = firstDate ? fmt(firstDate) : '—';
        last.textContent = lastDate ? fmt(lastDate) : '—';
        count.textContent = sessions+' bloques programados';
        holidayCount.textContent = String(excludedDates.length);
        lastTeachingDate = lastDate;

        dates.forEach(item => {
            const element = document.createElement('div');
            element.className = 'preview-date';
            element.textContent = fmt(item.date)+' · '+item.blocks+' bloque(s)';
            list.appendChild(element);
        });
        if (excludedDates.length) {
            excludedDates.forEach(item => {
                const warning = document.createElement('div');
                warning.className = 'preview-holiday-warning';
                const strong = document.createElement('strong');
                strong.textContent = fmt(item.date)+' · '+item.name;
                const detail = document.createElement('span');
                detail.textContent = ' — No se programará clase ('+item.blocks+' bloque(s) excluidos).';
                warning.append(strong,detail);
                holidayList.appendChild(warning);
            });
        } else {
            const ok = document.createElement('div');
            ok.className = 'preview-no-holidays';
            ok.textContent = 'No hay feriados bloqueantes dentro de las fechas y días seleccionados.';
            holidayList.appendChild(ok);
        }

        const endKey = dateKey(to);
        const endDay = to.getDay() === 0 ? 7 : to.getDay();
        const endIsClassDay = currentBlocks.some(block => block.day === endDay) && !holidays.has(endKey);
        note.textContent = sessions+' bloques dentro del rango; '+excludedDates.length+' fecha(s) feriada(s) excluida(s).'
            +(!endIsClassDay && lastTeachingDate ? ' La fecha final no es lectiva: la última clase efectiva será '+fmt(lastTeachingDate)+'.' : '');
        adjust.disabled = !lastTeachingDate || lastTeachingDate.getTime() === to.getTime();
    }

    adjust.addEventListener('click', () => {
        if (!lastTeachingDate) return;
        endInput.value = [lastTeachingDate.getFullYear(),String(lastTeachingDate.getMonth()+1).padStart(2,'0'),String(lastTeachingDate.getDate()).padStart(2,'0')].join('-');
        calculate();
    });
    calculate();
})();

(() => {
    const search = document.getElementById('session-search');
    if (!search) return;
    const rows = [...document.querySelectorAll('.session-item')];
    const empty = document.getElementById('session-empty');
    search.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        rows.forEach(row => {
            const show = row.dataset.search.includes(term);
            row.hidden = !show;
            if (show) visible++;
        });
        empty.hidden = visible > 0;
    });
})();
</script>
@endpush
