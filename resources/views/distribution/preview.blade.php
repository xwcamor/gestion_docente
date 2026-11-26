@extends('layouts.app')
@section('title', 'Revisar carga laboral')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Cargas laborales</a><span>›</span><a href="{{ route('distribution.create') }}">Importar</a><span>›</span><span class="current">Revisar</span></div>
<div class="page-head">
    <div>
        <p class="eyebrow">Paso 1 · Revisar antes de guardar</p>
        <h1>Revisar carga laboral</h1>
        <p class="subhead">Verifica la información detectada. Todavía no se ha guardado ningún dato.</p>
        <p class="load-preview-summary"><strong>{{ $data['academic_year'] }} · Ciclo {{ $data['cycle_code'] }}</strong><span>{{ $data['course_count'] }} cursos · {{ $data['modality_count'] }} modalidades · {{ $data['schedule_count'] }} bloques horarios · {{ \\Carbon\\Carbon::parse($data['start_date'])->format('d/m/Y') }} – {{ \\Carbon\\Carbon::parse($data['end_date'])->format('d/m/Y') }}</span></p>
    </div>
    <a class="btn btn-secondary" href="{{ route('distribution.create') }}"><x-icon name="file-text" /> Cambiar archivo</a>
</div>
@if(count($data['errors']))<div class="alert alert-error"><strong>Filas con errores:</strong><ul>@foreach($data['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(count($data['warnings']))<div class="alert alert-warning"><strong>Datos que requieren revisión:</strong><ul>@foreach($data['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
<section class="card">
    <div class="card-head"><div><h2>Cursos, modalidades y horarios</h2><p class="card-description">Los cursos se leen desde Cursos y los bloques desde Horarios. Cada modalidad conserva su NRC y calendario independiente.</p></div></div>
    @php($weekdayLabels = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'])
    <div class="table-wrap"><table><thead><tr><th>Curso</th><th>Código</th><th>Modalidad</th><th>NRC</th><th>Bloque</th><th>Desde</th><th>Hasta</th><th>Horario semanal</th></tr></thead><tbody>
    @foreach($data['rows'] as $row)
        @foreach($row['modalities'] as $modality)
        <tr>
            <td><strong>{{ $row['name'] }}</strong><div class="help">Fila {{ $row['source_row'] }}</div></td>
            <td class="mono">{{ $row['code'] }}</td>
            <td><span class="badge {{ $modality['type'] === 'TEC' ? 'badge-blue' : 'badge-green' }}">{{ $modality['type'] }}</span></td>
            <td>{{ $modality['nrc'] ?: 'Pendiente' }}</td>
            <td>{{ $modality['block_code'] ?: '—' }}</td>
            <td>{{ $modality['start_date'] ? \\Carbon\\Carbon::parse($modality['start_date'])->format('d/m/Y') : 'Pendiente' }}</td>
            <td>{{ $modality['end_date'] ? \\Carbon\\Carbon::parse($modality['end_date'])->format('d/m/Y') : 'Pendiente' }}</td>
            <td>
                @forelse(($modality['schedules'] ?? []) as $schedule)
                    <div class="preview-schedule-line">
                        <strong>{{ $weekdayLabels[$schedule['day_of_week']] ?? '—' }}</strong>
                        {{ $schedule['start_time'] }}–{{ $schedule['end_time'] }}
                        <span>{{ $schedule['instruction_type'] === 'LAB' ? 'Taller' : 'Teoría' }}</span>
                        @if(($schedule['repeat_interval_weeks'] ?? 1) > 1)<small>Cada {{ $schedule['repeat_interval_weeks'] }} semanas</small>@endif
                        @if($schedule['classroom'])<small>{{ $schedule['classroom'] }}</small>@endif
                    </div>
                @empty
                    <span class="muted">Sin horario</span>
                @endforelse
            </td>
        </tr>
        @endforeach
    @endforeach
    </tbody></table></div>
</section>
<section class="card">
    <h2>Confirmar importación</h2>
    <p class="card-description">El sistema creará o actualizará el ciclo y agrupará estos cursos en un grupo. Si vuelves a importar el mismo archivo al mismo grupo, no duplicará cursos ni modalidades.</p>
    <form method="POST" action="{{ route('distribution.confirm') }}">
        @csrf
        <div class="form-row"><div><label for="group_name">Nombre del grupo</label><input class="field" id="group_name" name="group_name" required maxlength="100" value="{{ old('group_name', 'Grupo principal') }}"><p class="help">Puedes cambiarlo por el nombre de tu sección o aula.</p></div><div><label>Ciclo que se creará o reutilizará</label><input class="field" value="{{ $data['academic_year'] }} · {{ $data['cycle_code'] }}" disabled></div></div>
        @if($data['schedule_count'] > 0)
            <div class="alert alert-info mt-16">Se importarán {{ $data['schedule_count'] }} bloques semanales. Al confirmar, el sistema calculará las horas y generará las sesiones según las fechas de cada modalidad, excluyendo feriados bloqueados.</div>
        @else
            <div class="alert alert-warning mt-16">No se detectaron bloques en la hoja Horarios. El ciclo y los cursos se pueden importar, pero deberás configurar los horarios de cada modalidad después.</div>
        @endif
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('distribution.create') }}"><x-icon name="arrow-left" /> Cancelar</a><button class="btn" type="submit" {{ count($data['rows']) === 0 || count($data['errors']) > 0 ? 'disabled' : '' }}><x-icon name="check" /> Confirmar e importar {{ count($data['rows']) }} cursos</button></div>
    </form>
</section>
@push('styles')
<style>
.load-preview-summary{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin:9px 0 0;color:var(--muted);font-size:11px}
.load-preview-summary strong{color:var(--ink)}
.load-preview-summary span{padding-left:9px;border-left:1px solid var(--line)}
.preview-schedule-line{display:flex;align-items:center;flex-wrap:wrap;gap:4px;font-size:10px;line-height:1.5}
.preview-schedule-line+.preview-schedule-line{margin-top:3px}
.preview-schedule-line strong{color:var(--ink)}
.preview-schedule-line span,.preview-schedule-line small{color:var(--muted)}
.preview-schedule-line small{font-size:9px}
@media(max-width:650px){.load-preview-summary{align-items:flex-start;flex-direction:column;gap:3px}.load-preview-summary span{padding:0;border:0}}
</style>
@endpush
@endsection