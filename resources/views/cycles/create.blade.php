@extends('layouts.app')
@section('title', 'Crear ciclo')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><span class="current">Nuevo ciclo</span></div>
<div class="page-head">
    <div>
        <p class="eyebrow">Programación docente</p>
        <h1>Crear ciclo académico</h1>
        <p class="subhead">Registra el año y el código del ciclo. Después podrás agregar sus grupos.</p>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div>
            <h2>Datos del ciclo</h2>
            <p class="card-description">Solo necesitas el año académico y el código de tu programación.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('cycles.store') }}" data-loading-form="1">
        @csrf
        <div class="form-row">
            <div>
                <label for="academic_year">Año académico</label>
                <input class="field" id="academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', date('Y')) }}" required>
                @error('academic_year')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cycle_code">Código del ciclo</label>
                <input class="field" id="cycle_code" data-uppercase name="cycle_code" value="{{ old('cycle_code', '10') }}" maxlength="20" required placeholder="Ej. 10 o 20">
                @error('cycle_code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('cycles.index') }}">Cancelar</a>
            <button class="btn" type="submit">Guardar ciclo</button>
        </div>
    </form>
</section>
@endsection
