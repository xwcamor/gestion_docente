@extends('layouts.app')
@section('title', 'Feriados y cierres')
@section('content')
<div class="page-head">
    <div><p class="eyebrow">Configuración del calendario</p><h1>Feriados y cierres</h1><p class="subhead">Administra fechas que deben bloquear sesiones. Los cierres personales no modifican los calendarios de otros profesores.</p></div>
</div>

<section class="card">
    <details class="panel">
        <summary>＋ Registrar un feriado o cierre</summary>
        <div class="panel-body">
            <p class="card-description">Completa los datos de la excepción. Si bloquea clases, el sistema recalculará las modalidades afectadas.</p>
            <form method="POST" action="{{ route('holidays.store') }}">
                @csrf
                <div class="form-row-3">
                    <div><label for="holiday_date">Fecha</label><input class="field" id="holiday_date" type="date" name="holiday_date" value="{{ old('holiday_date') }}" required></div>
                    <div><label for="name">Nombre del feriado o cierre</label><input class="field" id="name" name="name" value="{{ old('name') }}" required placeholder="Ej. Cierre institucional"></div>
                    <div><label for="scope">Ámbito</label><select class="field" id="scope" name="scope" required><option value="NATIONAL">Nacional</option><option value="REGIONAL">Regional</option><option value="INSTITUTIONAL">Institucional</option><option value="SECTOR">Sector</option></select></div>
                </div>
                <div class="form-row">
                    <div><label for="blocks_classes">¿Debe bloquear clases?</label><select class="field" id="blocks_classes" name="blocks_classes"><option value="1">Sí, no se dictan clases</option><option value="0">No, solo informativo</option></select></div>
                    <div><label for="source_url">Fuente o norma (opcional)</label><input class="field" id="source_url" type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://…"></div>
                </div>
                <label for="source_note">Nota o referencia (opcional)</label><textarea class="field" id="source_note" name="source_note" rows="2" placeholder="Indica la norma o el motivo del cierre…">{{ old('source_note') }}</textarea>
                <div class="form-actions"><span class="help">Las fechas personales quedan asociadas a tu cuenta.</span><button class="btn" type="submit">Guardar excepción</button></div>
            </form>
        </div>
    </details>
</section>

<section class="card">
    <div class="card-head"><div><h2>Calendario registrado</h2><p class="card-description">Puedes combinar los filtros para localizar una fecha.</p></div><span class="badge">{{ $holidays->total() }} registros</span></div>
    <form method="GET" action="{{ route('holidays.index') }}" class="filter-bar">
        <input class="filter-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o nota…" aria-label="Buscar feriados">
        <select class="filter-input" name="year"><option value="">Todos los años</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>@endforeach</select>
        <select class="filter-input" name="scope"><option value="">Todos los ámbitos</option><option value="NATIONAL" @selected(request('scope')==='NATIONAL')>Nacional</option><option value="REGIONAL" @selected(request('scope')==='REGIONAL')>Regional</option><option value="INSTITUTIONAL" @selected(request('scope')==='INSTITUTIONAL')>Institucional</option><option value="SECTOR" @selected(request('scope')==='SECTOR')>Sector</option></select>
        <button class="btn btn-secondary btn-sm" type="submit">Filtrar</button>
        @if(request()->hasAny(['q','year','scope']))<a class="btn btn-ghost btn-sm" href="{{ route('holidays.index') }}">Limpiar</a>@endif
    </form>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Motivo</th><th>Ámbito</th><th>Bloquea clases</th><th>Verificación</th><th>Fuente</th></tr></thead><tbody>
        @forelse($holidays as $holiday)
            <tr><td><strong>{{ $holiday->holiday_date->format('d/m/Y') }}</strong></td><td>{{ $holiday->name }}</td><td>{{ $holiday->scope }}</td><td>@if($holiday->blocks_classes)<span class="badge badge-orange">Sí, bloquea</span>@else<span class="badge">No</span>@endif</td><td>@if($holiday->verified)<span class="badge badge-green">Verificado</span>@else<span class="badge badge-orange">Pendiente</span>@endif</td><td>@if($holiday->source_url)<a href="{{ $holiday->source_url }}" target="_blank" rel="noopener">Consultar fuente</a>@else—@endif</td></tr>
        @empty
            <tr><td colspan="6"><div class="empty"><strong>No hay fechas que coincidan con los filtros.</strong><span>Prueba con otro año o registra una excepción.</span></div></td></tr>
        @endforelse
    </tbody></table></div>
    <div class="mt-16">{{ $holidays->links() }}</div>
</section>
@endsection
