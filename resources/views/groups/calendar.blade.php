@extends('layouts.app')
@section('title', 'Calendario · '.$group->name)
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.index') }}">Ciclos</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">Calendario</span>
</div>

<div class="page-head gc-page-head">
    <div>
        <p class="eyebrow"><x-icon name="calendar-days" size="12" /> {{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</p>
        <h1>Calendario de {{ $group->name }}</h1>
        <p class="subhead">Vista conjunta de las clases programadas de sus cursos. Cada bloque conserva las fechas, modalidad y tipo de enseñanza configurados en su propio curso.</p>
    </div>
    <div class="actions gc-page-actions">
        <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
        <form method="POST" action="{{ route('groups.calendar.rebuild', $group) }}" data-confirm="Se recalcularán por separado las sesiones de cada modalidad que tenga fechas y horario configurados. Las sesiones anteriores que ya no correspondan se marcarán como canceladas y los Excel existentes quedarán marcados para revisión.">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            @if($modalityFilter)<input type="hidden" name="modality" value="{{ $modalityFilter }}">@endif
            <button class="btn" type="submit"><x-icon name="calendar-check" /> Recalcular calendarios</button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning" role="status">{{ session('warning') }}</div>
@endif

<section class="card gc-toolbar-card" aria-label="Filtros del calendario">
    <form class="gc-filter-form" method="GET" action="{{ route('groups.calendar', $group) }}">
        <div class="gc-filter-field">
            <label for="calendar-month">Mes</label>
            <input class="field" id="calendar-month" type="month" name="month" value="{{ $month }}">
        </div>
        <div class="gc-filter-field">
            <label for="calendar-modality">Modalidad</label>
            <select class="field" id="calendar-modality" name="modality">
                <option value="">Todos los cursos</option>
                <option value="TEC" @selected($modalityFilter === 'TEC')>Solo TEC</option>
                <option value="TALL" @selected($modalityFilter === 'TALL')>Solo TALL</option>
            </select>
        </div>
        <button class="btn" type="submit"><x-icon name="filter" /> Aplicar</button>
    </form>
    <div class="gc-month-navigation">
        <a class="btn btn-secondary btn-sm" href="{{ route('groups.calendar', ['group' => $group, 'month' => $previousMonth, 'modality' => $modalityFilter]) }}" aria-label="Mes anterior"><x-icon name="arrow-left" /> Anterior</a>
        <h2>{{ $monthLabel }}</h2>
        <a class="btn btn-secondary btn-sm" href="{{ route('groups.calendar', ['group' => $group, 'month' => $nextMonth, 'modality' => $modalityFilter]) }}" aria-label="Mes siguiente">Siguiente <x-icon name="chevron-right" /></a>
    </div>
</section>

<div class="gc-summary" aria-label="Resumen del mes">
    <div><strong>{{ $sessions->count() }}</strong><span>clases programadas</span></div>
    <div><strong>{{ $configuredModalities->count() }} / {{ $modalities->count() }}</strong><span>modalidades con fechas y horario</span></div>
    <div><strong>{{ $holidaysByDate->count() }}</strong><span>fechas feriadas</span></div>
    <div class="{{ count($conflicts) ? 'has-conflicts' : '' }}"><strong>{{ count($conflicts) }}</strong><span>cruces de horario</span></div>
</div>

@if(count($conflicts))
    <section class="alert alert-warning gc-conflicts" role="alert">
        <div>
            <strong>Hay {{ count($conflicts) }} cruces de horario en este mes.</strong>
            <p>Dos cursos del grupo tienen clases simultáneas. Revisa los bloques implicados antes de confirmar la carga.</p>
            <ul>
                @foreach($conflicts as $conflict)
                    @php($leftModality = $conflict['left']->modality)
                    @php($rightModality = $conflict['right']->modality)
                    <li>
                        {{ \Illuminate\Support\Carbon::parse($conflict['date'])->format('d/m/Y') }}:
                        <a href="{{ route('modalities.show', $leftModality) }}">{{ $leftModality->assignment->course->name }} ({{ $leftModality->modality }})</a>
                        y
                        <a href="{{ route('modalities.show', $rightModality) }}">{{ $rightModality->assignment->course->name }} ({{ $rightModality->modality }})</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

<section class="card gc-calendar-card">
    <div class="gc-calendar-legend">
        <span><i class="gc-legend-dot gc-legend-tec"></i> TEC</span>
        <span><i class="gc-legend-dot gc-legend-tall"></i> TALL</span>
        <span><i class="gc-legend-dot gc-legend-theory"></i> Teoría</span>
        <span><i class="gc-legend-dot gc-legend-lab"></i> Laboratorio</span>
        <span><i class="gc-legend-dot gc-legend-conflict"></i> Cruce de horario</span>
    </div>
    <div class="gc-calendar-scroll" role="region" aria-label="Calendario mensual, desplazable horizontalmente" tabindex="0">
        <div class="gc-calendar-grid">
            @foreach($weekdays as $weekday)
                <div class="gc-weekday">{{ $weekday }}</div>
            @endforeach
            @foreach($calendarDays as $day)
                @php($dateKey = $day->toDateString())
                @php($isOutsideMonth = $day->format('Y-m') !== $month)
                @php($daySessions = $sessionsByDate->get($dateKey, collect()))
                @php($dayHolidays = $holidaysByDate->get($dateKey, collect()))
                <div class="gc-day {{ $isOutsideMonth ? 'is-outside' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                    <div class="gc-day-number"><time datetime="{{ $dateKey }}">{{ $day->format('j') }}</time></div>
                    @foreach($dayHolidays as $holiday)
                        <div class="gc-holiday"><strong>Feriado</strong><span>{{ $holiday->name }}</span></div>
                    @endforeach
                    @foreach($daySessions as $session)
                        @php($sessionModality = $session->modality)
                        @php($sessionCourse = $sessionModality?->assignment?->course)
                        @php($sessionSchedule = $session->schedule)
                        @php($isConflict = in_array((int) $session->id, $conflictingSessionIds, true))
                        <a class="gc-event gc-{{ strtolower($sessionModality?->modality ?? 'tec') }} {{ $isConflict ? 'is-conflict' : '' }}" href="{{ route('modalities.show', $sessionModality) }}" title="{{ $sessionCourse?->name }}">
                            <span class="gc-event-heading">
                                <strong>{{ $sessionModality?->modality }}</strong>
                                <time>{{ $sessionSchedule?->start_time ? substr((string) $sessionSchedule->start_time, 0, 5) : 'Hora pendiente' }}@if($sessionSchedule?->end_time)–{{ substr((string) $sessionSchedule->end_time, 0, 5) }}@endif</time>
                            </span>
                            <span class="gc-event-course">{{ $sessionCourse?->name ?? 'Curso sin nombre' }}</span>
                            <span class="gc-event-meta">{{ $sessionSchedule?->instruction_type === 'LAB' ? 'Laboratorio' : 'Teoría' }}@if($sessionSchedule?->classroom) · {{ $sessionSchedule->classroom }}@endif</span>
                            @if($isConflict)<span class="gc-event-conflict">Cruce de horario</span>@endif
                        </a>
                    @endforeach
                    @if($daySessions->isEmpty() && $dayHolidays->isEmpty() && !$isOutsideMonth)
                        <span class="gc-no-events">—</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    <p class="gc-calendar-note">Cada clase abre la configuración de su modalidad. Los días sin sesiones no generan bloques automáticamente.</p>
</section>

<section class="card gc-modalities-card">
    <div class="card-head">
        <div>
            <h2>Modalidades que componen este calendario</h2>
            <p class="card-description">La programación se configura por curso y modalidad, no como un horario único para todo el grupo.</p>
        </div>
    </div>
    @if($modalities->isNotEmpty())
        <div class="gc-modality-list">
            @foreach($modalities as $modality)
                @php($hasClassSchedule = $modality->schedules->contains(fn ($schedule) => $schedule->block_type === 'CLASS'))
                @php($isConfigured = $modality->start_date && $modality->end_date && $hasClassSchedule)
                @php($hasGeneratedSessions = (int) $modality->scheduled_sessions_count > 0)
                <div class="gc-modality-row">
                    <div class="gc-modality-course">
                        <strong>{{ $modality->assignment->course->name }}</strong>
                        <small>{{ $modality->assignment->course->code ?: 'Sin código' }} · NRC {{ $modality->nrc ?: 'pendiente' }}</small>
                    </div>
                    <span class="gc-modality-tag gc-{{ strtolower($modality->modality) }}">{{ $modality->modality }}</span>
                    <span class="gc-modality-state {{ $isConfigured && $hasGeneratedSessions ? 'is-ready' : 'is-pending' }}">@if(!$isConfigured) Configuración pendiente @elseif(!$hasGeneratedSessions) Pendiente de cálculo @else Calendario generado @endif</span>
                    <span class="gc-modality-dates">{{ $modality->start_date?->format('d/m/Y') ?: 'Sin inicio' }} – {{ $modality->end_date?->format('d/m/Y') ?: 'Sin fin' }}</span>
                    <a class="btn btn-ghost btn-sm" href="{{ route('modalities.show', $modality) }}">Abrir <x-icon name="arrow-up-right" /></a>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty"><strong>No hay modalidades para mostrar.</strong><span>Agrega cursos al grupo y configura sus modalidades para construir el calendario.</span></div>
    @endif
</section>

<style>
.gc-page-head .subhead{max-width:800px}
.gc-page-actions form{margin:0}
.gc-toolbar-card{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:18px}
.gc-filter-form{display:flex;align-items:end;gap:10px;flex-wrap:wrap}
.gc-filter-field{width:min(220px,100%)}
.gc-filter-field label{margin-top:0}
.gc-filter-field .field{min-height:36px}
.gc-month-navigation{display:flex;align-items:center;justify-content:flex-end;gap:10px;white-space:nowrap}
.gc-month-navigation h2{min-width:145px;margin:0;text-align:center;text-transform:capitalize}
.gc-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0 0 13px}
.gc-summary>div{display:flex;align-items:center;gap:10px;min-width:0;padding:12px 14px;border:1px solid var(--line);border-radius:8px;background:var(--surface)}
.gc-summary strong{color:var(--ink);font-size:20px;font-variant-numeric:tabular-nums}
.gc-summary span{color:var(--muted);font-size:10px;line-height:1.4}
.gc-summary .has-conflicts strong{color:var(--danger)}
.gc-conflicts{margin-bottom:13px}
.gc-conflicts p{margin:3px 0 6px}
.gc-conflicts ul{display:grid;gap:4px;margin:6px 0 0;padding-left:18px}
.gc-calendar-card{padding:14px}
.gc-calendar-legend{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:0 0 12px;color:var(--muted);font-size:10px}
.gc-calendar-legend>span{display:inline-flex;align-items:center;gap:6px}
.gc-legend-dot{width:8px;height:8px;display:inline-block;border-radius:50%;background:#718078}
.gc-legend-tec{background:#397f9e}.gc-legend-tall{background:#8268a5}.gc-legend-theory{background:#6b8474}.gc-legend-lab{background:#c18a42}.gc-legend-conflict{background:#b24d43}
.gc-calendar-scroll{overflow-x:auto;border:1px solid var(--line);border-radius:8px}
.gc-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(112px,1fr));min-width:784px;background:var(--line);gap:1px}
.gc-weekday{padding:9px 10px;background:var(--surface-soft);color:var(--muted);font-size:10px;font-weight:750;text-align:center;text-transform:uppercase;letter-spacing:.5px}
.gc-day{min-width:0;min-height:142px;padding:7px;background:var(--surface)}
.gc-day.is-outside{background:var(--surface-soft);opacity:.58}
.gc-day.is-today{box-shadow:inset 0 0 0 2px var(--accent)}
.gc-day-number{display:flex;justify-content:flex-end;margin:0 0 6px;color:var(--muted);font-size:11px;font-variant-numeric:tabular-nums}
.gc-day.is-today .gc-day-number time{display:grid;place-items:center;width:22px;height:22px;border-radius:50%;background:var(--accent);color:#fff;font-weight:750}
.gc-event{display:block;margin:0 0 5px;padding:6px 7px;border:1px solid transparent;border-left:3px solid #397f9e;border-radius:5px;background:rgba(57,127,158,.09);color:var(--ink);text-decoration:none;overflow-wrap:anywhere}
.gc-event:hover{border-color:var(--line-strong);text-decoration:none;filter:brightness(.98)}
.gc-event:focus-visible{outline-offset:1px}
.gc-event.gc-tall{border-left-color:#8268a5;background:rgba(130,104,165,.09)}
.gc-event-heading{display:flex;justify-content:space-between;align-items:center;gap:5px;color:var(--muted);font-size:9px}
.gc-event-heading strong{font-size:9px;color:var(--accent);letter-spacing:.3px}
.gc-event.gc-tall .gc-event-heading strong{color:#8268a5}
.gc-event-heading time{white-space:nowrap;font-variant-numeric:tabular-nums}
.gc-event-course{display:block;margin-top:3px;font-size:10px;font-weight:700;line-height:1.35}
.gc-event-meta{display:block;margin-top:3px;color:var(--muted);font-size:9px;line-height:1.3}
.gc-event.is-conflict{border-color:#d9aaa4;border-left-color:var(--danger);background:var(--danger-bg)}
.gc-event-conflict{display:inline-block;margin-top:4px;color:var(--danger);font-size:9px;font-weight:750}
.gc-holiday{display:grid;gap:2px;margin:0 0 5px;padding:5px 6px;border:1px solid #e7d9b8;border-radius:5px;background:var(--warning-bg);color:var(--warning);font-size:9px;line-height:1.35}
.gc-holiday span{overflow-wrap:anywhere}
.gc-no-events{display:block;padding:7px 0;color:var(--faint);font-size:10px;text-align:center}
.gc-calendar-note{margin:10px 2px 0;color:var(--muted);font-size:10px;line-height:1.5}
.gc-modality-list{display:grid}
.gc-modality-row{display:grid;grid-template-columns:minmax(180px,1.5fr) 52px minmax(145px,1fr) minmax(170px,1fr) auto;align-items:center;gap:12px;padding:10px 0;border-top:1px solid var(--line)}
.gc-modality-course{display:grid;gap:3px;min-width:0}
.gc-modality-course strong{color:var(--ink);font-size:11px}
.gc-modality-course small,.gc-modality-dates{color:var(--muted);font-size:10px}
.gc-modality-tag{justify-self:start;padding:4px 7px;border-radius:5px;background:rgba(57,127,158,.1);color:#397f9e;font-size:9px;font-weight:800}
.gc-modality-tag.gc-tall{background:rgba(130,104,165,.1);color:#8268a5}
.gc-modality-state{font-size:10px;font-weight:650}
.gc-modality-state.is-ready{color:var(--success)}
.gc-modality-state.is-pending{color:var(--warning)}
html[data-theme="dark"] .gc-event{background:rgba(80,155,187,.16);color:var(--ink)}
html[data-theme="dark"] .gc-event.gc-tall{background:rgba(155,128,190,.17)}
html[data-theme="dark"] .gc-event.is-conflict{background:rgba(153,75,67,.22);border-color:#74443e}
html[data-theme="dark"] .gc-holiday{border-color:#65563b;background:#30291d;color:#e4c58a}
@media(max-width:900px){.gc-toolbar-card{grid-template-columns:1fr}.gc-month-navigation{justify-content:space-between}.gc-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.gc-modality-row{grid-template-columns:minmax(0,1fr) auto;gap:7px 12px}.gc-modality-tag{grid-column:2;grid-row:1}.gc-modality-state,.gc-modality-dates{grid-column:1}.gc-modality-row .btn{grid-column:2;grid-row:2}}
@media(max-width:600px){.gc-filter-form{align-items:stretch;flex-direction:column}.gc-filter-field{width:100%}.gc-filter-form>.btn{width:100%}.gc-summary>div{align-items:flex-start;flex-direction:column;gap:3px;padding:10px}.gc-summary strong{font-size:18px}.gc-calendar-card{padding:10px}.gc-calendar-legend{gap:9px}.gc-modality-row{grid-template-columns:minmax(0,1fr) auto}.gc-page-head{align-items:flex-start;flex-direction:column}.gc-page-head .actions,.gc-page-head .actions form,.gc-page-head .btn{width:100%}}
@media(prefers-reduced-motion:reduce){.gc-event{transition:none}}
</style>
@endsection
