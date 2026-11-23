<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') · Gestión Docente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;color:#172033;background:#f4f6fa;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0;min-height:100vh}
        .guest-shell{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.04fr) minmax(420px,.96fr);padding:18px;gap:18px}
        .guest-aside{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;min-height:calc(100vh - 36px);padding:clamp(28px,5vw,72px);border-radius:25px;color:#fff;background:radial-gradient(ellipse at 15% 15%,rgba(76,132,193,.42),transparent 42%),radial-gradient(ellipse at 90% 80%,rgba(64,111,165,.28),transparent 38%),linear-gradient(145deg,#10233e 0%,#19385e 52%,#244d78 100%)}
        .guest-aside:before,.guest-aside:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.1);border-radius:50%;pointer-events:none}
        .guest-aside:before{width:520px;height:520px;right:-190px;top:12%}.guest-aside:after{width:360px;height:360px;right:-105px;top:23%}
        .guest-brand{position:relative;z-index:1;display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:-.3px}
        .guest-brand-mark{width:43px;height:43px;display:grid;place-items:center;border-radius:14px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);font-family:Manrope,sans-serif;font-weight:800}
        .guest-brand small{display:block;margin-top:3px;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:#b8c9dc;font-weight:600}
        .guest-message{position:relative;z-index:1;max-width:620px;margin:50px 0}
        .guest-kicker{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(255,255,255,.18);border-radius:30px;background:rgba(255,255,255,.07);font-size:11px;letter-spacing:.7px;text-transform:uppercase;color:#d8e5f3;font-weight:700}
        .guest-message h1{font-family:Manrope,sans-serif;font-size:clamp(34px,4.2vw,62px);line-height:1.08;letter-spacing:-2.5px;margin:23px 0 17px;font-weight:800}
        .guest-message p{max-width:490px;color:#c3d2e2;font-size:16px;line-height:1.8;margin:0}
        .guest-features{position:relative;z-index:1;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;max-width:650px}
        .guest-feature{padding:13px;border:1px solid rgba(255,255,255,.13);border-radius:13px;background:rgba(255,255,255,.055);backdrop-filter:blur(8px)}
        .guest-feature strong{display:block;font-size:12px;margin-bottom:4px}.guest-feature span{font-size:10px;color:#b8c9dc;line-height:1.45}
        .guest-footer{position:relative;z-index:1;color:#a9bdd2;font-size:11px;margin-top:26px}
        .guest-main{display:flex;align-items:center;justify-content:center;padding:clamp(12px,4vw,64px)}
        .guest-card{width:min(100%,440px)}
        .guest-eyebrow{font-size:11px;letter-spacing:1.7px;text-transform:uppercase;color:#66809e;font-weight:800;margin:0 0 12px}
        .guest-card h2{font-family:Manrope,sans-serif;font-size:32px;letter-spacing:-1.2px;line-height:1.2;color:#172b49;margin:0 0 10px}
        .guest-description{font-size:14px;line-height:1.7;color:#778397;margin:0 0 30px}
        .field-group{margin:0 0 19px}
        .field-label{display:flex;align-items:center;justify-content:space-between;margin:0 0 8px;color:#34445a;font-size:12px;font-weight:700}
        .field-wrap{position:relative}
        .field-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8290a3;display:flex;pointer-events:none}
        .field{display:block;width:100%;height:51px;border:1px solid #dce2eb;border-radius:12px;padding:0 44px 0 43px;font:inherit;font-size:14px;color:#1b2c44;background:#fff;transition:border-color .18s,box-shadow .18s,background .18s}
        .field::placeholder{color:#a5afbd}.field:hover{border-color:#b8c6d8}.field:focus{outline:0;border-color:#537eaf;box-shadow:0 0 0 4px rgba(64,112,166,.12);background:#fff}
        .field-toggle{position:absolute;right:9px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#718096;border-radius:8px;padding:7px;cursor:pointer;font-size:11px;font-weight:700}
        .field-toggle:hover{background:#f1f4f8;color:#284d76}
        .remember-row{display:flex;align-items:center;gap:9px;color:#657287;font-size:12px;margin:5px 0 23px}
        .remember-row input{width:16px;height:16px;margin:0;accent-color:#244c78}
        .btn{width:100%;height:51px;display:flex;align-items:center;justify-content:center;gap:10px;border:0;border-radius:12px;background:linear-gradient(110deg,#19395f,#2d5c8d);color:#fff;font:inherit;font-size:13px;font-weight:800;letter-spacing:.2px;cursor:pointer;box-shadow:0 8px 18px rgba(29,67,109,.18);transition:transform .18s,box-shadow .18s,filter .18s}
        .btn:hover{filter:brightness(1.08);box-shadow:0 11px 22px rgba(29,67,109,.24);transform:translateY(-1px)}.btn:active{transform:translateY(0)}
        .btn:focus-visible,.field-toggle:focus-visible{outline:3px solid #9dbde0;outline-offset:3px}
        .guest-security{display:flex;align-items:center;justify-content:center;gap:7px;margin:23px 0 0;color:#98a3b2;font-size:11px}
        .alert{padding:13px 15px;border-radius:12px;margin:0 0 19px;font-size:13px;line-height:1.55}.alert-error{background:#fff3f2;color:#a52e28;border:1px solid #f3d1ce}.alert-success{background:#effaf3;color:#22643b;border:1px solid #ccebd5}
        .error-text{color:#b42318;font-size:11px;margin-top:6px}
        @media(max-width:900px){.guest-shell{grid-template-columns:1fr;padding:12px}.guest-aside{min-height:auto;padding:25px 28px;border-radius:20px}.guest-message{margin:35px 0 25px}.guest-message h1{font-size:38px}.guest-features{display:none}.guest-footer{margin-top:0}.guest-main{padding:42px 20px}}
        @media(max-width:520px){.guest-shell{padding:0;gap:0}.guest-aside{min-height:230px;padding:22px 21px;border-radius:0 0 22px 22px}.guest-brand-mark{width:37px;height:37px}.guest-message{margin:28px 0 8px}.guest-message h1{font-size:31px;letter-spacing:-1.3px;margin:15px 0 8px}.guest-message p{font-size:12px;line-height:1.55}.guest-kicker{font-size:9px;padding:6px 9px}.guest-footer{font-size:10px}.guest-main{padding:34px 22px 40px;align-items:flex-start}.guest-card h2{font-size:28px}.guest-description{margin-bottom:25px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{transition:none!important;scroll-behavior:auto!important}}
    </style>
</head>
<body>
    <main class="guest-shell">
        <section class="guest-aside" aria-label="Gestión Docente">
            <div class="guest-brand"><span class="guest-brand-mark">GD</span><span>Gestión Docente<small>Plataforma académica</small></span></div>
            <div class="guest-message">
                <span class="guest-kicker"><span aria-hidden="true">✦</span> Tu espacio de trabajo</span>
                <h1>Enseñar.<br>Organizar.<br>Avanzar.</h1>
                <p>Todo lo que necesitas para organizar tus cursos, acompañar a tus alumnos y gestionar tu actividad académica desde un solo lugar.</p>
            </div>
            <div>
                <div class="guest-features">
                    <div class="guest-feature"><strong>Planificación</strong><span>Ciclos, grupos y horarios</span></div>
                    <div class="guest-feature"><strong>Seguimiento</strong><span>Alumnos y evaluaciones</span></div>
                    <div class="guest-feature"><strong>Gestión</strong><span>Sesiones y documentos</span></div>
                </div>
                <div class="guest-footer">Acceso seguro · Cuenta personal de docente</div>
            </div>
        </section>
        <section class="guest-main">@yield('content')</section>
    </main>
</body>
</html>