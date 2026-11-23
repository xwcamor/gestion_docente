<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') · Gestión Docente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{font-family:'DM Sans',-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#202a36;background:#f7f7f5;font-synthesis:none}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh}
        .guest-shell{min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(430px,.82fr);background:#f7f7f5}
        .guest-aside{position:relative;display:flex;flex-direction:column;justify-content:space-between;min-height:100vh;padding:42px clamp(28px,6vw,92px);background:#f1f2ef;border-right:1px solid #e4e6e1;overflow:hidden}
        .guest-aside:before,.guest-aside:after{display:none}
        .guest-brand{display:flex;align-items:center;gap:12px;color:#202a36;font-size:14px;font-weight:700;letter-spacing:-.2px}
        .guest-brand-mark{width:36px;height:36px;display:grid;place-items:center;border:1px solid #cfd5d0;border-radius:9px;background:#fff;color:#40584a;font:800 13px Manrope,sans-serif;letter-spacing:-.6px}
        .guest-brand small{display:block;margin-top:3px;color:#818981;font-size:10px;letter-spacing:.35px;font-weight:500}
        .guest-message{position:relative;z-index:1;max-width:530px;margin:70px 0}
        .guest-kicker{display:block;padding:0;border:0;background:none;color:#647366;font-size:10px;letter-spacing:1.7px;text-transform:uppercase;font-weight:700}
        .guest-message h1{font-family:Manrope,sans-serif;font-size:clamp(38px,4.7vw,66px);line-height:1.04;letter-spacing:-3.3px;margin:22px 0 19px;color:#202a36;font-weight:600}
        .guest-message p{max-width:410px;color:#69736d;font-size:15px;line-height:1.8;margin:0}
        .guest-features{display:flex;gap:28px;max-width:560px;padding-top:21px;border-top:1px solid #dfe3dc}
        .guest-feature{padding:0;border:0;background:none}
        .guest-feature strong{display:block;color:#36433a;font-size:11px;font-weight:700;margin-bottom:5px}
        .guest-feature span{font-size:10px;color:#879087;line-height:1.45}
        .guest-footer{color:#969e96;font-size:10px;margin-top:30px}
        .guest-main{display:flex;align-items:center;justify-content:center;padding:clamp(28px,6vw,86px);background:#fbfbfa}
        .guest-card{width:min(100%,390px)}
        .guest-eyebrow{font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:#78847a;font-weight:700;margin:0 0 15px}
        .guest-card h2{font-family:Manrope,sans-serif;font-size:31px;letter-spacing:-1.25px;line-height:1.2;color:#202a36;margin:0 0 9px;font-weight:600}
        .guest-description{font-size:13px;line-height:1.65;color:#788078;margin:0 0 35px}
        .field-group{margin:0 0 21px}
        .field-label{display:flex;align-items:center;justify-content:space-between;margin:0 0 8px;color:#465248;font-size:11px;font-weight:700}
        .field-wrap{position:relative}
        .field-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#89938b;display:flex;pointer-events:none}
        .field{display:block;width:100%;height:48px;border:1px solid #d9ded8;border-radius:7px;padding:0 43px 0 40px;font:inherit;font-size:13px;color:#202a36;background:#fff;transition:border-color 140ms ease-out,box-shadow 140ms ease-out}
        .field::placeholder{color:#a6ada6}
        .field:hover{border-color:#aeb9af}
        .field:focus{outline:0;border-color:#79917e;box-shadow:0 0 0 3px rgba(87,116,94,.10)}
        .field-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#65736a;border-radius:5px;padding:7px;cursor:pointer;font-size:10px;font-weight:700}
        .field-toggle:hover{background:#f0f2ef;color:#40584a}
        .remember-row{display:flex;align-items:center;gap:9px;color:#69756c;font-size:11px;margin:2px 0 22px}
        .remember-row input{width:15px;height:15px;margin:0;accent-color:#40584a}
        .btn{width:100%;height:48px;display:flex;align-items:center;justify-content:center;gap:10px;border:1px solid #40584a;border-radius:7px;background:#40584a;color:#fff;font:inherit;font-size:12px;font-weight:700;letter-spacing:.1px;cursor:pointer;transition:background 140ms ease-out,border-color 140ms ease-out,transform 100ms ease-out}
        .btn:hover{background:#344a3e;border-color:#344a3e}
        .btn:active{transform:scale(.99)}
        .btn:focus-visible,.field-toggle:focus-visible{outline:3px solid #bdcbbd;outline-offset:3px}
        .guest-security{display:flex;align-items:center;justify-content:center;gap:7px;margin:22px 0 0;color:#9aa39a;font-size:10px}
        .alert{padding:11px 13px;border-radius:7px;margin:0 0 18px;font-size:12px;line-height:1.55}
        .alert-error{background:#fff3f1;color:#a52e28;border:1px solid #f0d3cf}
        .alert-success{background:#eff8f1;color:#22643b;border:1px solid #d2e8d7}
        .error-text{color:#b42318;font-size:10px;margin-top:6px}
        @media(max-width:900px){
            .guest-shell{grid-template-columns:1fr}
            .guest-aside{min-height:auto;padding:22px 26px;border-right:0;border-bottom:1px solid #e4e6e1}
            .guest-message{margin:30px 0 25px}
            .guest-message h1{font-size:38px;letter-spacing:-1.8px}
            .guest-features{display:none}
            .guest-footer{margin-top:0}
            .guest-main{padding:45px 24px}
        }
        @media(max-width:520px){
            body{background:#fbfbfa}
            .guest-shell{min-height:100svh;display:flex;flex-direction:column}
            .guest-aside{height:62px;min-height:0;flex:none;padding:0 19px;background:#fbfbfa;border-bottom:1px solid #e9ece7;justify-content:center}
            .guest-message,.guest-features,.guest-footer{display:none}
            .guest-brand{font-size:13px;gap:9px}
            .guest-brand-mark{width:32px;height:32px;border-radius:8px}
            .guest-brand small{display:none}
            .guest-main{flex:1;align-items:flex-start;padding:clamp(43px,9vh,78px) 23px 30px}
            .guest-card{width:100%;max-width:420px}
            .guest-eyebrow{font-size:9px;margin-bottom:12px}
            .guest-card h2{font-size:28px;letter-spacing:-1px}
            .guest-description{font-size:12px;margin-bottom:28px}
            .field-group{margin-bottom:18px}
            .field{height:50px;font-size:16px}
            .remember-row{font-size:11px;margin:0 0 20px}
            .btn{height:50px;font-size:13px}
            .guest-security{display:none}
        }
        @media(max-height:700px) and (max-width:520px){.guest-main{padding-top:25px}.guest-description{margin-bottom:20px}.field-group{margin-bottom:14px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{transition:none!important;scroll-behavior:auto!important}}
    
        /* Acceso: misma paleta azul petróleo y turquesa del panel */
        :root{color:#203744;background:#f2f6f9}
        .guest-shell{background:#f2f6f9}
        .guest-aside{background:linear-gradient(145deg,#e7f1f7 0%,#eef7f6 100%);border-right-color:#d2e2ea}
        .guest-brand{color:#244a5d}
        .guest-brand-mark{border-color:#c7dce6;background:#fff;color:#28677d}
        .guest-brand small{color:#668493}
        .guest-kicker{color:#327d86}
        .guest-message h1{color:#203e4e}
        .guest-message p{color:#587584}
        .guest-features{border-top-color:#d0e0e8}
        .guest-feature strong{color:#315e70}
        .guest-feature span{color:#668391}
        .guest-footer{color:#78919d}
        .guest-main{background:#f8fbfd}
        .guest-eyebrow{color:#327d86}
        .guest-card h2{color:#203e4e}
        .guest-description{color:#65808d}
        .field-label{color:#3c6273}
        .field-icon{color:#56879a}
        .field{border-color:#cbdde6;color:#254657;background:#fff}
        .field::placeholder{color:#91a6b0}
        .field:hover{border-color:#9cbfcc}
        .field:focus{border-color:#4b91a9;box-shadow:0 0 0 3px rgba(53,131,158,.14)}
        .field-toggle{color:#38758a}
        .field-toggle:hover{background:#eaf4f8;color:#245f75}
        .remember-row{color:#607b88}
        .remember-row input{accent-color:#28677d}
        .btn{border-color:#28677d;background:#28677d}
        .btn:hover{border-color:#20566a;background:#20566a}
        .btn:focus-visible,.field-toggle:focus-visible{outline-color:#9ec7d5}
        .guest-security{color:#78919d}
        .alert-error{background:#fff3f1;color:#a74646;border-color:#efd4d2}
        .alert-success{background:#eaf5ee;color:#28734f;border-color:#cfe7d8}
        @media(max-width:900px){.guest-aside{border-bottom-color:#d2e2ea}}
        @media(max-width:520px){.guest-aside{background:#f3f8fa;border-bottom-color:#dce8ed}.guest-main{background:#f8fbfd}}
</style>
</head>
<body>
    <main class="guest-shell">
        <section class="guest-aside" aria-label="Gestión Docente">
            <div class="guest-brand"><span class="guest-brand-mark">GD</span><span>Gestión Docente<small>Organización académica</small></span></div>
            <div class="guest-message">
                <span class="guest-kicker">PLATAFORMA ACADÉMICA</span>
                <h1>Tu trabajo,<br>en orden.</h1>
                <p>Un espacio para planificar tus cursos, acompañar a tus alumnos y llevar el control de tu actividad académica.</p>
            </div>
            <div>
                <div class="guest-features">
                    <div class="guest-feature"><strong>Planificación</strong><span>Ciclos y horarios</span></div>
                    <div class="guest-feature"><strong>Seguimiento</strong><span>Alumnos y notas</span></div>
                    <div class="guest-feature"><strong>Gestión</strong><span>Sesiones y documentos</span></div>
                </div>
                <div class="guest-footer">Acceso personal para docentes</div>
            </div>
        </section>
        <section class="guest-main">@yield('content')</section>
    </main>
</body>
</html>