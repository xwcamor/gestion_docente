@extends('layouts.app')
@section('title', 'Feriados y cierres')
@section('content')
@php
    $monthNames = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $monthLabels = [1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR', 5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'];
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $scopeLabels = [
        'NATIONAL' => 'Nacional',
        'REGIONAL' => 'Regional',
        'INSTITUTIONAL' => 'Institucional',
        'SECTOR' => 'Sector',
    ];
    $monthEventCount = collect($calendarDays)->sum(fn ($day) => $day['holidays']->count());
    $isCalendarView = $presentation === 'calendar';
    $listViewUrl = route('holidays.index', array_merge(request()->except('page', 'presentation', 'calendar_month', 'calendar_year'), ['presentation' => 'list']));
    $calendarViewUrl = route('holidays.index', array_merge(request()->only('calendar_month', 'calendar_year'), ['presentation' => 'calendar']));
@endphp

<div class="page-head holidays-head">
    <div>
        <p class="eyebrow">Calendario académico</p>
        <h1>Feriados y cierres</h1>
        <p class="subhead">Consulta las fechas que afectan tus clases y el calendario de cada modalidad.</p>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="holiday-create-dialog"><x-icon name="plus" /> Registrar fecha</button>
    </div>
</div>

<nav class="holiday-view-switch" aria-label="Presentación de feriados">
    <a class="holiday-view-option {{ $isCalendarView ? 'active' : '' }}" href="{{ $calendarViewUrl }}" @if($isCalendarView) aria-current="page" @endif>
        <x-icon name="calendar-days" size="15" />
        <span>Calendario</span>
    </a>
    <a class="holiday-view-option {{ !$isCalendarView ? 'active' : '' }}" href="{{ $listViewUrl }}" @if(!$isCalendarView) aria-current="page" @endif>
        <x-icon name="file-text" size="15" />
        <span>Listado</span>
    </a>
</nav>

@if($isCalendarView)
<section class="holiday-summary">
    <div>
        <span class="holiday-summary-label">Fechas en {{ $monthNames[$calendarMonth] }}</span>
        <strong>{{ $monthEventCount }}</strong>
    </div>
    <div class="holiday-summary-note">Los feriados generales y tus cierres personales aparecen juntos. Los cierres personales solo afectan tus calendarios.</div>
</section>

<section class="card calendar-card">
    <div class="calendar-heading">
        <div>
            <p class="eyebrow">Vista mensual</p>
            <h2>{{ ucfirst($monthNames[$calendarMonth]) }} {{ $calendarYear }}</h2>
        </div>
        <div class="calendar-controls">
            <a class="calendar-nav" href="{{ $previousMonthUrl }}" aria-label="Mes anterior" title="Mes anterior"><x-icon name="arrow-left" size="14" /></a>
            <a class="calendar-today" href="{{ route('holidays.index', array_merge(request()->except('page', 'year', 'calendar_month', 'calendar_year'), ['calendar_month' => now()->month, 'calendar_year' => now()->year])) }}">Hoy</a>
            <a class="calendar-nav" href="{{ $nextMonthUrl }}" aria-label="Mes siguiente" title="Mes siguiente"><x-icon name="arrow-right" size="14" /></a>
        </div>
    </div>

    <div class="calendar-legend">
        <span><i class="legend-dot legend-block"></i> Suspende clases</span>
        <span><i class="legend-dot legend-info"></i> Informativo</span>
        <span class="calendar-month-count">{{ $monthEventCount }} {{ $monthEventCount === 1 ? 'fecha' : 'fechas' }}</span>
    </div>

    <div class="calendar-grid" role="grid" aria-label="Calendario de feriados de {{ $monthNames[$calendarMonth] }} {{ $calendarYear }}">
        @foreach($weekdays as $weekday)
            <div class="calendar-weekday" role="columnheader">{{ $weekday }}</div>
        @endforeach

        @foreach($calendarDays as $day)
            <div class="calendar-day {{ $day['inMonth'] ? '' : 'is-outside' }} {{ $day['holidays']->isNotEmpty() ? 'has-holiday' : '' }}" role="gridcell">
                <span class="calendar-day-number">{{ $day['date']->format('j') }}</span>
                @if($day['holidays']->isNotEmpty())
                    <div class="calendar-day-events">
                        @foreach($day['holidays']->take(2) as $holiday)
                            <button
                                class="calendar-event {{ $holiday->blocks_classes ? 'event-blocking' : 'event-info' }}"
                                type="button"
                                data-calendar-detail
                                aria-label="Ver {{ $holiday->name }} del {{ $day['date']->format('d/m/Y') }}"
                                data-date="{{ $day['date']->format('d/m/Y') }}"
                                data-name="{{ $holiday->name }}"
                                data-scope="{{ $scopeLabels[$holiday->scope] ?? $holiday->scope }}"
                                data-blocking="{{ $holiday->blocks_classes ? '1' : '0' }}"
                                data-verified="{{ $holiday->verified ? '1' : '0' }}"
                                data-source="{{ $holiday->source_url }}"
                                title="{{ $holiday->name }}"
                            >
                                <span class="calendar-event-dot" aria-hidden="true"></span>
                                <span class="calendar-event-name">{{ $holiday->name }}</span>
                            </button>
                        @endforeach
                        @if($day['holidays']->count() > 2)
                            <span class="calendar-more">+{{ $day['holidays']->count() - 2 }} más</span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($monthEventCount === 0)
        <p class="calendar-empty-note">No hay feriados ni cierres registrados para este mes.</p>
    @endif
</section>
@endif

@unless($isCalendarView)
<section class="card holiday-card">
    <div class="card-head">
        <div>
            <h2>Detalle de fechas</h2>
            <p class="card-description">Consulta el estado o filtra los registros por año, ámbito o nombre.</p>
        </div>
        <span class="badge">{{ $holidays->total() }} registros</span>
    </div>

    <form method="GET" action="{{ route('holidays.index') }}" class="filter-bar holiday-filters">
        <input type="hidden" name="presentation" value="list">
        <input type="hidden" name="calendar_month" value="{{ $calendarMonth }}">
        <input type="hidden" name="calendar_year" value="{{ $calendarYear }}">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre…" aria-label="Buscar por nombre">
        <select class="filter-input" name="year" aria-label="Filtrar por año">
            <option value="">Todos los años</option>
            @foreach($years as $year)
                <option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>
            @endforeach
        </select>
        <select class="filter-input" name="scope" aria-label="Filtrar por ámbito">
            <option value="">Todos los ámbitos</option>
            @foreach($scopeLabels as $value => $label)
                <option value="{{ $value }}" @selected(request('scope') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="filter" /> Aplicar</button>
        @if(request()->hasAny(['q','year','scope']))
            <a class="btn btn-ghost btn-sm" href="{{ route('holidays.index', ['calendar_month' => $calendarMonth, 'calendar_year' => $calendarYear]) }}"><x-icon name="x" /> Limpiar</a>
        @endif
    </form>

    <div class="holiday-list">
        @forelse($holidays as $holiday)
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
                    </div>
                    <p class="holiday-meta">{{ $holiday->holiday_date->format('d/m/Y') }} · {{ $holiday->professor_id ? 'Tu calendario' : 'Registro general' }}</p>
                </div>
                <div class="holiday-status">
                    @if($holiday->blocks_classes)
                        <span class="badge badge-orange">No hay clases</span>
                    @else
                        <span class="badge">Solo informativo</span>
                    @endif
                    @if($holiday->verified)
                        <span class="badge badge-green">Verificado</span>
                    @else
                        <span class="badge">Pendiente</span>
                    @endif
                    @if($holiday->source_url)
                        <a class="holiday-source" href="{{ $holiday->source_url }}" target="_blank" rel="noopener noreferrer"><x-icon name="arrow-up-right" /> Ver fuente</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty holiday-empty">
                <strong>{{ request()->hasAny(['q','year','scope']) ? 'No hay fechas con esos filtros.' : 'Todavía no registras feriados o cierres.' }}</strong>
                <span>{{ request()->hasAny(['q','year','scope']) ? 'Prueba con otro nombre, año o ámbito.' : 'Registra la primera fecha para que el calendario pueda excluirla.' }}</span>
                @if(!request()->hasAny(['q','year','scope']))
                    <div class="mt-16"><button class="btn btn-secondary btn-sm" type="button" data-modal-open="holiday-create-dialog">Registrar fecha</button></div>
                @endif
            </div>
        @endforelse
    </div>

    @if($holidays->hasPages())
        <div class="holiday-pagination"><x-pagination :paginator="$holidays" /></div>
    @endif
</section>
@endunless

@if($isCalendarView)
<dialog class="ui-dialog sheet-dialog" id="calendar-day-dialog" aria-labelledby="calendar-day-title" data-backdrop-close>
    <div class="dialog-head">
        <div>
            <h2 id="calendar-day-title">Detalle de la fecha</h2>
            <p id="calendar-day-date"></p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <div class="dialog-body">
        <div class="calendar-detail-name" id="calendar-day-name"></div>
        <div class="calendar-detail-meta">
            <span id="calendar-day-scope"></span>
            <span id="calendar-day-status"></span>
            <span id="calendar-day-verification"></span>
        </div>
        <a id="calendar-day-source" class="calendar-day-source" href="#" target="_blank" rel="noopener noreferrer" hidden><x-icon name="arrow-up-right" /> Consultar fuente</a>
    </div>
    <div class="dialog-footer">
        <button class="btn btn-secondary" type="button" data-modal-close>Cerrar</button>
    </div>
</dialog>
@endif

<dialog class="ui-dialog sheet-dialog" id="holiday-create-dialog" aria-labelledby="holiday-create-title" data-backdrop-close @if($errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="holiday-create-title">Registrar feriado o cierre</h2>
            <p>Completa los datos de la fecha que deseas excluir del calendario.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('holidays.store') }}">
        @csrf
        <div class="dialog-body">
            @if($errors->any())
                <div class="dialog-error" role="alert">
                    <strong>Revisa estos campos:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="form-row">
                <div>
                    <label for="holiday_date">Fecha</label>
                    <input class="field" id="holiday_date" type="date" name="holiday_date" value="{{ old('holiday_date') }}" required>
                    @error('holiday_date')<p class="help" style="color:#914d45">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="scope">Ámbito</label>
                    <select class="field" id="scope" name="scope" required>
                        @foreach($scopeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('scope', 'NATIONAL') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('scope')<p class="help" style="color:#914d45">{{ $message }}</p>@enderror
                </div>
            </div>
            <label for="name">Nombre de la fecha</label>
            <input class="field" id="name" name="name" value="{{ old('name') }}" maxlength="255" required placeholder="Ej. Feriado nacional o cierre institucional">
            @error('name')<p class="help" style="color:#914d45">{{ $message }}</p>@enderror

            <label for="blocks_classes">¿Se suspenden las clases?</label>
            <select class="field" id="blocks_classes" name="blocks_classes" required>
                <option value="1" @selected(old('blocks_classes', '1') === '1')>Sí, bloquear las sesiones</option>
                <option value="0" @selected(old('blocks_classes') === '0')>No, solo dejar constancia</option>
            </select>
            <p class="help">Al bloquear clases, se recalcularán las modalidades que tengan sesión en esa fecha.</p>

            <label for="source_url">Fuente o norma (opcional)</label>
            <input class="field" id="source_url" type="url" name="source_url" value="{{ old('source_url') }}" maxlength="500" placeholder="https://…">
            @error('source_url')<p class="help" style="color:#914d45">{{ $message }}</p>@enderror
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button>
            <button class="btn" type="submit"><x-icon name="check" /> Guardar fecha</button>
        </div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.holiday-view-switch{display:inline-flex;align-items:center;gap:3px;margin:0 0 14px;padding:4px;border:1px solid #e2e7e0;border-radius:9px;background:#eef1ed}
.holiday-view-option{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:36px;padding:8px 14px;border:1px solid transparent;border-radius:6px;color:#657166;font-size:11px;font-weight:650;transition:background .14s ease,color .14s ease,border-color .14s ease,box-shadow .14s ease}
.holiday-view-option:hover{color:#344a3e;text-decoration:none}
.holiday-view-option.active{border-color:#dce5da;background:#fff;color:#3c5843;box-shadow:0 1px 2px rgba(26,40,28,.04)}
.holiday-summary{display:flex;align-items:center;gap:18px;margin:0 0 13px;padding:13px 16px;border:1px solid #e2e6e0;border-radius:8px;background:#fbfcfa}
.holiday-summary>div:first-child{display:flex;align-items:baseline;gap:10px;flex:none}
.holiday-summary-label{color:#79847b;font-size:10px}
.holiday-summary strong{color:#40584a;font-size:20px;font-weight:680}
.holiday-summary-note{padding-left:17px;border-left:1px solid #e0e5de;color:#7b857c;font-size:10px;line-height:1.5}
.calendar-card{padding:17px}
.calendar-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
.calendar-heading h2{margin:0;font-size:18px;text-transform:capitalize}
.calendar-controls{display:flex;align-items:center;gap:6px}
.calendar-nav,.calendar-today{height:32px;min-width:32px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #dfe5dd;border-radius:6px;background:#fff;color:#566358;font-size:15px;font-weight:650}
.calendar-nav:hover,.calendar-today:hover{border-color:#bdcbbb;background:#f5f8f4;text-decoration:none}
.calendar-today{padding:0 10px;font-size:10px}
.calendar-legend{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:0 0 10px;color:#7c877e;font-size:9px}
.calendar-legend>span{display:inline-flex;align-items:center;gap:6px}
.legend-dot{width:7px;height:7px;display:inline-block;border-radius:50%}
.legend-block{background:#8b6b43}
.legend-info{background:#788b80}
.calendar-month-count{margin-left:auto}
.calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border-top:1px solid #e5e9e3;border-left:1px solid #e5e9e3;border-radius:7px;overflow:hidden}
.calendar-weekday{padding:9px 5px;border-right:1px solid #e5e9e3;border-bottom:1px solid #e5e9e3;background:#f8faf7;color:#788279;font-size:9px;font-weight:720;text-align:center}
.calendar-day{position:relative;min-width:0;min-height:108px;padding:8px;border-right:1px solid #e5e9e3;border-bottom:1px solid #e5e9e3;background:#fff}
.calendar-day.is-outside{background:#fafbf9}
.calendar-day.is-outside .calendar-day-number{color:#c0c7bf}
.calendar-day.has-holiday{background:#fffefb}
.calendar-day-number{width:23px;height:23px;display:grid;place-items:center;border-radius:50%;color:#536056;font-size:10px;font-weight:620}
.calendar-day-events{display:grid;gap:4px;margin-top:4px}
.calendar-event{display:flex;align-items:flex-start;gap:5px;width:100%;min-width:0;padding:3px 4px;border:0;border-radius:4px;background:#f4f0e8;color:#806338;text-align:left;font-size:9px;font-weight:620;line-height:1.3;cursor:pointer;transition:background .12s ease}
.calendar-event:hover{background:#ece3d4}
.calendar-event.event-info{background:#eff3ef;color:#586d5d}
.calendar-event.event-info:hover{background:#e5ece5}
.calendar-event-dot{width:6px;height:6px;flex:none;margin-top:2px;border-radius:50%;background:#94764d}
.event-info .calendar-event-dot{background:#718778}
.calendar-event-name{min-width:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow-wrap:anywhere}
.calendar-more{padding-left:4px;color:#8a938b;font-size:8px}
.calendar-empty-note{margin:12px 0 0;color:#8b958c;font-size:10px;text-align:center}
.calendar-detail-name{color:#344239;font-size:15px;font-weight:680;line-height:1.4;overflow-wrap:anywhere}
.calendar-detail-meta{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px}
.calendar-detail-meta span{padding:5px 7px;border:1px solid #e3e8e1;border-radius:5px;background:#f8faf7;color:#69756b;font-size:9px}
.calendar-day-source{display:inline-block;margin-top:13px;color:#526b59;font-size:10px;font-weight:650}
.holiday-card{padding:17px}
.holiday-filters .filter-input{flex:1 1 135px;min-width:120px}
.holiday-list{display:grid;gap:0}
.holiday-row{display:grid;grid-template-columns:58px minmax(0,1fr) minmax(130px,auto);align-items:center;gap:13px;padding:13px 2px;border-bottom:1px solid #edf0eb}
.holiday-row:last-child{border-bottom:0}
.holiday-date{width:52px;min-height:58px;display:flex;flex-direction:column;align-items:center;justify-content:center;border:1px solid #e3e8e1;border-radius:7px;background:#f8faf7}
.holiday-date span{color:#718075;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.holiday-date strong{margin:1px 0;color:#354b3d;font-size:20px;line-height:1.1;font-weight:700}
.holiday-date small{color:#929b92;font-size:9px}
.holiday-info{min-width:0}
.holiday-title-line{display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0}
.holiday-title-line h3{margin:0;color:#354239;font-size:12px;font-weight:680;overflow-wrap:anywhere}
.holiday-meta{margin:5px 0 0;color:#8a938b;font-size:10px;line-height:1.4;text-transform:capitalize;overflow-wrap:anywhere}
.holiday-status{display:flex;align-items:flex-end;justify-content:center;flex-direction:column;gap:5px;min-width:0}
.holiday-source{color:#526b59;font-size:10px;font-weight:640}
.holiday-empty{margin:4px 0}
.holiday-pagination{margin-top:13px;padding-top:12px;border-top:1px solid #edf0eb}
@media(max-width:760px){
 .holiday-view-switch{display:flex;width:100%}.holiday-view-option{flex:1}
 .holiday-summary{align-items:flex-start;flex-direction:column;gap:7px;padding:11px 12px}
 .holiday-summary-note{padding:0;border:0}
 .calendar-card{padding:11px}
 .calendar-day{min-height:75px;padding:5px}
 .calendar-weekday{padding:8px 2px;font-size:8px}
 .calendar-event{justify-content:center;width:19px;height:19px;padding:0;border-radius:50%;background:transparent}
 .calendar-event:hover{background:#f0f2ed}
 .calendar-event-dot{width:8px;height:8px;margin:0}
 .calendar-event-name{display:none}
 .calendar-more{padding:0;text-align:center;font-size:8px}
 .calendar-day-events{display:flex;justify-content:center;gap:3px;flex-wrap:wrap}
 .holiday-filters{grid-template-columns:repeat(2,minmax(0,1fr))}
 .holiday-row{grid-template-columns:48px minmax(0,1fr);gap:9px;padding:12px 0}
 .holiday-date{width:44px;min-height:52px}
 .holiday-date strong{font-size:18px}
 .holiday-status{grid-column:2;align-items:flex-start;justify-content:flex-start;flex-direction:row;flex-wrap:wrap;gap:5px}
 .holiday-title-line{gap:5px}
 .holiday-title-line h3{flex:1 1 100%}
 .holiday-meta{font-size:9px}
}
@media(max-width:390px){
 .calendar-day{min-height:58px;padding:3px}
 .calendar-day-number{width:20px;height:20px;font-size:9px}
 .calendar-weekday{font-size:7px}
 .calendar-heading h2{font-size:15px}
 .calendar-nav{min-width:29px;height:29px}
 .calendar-today{height:29px;padding:0 7px}
}

/* Feriados: calendario con señales de color claras */
.holiday-view-switch{border-color:#d3e2e9;background:#edf4f7}
.holiday-view-option{color:#56788a}
.holiday-view-option.active{border-color:#bdd6e1;color:#28677d}
.holiday-summary{border-color:#d7e6ec;background:linear-gradient(100deg,#eff7fa,#f5faf8)}
.holiday-summary strong{color:#28677d}
.holiday-summary-note{color:#5e7d8b;border-left-color:#d5e4e9}
.calendar-nav,.calendar-today{border-color:#cbdde6;color:#3c748b}
.calendar-nav:hover,.calendar-today:hover{border-color:#92bdcc;background:#eef7fa}
.calendar-weekday{background:#eaf3f7;color:#4b7284;border-color:#d6e4eb}
.calendar-grid{border-color:#d6e4eb}
.calendar-day{border-color:#dce8ed}
.calendar-day.has-holiday{background:#fffbf3}
.calendar-day-number{color:#3c6173}
.calendar-day-number.today{background:#28677d;color:#fff}
.calendar-event{background:#fff0d9;color:#8e6024}
.calendar-event.event-info{background:#eaf4f8;color:#3d7187}
.calendar-event-dot{background:#d0923e}
.event-info .calendar-event-dot{background:#4e91aa}
.calendar-detail-meta span{border-color:#dce8ed;background:#f3f8fa;color:#4a7181}
.holiday-date{border-color:#d6e5eb;background:#eff6f8}
.holiday-date strong{color:#28677d}
.holiday-title-line h3{color:#315a6d}

</style>
@endpush

@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('calendar-day-dialog');
    if (!dialog) return;
    const date = document.getElementById('calendar-day-date');
    const name = document.getElementById('calendar-day-name');
    const scope = document.getElementById('calendar-day-scope');
    const status = document.getElementById('calendar-day-status');
    const verification = document.getElementById('calendar-day-verification');
    const source = document.getElementById('calendar-day-source');

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-calendar-detail]');
        if (!button) return;
        date.textContent = button.dataset.date || '';
        name.textContent = button.dataset.name || 'Fecha académica';
        scope.textContent = button.dataset.scope || '';
        status.textContent = button.dataset.blocking === '1' ? 'Suspende clases' : 'Solo informativo';
        verification.textContent = button.dataset.verified === '1' ? 'Verificado' : 'Pendiente de verificación';
        const url = button.dataset.source || '';
        if (url) {
            source.href = url;
            source.hidden = false;
        } else {
            source.removeAttribute('href');
            source.hidden = true;
        }
        if (typeof dialog.showModal === 'function') dialog.showModal();
    });
})();
</script>
@endpush