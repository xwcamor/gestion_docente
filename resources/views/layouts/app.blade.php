<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') · Gestión Docente</title>
    <style>
        :root{font-family:Inter,"Segoe UI",Arial,sans-serif;color:#172033;background:#f4f6fa;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0;background:#f4f6fa;font-size:14px}
        a{color:#20558a;text-decoration:none}a:hover{text-decoration:underline}
        .topbar{height:64px;background:#17365d;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:10;box-shadow:0 2px 10px #10284422}
        .brand{color:#fff;font-weight:800;letter-spacing:.2px;font-size:17px}.brand small{display:block;color:#c8d5e6;font-size:10px;font-weight:500;letter-spacing:1.2px;text-transform:uppercase;margin-top:2px}
        .top-actions{display:flex;align-items:center;gap:18px}.top-user{color:#e4edf8;font-size:13px}
        .logout{color:#fff;background:#ffffff1c;border:1px solid #ffffff38;border-radius:8px;padding:8px 12px;cursor:pointer}
        .shell{display:grid;grid-template-columns:224px minmax(0,1fr);min-height:calc(100vh - 64px)}
        .sidebar{background:#fff;border-right:1px solid #e4e9f0;padding:22px 14px}
        .nav-label{font-size:10px;font-weight:800;color:#8994a5;text-transform:uppercase;letter-spacing:1.1px;padding:4px 11px 9px}
        .nav-link{display:block;padding:10px 11px;border-radius:8px;color:#35445b;font-weight:600;margin:2px 0}
        .nav-link:hover,.nav-link.active{background:#eaf1f9;color:#17365d;text-decoration:none}
        .main{padding:28px;min-width:0;max-width:1500px;width:100%;margin:0 auto}
        h1{font-size:26px;margin:0 0 7px;color:#172b4d}h2{font-size:18px;margin:0 0 15px;color:#172b4d}h3{font-size:15px;margin:0 0 10px;color:#263b58}
        p{line-height:1.55}.subhead{color:#6b7789;margin:0 0 22px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:19px;margin-bottom:18px;box-shadow:0 2px 8px #25385808}
        .grid{display:grid;gap:16px}.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
        .stat{font-size:26px;font-weight:800;color:#17365d;margin-top:6px}.stat-label{font-size:12px;color:#68768a}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#17365d;color:#fff;border:1px solid #17365d;border-radius:8px;padding:9px 13px;font-weight:700;cursor:pointer;font:inherit;font-size:13px;text-decoration:none}
        .btn:hover{background:#214b7d;color:#fff;text-decoration:none}.btn-secondary{background:#fff;color:#344054;border-color:#cfd7e3}.btn-secondary:hover{background:#f7f9fc;color:#17365d}.btn-danger{background:#fff;color:#b42318;border-color:#f0c4c4}
        .field{width:100%;padding:10px 11px;border:1px solid #cfd7e3;border-radius:8px;background:#fff;font:inherit;color:#172033}
        .field:focus{outline:3px solid #e0ebfa;border-color:#5381b5}label{display:block;font-size:12px;font-weight:700;color:#475467;margin:12px 0 6px}
        .help{font-size:12px;color:#748094;margin-top:5px}.checkline{display:flex;align-items:center;gap:8px;font-weight:600;color:#344054;margin:10px 0}.checkline input{width:16px;height:16px}
        .form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.form-row-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;border-bottom:1px solid #e8edf3;padding:10px 9px;vertical-align:top}th{background:#f7f9fc;color:#526176;font-size:11px;text-transform:uppercase;letter-spacing:.4px}
        .table-wrap{overflow-x:auto}.badge{display:inline-block;padding:4px 8px;border-radius:30px;background:#eef2f7;color:#42526a;font-size:11px;font-weight:750}.badge-blue{background:#eaf2fc;color:#21558b}.badge-green{background:#e9f7ee;color:#287544}.badge-orange{background:#fff3df;color:#946000}.badge-red{background:#fdecec;color:#a72e2e}
        .alert{padding:12px 14px;border-radius:9px;margin:0 0 16px;border:1px solid;font-size:13px}.alert-success{background:#effaf2;color:#21643a;border-color:#ccebd5}.alert-error{background:#fff1f1;color:#9c2525;border-color:#f1cbcb}.alert-info{background:#eff6ff;color:#24558a;border-color:#d7e6f8}
        .muted{color:#778397}.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px}
        .empty{border:1px dashed #cbd5e1;padding:22px;text-align:center;color:#748094;border-radius:10px}
        .mono{font-family:Consolas,monospace;font-size:12px}.small{font-size:12px}.mb-0{margin-bottom:0}.mt-16{margin-top:16px}
        textarea.field{min-height:110px;resize:vertical}pre{white-space:pre-wrap;word-break:break-word;background:#f7f9fc;border:1px solid #e5eaf1;padding:13px;border-radius:8px;font-size:12px;max-height:420px;overflow:auto}
        @media(max-width:900px){.shell{grid-template-columns:1fr}.sidebar{border-right:0;border-bottom:1px solid #e4e9f0;padding:10px 14px;display:flex;gap:5px;overflow:auto}.nav-label{display:none}.nav-link{white-space:nowrap}.main{padding:18px}.grid-3,.grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:600px){.topbar{padding:0 14px}.top-user{display:none}.grid-2,.grid-3,.grid-4,.form-row,.form-row-3{grid-template-columns:1fr}.page-head{display:block}.main{padding:14px}}
    </style>
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">Gestión Docente <small>Control académico y documental</small></a>
    <div class="top-actions">
        <span class="top-user">{{ auth()->user()->name ?? '' }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">Cerrar sesión</button></form>
    </div>
</header>
<div class="shell">
    <aside class="sidebar">
        <div>
            <div class="nav-label">Mi espacio</div>
            <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">Resumen</a>
            <a class="nav-link {{ request()->routeIs('cycles.*') ? 'active' : '' }}" href="{{ route('cycles.index') }}">Ciclos y grupos</a>
            <a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}">Feriados y cierres</a>
            @if(auth()->user()->isAdmin())
                <div class="nav-label" style="margin-top:18px">Administración</div>
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.professors.index') }}">Profesores y cuentas</a>
            @endif
        </div>
    </aside>
    <main class="main">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-error"><strong>Revisa estos datos:</strong><ul style="margin:7px 0 0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
