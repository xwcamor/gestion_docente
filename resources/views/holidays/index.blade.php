@extends('layouts.app')
@section('title','Feriados y cierres')
@section('content')
<div class="page-head"><div><h1>Feriados y cierres</h1><p class="subhead">Gestiona días que deben bloquear sesiones. Los cierres personales no alteran los calendarios de otros profesores.</p></div></div>
<section class="card"><h2>Agregar feriado o cierre</h2>
<form method="POST" action="{{ route('holidays.store') }}">@csrf
<div class="form-row-3"><div><label>Fecha</label><input class="field" type="date" name="holiday_date" required></div><div><label>Nombre</label><input class="field" name="name" required placeholder="Cierre institucional"></div><div><label>Ámbito</label><select class="field" name="scope" required><option value="NATIONAL">Nacional</option><option value="REGIONAL">Regional</option><option value="INSTITUTIONAL">Institucional</option><option value="SECTOR">Sector</option></select></div></div>
<div class="form-row"><div><label>Bloquea clases</label><select class="field" name="blocks_classes"><option value="1">Sí, no se dicta</option><option value="0">No, solo informativo</option></select></div><div><label>Fuente o norma (URL)</label><input class="field" type="url" name="source_url"></div></div>
<label>Nota / referencia</label><textarea class="field" name="source_note" rows="2"></textarea>
<button class="btn mt-16" type="submit">Guardar día</button></form></section>
<section class="card"><h2>Calendario registrado</h2>
<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Motivo</th><th>Ámbito</th><th>Bloquea clases</th><th>Verificado</th><th>Fuente</th></tr></thead><tbody>
@forelse($holidays as $holiday)<tr><td>{{ $holiday->holiday_date->format('d/m/Y') }}</td><td>{{ $holiday->name }}</td><td>{{ $holiday->scope }}</td><td>{{ $holiday->blocks_classes?'Sí':'No' }}</td><td>{{ $holiday->verified?'Sí':'Pendiente' }}</td><td>@if($holiday->source_url)<a href="{{ $holiday->source_url }}" target="_blank" rel="noopener">Consultar</a>@else—@endif</td></tr>
@empty<tr><td colspan="6">No hay fechas registradas.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-16">{{ $holidays->links() }}</div>
</section>
@endsection
