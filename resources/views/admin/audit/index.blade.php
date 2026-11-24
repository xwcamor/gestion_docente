@extends('layouts.app')
@section('title', 'Auditoría')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Administración</p>
        <h1>Auditoría de actividad</h1>
        <p class="subhead">Consulta quién realizó cambios, en qué módulo y cuándo.</p>
    </div>
</div>

<section class="card">
    <form method="GET" action="{{ route('admin.audit.index') }}" class="filters-toolbar">
        <input class="field" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar acción, usuario o módulo…">
        <select class="field" name="module">
            <option value="">Todos los módulos</option>
            @foreach(['ciclos'=>'Ciclos','grupos'=>'Grupos','cursos'=>'Cursos','modalidades'=>'Modalidades','alumnos'=>'Alumnos','evaluaciones'=>'Evaluaciones','malla_curricular'=>'Malla curricular','feriados'=>'Feriados','documentos'=>'Documentos','importaciones'=>'Importaciones','cuentas'=>'Cuentas','configuracion'=>'Configuración'] as $key=>$label)
                <option value="{{ $key }}" @selected(request('module') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="field" name="event">
            <option value="">Todos los eventos</option>
            @foreach(['created'=>'Creado','updated'=>'Actualizado','deleted'=>'Eliminado','activated'=>'Activado','deactivated'=>'Desactivado','generated'=>'Generado','imported'=>'Importado'] as $key=>$label)
                <option value="{{ $key }}" @selected(request('event') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input class="field" type="date" name="from" value="{{ request('from') }}" aria-label="Desde">
        <input class="field" type="date" name="to" value="{{ request('to') }}" aria-label="Hasta">
        <select class="field" name="per_page">
            @foreach([10,25,50,100] as $limit)<option value="{{ $limit }}" @selected((int)request('per_page',25)===$limit)>{{ $limit }} por página</option>@endforeach
        </select>
        <button class="btn btn-secondary" type="submit">Filtrar</button>
        <a class="btn btn-ghost" href="{{ route('admin.audit.index') }}">Limpiar</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Detalle</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'Usuario eliminado' }}</td>
                    <td><span class="badge badge-blue">{{ ucfirst(str_replace('_',' ',$log->module)) }}</span></td>
                    <td><span class="badge {{ in_array($log->event,['deleted','deactivated']) ? 'badge-red' : (in_array($log->event,['created','activated']) ? 'badge-green' : 'badge-orange') }}">{{ $log->event }}</span></td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><strong>No hay movimientos que coincidan.</strong></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<x-pagination :paginator="$logs" />@endif
</section>
@endsection
@push('styles')
<style>
.filters-toolbar{display:grid;grid-template-columns:minmax(200px,2fr) repeat(5,minmax(105px,1fr)) auto auto;gap:8px;margin-bottom:15px;align-items:center}
.filters-toolbar .field{width:100%;min-width:0}
@media(max-width:900px){.filters-toolbar{grid-template-columns:repeat(2,minmax(0,1fr))}.filters-toolbar input[type=search]{grid-column:1/-1}}
</style>
@endpush
