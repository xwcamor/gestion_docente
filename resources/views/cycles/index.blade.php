@extends('layouts.app')
@section('title','Ciclos')
@section('content')
<div class="page-head"><div><h1>Ciclos académicos</h1><p class="subhead">Cada ciclo puede contener varios grupos y cada grupo puede tener muchos cursos.</p></div></div>

<section class="card">
    <h2>Crear ciclo</h2>
    <form method="POST" action="{{ route('cycles.store') }}" id="cycle-form">
        @csrf
        <div class="form-row-3">
            <div>
                <label for="academic_year">Año académico</label>
                <input class="field" id="academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', date('Y')) }}" required>
            </div>
            <div>
                <label for="cycle_code">Código del ciclo</label>
                <input class="field" id="cycle_code" name="cycle_code" value="{{ old('cycle_code', '10') }}" required placeholder="10 o 20">
            </div>
            <div>
                <label for="start_date">Fecha de inicio</label>
                <input class="field" id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="end_date">Fecha de fin</label>
                <input class="field" id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" min="{{ old('start_date') }}" required>
                <p class="help">La fecha de fin debe ser igual o posterior a la fecha de inicio.</p>
                @error('end_date')
                    <p style="color:#b42318;font-size:12px;margin:6px 0 0">{{ $message }}</p>
                @enderror
            </div>
            <div style="display:flex;align-items:end">
                <button class="btn" type="submit">Crear ciclo</button>
            </div>
        </div>
    </form>
</section>

<section class="card">
    <h2>Historial de ciclos</h2>
    @if($cycles->isEmpty())
        <div class="empty">No hay ciclos registrados.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Año</th><th>Ciclo</th><th>Periodo</th><th>Grupos</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($cycles as $cycle)
                        <tr>
                            <td>{{ $cycle->academic_year }}</td>
                            <td>{{ $cycle->cycle_code }}</td>
                            <td>{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</td>
                            <td>{{ $cycle->groups_count }}</td>
                            <td><span class="badge">{{ $cycle->status }}</span></td>
                            <td><a href="{{ route('cycles.show', $cycle) }}">Abrir</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<script>
(() => {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    if (!startDate || !endDate) return;

    const syncEndDateMinimum = () => {
        endDate.min = startDate.value || '';

        if (startDate.value && endDate.value && endDate.value < startDate.value) {
            endDate.setCustomValidity('La fecha de fin debe ser igual o posterior a la fecha de inicio.');
        } else {
            endDate.setCustomValidity('');
        }
    };

    startDate.addEventListener('change', syncEndDateMinimum);
    endDate.addEventListener('change', syncEndDateMinimum);
    syncEndDateMinimum();
})();
</script>
@endsection
