@extends('layouts.app')
@section('title','Vista previa de importación')
@section('content')
<div class="page-head"><div><h1>Revisar alumnos antes de importar</h1><p class="subhead">{{ $modality->assignment->course->name }} · {{ $modality->modality }} · NRC {{ $modality->nrc ?: 'sin NRC' }}</p></div><a class="btn btn-secondary" href="{{ route('modalities.show',$modality) }}">Cancelar</a></div>
<div class="grid grid-3"><div class="card"><div class="stat-label">Filas detectadas</div><div class="stat">{{ $preview['total'] }}</div></div><div class="card"><div class="stat-label">Formato detectado</div><div style="font-weight:700;margin-top:10px">{{ $preview['format'] }}</div></div><div class="card"><div class="stat-label">Calificación general</div><div style="font-weight:700;margin-top:10px">{{ $preview['has_grade']?'Columna detectada':'No detectada' }}</div></div></div>
<section class="card"><h2>Columnas reconocidas</h2><div class="actions">@foreach($preview['headers'] as $header)<span class="badge badge-blue">{{ $header }}</span>@endforeach</div><p class="help">Se conserva el dato original. No se modifica ninguna nota importada.</p></section>
<section class="card"><h2>Muestra de alumnos (primeras filas)</h2><div class="table-wrap"><table><thead><tr><th>ID estudiante</th><th>Apellidos</th><th>Nombre</th><th>Usuario</th><th>Calificación</th></tr></thead><tbody>
@foreach($preview['sample'] as $student)<tr><td>{{ $student['id'] }}</td><td>{{ $student['surnames'] }}</td><td>{{ $student['names'] }}</td><td>{{ $student['username'] }}</td><td>{{ $student['grade'] }}</td></tr>@endforeach
</tbody></table></div></section>
<section class="card"><h2>Confirmar importación</h2><p>Los alumnos se asociarán exclusivamente a este curso, grupo y NRC. Los identificadores repetidos se actualizarán o se reportarán; no se fusionarán alumnos solo por nombre.</p>
<form method="POST" action="{{ route('students.confirm',$modality) }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><button class="btn" type="submit">Confirmar e importar {{ $preview['total'] }} filas</button></form></section>
@endsection
