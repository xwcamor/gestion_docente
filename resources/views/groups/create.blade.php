@extends('layouts.app')
@section('title', 'Crear grupo')
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('cycles.index') }}">Ciclos</a>
    <span>›</span>
    <a href="{{ route('cycles.show', $cycle) }}">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</a>
    <span>›</span>
    <span class="current">Nuevo grupo</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Ciclo {{ $cycle->academic_year }} · {{ $cycle->cycle_code }}</p>
        <h1>Crear grupo</h1>
        <p class="subhead">Registra el nombre del grupo. Su horario se configura después, desde el detalle del grupo.</p>
    </div>
</div>

<section class="card group-create-card">
    <form method="POST" action="{{ route('groups.store', $cycle) }}">
        @csrf
        <div class="group-name-field">
            <label for="name">Nombre del grupo</label>
            <input class="field" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus placeholder="Ej. Grupo 01">
            @error('name')<p class="help field-error">{{ $message }}</p>@enderror
            <p class="help">La disponibilidad y la carga horaria se configuran después, desde el detalle del grupo.</p>
        </div>

        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}">Cancelar</a>
            <button class="btn" type="submit">Guardar grupo</button>
        </div>
    </form>
</section>
@endsection

@push('styles')
<style>
.group-create-card{max-width:620px}
.group-name-field{max-width:480px}
.field-error{color:#914d45}
@media(max-width:720px){.group-create-card{padding:12px}.group-create-card .form-row{grid-template-columns:1fr}}
</style>
@endpush