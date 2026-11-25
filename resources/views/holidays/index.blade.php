@extends('layouts.app')
@section('title', 'Feriados y cierres')
@section('content')
@php
    $monthNames = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $monthLabels = [1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR', 5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'];
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $scopeLabels = ['NATIONAL' => 'Nacional', 'REGIONAL' => 'Regional', 'INSTITUTIONAL' => 'Institucional', 'SECTOR' => 'Sector'];
    $monthEventCount = collect($calendarDays)->sum(fn ($day) => $day['holidays']->count());
    $isCalendarView = $presentation === 'calendar';
    $isAdmin = auth()->user()->isAdmin();
    $listViewUrl = route('holidays.index', array_merge(request()->except('page', 'presentation', 'calendar_month', 'calendar_year'), ['presentation' => 'list']));
    $calendarViewUrl = route('holidays.index', array_merge(request()->only('calendar_month', 'calendar_year'), ['presentation' => 'calendar']));
@endphp

<div class="page-head holidays-head">
    <div>
        <p class="eyebrow">Calendario académico</p>
        <h1>Feriados y cierres</h1>
        <p class="subhead">Organiza las fechas que suspenden clases o solo deben quedar registradas.</p>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="holiday-create-dialog"><x-icon name="plus" /> Registrar fecha</button>
    </div>
</div>

<section class="holiday-stats" aria-label="Resumen de feriados">
    <article class="holiday-stat stat-total"><span>Fechas visibles</span><strong>{{ number_format($generalCount + $personalCount) }}</strong><small>General y personales</small></article>
    <article class="holiday-stat stat-global"><span>Calendario general</span><strong>{{ number_format($generalCount) }}</strong><small>Compartidas</small></article>
    <article class="holiday-stat stat-personal"><span>Mi calendario</span><strong>{{ number_format($personalCount) }}</strong><small>Solo para tus grupos</small></article>
    <article class="holiday-stat stat-blocking"><span>Próximas</span><strong>{{ number_format($upcomingCount) }}</strong><small>Desde hoy en adelante</small></article>
</section>

<nav class="holiday-view-switch" aria-label="Presentación de feriados">
    <a class="holiday-view-option {{ $isCalendarView ? 'active' : '' }}" href="{{ $calendarViewUrl }}" @if($isCalendarView) aria-current="page" @endif>
        <x-icon name="calendar-days" size="15" /><span>Calendario</span>
    </a>
    <a class="holiday-view-option {{ !$isCalendarView ? 'active' : '' }}" href="{{ $listViewUrl }}" @if(!$isCalendarView) aria-current="page" @endif>
        <x-icon name="file-text" size="15" /><span>Listado</span>
    </a>
</nav>

@if($isCalendarView)
<section class="holiday-summary">
    <div><span class="holiday-summary-label">Fechas en {{ $monthNames[$calendarMonth] }}</span><strong>{{ $monthEventCount }}</strong></div>
    <p>Los feriados generales y tus cierres personales se muestran juntos. Una fecha personal no modifica los calendarios de otros docentes.</p>
</section>

<section class="card calendar-card">
    <div class="calendar-heading">
        <div><p class="eyebrow">Vista mensual</p><h2>{{ ucfirst($monthNames[$calendarMonth]) }} {{ $calendarYear }}</h2></div>
        <div class="calendar-controls">
            <a class="calendar-nav" href="{{ $previousMonthUrl }}" aria-label="Mes anterior" title="Mes anterior"><x-icon name="arrow-left" size="15" /></a>
            <a class="calendar-today" href="{{ route('holidays.index', array_merge(request()->except('page', 'year', 'calendar_month', 'calendar_year'), ['calendar_month' => now()->month, 'calendar_year' => now()->year])) }}">Hoy</a>
            <a class="calendar-nav" href="{{ $nextMonthUrl }}" aria-label="Mes siguiente" title="Mes siguiente"><x-icon name="chevron-right" size="15" /></a>
        </div>
    </div>
    <div class="calendar-legend">
        <span><i class="legend-dot legend-block"></i> Suspende clases</span>
        <span><i class="legend-dot legend-info"></i> Informativo</span>
        <span class="calendar-month-count">{{ $monthEventCount }} {{ $monthEventCount === 1 ? 'fecha' : 'fechas' }}</span>
    </div>
    <div class="calendar-grid" role="grid" aria-label="Calendario de feriados de {{ $monthNames[$calendarMonth] }} {{ $calendarYear }}">
        @foreach($weekdays as $weekday)<div class="calendar-weekday" role="columnheader">{{ $weekday }}</div>@endforeach
        @foreach($calendarDays as $day)
            <div class="calendar-day {{ $day['inMonth'] ? '' : 'is-outside' }} {{ $day['isToday'] ? 'is-today' : '' }} {{ $day['holidays']->isNotEmpty() ? 'has-holiday' : '' }}" role="gridcell">
                <span class="calendar-day-number">{{ $day['date']->format('j') }}</span>
                @if($day['holidays']->isNotEmpty())
                    <div class="calendar-day-events">
                        @foreach($day['holidays']->take(3) as $holiday)
                            @php($canManage = ($holiday->professor_id !== null && (int) $holiday->professor_id === (int) auth()->user()->professor->id) || ($holiday->professor_id === null && $isAdmin))
                            <button class="calendar-event {{ $holiday->blocks_classes ? 'event-blocking' : 'event-info' }}" type="button"
                                data-calendar-detail
                                aria-label="Ver {{ $holiday->name }} del {{ $day['date']->format('d/m/Y') }}"
                                data-date="{{ $day['date']->format('d/m/Y') }}"
                                data-name="{{ $holiday->name }}"
                                data-scope="{{ $scopeLabels[$holiday->scope] ?? $holiday->scope }}"
                                data-scope-code="{{ $holiday->scope }}"
                                data-blocking="{{ $holiday->blocks_classes ? '1' : '0' }}"
                                data-verified="{{ $holiday->verified ? '1' : '0' }}"
                                data-source="{{ $holiday->source_url }}"
                                data-source-note="{{ $holiday->source_note }}"
                                data-calendar-type="{{ $holiday->professor_id ? 'Personal' : 'General' }}"
                                data-editable="{{ $canManage ? '1' : '0' }}"
                                data-update-url="{{ $canManage ? route('holidays.update', $holiday) : '' }}"
                                data-delete-url="{{ $canManage ? route('holidays.destroy', $holiday) : '' }}"
                                title="{{ $holiday->name }}">
                                <span class="calendar-event-dot" aria-hidden="true"></span>
                                <span class="calendar-event-name">{{ $holiday->name }}</span>
                            </button>
                        @endforeach
                        @if($day['holidays']->count() > 3)<span class="calendar-more">+{{ $day['holidays']->count() - 3 }} más</span>@endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @if($monthEventCount === 0)<p class="calendar-empty-note">No hay feriados ni cierres registrados para este mes.</p>@endif
</section>
@endif

@unless($isCalendarView)
<section class="card holiday-card">
    <div class="card-head">
        <div><h2>Fechas registradas</h2><p class="card-description">Filtra, consulta y administra los registros que te corresponden.</p></div>
        <span class="badge">{{ $holidays->total() }} registros</span>
    </div>
    <form method="GET" action="{{ route('holidays.index') }}" class="holiday-filters">
        <input type="hidden" name="presentation" value="list">
        <input type="hidden" name="calendar_month" value="{{ $calendarMonth }}">
        <input type="hidden" name="calendar_year" value="{{ $calendarYear }}">
        <label class="holiday-search"><x-icon name="search" size="15" /><input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar nombre o fuente…" aria-label="Buscar feriados"></label>
        <select class="field" name="year" aria-label="Año">
            <option value="">Todos los años</option>
            @foreach($years as $year)<option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>@endforeach
        </select>
        <select class="field" name="scope" aria-label="Ámbito">
            <option value="">Todos los ámbitos</option>
            @foreach($scopeLabels as $value => $label)<option value="{{ $value }}" @selected(request('scope') === $value)>{{ $label }}</option>@endforeach
        </select>
        <select class="field" name="impact" aria-label="Efecto en las clases">
            <option value="">Cualquier efecto</option>
            <option value="blocking" @selected(request('impact') === 'blocking')>Suspende clases</option>
            <option value="informative" @selected(request('impact') === 'informative')>Solo informativo</option>
        </select>
        <button class="btn btn-secondary" type="submit"><x-icon name="filter" size="14" /> Filtrar</button>
        @if(request()->hasAny(['q','year','scope','impact']))
            <a class="btn btn-ghost" href="{{ route('holidays.index', ['presentation'=>'list','calendar_month'=>$calendarMonth,'calendar_year'=>$calendarYear]) }}">Limpiar</a>
        @endif
    </form>

    <div class="holiday-list">
        @forelse($holidays as $holiday)
            @php($canManage = ($holiday->professor_id !== null && (int) $holiday->professor_id === (int) auth()->user()->professor->id) || ($holiday->professor_id === null && $isAdmin))
            <article class="holiday-row">
                <div class="holiday-date" aria-label="{{ $holiday->holiday_date->format('d/m/Y') }}">
                    <span>{{ $monthLabels[$holiday->holiday_date->month] }}</span>
                    <strong>{{ $holiday->holiday_date->format('d') }}</strong>
                    <small>{{ $holiday->holiday_date->format('Y') }}</small>
                </div>
                <div class="holiday-info">
                    <div class="holiday-title-line">
                        <h3>{{ $holiday->name }}</h3>
                        <span class="badge">{{ $scopeLabels[$holiday->scope] ?? $holiday->scope }}</span>
                        <span class="holiday-calendar-tag {{ $holiday->professor_id ? 'is-personal' : 'is-general' }}">{{ $holiday->professor_id ? 'Personal' : 'General' }}</span>
                    </div>
                    <p class="holiday-meta">
                        {{ $holiday->holiday_date->format('d/m/Y') }}
                        @if($holiday->source_note) · {{ $holiday->source_note }}@endif
                    </p>
                    @if($holiday->source_url)<a class="holiday-source" href="{{ $holiday->source_url }}" target="_blank" rel="noopener noreferrer"><x-icon name="arrow-up-right" size="12" /> Ver fuente</a>@endif
                </div>
                <div class="holiday-status">
                    <span class="holiday-impact {{ $holiday->blocks_classes ? 'is-blocking' : 'is-informative' }}">{{ $holiday->blocks_classes ? 'Suspende clases' : 'Informativo' }}</span>
                    @if($holiday->verified)<span class="badge badge-green">Verificado</span>@else<span class="badge">Pendiente de verificar</span>@endif
                </div>
                @if($canManage)
                    <div class="holiday-actions">
                        <button class="btn btn-secondary btn-sm" type="button" data-holiday-edit
                            data-update-url="{{ route('holidays.update', $holiday) }}"
                            data-holiday-date="{{ $holiday->holiday_date->toDateString() }}"
                            data-name="{{ $holiday->name }}"
                            data-scope="{{ $holiday->scope }}"
                            data-blocks="{{ $holiday->blocks_classes ? '1' : '0' }}"
                            data-source="{{ $holiday->source_url }}"
                            data-source-note="{{ $holiday->source_note }}"
                            data-verified="{{ $holiday->verified ? '1' : '0' }}">
                            <x-icon name="pencil" size="13" /> Editar
                        </button>
                        <form method="POST" action="{{ route('holidays.destroy', $holiday) }}" data-confirm="Se quitará esta fecha y se recalcularán las sesiones que correspondan. Los documentos generados podrían quedar pendientes de regeneración. ¿Continuar?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" size="13" /> Eliminar</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="holiday-empty">
                <span><x-icon name="search" size="26" /></span>
                <strong>{{ request()->hasAny(['q','year','scope','impact']) ? 'No hay fechas con esos filtros' : 'Todavía no hay feriados o cierres' }}</strong>
                <p>{{ request()->hasAny(['q','year','scope','impact']) ? 'Prueba con otro nombre, año o ámbito.' : 'Registra una fecha para mantener actualizados tus calendarios.' }}</p>
                @if(request()->hasAny(['q','year','scope','impact']))
                    <a class="btn btn-secondary btn-sm" href="{{ route('holidays.index', ['presentation'=>'list']) }}">Limpiar filtros</a>
                @else
                    <button class="btn btn-secondary btn-sm" type="button" data-modal-open="holiday-create-dialog">Registrar fecha</button>
                @endif
            </div>
        @endforelse
    </div>
    @if($holidays->hasPages())<div class="holiday-pagination"><x-pagination :paginator="$holidays" /></div>@endif
</section>
@endunless

@if($isCalendarView)
<dialog class="ui-dialog sheet-dialog" id="calendar-day-dialog" aria-labelledby="calendar-day-title">
    <div class="dialog-head">
        <div><p class="eyebrow">Detalle de fecha</p><h2 id="calendar-day-title">Feriado o cierre</h2><p id="calendar-day-date"></p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <div class="dialog-body">
        <div class="calendar-detail-name" id="calendar-day-name"></div>
        <div class="calendar-detail-meta">
            <span id="calendar-day-scope"></span><span id="calendar-day-calendar"></span><span id="calendar-day-status"></span><span id="calendar-day-verification"></span>
        </div>
        <p id="calendar-day-note" class="calendar-detail-note" hidden></p>
        <a id="calendar-day-source" class="calendar-day-source" href="#" target="_blank" rel="noopener noreferrer" hidden><x-icon name="arrow-up-right" /> Consultar fuente</a>
    </div>
    <div class="dialog-footer calendar-detail-actions">
        <button class="btn btn-secondary" type="button" data-calendar-edit hidden><x-icon name="pencil" /> Editar</button>
        <form method="POST" action="#" id="calendar-day-delete-form" data-confirm="Se quitará esta fecha y se recalcularán los calendarios afectados. ¿Continuar?" hidden>
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit"><x-icon name="x" /> Eliminar</button>
        </form>
        <button class="btn btn-ghost" type="button" data-modal-close>Cerrar</button>
    </div>
</dialog>
@endif

<dialog class="ui-dialog sheet-dialog" id="holiday-create-dialog" aria-labelledby="holiday-create-title" @if($errors->any() && request('dialog') !== 'edit-holiday') data-auto-open @endif>
    <div class="dialog-head">
        <div><p class="eyebrow">Nuevo registro</p><h2 id="holiday-create-title">Registrar feriado o cierre</h2><p>Define la fecha y si se suspenden las clases.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('holidays.store') }}">
        @csrf
        @foreach(['presentation','calendar_month','calendar_year','q','year','scope','impact'] as $filter)
            <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
        @endforeach
        <div class="dialog-body">
            @if($errors->any() && request('dialog') !== 'edit-holiday')
                <div class="dialog-error" role="alert"><strong>Revisa los datos ingresados:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @if($isAdmin)
                <label for="holiday_calendar_scope">Dónde se registrará</label>
                <select class="field" id="holiday_calendar_scope" name="calendar_scope">
                    <option value="PERSONAL" @selected(old('calendar_scope','PERSONAL') === 'PERSONAL')>Mi calendario</option>
                    <option value="GLOBAL" @selected(old('calendar_scope') === 'GLOBAL')>Calendario general (todos los docentes)</option>
                </select>
                <p class="help">Los registros generales afectan a todas las cuentas. Usa esta opción solo cuando la fecha sea oficial y compartida.</p>
            @endif
            <div class="form-row">
                <div>
                    <label for="holiday_date">Fecha</label>
                    <input class="field" id="holiday_date" type="date" name="holiday_date" value="{{ old('holiday_date') }}" required>
                    @error('holiday_date')<p class="help holiday-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="scope">Ámbito</label>
                    <select class="field" id="scope" name="scope" required>
                        @foreach($scopeLabels as $value => $label)<option value="{{ $value }}" @selected(old('scope','NATIONAL') === $value)>{{ $label }}</option>@endforeach
                    </select>
                    @error('scope')<p class="help holiday-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <label for="name">Nombre de la fecha</label>
            <input class="field" id="name" name="name" value="{{ old('name') }}" maxlength="255" required placeholder="Ej. Feriado nacional o cierre institucional">
            @error('name')<p class="help holiday-field-error">{{ $message }}</p>@enderror
            <label for="blocks_classes">Efecto en las clases</label>
            <select class="field" id="blocks_classes" name="blocks_classes" required>
                <option value="1" @selected(old('blocks_classes','1') === '1')>Suspende las clases y recalcula las sesiones</option>
                <option value="0" @selected(old('blocks_classes') === '0')>Solo informativo, no cambia las sesiones</option>
            </select>
            <label for="source_url">Fuente o norma (opcional)</label>
            <input class="field" id="source_url" type="url" name="source_url" value="{{ old('source_url') }}" maxlength="255" placeholder="https://…">
            @error('source_url')<p class="help holiday-field-error">{{ $message }}</p>@enderror
            <label for="source_note">Nota de referencia (opcional)</label>
            <textarea class="field" id="source_note" name="source_note" rows="2" maxlength="2000" placeholder="Número de norma, observación o detalle">{{ old('source_note') }}</textarea>
            @error('source_note')<p class="help holiday-field-error">{{ $message }}</p>@enderror
            @if($isAdmin)
                <label class="holiday-verified-option"><input type="checkbox" name="verified" value="1" @checked(old('verified'))> Confirmado con fuente oficial</label>
            @endif
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar fecha</button></div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="holiday-edit-dialog" aria-labelledby="holiday-edit-title" @if(request('dialog') === 'edit-holiday' && $errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div><p class="eyebrow">Editar registro</p><h2 id="holiday-edit-title">Actualizar feriado o cierre</h2><p>Los cambios pueden recalcular calendarios si la fecha suspende clases.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="#" id="holiday-edit-form">
        @csrf
        @method('PATCH')
        @foreach(['presentation','calendar_month','calendar_year','q','year','scope','impact'] as $filter)
            <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
        @endforeach
        <div class="dialog-body">
            <div class="form-row">
                <div><label for="edit_holiday_date">Fecha</label><input class="field" id="edit_holiday_date" type="date" name="holiday_date" value="{{ old('holiday_date') }}" required></div>
                <div>
                    <label for="edit_scope">Ámbito</label>
                    <select class="field" id="edit_scope" name="scope" required>
                        @foreach($scopeLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label for="edit_holiday_name">Nombre de la fecha</label>
            <input class="field" id="edit_holiday_name" name="name" value="{{ old('name') }}" maxlength="255" required>
            <label for="edit_blocks_classes">Efecto en las clases</label>
            <select class="field" id="edit_blocks_classes" name="blocks_classes" required>
                <option value="1">Suspende las clases y recalcula las sesiones</option>
                <option value="0">Solo informativo, no cambia las sesiones</option>
            </select>
            <label for="edit_source_url">Fuente o norma (opcional)</label>
            <input class="field" id="edit_source_url" type="url" name="source_url" value="{{ old('source_url') }}" maxlength="255">
            <label for="edit_source_note">Nota de referencia (opcional)</label>
            <textarea class="field" id="edit_source_note" name="source_note" rows="2" maxlength="2000">{{ old('source_note') }}</textarea>
            @if($isAdmin)
                <label class="holiday-verified-option"><input id="edit_verified" type="checkbox" name="verified" value="1" @checked(old('verified'))> Confirmado con fuente oficial</label>
            @endif
            @if($errors->any() && request('dialog') === 'edit-holiday')
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.holiday-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:12px}
.holiday-stat{display:grid;gap:3px;min-width:0;padding:11px 13px;border:1px solid var(--line);border-top:3px solid var(--accent);border-radius:9px;background:var(--surface)}
.holiday-stat span{color:var(--muted);font-size:9px;font-weight:750}
.holiday-stat strong{color:var(--ink);font-size:21px;font-weight:850;font-variant-numeric:tabular-nums}
.holiday-stat small{color:var(--muted);font-size:8px}
.stat-total{border-top-color:#4b87a7}.stat-global{border-top-color:#8875b1}.stat-personal{border-top-color:#419a8c}.stat-blocking{border-top-color:#c19343}
.holiday-view-switch{display:inline-flex;align-items:center;gap:3px;margin:0 0 12px;padding:3px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft)}
.holiday-view-option{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:34px;padding:7px 13px;border:1px solid transparent;border-radius:6px;color:var(--muted);font-size:10px;font-weight:700;text-decoration:none}
.holiday-view-option.active{border-color:var(--line);background:var(--surface);color:var(--accent);box-shadow:0 1px 2px rgba(20,40,50,.04)}
.holiday-summary{display:flex;align-items:center;gap:17px;margin:0 0 12px;padding:11px 14px;border:1px solid #d7e6ed;border-radius:9px;background:#f2f8fb}
.holiday-summary>div{display:flex;align-items:baseline;gap:9px;flex:none}
.holiday-summary-label{color:#668493;font-size:9px;font-weight:700}
.holiday-summary strong{color:#28677d;font-size:21px}
.holiday-summary p{margin:0;padding-left:15px;border-left:1px solid #d5e4e9;color:#617e8b;font-size:9px;line-height:1.45}
.calendar-card,.holiday-card{padding:15px}
.calendar-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
.calendar-heading h2{margin:0;color:var(--ink);font-size:17px;text-transform:capitalize}
.calendar-controls{display:flex;align-items:center;gap:5px}
.calendar-nav,.calendar-today{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 7px;border:1px solid var(--line-strong);border-radius:7px;background:var(--surface);color:var(--ink-soft);font-size:10px;text-decoration:none}
.calendar-today{padding:0 10px;font-weight:700}
.calendar-legend{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:9px;color:var(--muted);font-size:9px}
.calendar-legend>span{display:inline-flex;align-items:center;gap:5px}
.calendar-month-count{margin-left:auto}
.legend-dot{width:7px;height:7px;display:inline-block;border-radius:50%}
.legend-block{background:#c18b38}.legend-info{background:#4f8da5}
.calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));overflow:hidden;border-top:1px solid var(--line);border-left:1px solid var(--line);border-radius:8px}
.calendar-weekday{padding:8px 3px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--surface-soft);color:var(--muted);font-size:9px;font-weight:750;text-align:center}
.calendar-day{position:relative;min-width:0;min-height:90px;padding:6px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--surface)}
.calendar-day.is-outside{background:var(--surface-soft)}
.calendar-day.is-outside .calendar-day-number{color:var(--muted);opacity:.45}
.calendar-day.is-today .calendar-day-number{background:var(--accent);color:#fff}
.calendar-day.has-holiday{background:#fffdf8}
.calendar-day-number{display:grid;place-items:center;width:23px;height:23px;border-radius:50%;color:var(--ink-soft);font-size:10px;font-weight:700}
.calendar-day-events{display:grid;gap:3px;margin-top:4px}
.calendar-event{display:flex;align-items:flex-start;gap:4px;width:100%;min-width:0;padding:3px 4px;border:0;border-radius:4px;background:#fff1dd;color:#865f29;text-align:left;font-size:8px;font-weight:700;line-height:1.3;cursor:pointer}
.calendar-event.event-info{background:#edf5f8;color:#3d758b}
.calendar-event-dot{width:6px;height:6px;flex:none;margin-top:2px;border-radius:50%;background:#c08b40}
.event-info .calendar-event-dot{background:#4b8ca5}
.calendar-event-name{display:-webkit-box;min-width:0;overflow:hidden;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow-wrap:anywhere}
.calendar-more{padding-left:4px;color:var(--muted);font-size:8px}
.calendar-empty-note{margin:11px 0 0;color:var(--muted);font-size:10px;text-align:center}
.holiday-filters{display:grid;grid-template-columns:minmax(190px,1.5fr) repeat(3,minmax(100px,.7fr)) auto auto;align-items:center;gap:7px;margin:0 0 11px;padding:10px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft)}
.holiday-search{display:flex;align-items:center;gap:7px;min-width:0;min-height:38px;padding:0 9px;border:1px solid var(--line-strong);border-radius:7px;background:var(--surface);color:var(--muted)}
.holiday-search input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--ink);font-size:10px}
.holiday-filters .field{min-width:0;min-height:38px;padding:6px 8px;font-size:9px}
.holiday-filters .btn{min-height:38px;padding:7px 10px;font-size:9px;white-space:nowrap}
.holiday-list{display:grid}
.holiday-row{display:grid;grid-template-columns:48px minmax(0,1fr) minmax(105px,auto) auto;align-items:center;gap:11px;min-width:0;padding:11px 2px;border-bottom:1px solid var(--line)}
.holiday-row:last-child{border-bottom:0}
.holiday-date{display:flex;flex-direction:column;align-items:center;justify-content:center;width:44px;min-height:49px;border:1px solid #dbe8ed;border-radius:8px;background:#f1f7fa}
.holiday-date span{color:#597f90;font-size:8px;font-weight:800;letter-spacing:.04em}
.holiday-date strong{color:#2d6c84;font-size:18px;line-height:1.1}
.holiday-date small{color:#75909c;font-size:8px}
.holiday-info{display:grid;gap:3px;min-width:0}
.holiday-title-line{display:flex;align-items:center;gap:5px;flex-wrap:wrap;min-width:0}
.holiday-title-line h3{margin:0;color:var(--ink);font-size:11px;font-weight:750;line-height:1.35;overflow-wrap:anywhere}
.holiday-calendar-tag{padding:3px 5px;border-radius:5px;background:#f1f4f5;color:#687d87;font-size:8px;font-weight:750}
.holiday-calendar-tag.is-personal{background:#eaf5f2;color:#347e71}
.holiday-calendar-tag.is-general{background:#eef3fa;color:#547b9c}
.holiday-meta{margin:0;color:var(--muted);font-size:8px;line-height:1.4;overflow-wrap:anywhere}
.holiday-source{display:inline-flex;align-items:center;gap:4px;width:max-content;max-width:100%;color:var(--accent);font-size:8px;font-weight:700;overflow-wrap:anywhere}
.holiday-status{display:grid;justify-items:start;gap:4px;min-width:0}
.holiday-impact{display:inline-flex;align-items:center;max-width:100%;padding:4px 6px;border-radius:5px;font-size:8px;font-weight:750;line-height:1.25}
.holiday-impact.is-blocking{background:#fff2e0;color:#95651e}
.holiday-impact.is-informative{background:#edf4f7;color:#47788c}
.holiday-actions{display:flex;align-items:center;gap:5px;min-width:0}
.holiday-actions form{margin:0}
.holiday-actions .btn{min-height:31px;padding:5px 7px;border-radius:6px;font-size:8px;white-space:nowrap}
.holiday-empty{display:grid;justify-items:center;gap:6px;padding:28px 12px;border:1px dashed var(--line-strong);border-radius:8px;text-align:center}
.holiday-empty>span{display:grid;place-items:center;width:39px;height:39px;border-radius:9px;background:var(--surface-soft);color:var(--muted)}
.holiday-empty strong{color:var(--ink);font-size:11px}
.holiday-empty p{margin:0;color:var(--muted);font-size:9px}
.holiday-pagination{margin-top:12px;padding-top:10px;border-top:1px solid var(--line)}
.calendar-detail-name{color:var(--ink);font-size:14px;font-weight:750;line-height:1.4;overflow-wrap:anywhere}
.calendar-detail-meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
.calendar-detail-meta span{padding:5px 7px;border:1px solid var(--line);border-radius:5px;background:var(--surface-soft);color:var(--ink-soft);font-size:9px}
.calendar-detail-note{margin:10px 0 0;color:var(--muted);font-size:10px;line-height:1.5}
.calendar-day-source{display:inline-flex;align-items:center;gap:5px;margin-top:11px;color:var(--accent);font-size:10px;font-weight:700}
.calendar-detail-actions{align-items:center}
.calendar-detail-actions form{margin:0}
.holiday-field-error{color:var(--danger)}
.holiday-verified-option{display:flex;align-items:center;gap:8px;margin-top:12px;color:var(--ink-soft);font-size:10px}
.holiday-verified-option input{width:16px;height:16px;accent-color:var(--accent)}
@media(max-width:1000px){
 .holiday-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
 .holiday-filters{grid-template-columns:repeat(3,minmax(0,1fr))}
 .holiday-search{grid-column:1/-1}
 .holiday-row{grid-template-columns:45px minmax(0,1fr) 110px}
 .holiday-actions{grid-column:2/-1;justify-content:flex-start;padding-top:7px;border-top:1px solid var(--line)}
}
@media(max-width:700px){
 .holidays-head{align-items:stretch}
 .holidays-head .actions .btn{width:100%}
 .holiday-view-switch{display:flex;width:100%}
 .holiday-view-option{flex:1}
 .holiday-summary{align-items:flex-start;flex-direction:column;gap:6px}
 .holiday-summary p{padding:0;border:0}
 .calendar-card,.holiday-card{padding:10px}
 .calendar-day{min-height:66px;padding:4px}
 .calendar-weekday{padding:7px 1px;font-size:8px}
 .calendar-event{justify-content:center;width:17px;height:17px;padding:0;border-radius:50%;background:transparent}
 .calendar-event-name{display:none}
 .calendar-event-dot{width:7px;height:7px;margin:0}
 .calendar-day-events{display:flex;justify-content:center;gap:2px;flex-wrap:wrap}
 .calendar-more{padding:0;text-align:center}
 .holiday-row{grid-template-columns:42px minmax(0,1fr);gap:8px;padding:10px 0}
 .holiday-date{width:39px;min-height:44px}
 .holiday-date strong{font-size:17px}
 .holiday-status{grid-column:2;display:flex;align-items:center;flex-wrap:wrap;gap:4px}
 .holiday-actions{grid-column:2/-1;padding-top:7px}
 .holiday-filters{grid-template-columns:repeat(2,minmax(0,1fr));padding:8px}
 .holiday-search{grid-column:1/-1}
}
@media(max-width:420px){
 .holiday-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
 .holiday-stat{padding:9px}
 .holiday-stat strong{font-size:19px}
 .calendar-heading h2{font-size:15px}
 .calendar-day{min-height:54px;padding:3px}
 .calendar-day-number{width:20px;height:20px;font-size:9px}
 .calendar-weekday{font-size:7px}
 .calendar-controls{gap:3px}
 .calendar-nav{min-width:27px;height:28px;padding:0 5px}
 .calendar-today{height:28px;padding:0 7px}
 .holiday-filters{grid-template-columns:minmax(0,1fr)}
 .holiday-search{grid-column:1}
 .holiday-filters>*{width:100%}
 .holiday-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
 .holiday-actions .btn{width:100%;justify-content:center;font-size:9px}
 .holiday-title-line h3{font-size:10px}
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const editDialog = document.getElementById('holiday-edit-dialog');
    const editForm = document.getElementById('holiday-edit-form');
    const detailDialog = document.getElementById('calendar-day-dialog');

    const fields = {
        date: document.getElementById('edit_holiday_date'),
        name: document.getElementById('edit_holiday_name'),
        scope: document.getElementById('edit_scope'),
        blocks: document.getElementById('edit_blocks_classes'),
        source: document.getElementById('edit_source_url'),
        note: document.getElementById('edit_source_note'),
        verified: document.getElementById('edit_verified'),
    };

    const populateEdit = data => {
        if (!editDialog || !editForm) return;
        editForm.action = data.updateUrl || '#';
        fields.date.value = data.date || '';
        fields.name.value = data.name || '';
        fields.scope.value = data.scopeCode || 'NATIONAL';
        fields.blocks.value = data.blocks || '1';
        fields.source.value = data.source || '';
        fields.note.value = data.sourceNote || '';
        if (fields.verified) fields.verified.checked = data.verified === '1';
        if (!editDialog.open) editDialog.showModal();
        fields.date.focus();
    };

    document.addEventListener('click', event => {
        const editButton = event.target.closest('[data-holiday-edit]');
        if (editButton) {
            event.preventDefault();
            populateEdit({
                updateUrl: editButton.dataset.updateUrl,
                date: editButton.dataset.holidayDate,
                name: editButton.dataset.name,
                scopeCode: editButton.dataset.scope,
                blocks: editButton.dataset.blocks,
                source: editButton.dataset.source,
                sourceNote: editButton.dataset.sourceNote,
                verified: editButton.dataset.verified,
            });
            return;
        }

        const detailButton = event.target.closest('[data-calendar-detail]');
        if (detailButton && detailDialog) {
            document.getElementById('calendar-day-date').textContent = detailButton.dataset.date || '';
            document.getElementById('calendar-day-name').textContent = detailButton.dataset.name || '';
            document.getElementById('calendar-day-scope').textContent = detailButton.dataset.scope || '';
            document.getElementById('calendar-day-calendar').textContent = detailButton.dataset.calendarType || '';
            document.getElementById('calendar-day-status').textContent = detailButton.dataset.blocking === '1' ? 'Suspende clases' : 'Informativo';
            document.getElementById('calendar-day-verification').textContent = detailButton.dataset.verified === '1' ? 'Verificado' : 'Pendiente';
            const note = document.getElementById('calendar-day-note');
            note.textContent = detailButton.dataset.sourceNote || '';
            note.hidden = !note.textContent.trim();

            const source = document.getElementById('calendar-day-source');
            if (detailButton.dataset.source) {
                source.href = detailButton.dataset.source;
                source.hidden = false;
            } else {
                source.removeAttribute('href');
                source.hidden = true;
            }

            const canEdit = detailButton.dataset.editable === '1';
            const edit = detailDialog.querySelector('[data-calendar-edit]');
            const removeForm = document.getElementById('calendar-day-delete-form');
            edit.hidden = !canEdit;
            removeForm.hidden = !canEdit;
            removeForm.action = canEdit ? detailButton.dataset.deleteUrl : '#';
            edit.onclick = () => {
                detailDialog.close();
                populateEdit({
                    updateUrl: detailButton.dataset.updateUrl,
                    date: detailButton.dataset.date ? detailButton.dataset.date.split('/').reverse().join('-') : '',
                    name: detailButton.dataset.name,
                    scopeCode: detailButton.dataset.scopeCode || 'NATIONAL',
                    blocks: detailButton.dataset.blocking,
                    source: detailButton.dataset.source,
                    sourceNote: detailButton.dataset.sourceNote,
                    verified: detailButton.dataset.verified,
                });
            };
            if (!detailDialog.open) detailDialog.showModal();
        }
    });

    @if(request('dialog') === 'edit-holiday' && $errors->any())
        const editFormOpen = document.getElementById('holiday-edit-form');
        if (editFormOpen) {
            editFormOpen.action = @json(route('holidays.update', ['holiday' => (int) request('holiday_id')]));
            fields.date.value = @json(old('holiday_date', ''));
            fields.name.value = @json(old('name', ''));
            fields.scope.value = @json(old('scope', 'NATIONAL'));
            fields.blocks.value = @json(old('blocks_classes', '1'));
            fields.source.value = @json(old('source_url', ''));
            fields.note.value = @json(old('source_note', ''));
            if (fields.verified) fields.verified.checked = @json((bool) old('verified', false));
            if (!editDialog.open) editDialog.showModal();
            fields.date.focus();
        }
    @endif
})();
</script>
@endpush
