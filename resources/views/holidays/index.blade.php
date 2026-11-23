@extends('layouts.app')
@section('title', 'Feriados y cierres')
@section('content')
@php
    $monthLabels = [1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR', 5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'];
    $scopeLabels = [
        'NATIONAL' => 'Nacional',
        'REGIONAL' => 'Regional',
        'INSTITUTIONAL' => 'Institucional',
        'SECTOR' => 'Sector',
    ];
@endphp

<div class="page-head">
    <div>
        <p class="eyebrow">Calendario académico</p>
        <h1>Feriados y cierres</h1>
        <p class="subhead">Registra fechas en las que no habrá clases. Las modalidades que coincidan con esas fechas se actualizarán automáticamente.</p>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="holiday-create-dialog">＋ Registrar fecha</button>
    </div>
</div>

<section class="holiday-summary">
    <div>
        <span class="holiday-summary-label">Fechas encontradas</span>
        <strong>{{ $holidays->total() }}</strong>
    </div>
    <div class="holiday-summary-note">Las excepciones registradas desde tu cuenta se aplican a tus calendarios. No modifican los calendarios de otros docentes.</div>
</section>

<section class="card holiday-card">
    <div class="card-head">
        <div>
            <h2>Fechas registradas</h2>
            <p class="card-description">Filtra por año, ámbito o nombre para encontrar una fecha.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('holidays.index') }}" class="filter-bar holiday-filters">
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
        <button class="btn btn-secondary btn-sm" type="submit">Aplicar</button>
        @if(request()->hasAny(['q','year','scope']))
            <a class="btn btn-ghost btn-sm" href="{{ route('holidays.index') }}">Limpiar</a>
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
                    <p class="holiday-meta">
                        {{ $holiday->holiday_date->format('d/m/Y') }}
                        <span aria-hidden="true">·</span>
                        {{ $holiday->professor_id ? 'Tu calendario' : 'Registro general' }}
                    </p>
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
                        <a class="holiday-source" href="{{ $holiday->source_url }}" target="_blank" rel="noopener noreferrer">Ver fuente ↗</a>
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
        <div class="holiday-pagination">{{ $holidays->links() }}</div>
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="holiday-create-dialog" aria-labelledby="holiday-create-title" data-backdrop-close @if($errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <h2 id="holiday-create-title">Registrar feriado o cierre</h2>
            <p>Completa los datos de la fecha que deseas excluir del calendario.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar">×</button>
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
            <button class="btn" type="submit">Guardar fecha</button>
        </div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.holiday-summary{display:flex;align-items:center;gap:18px;margin:0 0 13px;padding:13px 16px;border:1px solid #e2e6e0;border-radius:8px;background:#fbfcfa}
.holiday-summary>div:first-child{display:flex;align-items:baseline;gap:10px;flex:none}
.holiday-summary-label{color:#79847b;font-size:10px}
.holiday-summary strong{color:#40584a;font-size:20px;font-weight:680}
.holiday-summary-note{padding-left:17px;border-left:1px solid #e0e5de;color:#7b857c;font-size:10px;line-height:1.5}
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
@media(max-width:640px){
 .holiday-summary{align-items:flex-start;flex-direction:column;gap:7px;padding:11px 12px}
 .holiday-summary-note{padding:0;border:0}
 .holiday-filters{grid-template-columns:repeat(2,minmax(0,1fr))}
 .holiday-row{grid-template-columns:48px minmax(0,1fr);gap:9px;padding:12px 0}
 .holiday-date{width:44px;min-height:52px}
 .holiday-date strong{font-size:18px}
 .holiday-status{grid-column:2;align-items:flex-start;justify-content:flex-start;flex-direction:row;flex-wrap:wrap;gap:5px}
 .holiday-title-line{gap:5px}
 .holiday-title-line h3{flex:1 1 100%}
 .holiday-meta{font-size:9px}
}
</style>
@endpush