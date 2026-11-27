@extends('layouts.app')
@section('title', 'Seleccionar institución')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Espacio de trabajo</p>
        <h1>Seleccionar institución</h1>
        <p class="subhead">Elige la institución cuyos ciclos, grupos y datos académicos vas a consultar.</p>
    </div>
</div>

@if($memberships->isEmpty())
    <section class="card">
        <div class="empty">
            <strong>No tienes instituciones disponibles</strong>
            <p>Tu cuenta no tiene una membresía activa. Solicita al administrador que te otorgue acceso.</p>
        </div>
    </section>
@else
    <section class="card">
        <div class="card-head">
            <div>
                <h2>Tus instituciones</h2>
                <p class="card-description">Los datos de cada institución se mantienen separados.</p>
            </div>
            <span class="badge">{{ $memberships->count() }} disponibles</span>
        </div>
        @foreach($memberships as $membership)
            <div class="list-row">
                <div>
                    <div class="list-title">{{ $membership->institution->name }}</div>
                    <div class="list-meta">
                        {{ $membership->institution->legal_name ?: $membership->institution->code }}
                        · {{ $membership->role === 'admin' || $membership->role === 'owner' ? 'Administrador' : 'Docente' }}
                    </div>
                </div>
                <form method="POST" action="{{ route('institution.switch') }}">
                    @csrf
                    <input type="hidden" name="institution_id" value="{{ $membership->institution_id }}">
                    <button class="btn btn-secondary btn-sm" type="submit">Entrar</button>
                </form>
            </div>
        @endforeach
    </section>
@endif
@endsection
