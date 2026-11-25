@extends('layouts.app')
@section('title', 'Inicio')
@section('content')
@php
    $greeting = now()->hour < 12 ? 'Buenos días' : (now()->hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $todayLabel = now()->format('d/m/Y');
@endphp

<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="eyebrow"><x-icon name="layout-dashboard" size="13" /> Panel de control</p>
        <h1>{{ $greeting }}, {{ $user->name }}</h1>
        <p>Este es el resumen de tu actividad académica y de lo que tienes por atender.</p>
    </div>
    <div class="dashboard-date">
        <span>HOY</span>
        <strong>{{ $todayLabel }}</strong>
    </div>
</section>

<section class="dashboard-metrics" aria-label="Resumen académico">
    <a class="dashboard-metric metric-blue" href="{{ route('cycles.index') }}">
        <span class="dashboard-metric-icon"><x-icon name="calendar-days" size="19" /></span>
        <span class="dashboard-metric-copy"><small>Ciclos</small><strong>{{ $cyclesCount }}</strong><em>{{ $activeCyclesCount }} activos</em></span>
    </a>
    <a class="dashboard-metric metric-violet" href="{{ route('cycles.index') }}">
        <span class="dashboard-metric-icon"><x-icon name="users" size="19" /></span>
        <span class="dashboard-metric-copy"><small>Grupos</small><strong>{{ $groupsCount }}</strong><em>En tus ciclos</em></span>
    </a>
    <a class="dashboard-metric metric-teal" href="{{ route('cycles.index') }}">
        <span class="dashboard-metric-icon"><x-icon name="book-open" size="19" /></span>
        <span class="dashboard-metric-copy"><small>Cursos asignados</small><strong>{{ $coursesCount }}</strong><em>{{ $activeModalitiesCount }} componentes activos</em></span>
    </a>
    <a class="dashboard-metric metric-amber" href="{{ route('cycles.index') }}">
        <span class="dashboard-metric-icon"><x-icon name="clipboard-check" size="19" /></span>
        <span class="dashboard-metric-copy"><small>Por configurar</small><strong>{{ $pendingModalitiesCount }}</strong><em>Componentes con datos pendientes</em></span>
    </a>
    <a class="dashboard-metric metric-green" href="{{ route('cycles.index') }}">
        <span class="dashboard-metric-icon"><x-icon name="graduation-cap" size="19" /></span>
        <span class="dashboard-metric-copy"><small>Alumnos</small><strong>{{ $studentsCount }}</strong><em>En tus grupos</em></span>
    </a>
</section>

<section class="dashboard-quick-actions" aria-label="Accesos rápidos">
    <a class="dashboard-quick-action quick-primary" href="{{ route('cycles.index') }}">
        <span><x-icon name="git-branch" size="18" /></span>
        <span><strong>Cargas laborales</strong><small>Organiza ciclos, grupos y cursos</small></span>
        <x-icon name="arrow-right" size="16" />
    </a>
    <a class="dashboard-quick-action" href="{{ route('distribution.create') }}">
        <span><x-icon name="file-up" size="18" /></span>
        <span><strong>Importar carga</strong><small>Sube el archivo maestro</small></span>
        <x-icon name="arrow-right" size="16" />
    </a>
    <a class="dashboard-quick-action" href="{{ route('holidays.index') }}">
        <span><x-icon name="calendar-range" size="18" /></span>
        <span><strong>Feriados</strong><small>Consulta fechas y cierres</small></span>
        <x-icon name="arrow-right" size="16" />
    </a>
    @if($user->isAdmin())
        <a class="dashboard-quick-action" href="{{ route('admin.professors.index') }}">
            <span><x-icon name="users-round" size="18" /></span>
            <span><strong>Gestionar cuentas</strong><small>Accesos y perfiles docentes</small></span>
            <x-icon name="arrow-right" size="16" />
        </a>
    @endif
</section>

<div class="dashboard-content-grid">
    <section class="card dashboard-panel dashboard-agenda">
        <div class="dashboard-panel-heading">
            <div>
                <p class="eyebrow">Agenda</p>
                <h2>Próximas clases</h2>
                <p>Sesiones programadas en tus modalidades activas.</p>
            </div>
            <span class="dashboard-panel-icon"><x-icon name="clock" size="18" /></span>
        </div>
        @forelse($upcomingSessions as $session)
            @php
                $modality = $session->modality;
                $assignment = $modality?->assignment;
                $group = $assignment?->group;
            @endphp
            <a class="dashboard-agenda-row" href="{{ $modality ? route('modalities.show', $modality) : route('cycles.index') }}">
                <span class="agenda-date">
                    <strong>{{ $session->session_date?->format('d') }}</strong>
                    <small>{{ $session->session_date?->translatedFormat('M') }}</small>
                </span>
                <span class="agenda-copy">
                    <strong>{{ $assignment?->course?->name ?? 'Curso' }}</strong>
                    <small>{{ $group?->name ?? 'Grupo' }} · {{ $modality?->modality === 'TALL' ? 'Taller' : 'Teoría' }}</small>
                    @if($session->topic)<em>{{ $session->topic }}</em>@endif
                </span>
                <span class="agenda-arrow"><x-icon name="chevron-right" size="16" /></span>
            </a>
        @empty
            <div class="dashboard-empty">
                <span><x-icon name="calendar-check" size="22" /></span>
                <strong>No hay clases próximas</strong>
                <p>Cuando configures los horarios y calendarios, tus siguientes sesiones aparecerán aquí.</p>
                <a href="{{ route('cycles.index') }}">Revisar cargas laborales <x-icon name="arrow-right" size="13" /></a>
            </div>
        @endforelse
    </section>

    <section class="card dashboard-panel dashboard-holidays">
        <div class="dashboard-panel-heading">
            <div>
                <p class="eyebrow">Calendario</p>
                <h2>Próximos feriados</h2>
                <p>Fechas generales y cierres de tu calendario.</p>
            </div>
            <a class="dashboard-panel-link" href="{{ route('holidays.index') }}">Ver todos <x-icon name="arrow-up-right" size="14" /></a>
        </div>
        @forelse($upcomingHolidays as $holiday)
            <div class="dashboard-holiday-row">
                <span class="holiday-date-box">
                    <strong>{{ $holiday->holiday_date->format('d') }}</strong>
                    <small>{{ $holiday->holiday_date->translatedFormat('M') }}</small>
                </span>
                <span class="dashboard-holiday-copy">
                    <strong>{{ $holiday->name }}</strong>
                    <small>{{ $holiday->professor_id ? 'Personal' : 'General' }} · {{ $holiday->blocks_classes ? 'Suspende clases' : 'Informativo' }}</small>
                </span>
                <span class="dashboard-holiday-dot {{ $holiday->blocks_classes ? 'is-blocking' : 'is-info' }}"></span>
            </div>
        @empty
            <div class="dashboard-empty dashboard-empty-small">
                <span><x-icon name="calendar-days" size="22" /></span>
                <strong>No hay feriados próximos registrados</strong>
                <p>Consulta el calendario para revisar otras fechas.</p>
                <a href="{{ route('holidays.index') }}">Abrir feriados <x-icon name="arrow-right" size="13" /></a>
            </div>
        @endforelse
    </section>

    <section class="card dashboard-panel dashboard-groups">
        <div class="dashboard-panel-heading">
            <div>
                <p class="eyebrow">Actividad reciente</p>
                <h2>Grupos para continuar</h2>
                <p>Accede directamente al trabajo de cada grupo.</p>
            </div>
            <a class="dashboard-panel-link" href="{{ route('cycles.index') }}">Ver ciclos <x-icon name="arrow-up-right" size="14" /></a>
        </div>
        @forelse($groups as $group)
            <a class="dashboard-group-row" href="{{ route('cycles.tree', $group->cycle) }}">
                <span class="dashboard-group-icon"><x-icon name="folder-open" size="17" /></span>
                <span class="dashboard-group-copy">
                    <strong>{{ $group->name }}</strong>
                    <small>{{ $group->cycle->academic_year }} · Ciclo {{ $group->cycle->cycle_code }}</small>
                </span>
                <span class="dashboard-group-meta">{{ $group->assignments_count }} cursos · {{ $group->students_count }} alumnos</span>
                <x-icon name="chevron-right" size="15" />
            </a>
        @empty
            <div class="dashboard-empty">
                <span><x-icon name="plus" size="22" /></span>
                <strong>Aún no hay grupos registrados</strong>
                <p>Crea un ciclo o importa tu primera carga laboral para comenzar.</p>
                <a href="{{ route('cycles.index') }}">Ir a cargas laborales <x-icon name="arrow-right" size="13" /></a>
            </div>
        @endforelse
    </section>

    <section class="card dashboard-panel dashboard-cycles">
        <div class="dashboard-panel-heading">
            <div>
                <p class="eyebrow">Periodos</p>
                <h2>Ciclos recientes</h2>
                <p>Resumen de tus últimos ciclos académicos.</p>
            </div>
            <a class="dashboard-panel-link" href="{{ route('cycles.index') }}">Ver todos <x-icon name="arrow-up-right" size="14" /></a>
        </div>
        @forelse($cycles as $cycle)
            <a class="dashboard-cycle-row" href="{{ route('cycles.tree', $cycle) }}">
                <span class="dashboard-cycle-year">{{ $cycle->academic_year }}</span>
                <span class="dashboard-cycle-copy">
                    <strong>Ciclo {{ $cycle->cycle_code }}</strong>
                    <small>{{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</small>
                </span>
                <span class="dashboard-cycle-groups">{{ $cycle->groups_count }} {{ $cycle->groups_count === 1 ? 'grupo' : 'grupos' }}</span>
                <x-icon name="chevron-right" size="15" />
            </a>
        @empty
            <div class="dashboard-empty">
                <span><x-icon name="calendar-days" size="22" /></span>
                <strong>No hay ciclos todavía</strong>
                <p>Registra un ciclo para organizar tus grupos y cursos.</p>
                <a href="{{ route('cycles.index', ['nuevo' => 1]) }}">Crear ciclo <x-icon name="arrow-right" size="13" /></a>
            </div>
        @endforelse
    </section>
</div>

@if($adminStats)
    <section class="dashboard-admin-strip">
        <span class="dashboard-admin-icon"><x-icon name="shield-check" size="18" /></span>
        <span><strong>Administración de cuentas</strong><small>{{ $adminStats['accounts'] }} cuentas · {{ $adminStats['active'] }} activas · {{ $adminStats['inactive'] }} inactivas</small></span>
        <a href="{{ route('admin.professors.index') }}">Gestionar <x-icon name="arrow-right" size="14" /></a>
    </section>
@endif
@endsection

@push('styles')
<style>
.dashboard-hero{
    display:flex;align-items:center;justify-content:space-between;gap:20px;
    margin:0 0 14px;padding:22px 24px;border:1px solid #d7e6ed;border-radius:12px;
    background:linear-gradient(112deg,#edf6fa 0%,#f8fbfc 62%,#eef7f4 100%);
}
.dashboard-hero-copy{min-width:0}
.dashboard-hero .eyebrow{display:flex;align-items:center;gap:6px;margin:0 0 8px;color:#4d8398}
.dashboard-hero h1{margin:0;color:#203e50;font-size:clamp(25px,3vw,35px);line-height:1.15;letter-spacing:-.035em}
.dashboard-hero-copy>p:last-child{margin:7px 0 0;color:#637d8a;font-size:12px;line-height:1.5}
.dashboard-date{display:grid;gap:3px;flex:none;min-width:110px;padding:11px 13px;border:1px solid #d8e6eb;border-radius:9px;background:rgba(255,255,255,.72);text-align:right}
.dashboard-date span{color:#71909d;font-size:8px;font-weight:800;letter-spacing:.1em}
.dashboard-date strong{color:#315f73;font-size:15px}
.dashboard-metrics{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;margin-bottom:12px}
.dashboard-metric{
    display:flex;align-items:flex-start;gap:10px;min-width:0;padding:13px 12px;
    border:1px solid var(--line);border-radius:10px;background:var(--surface);
    color:var(--ink);text-decoration:none;transition:border-color .15s ease,transform .15s ease;
}
.dashboard-metric:hover{transform:translateY(-2px);text-decoration:none}
.dashboard-metric-icon{display:grid;place-items:center;flex:none;width:35px;height:35px;border-radius:9px}
.dashboard-metric-copy{display:grid;gap:2px;min-width:0}
.dashboard-metric-copy small{color:var(--muted);font-size:9px;font-weight:700}
.dashboard-metric-copy strong{color:var(--ink);font-size:23px;line-height:1.1;font-weight:800;font-variant-numeric:tabular-nums}
.dashboard-metric-copy em{color:var(--muted);font-size:8px;font-style:normal;line-height:1.35;overflow-wrap:anywhere}
.metric-blue{border-top:3px solid #5588aa}.metric-blue .dashboard-metric-icon{background:#e9f2f8;color:#3b7698}
.metric-violet{border-top:3px solid #8b78b4}.metric-violet .dashboard-metric-icon{background:#f1edf8;color:#7862a2}
.metric-teal{border-top:3px solid #429d91}.metric-teal .dashboard-metric-icon{background:#e8f5f2;color:#34877d}
.metric-amber{border-top:3px solid #c49b4c}.metric-amber .dashboard-metric-icon{background:#faf3e4;color:#9a742c}
.metric-green{border-top:3px solid #639b72}.metric-green .dashboard-metric-icon{background:#edf6ef;color:#4f865e}
.dashboard-quick-actions{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:13px}
.dashboard-quick-action{
    display:grid;grid-template-columns:34px minmax(0,1fr) 16px;align-items:center;gap:9px;min-width:0;
    padding:11px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink);text-decoration:none;
    transition:border-color .14s ease,background .14s ease;
}
.dashboard-quick-action:hover{border-color:var(--accent);background:var(--surface-soft);text-decoration:none}
.dashboard-quick-action>span:first-child{display:grid;place-items:center;width:34px;height:34px;border-radius:8px;background:var(--surface-soft);color:var(--accent)}
.dashboard-quick-action>span:nth-child(2){display:grid;gap:2px;min-width:0}
.dashboard-quick-action strong{color:var(--ink);font-size:10px;line-height:1.3}
.dashboard-quick-action small{color:var(--muted);font-size:8px;line-height:1.4}
.dashboard-quick-action>.icon{color:var(--muted)}
.dashboard-quick-action.quick-primary{border-color:#bcd6e2;background:#f2f8fb}
.dashboard-content-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.dashboard-panel{min-width:0;margin:0;padding:15px 16px}
.dashboard-panel-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:9px}
.dashboard-panel-heading>div{min-width:0}
.dashboard-panel-heading .eyebrow{margin:0 0 4px;color:var(--accent);font-size:8px}
.dashboard-panel-heading h2{margin:0;color:var(--ink);font-size:16px;line-height:1.25}
.dashboard-panel-heading p:not(.eyebrow){margin:4px 0 0;color:var(--muted);font-size:9px;line-height:1.4}
.dashboard-panel-icon{display:grid;place-items:center;flex:none;width:31px;height:31px;border-radius:8px;background:var(--surface-soft);color:var(--accent)}
.dashboard-panel-link{display:inline-flex;align-items:center;gap:4px;flex:none;color:var(--accent);font-size:9px;font-weight:700;text-decoration:none}
.dashboard-agenda-row,.dashboard-group-row,.dashboard-cycle-row{
    display:flex;align-items:center;gap:10px;min-width:0;padding:9px 0;border-top:1px solid var(--line);color:var(--ink);text-decoration:none;
}
.dashboard-agenda-row:hover,.dashboard-group-row:hover,.dashboard-cycle-row:hover{background:var(--surface-soft);text-decoration:none}
.agenda-date,.holiday-date-box{
    display:grid;align-content:center;justify-items:center;flex:none;width:42px;min-height:43px;border:1px solid #dce8ed;border-radius:8px;background:#f1f7fa;color:#376d84
}
.agenda-date strong,.holiday-date-box strong{font-size:17px;line-height:1.1}
.agenda-date small,.holiday-date-box small{font-size:8px;font-weight:800;text-transform:uppercase}
.agenda-copy,.dashboard-holiday-copy,.dashboard-group-copy,.dashboard-cycle-copy{display:grid;gap:3px;min-width:0;flex:1}
.agenda-copy strong,.dashboard-holiday-copy strong,.dashboard-group-copy strong,.dashboard-cycle-copy strong{color:var(--ink);font-size:10px;line-height:1.35;overflow-wrap:anywhere}
.agenda-copy small,.dashboard-holiday-copy small,.dashboard-group-copy small,.dashboard-cycle-copy small{color:var(--muted);font-size:8px;line-height:1.35;overflow-wrap:anywhere}
.agenda-copy em{color:var(--muted);font-size:8px;font-style:normal;line-height:1.35;overflow-wrap:anywhere}
.agenda-arrow{display:grid;place-items:center;flex:none;color:var(--muted)}
.dashboard-holiday-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--line)}
.holiday-date-box{width:40px;min-height:40px;background:#fbf6e9;border-color:#eee2c3;color:#8d6b29}
.dashboard-holiday-dot{width:7px;height:7px;flex:none;border-radius:50%;background:#b88d3f}
.dashboard-holiday-dot.is-info{background:#4f91a6}
.dashboard-group-icon{display:grid;place-items:center;flex:none;width:32px;height:32px;border-radius:8px;background:var(--accent-soft);color:var(--accent)}
.dashboard-group-meta,.dashboard-cycle-groups{flex:none;color:var(--muted);font-size:8px;text-align:right}
.dashboard-cycle-year{display:grid;place-items:center;flex:none;min-width:47px;height:31px;padding:0 5px;border-radius:7px;background:var(--info-bg);color:var(--info);font-size:11px;font-weight:800}
.dashboard-admin-strip{display:flex;align-items:center;gap:10px;margin-top:12px;padding:11px 13px;border:1px solid #d9e5ec;border-radius:9px;background:#f4f8fa}
.dashboard-admin-icon{display:grid;place-items:center;width:32px;height:32px;flex:none;border-radius:8px;background:#e5f0f6;color:#3d758d}
.dashboard-admin-strip>span:nth-child(2){display:grid;gap:2px;min-width:0;flex:1}
.dashboard-admin-strip strong{color:#31596b;font-size:10px}
.dashboard-admin-strip small{color:#698493;font-size:8px}
.dashboard-admin-strip a{display:inline-flex;align-items:center;gap:5px;flex:none;color:#34758c;font-size:9px;font-weight:750}
.dashboard-empty{display:grid;justify-items:center;gap:5px;min-height:155px;padding:20px 12px;border-top:1px solid var(--line);text-align:center}
.dashboard-empty>span{display:grid;place-items:center;width:34px;height:34px;margin-bottom:3px;border-radius:9px;background:var(--surface-soft);color:var(--muted)}
.dashboard-empty strong{color:var(--ink);font-size:10px}
.dashboard-empty p{max-width:42ch;margin:0;color:var(--muted);font-size:9px;line-height:1.45}
.dashboard-empty a{display:inline-flex;align-items:center;gap:4px;margin-top:4px;color:var(--accent);font-size:9px;font-weight:750}
.dashboard-empty-small{min-height:120px}
@media(max-width:1100px){.dashboard-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.dashboard-quick-actions{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){
    .dashboard-hero{align-items:flex-start;padding:16px;gap:10px}
    .dashboard-hero h1{font-size:26px}
    .dashboard-date{min-width:86px;padding:8px}
    .dashboard-date strong{font-size:12px}
    .dashboard-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
    .dashboard-metric{gap:8px;padding:10px}
    .dashboard-metric-icon{width:31px;height:31px}
    .dashboard-metric-copy strong{font-size:20px}
    .dashboard-quick-actions{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
    .dashboard-quick-action{grid-template-columns:30px minmax(0,1fr) 13px;padding:9px;gap:7px}
    .dashboard-quick-action>span:first-child{width:30px;height:30px}
    .dashboard-content-grid{grid-template-columns:minmax(0,1fr);gap:9px}
    .dashboard-panel{padding:13px}
}
@media(max-width:420px){
    .dashboard-hero{display:grid;grid-template-columns:minmax(0,1fr) auto;padding:14px}
    .dashboard-hero h1{font-size:22px;overflow-wrap:anywhere}
    .dashboard-hero-copy>p:last-child{font-size:10px}
    .dashboard-date{align-self:start;min-width:78px}
    .dashboard-metrics{gap:6px}
    .dashboard-metric{padding:9px 8px}
    .dashboard-metric-icon{width:28px;height:28px}
    .dashboard-metric-icon .icon{width:16px;height:16px}
    .dashboard-metric-copy small{font-size:8px}
    .dashboard-metric-copy strong{font-size:18px}
    .dashboard-metric-copy em{font-size:7px}
    .dashboard-quick-actions{grid-template-columns:minmax(0,1fr)}
    .dashboard-quick-action{grid-template-columns:32px minmax(0,1fr) 15px}
    .dashboard-group-meta,.dashboard-cycle-groups{max-width:94px;font-size:7px;overflow-wrap:anywhere}
    .dashboard-admin-strip{align-items:flex-start;flex-wrap:wrap}
    .dashboard-admin-strip a{margin-left:42px}
}
</style>
@endpush
