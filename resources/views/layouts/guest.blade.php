<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Acceso') · Gestión Docente</title>
    <style>
        :root{font-family:Inter,"Segoe UI",Arial,sans-serif;color:#172033;background:#f3f6fb}
        *{box-sizing:border-box}body{margin:0;min-height:100vh}
        .guest-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px}
        .guest-card{width:min(100%,440px);background:#fff;border:1px solid #e4e9f1;border-radius:18px;padding:34px;box-shadow:0 18px 55px rgba(20,39,70,.09)}
        .brand-mark{width:48px;height:48px;border-radius:13px;background:#17365d;color:#fff;display:grid;place-items:center;font-weight:800;font-size:20px}
        .muted{color:#667085}.field{display:block;width:100%;border:1px solid #cfd7e3;border-radius:9px;padding:12px 13px;font:inherit;background:#fff}
        .field:focus{outline:3px solid #dceafe;border-color:#4778b8}label{display:block;font-weight:650;font-size:13px;margin:15px 0 7px}
        .btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;background:#17365d;color:#fff;padding:12px 18px;font-weight:700;text-decoration:none;cursor:pointer}
        .btn:hover{background:#214b7d}.btn-block{width:100%;margin-top:22px}
        .alert{padding:12px 14px;border-radius:9px;margin:14px 0;font-size:14px}.alert-error{background:#fff0f0;color:#9b2424;border:1px solid #f4caca}
        .alert-success{background:#effaf2;color:#22643b;border:1px solid #ccebd5}
        .error-text{color:#b42318;font-size:12px;margin-top:5px}
    </style>
</head>
<body>
    <main class="guest-shell">
        @yield('content')
    </main>
</body>
</html>
