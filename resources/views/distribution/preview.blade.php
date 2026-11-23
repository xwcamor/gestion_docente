@extends('layouts.app')
@section('title', 'Vista previa de importación')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos y cursos</a><span>›</span><a href="{{ route('distribution.create') }}">Importar Excel</a><span>›</span><span class="current">Vista previa</span></div>
<div class="page-head"><div><p class="eyebrow">Revisión antes de guardar</p><h1>Vista previa de tu carga académica</h1><p class="subhead">Comprueba los cursos detectados. Todavía no se ha guardado ningún dato.</p></div><a class="btn btn-secondary" href="{{ route('distribution.create') }}"><x-icon name="file-text" /> Cambiar archivo</a></div>
<div class="grid grid-4" style="margin-bottom:16px">
    <div class="stat-card"><div class="stat-label">Ciclo detectado</div><div class="stat">{{ $data['academic_year'] }} · {{ $data['cycle_code'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Cursos</div><div class="stat">{{ $data['course_count'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Modalidades</div><div class="stat">{{ $data['modality_count'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Rango general</div><div class="stat" style="font-size:15px">{{ \Carbon\Carbon::parse($data['start_date'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($data['end_date'])->format('d/m/Y') }}</div></div>
</div>
@if(count($data['errors']))<div class="alert alert-error"><strong>Filas con errores:</strong><ul>@foreach($data['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(count($data['warnings']))<div class="alert alert-warning"><strong>Datos que requieren revisión:</strong><ul>@foreach($data['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
<section class="card">
    <div class="card-head"><div><h2>Datos detectados en la pestaña {{ $data['source_sheet'] }}</h2><p class="card-description">Cada curso se registrará una sola vez; TEC y TALL conservarán su NRC y fechas por separado.</p></div><span class="badge">{{ count($data['rows']) }} cursos válidos</span></div>
    <div class="table-wrap"><table><thead><tr><th>Curso</th><th>Código</th><th>Modalidad</th><th>NRC</th><th>Bloque</th><th>Desde</th><th>Hasta</th></tr></thead><tbody>
    @foreach($data['rows'] as $row)
        @foreach($row['modalities'] as $modality)
        <tr><td><strong>{{ $row['name'] }}</strong><div class="help">Fila {{ $row['source_row'] }}</div></td><td class="mono">{{ $row['code'] }}</td><td><span class="badge {{ $modality['type'] === 'TEC' ? 'badge-blue' : 'badge-green' }}">{{ $modality['type'] }}</span></td><td>{{ $modality['nrc'] ?: 'Pendiente' }}</td><td>{{ $modality['block_code'] ?: '—' }}</td><td>{{ $modality['start_date'] ? \Carbon\Carbon::parse($modality['start_date'])->format('d/m/Y') : 'Pendiente' }}</td><td>{{ $modality['end_date'] ? \Carbon\Carbon::parse($modality['end_date'])->format('d/m/Y') : 'Pendiente' }}</td></tr>
        @endforeach
    @endforeach
    </tbody></table></div>
</section>
<section class="card">
    <h2>Confirmar importación</h2>
    <p class="card-description">El sistema creará o actualizará el ciclo y agrupará estos cursos en un grupo. Si vuelves a importar el mismo archivo al mismo grupo, no duplicará cursos ni modalidades.</p>
    <form method="POST" action="{{ route('distribution.confirm') }}">
        @csrf
        <div class="form-row"><div><label for="group_name">Nombre del grupo</label><input class="field" id="group_name" name="group_name" required maxlength="100" value="{{ old('group_name', 'Grupo principal') }}"><p class="help">Puedes cambiarlo por el nombre de tu sección o aula.</p></div><div><label>Ciclo que se creará o reutilizará</label><input class="field" value="{{ $data['academic_year'] }} · {{ $data['cycle_code'] }}" disabled></div></div>
        <div class="alert alert-info" style="margin-top:16px">Los horarios (días, horas y ambiente) no se importan desde esta pestaña porque no están en sus columnas. Podrás completarlos después y generar el calendario excluyendo feriados.</div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('distribution.create') }}"><x-icon name="arrow-left" /> Cancelar</a><button class="btn" type="submit" {{ count($data['rows']) === 0 ? 'disabled' : '' }}><x-icon name="check" /> Confirmar e importar {{ count($data['rows']) }} cursos</button></div>
    </form>
</section>
@endsection