@extends('layouts.app')
@section('title', 'Revisar padrón de alumnos')
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span>
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</a><span>›</span>
    <a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><span>›</span>
    <span class="current">Revisar alumnos</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Padrón del grupo · Vista previa</p>
        <h1>Revisar alumnos</h1>
        <p class="subhead">{{ $group->name }} · {{ $group->cycle->academic_year }} / {{ $group->cycle->cycle_code }}</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}#group-roster"><x-icon name="arrow-left" /> Cancelar</a>
</div>

<div class="grid grid-3">
    <div class="stat-card"><div class="stat-label">Filas detectadas</div><div class="stat">{{ $preview['total'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Formato</div><strong>{{ $preview['format'] }}</strong></div>
    <div class="stat-card"><div class="stat-label">Alumnos actuales del grupo</div><div class="stat">{{ $group->students()->count() }}</div></div>
</div>

<section class="card">
    <div class="card-head">
        <div><h2>Columnas reconocidas</h2><p class="card-description">Se identifican por encabezados; el padrón se vincula al grupo, no a un curso concreto.</p></div>
    </div>
    <div class="actions">
        @foreach($preview['headers'] as $header)
            <span class="badge badge-blue">{{ $header }}</span>
        @endforeach
    </div>
</section>

<section class="card">
    <div class="card-head"><div><h2>Muestra del archivo</h2><p class="card-description">Se muestran las primeras filas para comprobar que el archivo correcto fue seleccionado.</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID estudiante</th><th>Apellidos</th><th>Nombres</th><th>Usuario</th></tr></thead>
            <tbody>
            @foreach($preview['sample'] as $student)
                <tr>
                    <td class="mono">{{ $student['id'] ?: '—' }}</td>
                    <td>{{ $student['surnames'] }}</td>
                    <td>{{ $student['names'] }}</td>
                    <td>{{ $student['username'] ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($preview['has_grade'])
        <div class="alert alert-info mt-16">El archivo contiene una columna de calificaciones. En esta carga grupal solo se actualizará el padrón de alumnos; las notas deben importarse desde el curso correspondiente para no mezclar calificaciones entre cursos.</div>
    @endif
</section>

<section class="card">
    <h2>Confirmar padrón</h2>
    <p class="card-description">Al confirmar, los alumnos quedarán vinculados una sola vez al grupo. El sistema habilitará sus matrículas en los cursos actuales; no sobrescribirá notas ni datos de actividad de cada curso.</p>
    <form method="POST" action="{{ route('groups.students.confirm', $group) }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="actions">
            <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}#group-roster">Volver</a>
            <button class="btn" type="submit"><x-icon name="check" /> Confirmar {{ $preview['total'] }} filas</button>
        </div>
    </form>
</section>
@endsection
