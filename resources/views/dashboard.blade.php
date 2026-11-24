@extends('layouts.app')
@section('title', 'Carga laboral')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Programación académica</p>
        <h1>Gestiona tu carga laboral</h1>
        <p class="subhead">Importa el archivo maestro y completa cada grupo hasta preparar los Excel y registrar las charlas.</p>
    </div>
    <div class="actions dashboard-actions">
        <a class="btn btn-secondary" href="{{ route('cycles.index') }}"><x-icon name="folder-open" /> Ver cargas laborales</a>
        <a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar carga laboral</a>
    </div>
</div>

<section class="card workflow-card" aria-labelledby="workflow-title">
    <div class="card-head">
        <div>
            <h2 id="workflow-title">Flujo de trabajo</h2>
            <p class="card-description">Este es el orden para completar cada grupo que te asignan.</p>
        </div>
    </div>
    <ol class="workflow-steps">
        <li>
            <span class="workflow-step-number">1</span>
            <div><strong>Importar carga laboral</strong><p>Recibe el archivo maestro con cursos, códigos, NRC y fechas.</p></div>
        </li>
        <li>
            <span class="workflow-step-number">2</span>
            <div><strong>Revisar cursos y horarios</strong><p>Completa los bloques TEC/TALL con días, horas y ambientes.</p></div>
        </li>
        <li>
            <span class="workflow-step-number">3</span>
            <div><strong>Subir la malla curricular</strong><p>Adjunta el PDF de cada curso para obtener sus temas semanales.</p></div>
        </li>
        <li>
            <span class="workflow-step-number">4</span>
            <div><strong>Preparar los Excel con IA</strong><p>Organiza Plan de sesión y Control de avance para teoría y taller.</p></div>
        </li>
        <li>
            <span class="workflow-step-number">5</span>
            <div><strong>Registrar charlas de 5 minutos</strong><p>Completa la charla correspondiente a cada fecha del grupo.</p></div>
        </li>
    </ol>
    <div class="workflow-start">
        <span>¿Tienes un nuevo archivo asignado?</span>
        <a class="btn btn-sm" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar carga laboral</a>
    </div>
</section>

<section class="card assigned-groups-card">
    <div class="card-head">
        <div>
            <h2>Grupos para continuar</h2>
            <p class="card-description">Abre un grupo para revisar sus cursos, horarios, mallas y charlas.</p>
        </div>
        <a href="{{ route('cycles.index') }}"><x-icon name="arrow-right" /> Ver todos</a>
    </div>
    @forelse($groups as $group)
        <div class="list-row">
            <div>
                <div class="list-title">{{ $group->name }}</div>
                <div class="list-meta">{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }} · {{ $group->assignments_count }} {{ $group->assignments_count === 1 ? 'curso' : 'cursos' }}</div>
            </div>
            <a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}"><x-icon name="arrow-right" /> Continuar</a>
        </div>
    @empty
        <div class="empty">
            <strong>Aún no tienes cargas laborales registradas.</strong>
            <span>Importa el archivo maestro para crear el ciclo, el grupo y sus cursos.</span>
            <div class="mt-16"><a class="btn" href="{{ route('distribution.create') }}"><x-icon name="file-up" /> Importar carga laboral</a></div>
        </div>
    @endforelse
</section>
@endsection

@push('styles')
<style>
.workflow-card{margin-bottom:14px}
.workflow-steps{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0;margin:0;padding:0;list-style:none}
.workflow-steps li{position:relative;display:flex;align-items:flex-start;gap:9px;min-width:0;padding:5px 12px 8px 0}
.workflow-steps li+li{padding-left:12px;border-left:1px solid var(--line)}
.workflow-step-number{display:grid;place-items:center;flex:0 0 25px;width:25px;height:25px;border:1px solid var(--line-strong);border-radius:50%;background:var(--surface-soft);color:var(--accent);font-size:11px;font-weight:750}
.workflow-steps li strong{display:block;color:var(--ink);font-size:11px;line-height:1.4}
.workflow-steps li p{margin:4px 0 0;color:var(--muted);font-size:10px;line-height:1.5}
.workflow-start{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:15px;padding-top:13px;border-top:1px solid var(--line);color:var(--muted);font-size:11px}
.assigned-groups-card{margin-bottom:0}
@media(max-width:1050px){.workflow-steps{grid-template-columns:repeat(3,minmax(0,1fr));row-gap:12px}.workflow-steps li:nth-child(4){padding-left:0;border-left:0}}
@media(max-width:720px){
    .page-head{align-items:stretch;flex-direction:column}
    .dashboard-actions{width:100%}
    .dashboard-actions .btn{flex:1}
    .workflow-steps{grid-template-columns:repeat(2,minmax(0,1fr));row-gap:10px}
    .workflow-steps li:nth-child(3),.workflow-steps li:nth-child(5){padding-left:0;border-left:0}
    .workflow-start{align-items:stretch;flex-direction:column}
    .workflow-start .btn{width:100%}
}
@media(max-width:440px){
    .workflow-steps{grid-template-columns:1fr}
    .workflow-steps li,.workflow-steps li+li,.workflow-steps li:nth-child(n){padding:8px 0;border-left:0}
    .dashboard-actions{align-items:stretch;flex-direction:column}
}
</style>
@endpush
