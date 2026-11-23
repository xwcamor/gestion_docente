<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Gestión Docente</title>
    <style>
        :root{
            color-scheme:light;
            --ink:#182230;--muted:#667085;--soft:#98a2b3;--line:#e4e7ec;
            --surface:#fff;--canvas:#f7f8fa;--navy:#17365d;--navy-hover:#214b7d;
            --blue-soft:#eff5fc;--green:#16794a;--green-soft:#ecfdf3;
            --red:#b42318;--red-soft:#fff1f0;--amber:#a15c07;--amber-soft:#fff7e8;
            --radius:14px;--ease:cubic-bezier(.23,1,.32,1);
            font-family:Inter,"Segoe UI",Arial,sans-serif;
        }
        *{box-sizing:border-box}
        body{margin:0;background:var(--canvas);color:var(--ink);font-size:14px;-webkit-font-smoothing:antialiased}
        a{color:#24578c;text-decoration:none}a:hover{text-decoration:underline}
        button,input,select,textarea{font:inherit}
        .topbar{height:66px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 30px;position:sticky;top:0;z-index:20}
        .brand{display:flex;align-items:center;gap:11px;color:var(--ink);font-weight:800;font-size:15px;letter-spacing:-.2px}
        .brand-mark{width:34px;height:34px;display:grid;place-items:center;border-radius:10px;background:var(--navy);color:#fff;font-weight:800;letter-spacing:-.5px}
        .brand small{display:block;color:var(--muted);font-size:10px;font-weight:500;letter-spacing:.5px;margin-top:2px}
        .top-actions{display:flex;align-items:center;gap:16px}.top-user{color:#475467;font-size:13px}
        .logout{border:1px solid var(--line);background:#fff;color:#344054;border-radius:9px;padding:8px 12px;cursor:pointer;transition:background 150ms ease-out,border-color 150ms ease-out}
        .logout:hover{background:#f8fafc;border-color:#cfd4dc}.logout:active{transform:scale(.98)}
        .app-shell{display:grid;grid-template-columns:236px minmax(0,1fr);min-height:calc(100vh - 66px)}
        .sidebar{background:#fff;border-right:1px solid var(--line);padding:22px 14px}
        .nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:800;color:#98a2b3;padding:7px 11px 9px}
        .nav-link{display:flex;align-items:center;gap:10px;margin:3px 0;padding:10px 11px;border-radius:9px;color:#475467;font-weight:600;transition:background 140ms ease-out,color 140ms ease-out}
        .nav-link:hover,.nav-link.active{background:#f0f4f8;color:var(--navy);text-decoration:none}
        .nav-dot{width:7px;height:7px;border-radius:50%;background:#c1cad5;flex:none}.nav-link.active .nav-dot{background:#2f6da9}
        .sidebar-note{margin:22px 6px 0;padding:13px 12px;background:#f8fafc;border:1px solid #edf0f3;border-radius:11px;color:#667085;font-size:11px;line-height:1.55}
        .main{width:100%;max-width:1500px;margin:0 auto;padding:30px;min-width:0}
        .page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:23px}
        h1{font-size:27px;line-height:1.2;letter-spacing:-.8px;margin:0 0 7px;color:#172b4d;font-weight:750}
        h2{font-size:17px;letter-spacing:-.25px;margin:0 0 7px;color:#1d2939;font-weight:720}
        h3{font-size:14px;margin:0 0 8px;color:#344054;font-weight:700}
        p{line-height:1.55}.subhead{margin:0;color:#667085;max-width:760px;font-size:14px}
        .eyebrow{font-size:11px;color:#667085;font-weight:700;text-transform:uppercase;letter-spacing:.75px;margin:0 0 8px}
        .breadcrumbs{display:flex;align-items:center;gap:8px;color:#98a2b3;font-size:12px;margin:0 0 10px;flex-wrap:wrap}
        .breadcrumbs a{color:#667085}.breadcrumbs .current{color:#344054;font-weight:650}
        .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:21px;margin-bottom:16px;box-shadow:0 1px 2px rgba(16,24,40,.025)}
        .card-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:15px}
        .card-description{margin:3px 0 0;color:var(--muted);font-size:12px;line-height:1.5}
        .grid{display:grid;gap:16px}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
        .stat-card{background:#fff;border:1px solid var(--line);border-radius:13px;padding:17px 18px}.stat-label{font-size:12px;color:var(--muted)}.stat{font-size:27px;font-weight:780;color:var(--navy);margin-top:7px;letter-spacing:-.7px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px;padding:9px 14px;border:1px solid var(--navy);border-radius:9px;background:var(--navy);color:#fff;font-size:13px;font-weight:680;line-height:1.2;text-decoration:none;cursor:pointer;white-space:nowrap;transition:background 140ms ease-out,border-color 140ms ease-out,transform 120ms ease-out,box-shadow 140ms ease-out}
        .btn:hover{background:var(--navy-hover);border-color:var(--navy-hover);color:#fff;text-decoration:none;box-shadow:0 2px 5px rgba(23,54,93,.13)}
        .btn:active{transform:scale(.98)}
        .btn:focus-visible,.nav-link:focus-visible,.filter-input:focus-visible{outline:3px solid #c7ddf5;outline-offset:2px}
        .btn-secondary{background:#fff;color:#344054;border-color:#d0d5dd}.btn-secondary:hover{background:#f8fafc;color:#182230;border-color:#bfc7d2}
        .btn-ghost{background:transparent;color:#475467;border-color:transparent}.btn-ghost:hover{background:#f2f4f7;color:#182230;border-color:#f2f4f7;box-shadow:none}
        .btn-danger{background:#fff;color:var(--red);border-color:#f0c5c2}.btn-danger:hover{background:var(--red-soft);color:var(--red);border-color:#e7aaa5}
        .btn-sm{min-height:32px;padding:7px 10px;font-size:12px}.btn-block{width:100%}
        .field{width:100%;min-height:41px;padding:10px 12px;border:1px solid #d0d5dd;border-radius:9px;background:#fff;color:#182230;font-size:13px;transition:border-color 140ms ease-out,box-shadow 140ms ease-out}
        .field:focus{outline:none;border-color:#7ca5d2;box-shadow:0 0 0 3px #eaf2fb}
        .field::placeholder{color:#a0a8b4}
        label{display:block;color:#475467;font-size:12px;font-weight:680;margin:13px 0 6px}
        .field-label{display:block;color:#475467;font-size:12px;font-weight:680;margin:13px 0 6px}
        .help{font-size:11px;color:#8490a0;margin:6px 0 0;line-height:1.5}
        .checkline{display:flex;align-items:center;gap:8px;margin:8px 0;color:#344054;font-size:12px;font-weight:600}.checkline input{width:16px;height:16px;accent-color:var(--navy)}
        .form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.form-row-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        .form-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:22px;padding-top:17px;border-top:1px solid #edf0f2}
        .actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.mt-16{margin-top:16px}.mb-0{margin-bottom:0}.small{font-size:12px}.muted{color:var(--muted)}
        .alert{padding:12px 14px;border-radius:10px;margin:0 0 16px;border:1px solid;font-size:13px;line-height:1.5}
        .alert-success{background:#effaf3;color:#17663b;border-color:#cdebd7}.alert-error{background:#fff2f1;color:#9c2921;border-color:#f3cbc7}.alert-info{background:#f0f6fd;color:#24558b;border-color:#d7e6f5}.alert-warning{background:#fff8e9;color:#85550c;border-color:#f2dfb7}
        .badge{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:30px;background:#f2f4f7;color:#475467;font-size:10px;font-weight:750;white-space:nowrap}
        .badge-blue{background:#eaf2fb;color:#24578c}.badge-green{background:#eaf8ef;color:#1c7144}.badge-orange{background:#fff4df;color:#8a5a0c}.badge-red{background:#fff0ef;color:#a72e2e}
        .empty{border:1px dashed #d0d5dd;background:#fcfcfd;padding:25px;text-align:center;color:#667085;border-radius:11px}.empty strong{display:block;color:#344054;margin-bottom:5px}
        .filter-bar{display:flex;align-items:center;gap:9px;flex-wrap:wrap;padding:13px;background:#fafbfc;border:1px solid #edf0f2;border-radius:11px;margin-bottom:15px}
        .filter-input{min-height:38px;padding:8px 11px;border:1px solid #d0d5dd;border-radius:8px;background:#fff;color:#344054;font-size:12px;min-width:180px}
        .list-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 0;border-bottom:1px solid #eef0f2}.list-row:last-child{border-bottom:0}
        .list-title{font-size:14px;font-weight:700;color:#25354b}.list-meta{font-size:12px;color:#8490a0;margin-top:4px}
        .course-card{border:1px solid var(--line);border-radius:12px;padding:16px;background:#fff;transition:border-color 140ms ease-out,box-shadow 140ms ease-out}
        .course-card:hover{border-color:#cbd5e1;box-shadow:0 3px 10px rgba(16,24,40,.045)}
        .course-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
        .stepper{display:flex;align-items:center;gap:8px;margin:0 0 20px}
        .step{display:flex;align-items:center;gap:8px;color:#98a2b3;font-size:12px;font-weight:650}
        .step-number{width:27px;height:27px;border-radius:50%;display:grid;place-items:center;border:1px solid #d0d5dd;background:#fff;color:#667085;font-size:11px}
        .step.active{color:#17365d}.step.active .step-number{background:#17365d;border-color:#17365d;color:#fff}
        .step.done{color:#16794a}.step.done .step-number{background:#ecfdf3;border-color:#b7e4ca;color:#16794a}
        .step-line{width:54px;height:1px;background:#e4e7ec}
        .choice-card{border:1px solid var(--line);border-radius:12px;padding:17px;background:#fff}
        .choice-card.selected{border-color:#8badcf;box-shadow:0 0 0 3px #eef5fc}
        .choice-toggle{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:730;color:#263b58;margin:0}
        .choice-toggle input{width:17px;height:17px;accent-color:var(--navy)}
        .weekday-list{display:flex;flex-wrap:wrap;gap:7px;margin-top:8px}
        .weekday{display:inline-flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid #e1e5ea;border-radius:8px;background:#fff;font-size:11px;color:#475467}
        .weekday:has(input:checked){border-color:#9bb7d5;background:#f0f6fc;color:#214e7e}
        .weekday input{accent-color:var(--navy)}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:12px}
        th,td{text-align:left;border-bottom:1px solid #eef0f2;padding:11px 10px;vertical-align:middle}
        th{background:#f8fafc;color:#667085;font-size:10px;text-transform:uppercase;letter-spacing:.55px;font-weight:750}
        tbody tr:hover{background:#fcfcfd}
        textarea.field{min-height:100px;resize:vertical}
        .mono{font-family:Consolas,monospace;font-size:12px}
        details.panel{border:1px solid var(--line);border-radius:11px;background:#fff;overflow:hidden}
        details.panel>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;font-size:13px;font-weight:700;color:#344054}
        details.panel>summary::-webkit-details-marker{display:none}
        details.panel>summary:after{content:"+";font-size:19px;font-weight:400;color:#667085}
        details.panel[open]>summary:after{content:"−"}
        details.panel[open]>summary{border-bottom:1px solid #eef0f2}
        .panel-body{padding:18px}
        .quick-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 16px}
        .quick-nav a{font-size:11px;font-weight:650;padding:7px 10px;border:1px solid #e4e7ec;border-radius:20px;color:#475467;background:#fff}
        .quick-nav a:hover{border-color:#c7d5e5;background:#f6f9fc;text-decoration:none}
        @media(max-width:1000px){.app-shell{grid-template-columns:1fr}.sidebar{display:flex;align-items:center;gap:4px;overflow-x:auto;padding:8px 14px;border-right:0;border-bottom:1px solid var(--line)}.nav-label,.sidebar-note{display:none}.nav-link{white-space:nowrap;margin:0}.main{padding:22px}.grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:680px){.topbar{padding:0 14px}.top-user{display:none}.main{padding:15px}.page-head{display:block}.page-head>.btn{margin-top:13px}.grid-2,.grid-3,.grid-4,.form-row,.form-row-3{grid-template-columns:1fr}.form-actions{align-items:stretch;flex-direction:column-reverse}.form-actions .actions,.form-actions .btn{width:100%}.filter-input{min-width:100%;width:100%}.stepper{gap:5px}.step-line{width:15px}.step{font-size:10px;gap:5px}.card{padding:16px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important;animation:none!important}}
    </style>
    @stack('styles')
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">
        <span class="brand-mark">GD</span>
        <span>Gestión Docente<small>Organización académica</small></span>
    </a>
    <div class="top-actions">
        <span class="top-user">{{ auth()->user()->name ?? '' }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">Cerrar sesión</button></form>
    </div>
</header>
<div class="app-shell">
    <aside class="sidebar">
        <div>
            <div class="nav-label">Espacio de trabajo</div>
            <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-dot"></span>Resumen</a>
            <a class="nav-link {{ request()->routeIs('cycles.*','groups.*','assignments.*','modalities.*','evaluations.*','curricula.*','students.*','documents.*','distribution.*') ? 'active' : '' }}" href="{{ route('cycles.index') }}"><span class="nav-dot"></span>Ciclos y cursos</a>
            <a class="nav-link {{ request()->routeIs('distribution.*') ? 'active' : '' }}" href="{{ route('distribution.create') }}"><span class="nav-dot"></span>Importar carga Excel</a>
            <a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}"><span class="nav-dot"></span>Feriados y cierres</a>
            @if(auth()->user()->isAdmin())
                <div class="nav-label" style="margin-top:19px">Administración</div>
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.professors.index') }}"><span class="nav-dot"></span>Profesores y cuentas</a>
            @endif
            <div class="sidebar-note"><strong style="display:block;color:#475467;margin-bottom:4px">Tu flujo de trabajo</strong>1. Ciclo académico<br>2. Grupo asignado<br>3. Curso y modalidades<br>4. Alumnos, calendario y documentos</div>
        </div>
    </aside>
    <main class="main">
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error" role="alert">{{ session('error') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-error" role="alert"><strong>Hay datos que necesitan revisión.</strong><ul style="margin:7px 0 0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>