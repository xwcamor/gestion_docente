@extends('layouts.app')
@section('title', 'Evaluaciones y calificaciones')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('groups.show', $modality->assignment->group) }}">{{ $modality->assignment->group->name }}</a><span>›</span><a href="{{ route('modalities.show', $modality) }}">{{ $modality->assignment->course->name }} · {{ $modality->modality }}</a><span>›</span><span class="current">Evaluaciones</span></div>
<div class="page-head">
    <div><p class="eyebrow">Gestión de calificaciones</p><h1>Evaluaciones y notas</h1><p class="subhead">{{ $modality->assignment->course->name }} · {{ $modality->modality }} · NRC {{ $modality->nrc ?: 'sin asignar' }} · {{ $enrollmentCount }} alumnos.</p></div>
    <a class="btn btn-secondary" href="{{ route('modalities.show', $modality) }}"><x-icon name="arrow-left" /> Volver a modalidad</a>
</div>

<section class="card">
    <details class="panel">
        <summary>＋ Crear una evaluación</summary>
        <div class="panel-body">
            <p class="card-description">Cada evaluación guarda sus notas por separado. La nota general importada del campus no se modifica.</p>
            <form method="POST" action="{{ route('evaluations.store', $modality) }}">
                @csrf
                <div class="form-row-3">
                    <div><label for="title">Nombre de la evaluación</label><input class="field" id="title" name="title" required placeholder="Ej. Evaluación 1"></div>
                    <div><label for="evaluation_date">Fecha (opcional)</label><input class="field" id="evaluation_date" type="date" name="evaluation_date"></div>
                    <div><label for="weight">Peso (%) (opcional)</label><input class="field" id="weight" type="number" step=".01" min="0" max="100" name="weight" placeholder="Ej. 25"></div>
                </div>
                <div class="form-row"><div><label for="maximum_score">Nota máxima</label><input class="field" id="maximum_score" type="number" name="maximum_score" min=".01" max="100" step=".01" value="20" required></div></div>
                <div class="form-actions"><span class="help">La nota máxima se utiliza para validar los resultados.</span><button class="btn" type="submit"><x-icon name="check" /> Guardar evaluación</button></div>
            </form>
        </div>
    </details>
</section>

@if($enrollmentCount === 0)
    <section class="card"><div class="empty"><strong>Primero importa la lista de alumnos.</strong><span>Regresa a la modalidad, carga el archivo original del campus y confirma la importación.</span><div class="mt-16"><a class="btn btn-secondary" href="{{ route('modalities.show', $modality) }}#students"><x-icon name="users" /> Ir a importar alumnos</a></div></div></section>
@endif

@forelse($modality->evaluations as $evaluation)
    <section class="card">
        <div class="card-head">
            <div><h2>{{ $evaluation->title }}</h2><p class="card-description">{{ $evaluation->evaluation_date?->format('d/m/Y') ?? 'Sin fecha' }} · Peso {{ $evaluation->weight ?? 'No definido' }}% · Nota máxima {{ $evaluation->maximum_score }}</p></div>
            <span class="badge badge-blue">Evaluación #{{ $evaluation->id }}</span>
        </div>
        @if($enrollmentCount > 0)
            <form method="POST" action="{{ route('evaluations.grades', $evaluation) }}">
                @csrf @method('PUT')
                <input type="hidden" name="page" value="{{ $enrollments->currentPage() }}">
                <div class="filter-bar"><input class="filter-input grade-search" type="search" placeholder="Buscar alumno por nombre o código…" aria-label="Buscar alumnos"><span class="help">{{ $enrollments->count() }} visibles en esta página, de {{ $enrollmentCount }}</span></div>
                <div class="table-wrap"><table><thead><tr><th>ID alumno</th><th>Alumno</th><th>Nota campus</th><th>Nota de evaluación</th><th>Observación</th></tr></thead><tbody>
                    @foreach($enrollments as $enrollment)
                        @php($grade = $evaluation->grades->firstWhere('enrollment_id', $enrollment->id))
                        <tr class="grade-row" data-student="{{ strtolower($enrollment->student->institutional_id.' '.$enrollment->student->surnames.' '.$enrollment->student->given_names) }}">
                            <td>{{ $enrollment->student->institutional_id }}</td>
                            <td><strong>{{ $enrollment->student->surnames }}, {{ $enrollment->student->given_names }}</strong></td>
                            <td>{{ $enrollment->platform_grade ?? '—' }}</td>
                            <td><input class="field" style="min-width:100px;max-width:130px" type="number" min="0" max="{{ $evaluation->maximum_score }}" step=".01" name="scores[{{ $enrollment->id }}]" value="{{ $grade?->score }}" aria-label="Nota de {{ $enrollment->student->given_names }}"></td>
                            <td><input class="field" style="min-width:170px" name="observations[{{ $enrollment->id }}]" value="{{ $grade?->observations }}" placeholder="Observación (opcional)"></td>
                        </tr>
                    @endforeach
                </tbody></table></div>
                <div class="form-actions"><span class="help">Se guardan únicamente las notas ingresadas en esta evaluación.</span><button class="btn" type="submit"><x-icon name="check" /> Guardar calificaciones</button></div>
            </form>
            <div class="empty grade-empty" hidden><strong>No se encontraron alumnos.</strong><span>Revisa el nombre o código que buscaste.</span></div>
        @else
            <div class="empty"><strong>No hay alumnos matriculados en este NRC.</strong><span>Importa la lista del campus antes de registrar notas.</span></div>
        @endif
    </section>
@empty
    <section class="card"><div class="empty"><strong>Aún no has creado evaluaciones.</strong><span>Usa el botón superior para crear la primera evaluación de esta modalidad.</span></div></section>

@if($modality->evaluations->isNotEmpty() && $enrollments->hasPages())
    <section class="card"><x-pagination :paginator="$enrollments" /></section>
@endif
@endforelse
@endsection

@push('scripts')
<script>
(() => {
    document.querySelectorAll('.grade-search').forEach(input => {
        const form = input.closest('form');
        const rows = [...form.querySelectorAll('.grade-row')];
        const empty = form.parentElement.querySelector('.grade-empty');
        input.addEventListener('input', () => {
            const term = input.value.trim().toLocaleLowerCase();
            let visible = 0;
            rows.forEach(row => {
                const show = row.dataset.student.includes(term);
                row.hidden = !show;
                if (show) visible++;
            });
            if (empty) empty.hidden = visible > 0;
        });
    });
})();
</script>
@endpush
