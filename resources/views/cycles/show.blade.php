@extends('layouts.app')
@section('title', $cycle->academic_year.' · Ciclo '.$cycle->cycle_code)
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.index') }}">Ciclos académicos</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span>
</div>
<a class="cycle-back" href="{{ route('cycles.index') }}"><x-icon name="arrow-left" size="15" /> Volver a todos los ciclos</a>

<div class="page-head">
    <div>
        <p class="eyebrow"><x-icon name="calendar-days" size="12" /> Ciclo académico</p>
        <h1>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h1>
        <p class="subhead">Este ciclo contiene los grupos que te asignaron. Abre un grupo para registrar sus cursos, modalidades, horarios y alumnos.</p>
        <div class="cycle-item-meta">
            <span><x-icon name="calendar-range" size="12" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            <span class="badge badge-green">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
        </div>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Agregar grupo</button>
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:17px">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Grupos registrados</span><span class="stat-icon"><x-icon name="users" /></span></div>
        <div class="stat">{{ $cycle->groups->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Cursos asignados</span><span class="stat-icon"><x-icon name="book-open" /></span></div>
        <div class="stat">{{ $cycle->groups->sum(fn($group) => $group->assignments->count()) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Modalidades activas</span><span class="stat-icon"><x-icon name="layers" /></span></div>
        <div class="stat">{{ $cycle->groups->sum(fn($group) => $group->assignments->sum(fn($assignment) => $assignment->modalities->count())) }}</div>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div>
            <h2>Grupos de este ciclo</h2>
            <p class="card-description">Cada grupo representa una sección o aula. Entra a uno para administrar sus cursos.</p>
        </div>
        <span class="badge">{{ $cycle->groups->count() }} {{ $cycle->groups->count() === 1 ? 'grupo' : 'grupos' }}</span>
    </div>
    @forelse($cycle->groups as $group)
        <article class="cycle-item">
            <div class="cycle-item-main">
                <span class="cycle-item-icon"><x-icon name="users" size="18" /></span>
                <div>
                    <h3 class="cycle-item-title">{{ $group->name }}</h3>
                    <div class="cycle-item-meta">
                        <span><x-icon name="clock" size="12" /> Asignado: {{ $group->assigned_at?->format('d/m/Y') ?? 'Fecha no indicada' }}</span>
                        <span>·</span>
                        <span><x-icon name="book-open" size="12" /> {{ $group->assignments->count() }} {{ $group->assignments->count() === 1 ? 'curso' : 'cursos' }}</span>
                        <span>·</span>
                        <span><x-icon name="layers" size="12" /> {{ $group->assignments->sum(fn($a) => $a->modalities->count()) }} modalidades</span>
                    </div>
                </div>
            </div>
            <div class="cycle-item-actions">
                <a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}">Abrir grupo <x-icon name="arrow-right" /></a>
            </div>
        </article>
    @empty
        <div class="empty">
            <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="users" size="20" /></span>
            <strong>Este ciclo todavía no tiene grupos.</strong>
            <span>El siguiente paso es crear el grupo o sección que te asignaron. Después podrás cargar sus cursos.</span>
            <div class="mt-16"><button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Crear primer grupo</button></div>
        </div>
    @endforelse
</section>

<dialog class="ui-dialog sheet-dialog" id="group-create-dialog" aria-labelledby="group-create-title" data-backdrop-close @if(request()->boolean('nuevo_grupo') || $errors->any()) data-auto-open @endif>
    <div class="dialog-head">
        <div>
            <p class="eyebrow">Paso 2 · Grupos</p>
            <h2 id="group-create-title">Agregar grupo al ciclo</h2>
            <p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('groups.store', $cycle) }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any())
                <div class="dialog-error" role="alert">
                    <strong><x-icon name="circle-alert" size="13" /> Revisa estos datos:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="modal-context">
                <x-icon name="info" size="16" />
                <span>Registra el nombre tal como aparece en tu asignación. Luego podrás agregar los cursos que pertenecen a este grupo.</span>
            </div>
            <div>
                <label for="group_name">Nombre del grupo <span class="muted">*</span></label>
                <input class="field" id="group_name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus placeholder="Ej. Grupo 01, A1, 2026-10-A">
                <p class="help">Usa el nombre o código de sección que te entregaron.</p>
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="mt-16">
                <label for="group_assigned_at">Fecha en que te asignaron el grupo <span class="muted">(opcional)</span></label>
                <input class="field" id="group_assigned_at" type="date" name="assigned_at" value="{{ old('assigned_at') }}">
                <p class="help">Es solo una referencia; no modifica las fechas del ciclo.</p>
                @error('assigned_at')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button>
            <button class="btn" type="submit"><x-icon name="check" /> Guardar grupo</button>
        </div>
    </form>
</dialog>
@endsection
