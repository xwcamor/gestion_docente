@extends('layouts.app')
@section('title','Detalle del ciclo')
@section('content')
<div class="page-head"><div><p class="muted small"><a href="{{ route('cycles.index') }}">Ciclos</a> / Detalle</p><h1>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h1><p class="subhead">{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</p></div><a class="btn btn-secondary" href="{{ route('cycles.index') }}">Volver</a></div>
<section class="card"><h2>Agregar grupo</h2>
<form method="POST" action="{{ route('groups.store',$cycle) }}">@csrf
<div class="form-row"><div><label>Nombre o código del grupo</label><input class="field" name="name" placeholder="Grupo 01" required></div><div><label>Fecha de asignación</label><input class="field" type="date" name="assigned_at"></div></div>
<label>Observaciones</label><textarea class="field" name="notes" rows="2"></textarea>
<button class="btn mt-16" type="submit">Crear grupo</button>
</form></section>
<section class="card"><h2>Grupos del ciclo ({{ $cycle->groups->count() }})</h2>
@if($cycle->groups->isEmpty())<div class="empty">Aún no hay grupos. Puedes agregar uno ahora o cuando recibas una nueva asignación.</div>@else
<div class="table-wrap"><table><thead><tr><th>Grupo</th><th>Fecha asignación</th><th>Cursos</th><th>Modalidades</th><th></th></tr></thead><tbody>
@foreach($cycle->groups as $group)<tr><td><strong>{{ $group->name }}</strong></td><td>{{ $group->assigned_at?->format('d/m/Y') ?? '—' }}</td><td>{{ $group->assignments->count() }}</td><td>{{ $group->assignments->sum(fn($a)=>$a->modalities->count()) }}</td><td><a href="{{ route('groups.show',$group) }}">Administrar grupo</a></td></tr>@endforeach
</tbody></table></div>@endif
</section>
@endsection
