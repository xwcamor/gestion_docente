@extends('layouts.app')
@section('title','Evaluaciones y notas')
@section('content')
<div class="page-head"><div><p class="muted small"><a href="{{ route('modalities.show',$modality) }}">{{ $modality->assignment->course->name }}</a> / {{ $modality->modality }}</p><h1>Evaluaciones y calificaciones</h1><p class="subhead">NRC {{ $modality->nrc ?: 'sin asignar' }} · {{ $modality->enrollments->count() }} alumnos</p></div><a class="btn btn-secondary" href="{{ route('modalities.show',$modality) }}">Volver a modalidad</a></div>
<section class="card"><h2>Crear evaluación</h2>
<form method="POST" action="{{ route('evaluations.store',$modality) }}">@csrf
<div class="form-row-3"><div><label>Nombre</label><input class="field" name="title" required placeholder="Evaluación 1"></div><div><label>Fecha</label><input class="field" type="date" name="evaluation_date"></div><div><label>Peso (%)</label><input class="field" type="number" step=".01" min="0" max="100" name="weight"></div></div>
<div class="form-row"><div><label>Nota máxima</label><input class="field" type="number" name="maximum_score" min=".01" max="100" step=".01" value="20" required></div><div style="display:flex;align-items:end"><button class="btn" type="submit">Agregar evaluación</button></div></div>
</form></section>
@forelse($modality->evaluations as $evaluation)
<section class="card"><div class="page-head"><div><h2>{{ $evaluation->title }}</h2><p class="muted small">{{ $evaluation->evaluation_date?->format('d/m/Y') ?? 'Sin fecha' }} · Peso {{ $evaluation->weight ?? 'No definido' }}% · Máximo {{ $evaluation->maximum_score }}</p></div><span class="badge badge-blue">Evaluación #{{ $evaluation->id }}</span></div>
<form method="POST" action="{{ route('evaluations.grades',$evaluation) }}">@csrf @method('PUT')
<div class="table-wrap"><table><thead><tr><th>ID alumno</th><th>Alumno</th><th>Nota campus (importada)</th><th>Nota de evaluación</th><th>Observación</th></tr></thead><tbody>
@foreach($modality->enrollments as $enrollment)
@php($grade = $evaluation->grades->firstWhere('enrollment_id',$enrollment->id))
<tr><td>{{ $enrollment->student->institutional_id }}</td><td>{{ $enrollment->student->surnames }}, {{ $enrollment->student->given_names }}</td><td>{{ $enrollment->platform_grade ?? '—' }}</td><td><input class="field" style="min-width:90px" type="number" min="0" max="{{ $evaluation->maximum_score }}" step=".01" name="scores[{{ $enrollment->id }}]" value="{{ $grade?->score }}"></td><td><input class="field" name="observations[{{ $enrollment->id }}]" value="{{ $grade?->observations }}"></td></tr>
@endforeach
</tbody></table></div>
<button class="btn mt-16" type="submit">Guardar calificaciones</button>
</form></section>
@empty <section class="card"><div class="empty">Todavía no se han creado evaluaciones para esta modalidad.</div></section> @endforelse
@endsection
