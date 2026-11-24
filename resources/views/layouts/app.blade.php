<!doctype html>
<html lang="es" style="--accent: {{ $appBranding['primary_color'] ?? '#28677d' }}; --accent-hover: {{ $appBranding['primary_hover'] ?? '#20566a' }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>try{if(localStorage.getItem('gd-theme')==='dark')document.documentElement.dataset.theme='dark';}catch(e){}</script>
    <title>@yield('title', 'Panel') · {{ $appBranding['name'] ?? 'Gestión Docente' }}</title>
    <style>
        :root{
            color-scheme:light;
            --ink:#252b27;
            --ink-soft:#465049;
            --muted:#747d76;
            --faint:#9aa29b;
            --line:#e2e6e0;
            --line-strong:#d3dad2;
            --canvas:#f5f6f3;
            --surface:#fff;
            --surface-soft:#fafbf9;
            --accent:#40584a;
            --accent-hover:#344a3e;
            --accent-soft:#edf2ed;
            --success:#37694b;
            --success-bg:#eef5ef;
            --warning:#89622e;
            --warning-bg:#f8f3e9;
            --danger:#994b43;
            --danger-bg:#fbf0ee;
            --info:#4f6872;
            --info-bg:#eff4f5;
            --radius:9px;
            --ease-out:cubic-bezier(.23,1,.32,1);
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            font-synthesis:none;
            text-rendering:optimizeLegibility;
        }
        *{box-sizing:border-box}
        .icon{display:inline-block;flex:none;width:1em;height:1em;vertical-align:-.18em;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
        .icon-sprite{position:absolute;width:0;height:0;overflow:hidden;pointer-events:none}
        .btn .icon{width:15px;height:15px}
        .nav-link .icon{width:16px;height:16px;color:#869187}
        .nav-link.active .icon{color:#40584a}
        .logout{display:inline-flex;align-items:center;gap:7px}
        .main{animation:page-enter .22s var(--ease-out) both}
        @keyframes page-enter{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
        .btn.is-loading{pointer-events:none;opacity:.78}
        .btn.is-loading:after{content:"";width:12px;height:12px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:btn-spin .65s linear infinite}
        .form-submitting button[type="submit"],.form-submitting button:not([type]),.form-submitting input[type="submit"]{pointer-events:none;opacity:.72;cursor:wait}
        @keyframes btn-spin{to{transform:rotate(360deg)}}
        .cycle-workflow{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
        .workflow-step{display:flex;align-items:flex-start;gap:10px;min-width:0;padding:13px;border:1px solid #e7ebe5;border-radius:8px;background:#fff}
        .workflow-icon{width:32px;height:32px;flex:none;display:grid;place-items:center;border-radius:8px;background:#f0f4ef;color:#49634f}
        .workflow-step strong{display:block;color:#3e4a40;font-size:11px;font-weight:700}
        .workflow-step small{display:block;margin-top:3px;color:#818b82;font-size:10px;line-height:1.45}
        .cycle-item{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 0;border-bottom:1px solid #edf0eb;animation:item-enter .24s var(--ease-out) both}
        .cycle-item:last-child{border-bottom:0}
        .cycle-item:nth-child(2){animation-delay:.025s}.cycle-item:nth-child(3){animation-delay:.05s}.cycle-item:nth-child(4){animation-delay:.075s}
        @keyframes item-enter{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
        .cycle-item-main{display:flex;align-items:flex-start;gap:11px;min-width:0}
        .cycle-item-icon{width:36px;height:36px;flex:none;display:grid;place-items:center;border:1px solid #e3e9e2;border-radius:9px;background:#f7f9f6;color:#526a57}
        .cycle-item-title{margin:0;color:#344238;font-size:13px;font-weight:720;line-height:1.4}
        .cycle-item-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:5px;color:#7d887e;font-size:10px;line-height:1.5}
        .cycle-item-actions{display:flex;align-items:center;gap:7px;flex:none}
        .modal-context{display:flex;align-items:flex-start;gap:9px;margin:0 0 14px;padding:10px 11px;border:1px solid #e0e8df;border-radius:7px;background:#f5f8f4;color:#546456;font-size:11px;line-height:1.5}
        .modal-context .icon{margin-top:1px}
        .field-error{margin:5px 0 0;color:#914d45;font-size:10px;line-height:1.45}
        .stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .stat-icon{width:29px;height:29px;display:grid;place-items:center;border-radius:7px;background:#f1f5f0;color:#536b58}
        .stat-icon .icon{width:15px;height:15px}
        .cycle-back{display:inline-flex;align-items:center;gap:7px;margin-bottom:12px;color:#647267;font-size:11px;font-weight:650}
        .cycle-back:hover{color:#344a3e;text-decoration:none}
        @media(max-width:900px){.cycle-workflow{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:720px){.cycle-item{align-items:stretch;flex-direction:column;gap:10px}.cycle-item-actions{width:100%}.cycle-item-actions .btn{flex:1}.cycle-workflow{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:390px){.cycle-workflow{grid-template-columns:1fr}.workflow-step{padding:10px}}
        html{min-width:320px;scroll-behavior:smooth}
        body{margin:0;min-height:100vh;background:var(--canvas);color:var(--ink);font-size:13px;-webkit-font-smoothing:antialiased;overflow-wrap:break-word}
        button,input,select,textarea{font:inherit}
        a{color:var(--accent);text-decoration:none}
        a:hover{text-decoration:underline}
        button{color:inherit}
        :focus-visible{outline:2px solid #78927e;outline-offset:2px}
        .topbar{height:60px;position:sticky;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 clamp(14px,2.6vw,34px);background:rgba(255,255,255,.97);border-bottom:1px solid var(--line)}
        .brand{display:flex;align-items:center;gap:10px;min-width:0;color:var(--ink);font-size:13px;font-weight:720;letter-spacing:-.2px}
        .brand:hover{text-decoration:none}
        .brand-mark{width:32px;height:32px;flex:none;display:grid;place-items:center;border:1px solid #d6ded6;border-radius:8px;background:#f6f8f5;color:#40584a;font-size:11px;font-weight:800;letter-spacing:-.4px}
        .brand small{display:block;margin-top:2px;color:var(--muted);font-size:10px;font-weight:450;letter-spacing:0}
        .top-actions{display:flex;align-items:center;gap:12px;min-width:0}
        .top-user{max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted);font-size:12px}
        .logout{min-height:34px;padding:7px 10px;border:1px solid var(--line-strong);border-radius:7px;background:#fff;color:var(--ink-soft);font-size:11px;font-weight:650;cursor:pointer;transition:background .14s ease,border-color .14s ease}
        .logout:hover{background:var(--surface-soft);border-color:#bfc9bf}
        .logout:active{transform:scale(.98)}
        .app-shell{display:grid;grid-template-columns:224px minmax(0,1fr);min-height:calc(100vh - 60px)}
        .sidebar{position:sticky;top:60px;align-self:start;height:calc(100vh - 60px);overflow-y:auto;padding:20px 12px;background:#fafbf9;border-right:1px solid var(--line)}
        .nav-label{padding:9px 10px 7px;color:#8b958d;font-size:9px;font-weight:750;text-transform:uppercase;letter-spacing:1.05px}
        .nav-link{display:flex;align-items:center;gap:10px;min-width:0;margin:2px 0;padding:10px;border:1px solid transparent;border-radius:7px;color:#59645c;font-size:12px;font-weight:560;transition:background .14s ease,color .14s ease,border-color .14s ease}
        .nav-link:hover,.nav-link.active{background:#eef2ed;color:#344c3d;text-decoration:none}
        .nav-link.active{border-color:#e0e8df;font-weight:690}
        .nav-dot{width:6px;height:6px;flex:none;border-radius:50%;background:#c3cbc2}
        .nav-link.active .nav-dot{background:#55745e}
        .sidebar-note{margin:20px 5px 0;padding:12px;border:1px solid #e8ece6;border-radius:7px;background:#f5f7f3;color:#707a71;font-size:10px;line-height:1.6}
        .sidebar-note strong{display:flex;align-items:center;gap:6px;margin-bottom:7px;color:#48564b}.sidebar-note>div{display:flex;align-items:center;gap:7px;margin-top:5px}
        .main{width:100%;max-width:1520px;min-width:0;margin:0 auto;padding:clamp(18px,2.7vw,36px)}
        .page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin:0 0 22px}
        .page-head>div:first-child{min-width:0}
        h1{margin:0 0 6px;color:#252d27;font-size:clamp(23px,2.1vw,29px);font-weight:690;line-height:1.16;letter-spacing:-.9px;overflow-wrap:anywhere}
        h2{margin:0 0 5px;color:#303a33;font-size:16px;font-weight:680;line-height:1.3;letter-spacing:-.25px}
        h3{margin:0 0 7px;color:#3d4940;font-size:13px;font-weight:680;line-height:1.35}
        p{line-height:1.55}
        .eyebrow{margin:0 0 7px;color:#7c887e;font-size:9px;font-weight:750;letter-spacing:1.25px;text-transform:uppercase}
        .subhead{max-width:720px;margin:0;color:#737d75;font-size:12px;line-height:1.6}
        .breadcrumbs{display:flex;align-items:center;gap:7px;flex-wrap:wrap;min-width:0;margin:0 0 11px;color:#a0a8a0;font-size:10px}
        .breadcrumbs a{color:#718075}
        .breadcrumbs .current{color:#3f4b42;font-weight:650}
        .card{min-width:0;margin:0 0 13px;padding:18px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface)}
        .card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;min-width:0;margin-bottom:14px}
        .card-head>div{min-width:0}
        .card-description{margin:3px 0 0;color:var(--muted);font-size:11px;line-height:1.5}
        .grid{display:grid;gap:13px;min-width:0}
        .grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
        .grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
        .grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
        .stat-card{min-width:0;padding:15px;border:1px solid var(--line);border-radius:8px;background:#fff}
        .stat-label{color:#7a847c;font-size:10px;line-height:1.4}
        .stat{margin-top:6px;color:#3c5344;font-size:25px;font-weight:680;line-height:1.15;letter-spacing:-.6px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:0;min-height:36px;padding:8px 12px;border:1px solid var(--accent);border-radius:7px;background:var(--accent);color:#fff;font-size:11px;font-weight:670;line-height:1.3;text-align:center;text-decoration:none;white-space:normal;overflow-wrap:anywhere;cursor:pointer;transition:background .14s ease,border-color .14s ease,transform .12s ease}
        .btn:hover{border-color:var(--accent-hover);background:var(--accent-hover);color:#fff;text-decoration:none}
        .btn:active{transform:scale(.985)}
        .btn-secondary{border-color:var(--line-strong);background:#fff;color:#465249}
        .btn-secondary:hover{border-color:#b9c5b9;background:#f8faf7;color:#303d33}
        .btn-ghost{border-color:transparent;background:transparent;color:#69756b}
        .btn-ghost:hover{border-color:#e8ece7;background:#f4f6f3;color:#3e4e42}
        .btn-danger{border-color:#e4c9c5;background:#fff;color:var(--danger)}
        .btn-danger:hover{border-color:#d8b4ae;background:var(--danger-bg);color:#87443e}
        .btn-sm{min-height:31px;padding:6px 9px;font-size:10px}
        .btn-block{width:100%}
        .actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap;min-width:0}
        .actions>*{min-width:0}
        .field{display:block;width:100%;min-width:0;min-height:40px;padding:9px 11px;border:1px solid #d8ded7;border-radius:7px;background:#fff;color:#28322b;font-size:12px;line-height:1.4;transition:border-color .14s ease,box-shadow .14s ease}
        .field:hover{border-color:#bfc9bf}
        .field:focus{outline:none;border-color:#79917e;box-shadow:0 0 0 3px rgba(87,116,94,.10)}
        .field::placeholder{color:#a0a9a0}
        textarea.field{min-height:94px;resize:vertical}
        label,.field-label{display:block;margin:12px 0 6px;color:#4c584e;font-size:11px;font-weight:650;line-height:1.4}
        .help{margin:5px 0 0;color:#899289;font-size:10px;line-height:1.5}
        .checkline{display:flex;align-items:center;gap:8px;margin:9px 0;color:#4d5a50;font-size:11px;font-weight:550}
        .checkline input{width:15px;height:15px;accent-color:var(--accent)}
        .form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
        .form-row-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}
        .form-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:19px;padding-top:14px;border-top:1px solid #edf0eb}
        .form-actions>span{flex:1 1 180px;min-width:0}
        .mt-16{margin-top:16px}
        .mb-0{margin-bottom:0}
        .small{font-size:11px}
        .muted{color:var(--muted)}
        .alert{display:flex;align-items:flex-start;gap:9px;min-width:0;margin:0 0 13px;padding:11px 12px;border:1px solid;border-radius:7px;font-size:11px;line-height:1.55}
        .alert-success{border-color:#d9e6d9;background:var(--success-bg);color:#42634a}
        .alert-error{border-color:#ecd8d4;background:var(--danger-bg);color:#914d45}
        .alert-info{border-color:#dce6e6;background:var(--info-bg);color:#52686e}
        .alert-warning{border-color:#e9dfca;background:var(--warning-bg);color:#806338}
        .badge{display:inline-flex;align-items:center;justify-content:center;gap:4px;max-width:100%;padding:4px 7px;border:1px solid #e7ebe5;border-radius:5px;background:#f7f8f6;color:#68736a;font-size:9px;font-weight:680;line-height:1.3;white-space:normal;text-align:center;overflow-wrap:anywhere}
        .badge-blue{border-color:#dce5df;background:#f0f4f0;color:#506957}
        .badge-green{border-color:#d9e6d9;background:#eff5ef;color:#45674d}
        .badge-orange{border-color:#e9dfca;background:#f8f3e9;color:#806338}
        .badge-red{border-color:#ecd8d4;background:#fbf0ee;color:#914d45}
        .empty{padding:24px 15px;border:1px dashed #d8ded7;border-radius:8px;background:#fafbf9;color:#7b857c;font-size:11px;line-height:1.55;text-align:center}
        .empty strong{display:block;margin-bottom:4px;color:#475349;font-size:12px}
        .filter-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0;margin-bottom:14px;padding:10px;border:1px solid #e7ebe5;border-radius:8px;background:#fafbf9}
        .filter-input{min-width:150px;min-height:35px;padding:7px 10px;border:1px solid #dce1da;border-radius:7px;background:#fff;color:#49554b;font-size:11px}
        .filter-bar .filter-input[type=search]{flex:1 1 210px}
        .list-row{display:flex;align-items:center;justify-content:space-between;gap:12px;min-width:0;padding:13px 0;border-bottom:1px solid #edf0eb}
        .list-row:last-child{border-bottom:0}
        .list-row>div:first-child{min-width:0}
        .list-title{color:#354239;font-size:12px;font-weight:680;line-height:1.4;overflow-wrap:anywhere}
        .list-meta{margin-top:4px;color:#8a938b;font-size:10px;line-height:1.45;overflow-wrap:anywhere}
        .course-card{min-width:0;padding:14px;border:1px solid var(--line);border-radius:8px;background:#fff;transition:border-color .14s ease}
        .course-card:hover{border-color:#c6d1c5;box-shadow:none}
        .course-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;min-width:0}
        .stepper{display:flex;align-items:center;gap:7px;min-width:0;margin:0 0 18px}
        .step{display:flex;align-items:center;gap:7px;color:#929b93;font-size:10px;font-weight:620}
        .step-number{width:25px;height:25px;flex:none;display:grid;place-items:center;border:1px solid #d8ded7;border-radius:7px;background:#fff;color:#768078;font-size:10px}
        .step.active{color:#40584a}.step.active .step-number{border-color:#40584a;background:#40584a;color:#fff}
        .step.done{color:#4d7354}.step.done .step-number{border-color:#d9e6d9;background:#eff5ef;color:#4d7354}
        .step-line{width:32px;height:1px;flex:none;background:#e1e6df}
        .choice-card{min-width:0;padding:15px;border:1px solid var(--line);border-radius:8px;background:#fff}
        .choice-card.selected{border-color:#9eaf9f;box-shadow:0 0 0 2px #f0f4ef}
        .choice-toggle{display:flex;align-items:center;gap:9px;margin:0;color:#3c4d40;font-size:12px;font-weight:680}
        .choice-toggle input{width:16px;height:16px;accent-color:var(--accent)}
        .weekday-list{display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin-top:8px}
        .weekday{display:inline-flex;align-items:center;gap:5px;margin:0;padding:7px 8px;border:1px solid #e0e5de;border-radius:6px;background:#fff;color:#58645a;font-size:10px}
        .weekday:has(input:checked){border-color:#aabaaa;background:#f1f5f0;color:#40584a}
        .weekday input{accent-color:var(--accent)}
        .table-wrap{width:100%;min-width:0;overflow-x:auto;overscroll-behavior-x:contain}
        table{width:100%;border-collapse:collapse;font-size:11px}
        th,td{padding:10px 9px;border-bottom:1px solid #edf0eb;text-align:left;vertical-align:middle;overflow-wrap:anywhere}
        th{background:#f8f9f7;color:#7a857b;font-size:9px;font-weight:740;letter-spacing:.65px;text-transform:uppercase}
        tbody tr:hover{background:#fcfdfb}
        .mono{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:11px}
        details.panel{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:8px;background:#fff}
        details.panel>summary{display:flex;align-items:center;justify-content:space-between;gap:10px;min-width:0;padding:13px 15px;color:#465248;font-size:12px;font-weight:660;line-height:1.4;list-style:none;cursor:pointer}
        details.panel>summary::-webkit-details-marker{display:none}
        details.panel>summary:after{content:"+";flex:none;color:#7c887d;font-size:17px;font-weight:400}
        details.panel[open]>summary:after{content:"−"}
        details.panel[open]>summary{border-bottom:1px solid #edf0eb}
        .panel-body{min-width:0;padding:15px}
        .quick-nav{position:sticky;top:60px;z-index:10;display:flex;align-items:center;gap:6px;flex-wrap:wrap;min-width:0;margin:0 0 14px;padding:8px;border:1px solid #e5e9e3;border-radius:8px;background:rgba(247,248,245,.96)}
        .quick-nav a{max-width:100%;padding:7px 9px;border:1px solid transparent;border-radius:6px;color:#657267;font-size:10px;font-weight:620;line-height:1.3;overflow-wrap:anywhere}
        .quick-nav a:hover{border-color:#e0e6de;background:#fff;text-decoration:none}
        /* Flash messages */
        .flash-stack{position:relative;z-index:25;display:grid;gap:7px;margin:0 0 15px}.flash-stack:empty{display:none}
        .flash{display:flex;align-items:flex-start;gap:10px;min-width:0;padding:11px 12px;border:1px solid;border-radius:8px;background:#fff;font-size:11px;line-height:1.5;box-shadow:0 2px 5px rgba(30,40,32,.025);transition:opacity .13s ease,transform .13s ease}
        .flash-mark{width:18px;height:18px;flex:none;display:grid;place-items:center;border-radius:50%;font-size:10px;font-weight:800}
        .flash-content{flex:1;min-width:0}
        .flash-content strong{display:block;margin-bottom:2px;font-weight:720}
        .flash-success{border-color:#d9e6d9;color:#49654d}.flash-success .flash-mark{background:#edf5ee;color:#45674d}
        .flash-error{border-color:#ead6d2;color:#914d45}.flash-error .flash-mark{background:#fbefed;color:#914d45}
        .flash-info{border-color:#dce6e6;color:#52686e}.flash-info .flash-mark{background:#eff4f5;color:#52686e}
        .flash-close{flex:none;width:25px;height:25px;margin:-3px -4px 0 0;border:0;border-radius:5px;background:transparent;color:currentColor;font-size:17px;line-height:1;cursor:pointer;opacity:.65}
        .flash-close:hover{background:rgba(0,0,0,.045);opacity:1}
        /* Native dialog layer */
        .ui-dialog{width:min(520px,calc(100% - 32px));max-width:calc(100% - 32px);max-height:min(88dvh,760px);margin:auto;padding:0;overflow:auto;border:1px solid #dfe5de;border-radius:11px;background:#fff;color:var(--ink);box-shadow:0 18px 60px rgba(22,32,24,.2)}
        .ui-dialog::backdrop{background:rgba(24,31,25,.38)}
        .ui-dialog[open]{animation:dialog-enter .17s var(--ease-out) both}
        @keyframes dialog-enter{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
        .dialog-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:17px 18px 13px;border-bottom:1px solid #edf0eb}
        .dialog-head>div{min-width:0}
        .dialog-head h2{margin:0 0 4px;font-size:16px}
        .dialog-head p{margin:0;color:#7e887f;font-size:11px;line-height:1.5}
        .dialog-close{width:29px;height:29px;flex:none;display:grid;place-items:center;border:1px solid #e5e9e3;border-radius:6px;background:#fff;color:#657067;font-size:17px;cursor:pointer}
        .dialog-close:hover{background:#f6f8f5}
        .dialog-body{padding:16px 18px}
        .dialog-footer{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;padding:12px 18px 17px;border-top:1px solid #edf0eb}
        .dialog-footer .btn{min-width:95px}
        .dialog-error{margin:0 0 13px;padding:10px 11px;border:1px solid #ecd8d4;border-radius:7px;background:#fbf0ee;color:#914d45;font-size:11px;line-height:1.5}
        .dialog-error ul{margin:5px 0 0;padding-left:18px}
        .confirm-dialog{width:min(420px,calc(100% - 32px))}
        /* Small utilities and content safety */
        .text-wrap,.list-title,.list-meta,.card-description,.help,td,th{overflow-wrap:anywhere}
        [hidden]{display:none!important}
        @media(hover:hover) and (pointer:fine){
            .btn-secondary:hover,.btn-ghost:hover,.logout:hover,.dialog-close:hover{transition-duration:.12s}
            .nav-link:hover{transform:translateX(1px)}
        }
        @media(max-width:1050px){
            .app-shell{grid-template-columns:1fr}
            .sidebar{position:sticky;top:60px;z-index:20;height:auto;max-height:none;overflow-x:auto;overflow-y:hidden;white-space:nowrap;padding:7px 10px;border-right:0;border-bottom:1px solid var(--line);background:rgba(250,251,249,.98)}
            .sidebar>div{display:flex;align-items:center;gap:4px;min-width:max-content}
            .sidebar .nav-label,.sidebar .sidebar-note{display:none}
            .nav-link{margin:0;padding:8px 10px;border-radius:6px;font-size:11px}
            .nav-link.active{border-color:#e0e8df}
            .main{padding:22px}
            .quick-nav{top:60px}
        }
        @media(max-width:720px){
            .topbar{height:54px;padding:0 11px}
            .brand{gap:8px;font-size:12px}
            .brand-mark{width:29px;height:29px;border-radius:7px;font-size:10px}
            .brand small{display:none}
            .top-actions{gap:6px}
            .top-user{display:none}
            .logout{min-height:31px;padding:6px 8px;font-size:10px}
            .app-shell{min-height:calc(100vh - 54px)}
            .sidebar{top:54px;padding:6px 8px}
            .sidebar>div{width:100%;min-width:0}
            .nav-link{flex:1;justify-content:center;gap:5px;padding:8px 6px;font-size:10px;text-align:center;white-space:normal}
            .nav-dot{display:none}
            .main{padding:14px 10px 28px}
            .page-head{display:flex;flex-direction:column;align-items:stretch;gap:11px;margin-bottom:15px}
            .page-head h1{font-size:22px;letter-spacing:-.55px}
            .page-head .actions{display:flex;align-items:stretch;width:100%}
            .page-head .actions .btn,.page-head>.btn{flex:1 1 auto}
            .subhead{font-size:11px}
            .eyebrow{font-size:8px;margin-bottom:5px}
            .grid-2,.grid-3,.grid-4,.form-row,.form-row-3{grid-template-columns:minmax(0,1fr);gap:9px}
            .card{padding:13px;margin-bottom:10px;border-radius:8px}
            .card-head{align-items:flex-start;gap:8px;margin-bottom:11px}
            .card-head>div{flex:1}
            .card-head .badge{flex:none}
            .card-description{font-size:10px}
            h2{font-size:14px}
            h3{font-size:12px}
            .stat-card{padding:12px;border-radius:7px}
            .stat-label{font-size:9px}
            .stat{font-size:21px}
            .btn{min-height:36px;padding:8px 9px;font-size:10px}
            .btn-sm{min-height:31px;padding:6px 8px;font-size:9px}
            .actions{gap:6px}
            .actions .btn{flex:1 1 auto}
            .field,.filter-input{min-height:40px;font-size:13px}
            label,.field-label{font-size:10px;margin-top:10px}
            .help{font-size:9px}
            .form-actions{align-items:stretch;flex-direction:column-reverse;gap:9px;margin-top:15px;padding-top:12px}
            .form-actions>span{flex-basis:auto}
            .form-actions .btn{width:100%}
            .filter-bar{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:7px;padding:8px}
            .filter-bar .filter-input[type=search]{grid-column:1/-1;width:100%}
            .filter-bar .btn,.filter-bar>a{width:100%;min-width:0}
            .list-row{align-items:flex-start;gap:8px;padding:11px 0}
            .list-row .actions{justify-content:flex-end}
            .list-title{font-size:11px}
            .list-meta{font-size:9px}
            .badge{font-size:8px;padding:4px 6px}
            .empty{padding:18px 11px}
            table{min-width:560px;font-size:10px}
            th,td{padding:8px 7px}
            .quick-nav{top:54px;flex-wrap:nowrap;overflow-x:auto;white-space:nowrap;padding:6px;scrollbar-width:none}
            .quick-nav a{flex:none;font-size:9px}
            .stepper{overflow-x:auto;white-space:nowrap;padding-bottom:3px}
            .step{flex:none;font-size:9px;gap:5px}
            .step-line{width:17px}
            .dialog-head{padding:14px 14px 11px}
            .dialog-body{padding:13px 14px}
            .dialog-footer{padding:11px 14px 14px}
            .ui-dialog{width:calc(100% - 20px);max-width:calc(100% - 20px);max-height:90dvh;border-radius:10px}
            .ui-dialog.sheet-dialog{width:100%;max-width:100%;max-height:92dvh;margin:auto 0 0;border-radius:12px 12px 0 0;padding-bottom:env(safe-area-inset-bottom)}
            .dialog-footer .btn{flex:1}
            .flash{font-size:10px}
        }
        @media(max-width:390px){
            .main{padding-left:8px;padding-right:8px}
            .nav-link{font-size:9px;padding:8px 4px}
            .page-head .actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
            .page-head .actions .btn{width:100%}
            .list-row{flex-direction:column}
            .list-row .actions{width:100%;justify-content:flex-start}
            .list-row .actions .btn{flex:1}
            .filter-bar{grid-template-columns:1fr}
            .filter-bar .filter-input[type=search]{grid-column:auto}
        }
        @media(prefers-reduced-motion:reduce){
            *,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}
        }

        /* Color system: blue, teal and warm semantic accents */
        :root{
            --ink:#203440;--ink-soft:#3f5663;--muted:#687f8b;--faint:#90a2aa;
            --line:#dce6eb;--line-strong:#c8d7df;--canvas:#f2f6f9;--surface:#fff;--surface-soft:#f7fafc;
            --accent:#28677d;--accent-hover:#20566a;--accent-soft:#e8f2f6;
            --success:#28734f;--success-bg:#eaf5ee;--warning:#986329;--warning-bg:#fff4e3;
            --danger:#a74646;--danger-bg:#fff0ef;--info:#346c91;--info-bg:#edf5fb;
        }
        body{background:linear-gradient(135deg,#f2f6f9 0%,#f5f8fa 46%,#eff5f7 100%);color:var(--ink)}
        .topbar{background:rgba(255,255,255,.97);border-bottom-color:#d8e4eb;box-shadow:0 2px 10px rgba(35,72,92,.035)}
        .brand-mark{border-color:#cfe1e8;background:linear-gradient(145deg,#eaf5f8,#e9f2f8);color:#28677d}
        .sidebar{background:linear-gradient(180deg,#f4f8fa 0%,#f8fafb 100%);border-right-color:#dce7ed}
        .nav-label{color:#78909d}
        .nav-link{color:#526b78}
        .nav-link:hover,.nav-link.active{background:#e5f1f6;color:#245f75}
        .nav-link.active{border-color:#cfe2eb}
        .nav-link.active .icon{color:#28677d}
        .sidebar-note{border-color:#dce8ed;background:#edf5f8;color:#59717e}
        .sidebar-note strong{color:#305a6e}
        .main{color:var(--ink)}
        h1{color:#203744}
        h2{color:#2c4b5b}
        h3{color:#385a6a}
        .eyebrow{color:#4c849a}
        .subhead,.card-description{color:#607987}
        .breadcrumbs{color:#8ca0aa}.breadcrumbs a{color:#41758a}.breadcrumbs .current{color:#365767}
        .card{border-color:#dce7ec;box-shadow:0 2px 8px rgba(40,78,96,.025)}
        .card-head{border-bottom:1px solid #edf2f5;padding-bottom:11px}
        .stat-card{border-color:#dce7ed;background:#fff;box-shadow:0 2px 7px rgba(40,78,96,.025)}
        .stat{color:#28677d}
        .stat-icon{background:#e8f3f7;color:#28677d}
        .btn{border-color:#28677d;background:#28677d}
        .btn:hover{border-color:#20566a;background:#20566a}
        .btn-secondary{border-color:#cbdce4;background:#fff;color:#3d6070}
        .btn-secondary:hover{border-color:#a8c8d6;background:#eef6f9;color:#255a70}
        .btn-ghost{color:#527588}
        .btn-ghost:hover{border-color:#d7e7ed;background:#edf5f8;color:#2b657c}
        .field{border-color:#cadbe3;color:#294653}
        .field:hover{border-color:#9dbdcb}
        .field:focus{border-color:#4b91a9;box-shadow:0 0 0 3px rgba(53,131,158,.14)}
        label,.field-label{color:#3c5f70}
        .help{color:#718894}
        .badge{border-color:#dce7ec;background:#f2f6f8;color:#526a76}
        .badge-blue{border-color:#cfe2ed;background:#eaf4fa;color:#316d8d}
        .badge-green{border-color:#cfe7d8;background:#eaf5ee;color:#34744f}
        .badge-orange{border-color:#f0dfc1;background:#fff4e4;color:#93652b}
        .badge-red{border-color:#efd4d2;background:#fff0ef;color:#a34a48}
        .badge-purple{border-color:#e2d9f2;background:#f4effb;color:#72569b}
        .badge-teal{border-color:#cce8e3;background:#eaf7f4;color:#2d766b}
        .empty{border-color:#d4e3e9;background:#f7fafb;color:#68818d}
        .empty strong{color:#355667}
        .filter-bar{border-color:#dce7ed;background:#f3f7f9}
        .filter-input{border-color:#cddde5;color:#405f6e}
        .list-title{color:#315365}.list-meta{color:#718895}
        .course-card{border-color:#dce7ed;background:#fff}
        .course-card:hover{border-color:#a8c9d7;background:#fbfdfe}
        .choice-card{border-color:#dce7ed}
        .choice-card.selected{border-color:#70a7bc;background:#f1f8fa;box-shadow:0 0 0 2px #e5f2f6}
        th{background:#edf4f7;color:#557584}
        td{color:#3f5965}
        tbody tr:hover{background:#f4f9fb}
        .quick-nav{border-color:#dce7ed;background:rgba(240,247,250,.96)}
        .quick-nav a{color:#557687}
        .quick-nav a:hover{border-color:#d3e5eb;background:#fff}
        .flash-success{border-color:#cfe7d8;background:#f2faf5;color:#34744f}
        .flash-error{border-color:#efd4d2;background:#fff5f4;color:#a34a48}
        .flash-info{border-color:#cfe2ed;background:#f2f8fc;color:#346c91}
        .flash-success .flash-mark{background:#dff1e6;color:#28734f}
        .flash-error .flash-mark{background:#fbe4e2;color:#a74646}
        .flash-info .flash-mark{background:#e0eff8;color:#346c91}
        .dialog-head{background:linear-gradient(135deg,#f3f8fa,#fff)}
        .modal-context{border-color:#d2e5ec;background:#eff7fa;color:#476a79}
        .cycle-item-icon{border-color:#d4e5ed;background:#edf5f8;color:#39758d}
        .workflow-icon{background:#e8f3f7;color:#28677d}
        .stat-card:nth-child(4n+1) .stat-icon{background:#e8f1fb;color:#3f70a0}
        .stat-card:nth-child(4n+2) .stat-icon{background:#e7f5f1;color:#347d72}
        .stat-card:nth-child(4n+3) .stat-icon{background:#fff2e2;color:#a36b2f}
        .stat-card:nth-child(4n) .stat-icon{background:#f2edfa;color:#795fa5}
        .dashboard-stats .stat-card:nth-child(1){border-top:3px solid #5b8db7;background:#f5f9fd}
        .dashboard-stats .stat-card:nth-child(2){border-top:3px solid #4b9a8b;background:#f4faf8}
        .dashboard-stats .stat-card:nth-child(3){border-top:3px solid #d19a55;background:#fffaf3}
        .dashboard-stats .stat-card:nth-child(4){border-top:3px solid #8975b1;background:#faf8fd}
        .cycle-item{border-bottom-color:#e8eff2}
        .cycle-item:hover .cycle-item-icon{background:#e3f1f6}
        .pagination{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin:16px 0 0}
        .pagination a,.pagination span{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 9px;border:1px solid #d5e3e9;border-radius:7px;background:#fff;color:#456a7b;font-size:11px}
        .pagination [aria-current="page"] span{border-color:#28677d;background:#28677d;color:#fff}
        .pagination a:hover{border-color:#9fc0cd;background:#eff7fa;text-decoration:none}
        .inline-edit{display:inline-flex;align-items:center;gap:5px;border:0;background:transparent;color:#34758c;font-size:10px;font-weight:650;cursor:pointer}
        .inline-edit:hover{color:#1e566b;text-decoration:underline}
        .modal-help-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .modal-help-item{display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid #dce8ed;border-radius:9px;background:#f8fbfc}
        .modal-help-item .workflow-icon{width:34px;height:34px}
        .modal-help-item strong{display:block;color:#31586b;font-size:12px}
        .modal-help-item p{margin:4px 0 0;color:#6d8390;font-size:10px;line-height:1.5}
        @media(max-width:720px){.modal-help-grid{grid-template-columns:1fr}}

        .pagination{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:17px 0 0;padding-top:13px;border-top:1px solid #e7eef2}
        .pagination-status{color:#6f8793;font-size:10px}
        .pagination-links{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
        .pagination-links a,.pagination-links>span{min-width:31px;height:31px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid #d6e4ea;border-radius:7px;background:#fff;color:#4c7182;font-size:10px;font-weight:650;text-decoration:none}
        .pagination-links a:hover{border-color:#9fc3d0;background:#eff7fa}
        .pagination-links .is-current{border-color:#28677d;background:#28677d;color:#fff}
        .pagination-links .is-disabled{color:#a7b5bc;background:#f5f8f9}
        .pagination-links .pagination-ellipsis{min-width:18px;padding:0;border:0;background:transparent;color:#899da7}
        @media(max-width:600px){.pagination{align-items:flex-start;flex-direction:column}.pagination-links{width:100%}.pagination-links a,.pagination-links>span{flex:1}}
    
        /* Tema global de alto contraste y acciones coherentes */
        .brand-logo{width:34px;height:34px;object-fit:contain;border-radius:8px;background:#fff}
        .top-help{display:inline-flex;align-items:center;gap:6px;border:1px solid #c8d8e1;border-radius:7px;background:#f3f8fb;color:#16485d;padding:7px 10px;font-size:11px;font-weight:700;cursor:pointer}
        .top-help:hover{background:#e6f2f7;border-color:#82b4c8}
        .top-user{color:#244556;font-weight:650}
        .btn,.btn:visited,a.btn,button.btn{background:var(--accent);border:1px solid var(--accent);color:#fff!important;text-decoration:none;font-weight:700;letter-spacing:.005em;box-shadow:0 1px 1px rgba(12,38,52,.08)}
        .btn .icon,.btn svg,.btn span{color:inherit}
        .btn:hover,button.btn:hover,a.btn:hover{background:var(--accent-hover);border-color:var(--accent-hover);color:#fff!important}
        .btn:focus-visible,.top-help:focus-visible,.logout:focus-visible,.nav-link:focus-visible{outline:3px solid rgba(23,107,135,.25);outline-offset:2px}
        .btn-secondary,.btn-secondary:visited,a.btn-secondary,button.btn-secondary{background:#f1f5f8!important;border-color:#c8d5dd!important;color:#213f50!important}
        .btn-secondary:hover,button.btn-secondary:hover,a.btn-secondary:hover{background:#e4edf2!important;border-color:#aebfc9!important;color:#183545!important}
        .btn-ghost,.btn-ghost:visited,a.btn-ghost,button.btn-ghost{background:#fff!important;border-color:#d5e0e5!important;color:#385667!important}
        .btn-ghost:hover,button.btn-ghost:hover,a.btn-ghost:hover{background:#eef5f8!important;color:#1d4d63!important}
        .btn-danger,.btn-danger:visited,a.btn-danger,button.btn-danger{background:#fff1f0!important;border-color:#edc5c1!important;color:#9f252b!important}
        .btn-danger:hover,button.btn-danger:hover,a.btn-danger:hover{background:#ffe2df!important;border-color:#d99a94!important;color:#861c23!important}
        .btn-success,.btn-success:visited,a.btn-success,button.btn-success{background:#e9f7ef!important;border-color:#b9e1c7!important;color:#17663a!important}
        .btn-success:hover,button.btn-success:hover,a.btn-success:hover{background:#d8f0e2!important;color:#0c512b!important}
        .inline-edit{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid #bfd3df;border-radius:7px;background:#eff7fa;color:#15536b!important;font-weight:700;cursor:pointer}
        .inline-edit:hover{background:#e0f0f5;border-color:#8fb8ca;color:#0d4055!important}
        .logout{border:1px solid #d5e0e5;border-radius:7px;background:#fff;color:#365464;padding:7px 10px;font-weight:650;cursor:pointer}
        .logout:hover{background:#f0f6f8;color:#153f54}
        .badge-green,.status-done{background:#e7f6ed!important;color:#166534!important;border:1px solid #bce3ca}
        .badge-orange,.status-pending{background:#fff6df!important;color:#87530b!important;border:1px solid #efd99b}
        .badge-blue{background:#e9f3fa!important;color:#145578!important;border:1px solid #c2deed}
        .badge-red{background:#fff0ef!important;color:#a3292a!important;border:1px solid #efc2bf}
        .field,.form-control,select.field,textarea.field{color:#183746;border-color:#cbd8df;background:#fff}
        .field:focus,.form-control:focus,select.field:focus,textarea.field:focus{border-color:#4b9bb3;box-shadow:0 0 0 3px rgba(34,132,160,.13);outline:0}
        .field::placeholder,textarea.field::placeholder{color:#889aa4;opacity:1}
        .field-error{color:#a3262c}
        .flash{opacity:1;transform:none}
        .settings-accordion{margin-top:18px;border:1px solid #d9e3e8;border-radius:9px;background:#f8fbfc}
        .settings-accordion summary{padding:13px 15px;color:#1e4d63;font-weight:700;cursor:pointer}
        .settings-accordion>div{padding:0 15px 15px}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .form-field{min-width:0}
        .form-field label{display:block;margin-bottom:6px;color:#29495b;font-size:12px;font-weight:700}
        .form-field .field{width:100%;min-height:39px}
        .field-error{margin:5px 0 0;font-size:11px}
        .status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:750}
        .status-active{background:#e8f7ed;color:#176337;border:1px solid #bfe5ca}
        .status-inactive{background:#f1f3f5;color:#596774;border:1px solid #d6dde2}
        .table-wrap table th{color:#294b5d}
        .table-wrap table td{color:#334b58}
        @media(max-width:720px){.form-grid{grid-template-columns:1fr}.top-help{padding:7px}.top-help .icon{display:none}.top-user{max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}

/* Sidebar configurable: expandir, contraer y ajustar ancho */
:root{--sidebar-width:250px}
.app-shell{grid-template-columns:var(--sidebar-width) minmax(0,1fr);transition:grid-template-columns .2s ease}
.sidebar{padding:15px 11px 13px;display:flex;flex-direction:column;min-width:0}
.sidebar-inner{display:flex;flex:1;flex-direction:column;gap:12px;min-height:0}
.sidebar-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:0 5px 8px;border-bottom:1px solid var(--line)}
.sidebar-toolbar-label{color:var(--muted);font-size:9px;font-weight:800;letter-spacing:1px}
.sidebar-icon-button{width:32px;height:30px;display:grid;place-items:center;border:1px solid var(--line-strong);border-radius:7px;background:var(--surface);color:var(--ink-soft);cursor:pointer}
.sidebar-icon-button:hover{border-color:var(--accent);color:var(--accent)}
.sidebar-nav{display:grid;align-content:start;gap:2px;min-width:0}
.sidebar .nav-label{padding:9px 10px 6px}
.sidebar .nav-section-label{margin-top:10px}
.sidebar .nav-link{gap:11px;min-height:39px;padding:9px 10px;color:var(--ink-soft)}
.sidebar .nav-link .icon{width:17px;height:17px;color:var(--muted)}
.sidebar .nav-link.active{background:var(--accent-soft);border-color:color-mix(in srgb,var(--accent) 22%,transparent);color:var(--accent)}
.sidebar .nav-link.active .icon{color:var(--accent)}
.sidebar-preferences{display:grid;gap:8px;margin-top:auto;padding:12px 5px 2px;border-top:1px solid var(--line)}
.sidebar-width-setting label{display:block;margin:0 0 6px;color:var(--muted);font-size:10px;font-weight:700}
.sidebar-width-row{display:flex;align-items:center;gap:8px}
.sidebar-width-row input{width:100%;min-width:0;accent-color:var(--accent);cursor:ew-resize}
.sidebar-width-row output{width:48px;flex:none;color:var(--muted);font-size:9px;text-align:right;font-variant-numeric:tabular-nums}
.sidebar-preference-button{display:flex;align-items:center;gap:9px;min-height:36px;padding:8px 9px;border:1px solid transparent;border-radius:7px;background:transparent;color:var(--ink-soft);font-size:11px;font-weight:620;text-align:left;cursor:pointer}
.sidebar-preference-button:hover{border-color:var(--line);background:var(--surface);color:var(--accent)}
.sidebar-preference-button .icon{color:var(--accent)}
.sidebar-preference-button small{margin-left:auto;color:var(--muted);font-size:9px;font-weight:500}
body.sidebar-collapsed .app-shell{grid-template-columns:70px minmax(0,1fr)}
body.sidebar-collapsed .sidebar{padding-right:8px;padding-left:8px}
body.sidebar-collapsed .sidebar-toolbar{justify-content:center;padding-right:0;padding-left:0;border-bottom:0}
body.sidebar-collapsed .sidebar-toolbar-label,
body.sidebar-collapsed .sidebar .nav-label,
body.sidebar-collapsed .sidebar .nav-link span,
body.sidebar-collapsed .sidebar-width-setting,
body.sidebar-collapsed .sidebar-preference-button span,
body.sidebar-collapsed .sidebar-preference-button small{display:none}
body.sidebar-collapsed .sidebar .nav-link{justify-content:center;padding:10px 5px}
body.sidebar-collapsed .sidebar .nav-link .icon{width:19px;height:19px}
body.sidebar-collapsed .sidebar-preferences{padding:10px 0 0}
body.sidebar-collapsed .sidebar-preference-button{justify-content:center;padding:8px 5px}
body.sidebar-collapsed .sidebar-preference-button .icon{width:18px;height:18px}
body.sidebar-collapsed .sidebar-icon-button{width:36px}
body.sidebar-collapsed .sidebar-icon-button .icon{transform:rotate(180deg)}
@media(max-width:1050px){
  .app-shell,body.sidebar-collapsed .app-shell{grid-template-columns:minmax(0,1fr)}
  .sidebar{height:auto;max-height:none;overflow:visible;padding:7px 10px}
  .sidebar-inner{height:auto;flex-direction:row;align-items:center;gap:6px}
  .sidebar-toolbar,.sidebar-preferences{display:none}
  .sidebar-nav{display:flex;align-items:center;gap:4px;min-width:max-content}
  .sidebar .nav-label{display:none}
  .sidebar .nav-link{justify-content:center;min-height:34px;white-space:nowrap}
  .sidebar .nav-link span{display:inline}
  body.sidebar-collapsed .sidebar .nav-link span{display:inline}
  body.sidebar-collapsed .sidebar .nav-link{justify-content:center;padding:8px 10px}
}
@media(max-width:720px){
  .sidebar{overflow-x:auto;overflow-y:hidden}
  .sidebar-inner{width:max-content;min-width:100%}
  .sidebar-nav{width:max-content;min-width:0}
  .sidebar .nav-link{font-size:10px;gap:5px;padding:8px 7px}
}
@media(prefers-reduced-motion:reduce){.app-shell{transition:none}}

/* Modo oscuro completo, conserva el color institucional configurado */
html[data-theme="dark"]{color-scheme:dark;--ink:#e6edf2;--ink-soft:#c3d0d8;--muted:#9aabb6;--faint:#7c8c97;--line:#2b3a45;--line-strong:#3a4d59;--canvas:#0b1218;--surface:#141f28;--surface-soft:#192630;--accent-soft:color-mix(in srgb,var(--accent) 20%,#14222c);--success-bg:#132b22;--warning-bg:#332717;--danger-bg:#341d20;--info-bg:#172b38}
html[data-theme="dark"] body{background:#0b1218!important;color:var(--ink)}
html[data-theme="dark"] .topbar{background:#121c25!important;border-bottom-color:#2b3b47;box-shadow:0 2px 12px rgba(0,0,0,.22)}
html[data-theme="dark"] .brand,html[data-theme="dark"] .top-user{color:#e3edf2}
html[data-theme="dark"] .brand small{color:#9aabb6}
html[data-theme="dark"] .brand-mark{background:#1e303b!important;border-color:#38505d;color:#e4edf2}
html[data-theme="dark"] .sidebar{background:#101a22!important;border-right-color:#2b3a45;border-bottom-color:#2b3a45}
html[data-theme="dark"] .sidebar-toolbar{border-bottom-color:#2b3a45}
html[data-theme="dark"] .sidebar-icon-button,html[data-theme="dark"] .sidebar-preference-button{background:#17232d;border-color:#30414d;color:#c5d2da}
html[data-theme="dark"] .sidebar-preference-button:hover,html[data-theme="dark"] .sidebar-icon-button:hover{background:#20313d;border-color:var(--accent);color:#fff}
html[data-theme="dark"] .sidebar .nav-link{color:#b6c5ce}
html[data-theme="dark"] .sidebar .nav-link:hover,html[data-theme="dark"] .sidebar .nav-link.active{background:color-mix(in srgb,var(--accent) 20%,#14222c);border-color:color-mix(in srgb,var(--accent) 45%,#233642);color:#fff}
html[data-theme="dark"] .sidebar .nav-link.active .icon{color:var(--accent)}
html[data-theme="dark"] .nav-label,html[data-theme="dark"] .sidebar-width-setting label{color:#91a3ae}
html[data-theme="dark"] .main{color:var(--ink)}
html[data-theme="dark"] h1,html[data-theme="dark"] h2,html[data-theme="dark"] h3,html[data-theme="dark"] .list-title,html[data-theme="dark"] .cycle-item-title{color:#e0e9ee}
html[data-theme="dark"] p,html[data-theme="dark"] .subhead,html[data-theme="dark"] .muted,html[data-theme="dark"] .card-description,html[data-theme="dark"] .help,html[data-theme="dark"] .list-meta{color:#9aabb6}
html[data-theme="dark"] .card,html[data-theme="dark"] .stat-card,html[data-theme="dark"] .course-card,html[data-theme="dark"] .choice-card,html[data-theme="dark"] .settings-accordion{background:#141f28!important;border-color:#2b3a45!important;box-shadow:none}
html[data-theme="dark"] .stat-card,html[data-theme="dark"] .course-card,html[data-theme="dark"] .choice-card{color:#d9e3e9}
html[data-theme="dark"] .stat-label,html[data-theme="dark"] .eyebrow,html[data-theme="dark"] .breadcrumbs,html[data-theme="dark"] .breadcrumbs a{color:#91a3ae}
html[data-theme="dark"] .stat{color:#d6e7ee}
html[data-theme="dark"] .filter-bar,html[data-theme="dark"] .empty,html[data-theme="dark"] .quick-nav{background:#17232c!important;border-color:#30414d!important;color:#afbdc5}
html[data-theme="dark"] .quick-nav a{color:#b5c7d1}
html[data-theme="dark"] .quick-nav a:hover{background:#22333e;border-color:#3c515e}
html[data-theme="dark"] .field,html[data-theme="dark"] .filter-input,html[data-theme="dark"] .form-control,html[data-theme="dark"] select,html[data-theme="dark"] textarea{background:#0f1921!important;border-color:#354955!important;color:#e0e9ee!important}
html[data-theme="dark"] .field::placeholder,html[data-theme="dark"] textarea::placeholder{color:#71838e}
html[data-theme="dark"] label,html[data-theme="dark"] .field-label{color:#bdcbd3}
html[data-theme="dark"] .btn-secondary,html[data-theme="dark"] .btn-ghost,html[data-theme="dark"] .logout,html[data-theme="dark"] .top-help{background:#1b2a34!important;border-color:#3a4e5a!important;color:#d6e1e7!important}
html[data-theme="dark"] .btn-secondary:hover,html[data-theme="dark"] .btn-ghost:hover,html[data-theme="dark"] .logout:hover,html[data-theme="dark"] .top-help:hover{background:#263945!important;color:#fff!important}
html[data-theme="dark"] .btn-danger{background:#351f22!important;border-color:#714044!important;color:#ffc4c1!important}
html[data-theme="dark"] th{background:#1c2b35!important;color:#b2c2cb!important}
html[data-theme="dark"] td{color:#d0dbe1}
html[data-theme="dark"] tbody tr:hover{background:#1b2a34}
html[data-theme="dark"] th,html[data-theme="dark"] td{border-bottom-color:#2b3b46}
html[data-theme="dark"] details.panel,html[data-theme="dark"] details.panel>summary{background:#141f28!important;color:#d6e1e7;border-color:#2b3a45}
html[data-theme="dark"] details.panel[open]>summary{border-bottom-color:#2b3a45}
html[data-theme="dark"] .panel-body{background:#141f28}
html[data-theme="dark"] .ui-dialog{background:#141f28!important;border-color:#354955;color:#e2eaf0}
html[data-theme="dark"] .dialog-head,html[data-theme="dark"] .dialog-footer{background:#17232c;border-color:#2b3a45}
html[data-theme="dark"] .dialog-close{background:#1b2a34;border-color:#3a4c57;color:#d5e0e6}
html[data-theme="dark"] .modal-help-item{background:#1a2832;border-color:#344752}
html[data-theme="dark"] .modal-help-item strong{color:#d7e4ea}
html[data-theme="dark"] .modal-help-item p{color:#9aabb6}
html[data-theme="dark"] .flash{background:#17232c}
html[data-theme="dark"] .flash-success{background:#132b22;color:#a6dbb8;border-color:#285b43}
html[data-theme="dark"] .flash-error{background:#341d20;color:#ffc3bf;border-color:#754144}
html[data-theme="dark"] .flash-info{background:#172b38;color:#b6d9ee;border-color:#355a70}
html[data-theme="dark"] .alert-success{background:#132b22;color:#a6dbb8;border-color:#285b43}
html[data-theme="dark"] .alert-error{background:#341d20;color:#ffc3bf;border-color:#754144}
html[data-theme="dark"] .alert-info{background:#172b38;color:#b6d9ee;border-color:#355a70}
html[data-theme="dark"] .alert-warning{background:#332717;color:#f1d09a;border-color:#6a502d}
html[data-theme="dark"] .badge,html[data-theme="dark"] .badge-blue,html[data-theme="dark"] .badge-green,html[data-theme="dark"] .badge-orange,html[data-theme="dark"] .badge-red{background:#1d2a33!important;border-color:#3a4c57!important;color:#d0dce2!important}
html[data-theme="dark"] .pagination a,html[data-theme="dark"] .pagination span{background:#1a2832;border-color:#374a56;color:#cedae0}
html[data-theme="dark"] .pagination [aria-current="page"] span{background:var(--accent);border-color:var(--accent);color:#fff}
html[data-theme="dark"] .cycle-item,html[data-theme="dark"] .list-row{border-bottom-color:#2b3b46}
html[data-theme="dark"] .workflow-step{background:#17232c;border-color:#334650}
html[data-theme="dark"] .workflow-step strong{color:#d5e1e7}
html[data-theme="dark"] .step-number{background:#1b2a34;border-color:#3a4e5a;color:#c6d3da}
html[data-theme="dark"] .step-line{background:#354955}
html[data-theme="dark"] .calendar-preview,html[data-theme="dark"] .weekly-load-summary{background:#172732!important;border-color:#344b58!important}
html[data-theme="dark"] .calendar-preview-stats>div,html[data-theme="dark"] .weekly-load-day{background:#13202a!important;border-color:#344b58!important}
html[data-theme="dark"] .weekly-load-block.is-theory{background:#1b3240!important;color:#b8d9e9;border-color:#365d70}
html[data-theme="dark"] .weekly-load-block.is-lab{background:#1a352a!important;color:#b9e1c7;border-color:#37674a}
html[data-theme="dark"] .preview-date{background:#182833;border-color:#3a5360;color:#c6dce6}
html[data-theme="dark"] .preview-holiday-warning{background:#351f22;border-color:#754144;color:#ffc3bf}
html[data-theme="dark"] .preview-no-holidays{background:#153126;border-color:#2d6448;color:#b0e0bf}
html[data-theme="dark"] .holiday-row,html[data-theme="dark"] .calendar-grid{border-color:#2b3b46}
html[data-theme="dark"] .holiday-date{background:#1a2b35;border-color:#3b515e}
html[data-theme="dark"] .holiday-date strong{color:#d7e6ed}


.top-theme-toggle{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:34px;padding:7px 10px;border:1px solid var(--line-strong);border-radius:7px;background:var(--surface);color:var(--ink-soft);font-size:10px;font-weight:650;cursor:pointer}
.top-theme-toggle:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-soft)}
html[data-theme="dark"] .top-theme-toggle{border-color:#3a4e5a;background:#1b2a34;color:#d6e1e7}
html[data-theme="dark"] .top-theme-toggle:hover{border-color:var(--accent);background:#263945;color:#fff}
@media(max-width:720px){.top-theme-toggle{width:32px;min-width:32px;height:32px;padding:6px}.top-theme-toggle span{display:none}}


.workflow-intro{margin:0 0 13px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:11px;line-height:1.55}
.modal-help-item{position:relative;align-items:center;min-height:94px}
.modal-step-index{width:28px;height:28px;flex:none;display:grid;place-items:center;border:1px solid color-mix(in srgb,var(--accent) 28%,transparent);border-radius:8px;background:var(--accent-soft);color:var(--accent);font-size:10px;font-weight:800;font-variant-numeric:tabular-nums}
html[data-theme="dark"] .workflow-intro{background:#17232c;border-color:#344752;color:#a8bac4}
html[data-theme="dark"] .modal-step-index{background:#203641;border-color:#426070;color:#a9d8e8}


/* La identidad visual configurada debe controlar todos los acentos principales */
.brand-mark{color:var(--accent)!important}
.nav-link.active,.nav-link:hover{background:var(--accent-soft)!important;color:var(--accent)!important}
.nav-link.active .icon{color:var(--accent)!important}
.stat{color:var(--accent)!important}
.workflow-icon{background:var(--accent-soft)!important;color:var(--accent)!important}
.inline-edit{border-color:color-mix(in srgb,var(--accent) 28%,transparent)!important;background:var(--accent-soft)!important;color:var(--accent)!important}
.inline-edit:hover{border-color:var(--accent)!important;color:var(--accent-hover)!important}
.pagination [aria-current="page"] span,.pagination-links .is-current{border-color:var(--accent)!important;background:var(--accent)!important;color:#fff!important}
.btn:not(.btn-secondary):not(.btn-ghost):not(.btn-danger):not(.btn-success){border-color:var(--accent);background:var(--accent)}
.btn:not(.btn-secondary):not(.btn-ghost):not(.btn-danger):not(.btn-success):hover{border-color:var(--accent-hover);background:var(--accent-hover)}
html[data-theme="dark"] .nav-link.active,html[data-theme="dark"] .nav-link:hover{background:color-mix(in srgb,var(--accent) 20%,#14222c)!important;color:#fff!important}


/* Aviso global único: animado, visible y fuera del flujo de la página */
.app-notice-wrap{position:fixed;top:76px;right:22px;z-index:120;display:grid;width:min(420px,calc(100vw - 28px));pointer-events:none}
.app-notice{position:relative;display:flex;align-items:center;gap:12px;min-width:0;padding:15px 16px 17px;overflow:hidden;border:1px solid #b9e0c8;border-left:4px solid #23834d;border-radius:12px;background:#fff;color:#203d2d;box-shadow:0 16px 42px rgba(18,47,31,.18);pointer-events:auto;animation:notice-enter .42s cubic-bezier(.18,.89,.32,1.22) both}
.app-notice-icon{width:39px;height:39px;flex:none;display:grid;place-items:center;border-radius:50%;background:#e5f6eb;color:#17713e;animation:notice-pop .48s .08s cubic-bezier(.18,.89,.32,1.35) both}
.app-notice-copy{flex:1;min-width:0}
.app-notice-copy strong{display:block;color:#185d35;font-size:12px;font-weight:800;line-height:1.35}
.app-notice-copy p{margin:3px 0 0;color:#526d5c;font-size:11px;line-height:1.5;overflow-wrap:anywhere}
.app-notice-close{width:28px;height:28px;flex:none;display:grid;place-items:center;border:0;border-radius:7px;background:transparent;color:#668273;cursor:pointer}
.app-notice-close:hover{background:#edf7f0;color:#185d35}
.app-notice-progress{position:absolute;right:0;bottom:0;left:0;height:3px;background:#e7f3eb}
.app-notice-progress>span{display:block;width:100%;height:100%;transform-origin:left;background:#32925b;animation:notice-progress 5.2s linear both}
.app-notice-error{border-color:#ecc4c1;border-left-color:#b93b3b;background:#fff;color:#572222}
.app-notice-error .app-notice-icon{background:#fde9e7;color:#a92f2f}
.app-notice-error .app-notice-copy strong{color:#9d2929}
.app-notice-error .app-notice-copy p{color:#774646}
.app-notice-error .app-notice-progress{background:#f8e5e3}
.app-notice-error .app-notice-progress>span{background:#be4141;animation:none}
.app-notice-info{border-color:#c7ddea;border-left-color:#347da4;background:#fff;color:#24465b}
.app-notice-info .app-notice-icon{background:#e7f3f9;color:#286b91}
.app-notice-info .app-notice-copy strong{color:#245e7e}
.app-notice-info .app-notice-copy p{color:#587184}
.app-notice-info .app-notice-progress{background:#e6f1f7}
.app-notice-info .app-notice-progress>span{background:#4389b1}
.app-notice.is-leaving{animation:notice-exit .24s ease-in both}
@keyframes notice-enter{from{opacity:0;transform:translate3d(0,-16px,0) scale(.97)}to{opacity:1;transform:translate3d(0,0,0) scale(1)}}
@keyframes notice-pop{0%{transform:scale(.5);opacity:.4}70%{transform:scale(1.12)}100%{transform:scale(1)}}
@keyframes notice-progress{from{transform:scaleX(1)}to{transform:scaleX(0)}}
@keyframes notice-exit{to{opacity:0;transform:translate3d(0,-8px,0) scale(.98)}}
html[data-theme="dark"] .app-notice{background:#17282f;border-color:#335a45;color:#dbece0;box-shadow:0 16px 42px rgba(0,0,0,.42)}
html[data-theme="dark"] .app-notice-copy strong{color:#a9e2bd}
html[data-theme="dark"] .app-notice-copy p{color:#bdd0c3}
html[data-theme="dark"] .app-notice-icon{background:#1e3b2b;color:#9ee2b4}
html[data-theme="dark"] .app-notice-error{background:#2d1c20;border-color:#704045}
html[data-theme="dark"] .app-notice-error .app-notice-copy strong{color:#ffb8b2}
html[data-theme="dark"] .app-notice-error .app-notice-copy p{color:#e7b8b5}
html[data-theme="dark"] .app-notice-info{background:#182a35;border-color:#35566b}
html[data-theme="dark"] .app-notice-info .app-notice-copy strong{color:#a9d8ef}
html[data-theme="dark"] .app-notice-info .app-notice-copy p{color:#bfd3df}
@media(max-width:600px){.app-notice-wrap{top:62px;right:10px;width:calc(100vw - 20px)}.app-notice{padding:12px 13px 15px;gap:9px}.app-notice-icon{width:34px;height:34px}}
@media(prefers-reduced-motion:reduce){.app-notice,.app-notice-icon,.app-notice-progress>span{animation:none!important}}

</style>
    @stack('styles')
    <style>
        /* Sistema visual compartido · Gestión Docente */
        :root{
            --gd-border:#e3e9e5;
            --gd-border-strong:#d4ded7;
            --gd-surface:#ffffff;
            --gd-surface-soft:#f7faf8;
            --gd-ink:#26372e;
            --gd-muted:#6d7c73;
            --gd-radius:12px;
            --gd-shadow:0 1px 2px rgba(25,43,33,.035),0 5px 16px rgba(25,43,33,.025);
            --gd-shadow-hover:0 5px 18px rgba(25,43,33,.075);
            --gd-focus:0 0 0 3px color-mix(in srgb,var(--accent) 20%,transparent);
        }

        /* Jerarquía de página */
        .page-head{
            display:flex!important;
            align-items:flex-end!important;
            justify-content:space-between!important;
            gap:20px!important;
            margin:0 0 20px!important;
            padding:0 0 16px!important;
            border-bottom:1px solid var(--gd-border)!important;
        }
        .page-head>div:first-child{min-width:0}
        .page-head .eyebrow{
            display:flex;
            align-items:center;
            gap:6px;
            margin:0 0 7px!important;
            color:var(--accent)!important;
            font-size:10px!important;
            font-weight:800!important;
            letter-spacing:.075em!important;
            line-height:1.35!important;
            text-transform:uppercase!important;
        }
        .page-head h1{
            margin:0!important;
            color:var(--gd-ink)!important;
            font-size:clamp(25px,2.2vw,31px)!important;
            font-weight:780!important;
            line-height:1.12!important;
            letter-spacing:-.04em!important;
            overflow-wrap:anywhere;
        }
        .page-head .subhead{
            max-width:72ch;
            margin:7px 0 0!important;
            color:var(--gd-muted)!important;
            font-size:12.5px!important;
            line-height:1.55!important;
        }
        .page-head .actions{
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            justify-content:flex-end;
            gap:8px;
        }
        .breadcrumbs{
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:7px;
            margin:0 0 14px!important;
            color:#829087;
            font-size:10.5px;
            line-height:1.45;
        }
        .breadcrumbs a{color:var(--accent);text-decoration:none}
        .breadcrumbs a:hover{text-decoration:underline;text-underline-offset:3px}
        .breadcrumbs .current{color:var(--gd-ink);font-weight:750}

        /* Superficies: las tarjetas agrupan contenido, no decoran cada dato */
        .card{
            min-width:0;
            border:1px solid var(--gd-border)!important;
            border-radius:var(--gd-radius)!important;
            background:var(--gd-surface)!important;
            box-shadow:var(--gd-shadow)!important;
        }
        .card>.card-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            margin:0 0 14px;
            padding:0 0 12px;
            border-bottom:1px solid #edf1ee;
        }
        .card-head>div:first-child{min-width:0}
        .card .card-head h2,
        .card>.workflow-heading h2,
        .card>.detail-progress-heading h2,
        .card>.cycle-progress-heading h2{
            margin:0;
            color:var(--gd-ink);
            font-size:15px;
            font-weight:780;
            line-height:1.3;
            letter-spacing:-.018em;
        }
        .card .card-description{
            max-width:76ch;
            margin:5px 0 0;
            color:var(--gd-muted);
            font-size:11.5px;
            line-height:1.55;
        }
        .card h3:not(.cycle-item-title){
            color:var(--gd-ink);
            font-size:13px;
            font-weight:740;
            line-height:1.35;
        }
        .card a:not(.btn){text-underline-offset:3px}
        .card a:not(.btn):focus-visible{outline:2px solid var(--accent);outline-offset:3px;border-radius:3px}

        /* Indicadores solo cuando ayudan a decidir */
        .stat-card{
            display:flex;
            min-width:0;
            flex-direction:column;
            gap:7px;
            padding:15px 16px!important;
            border:1px solid var(--gd-border)!important;
            border-radius:11px!important;
            background:var(--gd-surface)!important;
            box-shadow:none!important;
        }
        .stat-card .stat-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
        }
        .stat-card .stat-label{
            color:var(--gd-muted)!important;
            font-size:10.5px!important;
            font-weight:720!important;
            line-height:1.35!important;
        }
        .stat-card .stat{
            margin:0!important;
            color:var(--gd-ink)!important;
            font-size:clamp(24px,2vw,29px)!important;
            font-weight:820!important;
            line-height:1.08!important;
            letter-spacing:-.035em!important;
            font-variant-numeric:tabular-nums;
        }
        .stat-card .stat-icon{
            flex:none;
            width:34px;
            height:34px;
            border-radius:9px;
        }

        /* Botones: acción principal clara, feedback breve y foco visible */
        .btn{
            border-radius:8px;
            font-weight:680;
            transition:background-color .14s ease,border-color .14s ease,color .14s ease,box-shadow .14s ease,transform .14s ease;
        }
        .btn:focus-visible,
        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible,
        summary:focus-visible{
            outline:2px solid var(--accent);
            outline-offset:2px;
        }
        .btn:active:not(:disabled){transform:translateY(1px)}
        .btn:disabled,[aria-disabled="true"]{cursor:not-allowed}
        .btn:hover:not(:disabled){filter:brightness(.98)}
        a.card[href],button.card{transition:border-color .16s ease,box-shadow .16s ease,transform .16s ease}
        a.card[href]:hover,button.card:hover{border-color:var(--gd-border-strong)!important;box-shadow:var(--gd-shadow-hover)!important;transform:translateY(-1px)}

        /* Búsqueda y filtros */
        .filter-bar,.accounts-toolbar,.course-toolbar{
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:9px!important;
            margin:13px 0!important;
            padding:10px!important;
            border:1px solid var(--gd-border);
            border-radius:10px;
            background:var(--gd-surface-soft);
        }
        .filter-bar .filter-input,.filter-bar .field,
        .accounts-toolbar .filter-input,.accounts-toolbar .field,
        .course-toolbar .filter-input{
            min-height:38px;
            border:1px solid #dce4de;
            border-radius:8px;
            background:#fff;
            color:#34463a;
            font-size:11.5px;
        }
        .filter-bar input[type="search"],
        .accounts-toolbar input[type="search"]{flex:1 1 220px;min-width:180px}

        /* Tablas: una superficie, encabezados discretos y lectura alineada */
        .table-wrap{
            overflow:auto;
            border:1px solid var(--gd-border);
            border-radius:10px;
        }
        .table-wrap table{width:100%;border-collapse:separate;border-spacing:0}
        .table-wrap thead th{
            position:relative;
            padding:10px 12px;
            border-bottom:1px solid var(--gd-border);
            background:#f5f8f6;
            color:#53665a;
            font-size:10.5px;
            font-weight:760;
            text-align:left;
            white-space:nowrap;
        }
        .table-wrap tbody td{
            padding:10px 12px;
            border-bottom:1px solid #edf1ee;
            color:#425247;
            font-size:11.5px;
            vertical-align:middle;
        }
        .table-wrap tbody tr:last-child td{border-bottom:0}
        .table-wrap tbody tr:hover td{background:#fafcfb}

        /* Vacíos y estados */
        .empty{
            padding:30px 18px!important;
            border:1px dashed #d7e1da;
            border-radius:10px;
            background:#fafcfb;
            color:#708077;
            text-align:center;
        }
        .empty strong{display:block;color:#405449;font-size:13px}
        .empty>span{display:block;max-width:52ch;margin:6px auto 0;font-size:11px;line-height:1.55}
        .section-status{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:5px;
            flex:none;
            padding:5px 9px;
            border:1px solid;
            border-radius:999px;
            font-size:10px;
            font-weight:750;
            line-height:1.2;
            white-space:nowrap;
        }
        .section-status-symbol{font-size:11px;font-weight:900}
        .section-status-complete{color:#24734b;background:#edf8f0;border-color:#cfe8d6}
        .section-status-partial{color:#98651d;background:#fff7e8;border-color:#f1dfb8}
        .section-status-pending{color:#68747c;background:#f3f5f6;border-color:#dce2e5}

        /* Modo oscuro: conservar el mismo contraste y jerarquía */
        html[data-theme="dark"] .page-head h1,
        html[data-theme="dark"] .card .card-head h2,
        html[data-theme="dark"] .card h3:not(.cycle-item-title){color:#e2ebe4!important}
        html[data-theme="dark"] .page-head .subhead,
        html[data-theme="dark"] .breadcrumbs{color:#a5b5aa!important}
        html[data-theme="dark"] .breadcrumbs .current{color:#e2ebe4}
        html[data-theme="dark"] .card{background:#16251e!important;border-color:#34483c!important;box-shadow:0 1px 2px rgba(0,0,0,.16)!important}
        html[data-theme="dark"] .card>.card-head{border-bottom-color:#2b3d32}
        html[data-theme="dark"] .card .card-description{color:#a5b5aa}
        html[data-theme="dark"] .stat-card{background:#192920!important;border-color:#34483c!important}
        html[data-theme="dark"] .stat-card .stat{color:#e2ebe4!important}
        html[data-theme="dark"] .filter-bar,
        html[data-theme="dark"] .accounts-toolbar,
        html[data-theme="dark"] .course-toolbar{background:#17251f;border-color:#34483c}
        html[data-theme="dark"] .filter-bar .filter-input,
        html[data-theme="dark"] .filter-bar .field,
        html[data-theme="dark"] .accounts-toolbar .filter-input,
        html[data-theme="dark"] .accounts-toolbar .field,
        html[data-theme="dark"] .course-toolbar .filter-input{background:#101d17;color:#e0e9e3;border-color:#405548}
        html[data-theme="dark"] .table-wrap{border-color:#34483c}
        html[data-theme="dark"] .table-wrap thead th{background:#1d3026;color:#c2d8c9;border-color:#34483c}
        html[data-theme="dark"] .table-wrap tbody td{color:#c2d1c7;border-color:#293d32}
        html[data-theme="dark"] .table-wrap tbody tr:hover td{background:#1a2b21}
        html[data-theme="dark"] .empty{background:#17251f;border-color:#405548;color:#a9bcb0}
        html[data-theme="dark"] .empty strong{color:#d7e6dc}
        html[data-theme="dark"] .section-status-complete{color:#b9e9ca;background:#173a29;border-color:#285a40}
        html[data-theme="dark"] .section-status-partial{color:#f1d49a;background:#3b2e17;border-color:#65502a}
        html[data-theme="dark"] .section-status-pending{color:#c0cbd0;background:#26343a;border-color:#40515a}

        @media(max-width:700px){
            .btn{min-height:44px}
            .btn.btn-sm{min-height:40px}
            .weekday{min-height:44px;display:inline-flex;align-items:center;justify-content:center}
            .page-head{align-items:flex-start!important;flex-direction:column!important}
            .page-head .actions{width:100%;justify-content:flex-start}
            .card>.card-head{align-items:flex-start;flex-direction:column}
            .filter-bar,.accounts-toolbar,.course-toolbar{align-items:stretch!important;flex-direction:column!important}
            .filter-bar .filter-input,.filter-bar .field,
            .accounts-toolbar .filter-input,.accounts-toolbar .field,
            .course-toolbar .filter-input{width:100%;max-width:none!important}
        }
        @media(prefers-reduced-motion:reduce){
            .btn{transition:none!important}
            .btn:active:not(:disabled){transform:none}
        }
    </style>
</head>
<body style="--accent: {{ $appBranding['primary_color'] ?? '#176B87' }}; --accent-hover: {{ $appBranding['primary_hover'] ?? '#10546B' }};">

<svg class="icon-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
 <symbol id="icon-layout-dashboard" viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></symbol>
 <symbol id="icon-calendar-days" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/></symbol>
 <symbol id="icon-calendar-check" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14l2 2 4-4"/></symbol>
 <symbol id="icon-calendar-range" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3m2 0h3m-8 3h3"/></symbol>
 <symbol id="icon-users-round" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M16 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3 4.5V20"/></symbol>
 <symbol id="icon-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
 <symbol id="icon-book-open" viewBox="0 0 24 24"><path d="M12 7v14M3 18V5a1 1 0 0 1 1-1h3a5 5 0 0 1 5 5 5 5 0 0 1 5-5h3a1 1 0 0 1 1 1v13h-4a5 5 0 0 0-5 3 5 5 0 0 0-5-3H3z"/></symbol>
 <symbol id="icon-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
 <symbol id="icon-arrow-left" viewBox="0 0 24 24"><path d="m12 19-7-7 7-7M5 12h14"/></symbol>
 <symbol id="icon-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14m-7-7 7 7-7 7"/></symbol>
 <symbol id="icon-chevron-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
 <symbol id="icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
 <symbol id="icon-x" viewBox="0 0 24 24"><path d="m18 6-12 12M6 6l12 12"/></symbol>
 <symbol id="icon-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol>
 <symbol id="icon-filter" viewBox="0 0 24 24"><path d="M4 6h16M7 12h10m-7 6h4"/></symbol>
 <symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
 <symbol id="icon-layers" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></symbol>
 <symbol id="icon-graduation-cap" viewBox="0 0 24 24"><path d="m2 10 10-5 10 5-10 5-10-5z"/><path d="M6 12v5c3.5 3 8.5 3 12 0v-5m4-2v6"/></symbol>
 <symbol id="icon-file-text" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></symbol>
 <symbol id="icon-clipboard-check" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1zM8 13l2.5 2.5L16 10"/></symbol>
 <symbol id="icon-log-out" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></symbol>
 <symbol id="icon-circle-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></symbol>
 <symbol id="icon-circle-alert" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></symbol>
 <symbol id="icon-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></symbol>
 <symbol id="icon-circle-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.2 1.8c-1.2 1.1-1.7 1.5-1.7 3.2m0 3h.01"/></symbol>
 <symbol id="icon-building-2" viewBox="0 0 24 24"><path d="M3 21V5l9-3 9 3v16M3 21h18M8 8h1m6 0h1M8 12h1m6 0h1M10 21v-4h4v4"/></symbol>
 <symbol id="icon-arrow-up-right" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></symbol>
 <symbol id="icon-sparkles" viewBox="0 0 24 24"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3zM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15z"/></symbol>
 <symbol id="icon-panel-left" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15 9l-3 3 3 3"/></symbol>
 <symbol id="icon-user-round" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
 <symbol id="icon-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 2z"/></symbol>
 <symbol id="icon-history" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5m4-1v5l3 2"/></symbol>
 <symbol id="icon-moon" viewBox="0 0 24 24"><path d="M20.5 15.2A8.5 8.5 0 0 1 8.8 3.5 8.5 8.5 0 1 0 20.5 15.2z"/></symbol>
 <symbol id="icon-shield-check" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="m9 12 2 2 4-4"/></symbol>
 <symbol id="icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></symbol>
</svg>

<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">
        @if(!empty($appBranding['logo_path']))
            <img class="brand-logo" src="{{ asset('storage/'.$appBranding['logo_path']) }}" alt="Logo del sistema">
        @else
            <span class="brand-mark" style="background:var(--accent-soft);color:var(--accent)">{{ $appBranding['short_name'] ?? 'GD' }}</span>
        @endif
        <span>{{ $appBranding['name'] ?? 'Gestión Docente' }}<small>{{ $appBranding['subtitle'] ?? 'Organización académica' }}</small></span>
    </a>
    <div class="top-actions">
        <button class="top-theme-toggle" type="button" data-theme-toggle aria-label="Cambiar tema" title="Cambiar tema"><x-icon name="moon" size="16" /><span>Modo oscuro</span></button>
        <a class="top-user" href="{{ route('profile.edit') }}">{{ auth()->user()->name ?? '' }} · Mi perfil</a>
        <button class="top-help" type="button" data-modal-open="workflow-help-dialog"><x-icon name="circle-help" size="15" /> Ayuda</button>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit"><x-icon name="log-out" size="15" />Cerrar sesión</button></form>
    </div>
</header>
<div class="app-shell">
    <aside class="sidebar" id="app-sidebar" aria-label="Navegación principal">
        <div class="sidebar-inner">
            <div class="sidebar-toolbar">
                <span class="sidebar-toolbar-label">MENÚ PRINCIPAL</span>
                <button class="sidebar-icon-button" type="button" id="sidebar-collapse" aria-label="Contraer menú" aria-expanded="true" title="Contraer menú">
                    <x-icon name="panel-left" size="17" />
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-label">Espacio de trabajo</div>
                <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Resumen">
                    <x-icon name="layout-dashboard" /><span>Resumen</span>
                </a>
                <a class="nav-link {{ request()->routeIs('cycles.*','groups.*','assignments.*','modalities.*','evaluations.*','curricula.*','students.*','documents.*','distribution.*') ? 'active' : '' }}" href="{{ route('cycles.index') }}" title="Ciclos y cursos">
                    <x-icon name="calendar-days" /><span>Ciclos y cursos</span>
                </a>
                <a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}" title="Feriados">
                    <x-icon name="calendar-range" /><span>Feriados</span>
                </a>

                <div class="nav-label nav-section-label">Mi cuenta</div>
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}" title="Mi perfil">
                    <x-icon name="user-round" /><span>Mi perfil</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="nav-label nav-section-label">Administración</div>
                    <a class="nav-link {{ request()->routeIs('admin.professors.*') ? 'active' : '' }}" href="{{ route('admin.professors.index') }}" title="Profesores y cuentas">
                        <x-icon name="users-round" /><span>Profesores y cuentas</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}" title="Auditoría">
                        <x-icon name="history" /><span>Auditoría</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}" title="Ajustes del sistema">
                        <x-icon name="settings" /><span>Ajustes del sistema</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-preferences">
                <div class="sidebar-width-setting">
                    <label for="sidebar-width">Ancho del menú</label>
                    <div class="sidebar-width-row">
                        <input type="range" id="sidebar-width" min="200" max="330" step="10" value="250" aria-label="Cambiar ancho del menú">
                        <output id="sidebar-width-value" for="sidebar-width">250 px</output>
                    </div>
                </div>
                <button class="sidebar-preference-button" type="button" id="theme-toggle" data-theme-toggle aria-pressed="false">
                    <x-icon name="moon" size="16" /><span id="theme-toggle-label">Modo oscuro</span>
                    <small id="theme-toggle-state">Desactivado</small>
                </button>
            </div>
        </div>
    </aside>
    <main class="main">

        @yield('content')
    </main>
</div>

@php
    // Consumir los mensajes flash al renderizar evita reutilizarlos en otra respuesta.
    $flashSuccess = session()->pull('success');
    $flashError = session()->pull('error');
    $flashInfo = session()->pull('info');
@endphp

@if($flashSuccess !== null || $flashError !== null || $flashInfo !== null)
    @php
        $noticeType = $flashError !== null ? 'error' : ($flashInfo !== null ? 'info' : 'success');
        $noticeTitle = $noticeType === 'error' ? 'No se pudo completar' : ($noticeType === 'info' ? 'Información' : '¡Listo! Se guardó correctamente');
        $noticeMessage = $flashError ?? $flashInfo ?? $flashSuccess;
    @endphp
    <div class="app-notice-wrap" aria-live="polite" aria-atomic="true">
        <section class="app-notice app-notice-{{ $noticeType }}" id="app-notice" role="{{ $noticeType === 'error' ? 'alert' : 'status' }}">
            <span class="app-notice-icon" aria-hidden="true">
                @if($noticeType === 'error')<x-icon name="circle-alert" size="20" />
                @elseif($noticeType === 'info')<x-icon name="info" size="20" />
                @else<x-icon name="check" size="20" />@endif
            </span>
            <div class="app-notice-copy">
                <strong>{{ $noticeTitle }}</strong>
                <p>{{ $noticeMessage }}</p>
            </div>
            <button class="app-notice-close" type="button" data-alert-close aria-label="Cerrar aviso"><x-icon name="x" size="15" /></button>
            <span class="app-notice-progress" aria-hidden="true"><span></span></span>
        </section>
    </div>
@endif

<dialog class="ui-dialog confirm-dialog" id="app-confirm-dialog" aria-labelledby="app-confirm-title">
    <div class="dialog-head"><div><h2 id="app-confirm-title">Confirmar acción</h2><p id="app-confirm-message">¿Deseas continuar?</p></div><button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" size="15" /></button></div>
    <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="button" id="app-confirm-submit"><x-icon name="check" /> Continuar</button></div>
</dialog>


<dialog class="ui-dialog sheet-dialog" id="workflow-help-dialog" aria-labelledby="workflow-help-title">
    <div class="dialog-head">
        <div>
            <p class="eyebrow">Ayuda rápida</p>
            <h2 id="workflow-help-title">¿Cómo se organiza tu trabajo?</h2>
            <p>El sistema conecta tus periodos, grupos y cursos en un mismo lugar.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <div class="dialog-body">
        <p class="workflow-intro">Sigue este orden para configurar un periodo sin repetir información. Puedes volver a cada sección cuando necesites hacer cambios.</p>
        <div class="modal-help-grid">
            <div class="modal-help-item">
                <span class="modal-step-index">01</span>
                <span class="workflow-icon"><x-icon name="calendar-days" /></span>
                <div><strong>Crear el ciclo</strong><p>Indica el año, código y rango general del periodo académico.</p></div>
            </div>
            <div class="modal-help-item">
                <span class="modal-step-index">02</span>
                <span class="workflow-icon"><x-icon name="users" /></span>
                <div><strong>Registrar los grupos</strong><p>Agrega las secciones que tienes asignadas para ese ciclo.</p></div>
            </div>
            <div class="modal-help-item">
                <span class="modal-step-index">03</span>
                <span class="workflow-icon"><x-icon name="book-open" /></span>
                <div><strong>Asignar los cursos</strong><p>Vincula cada curso con su modalidad: teoría, taller o ambas.</p></div>
            </div>
            <div class="modal-help-item">
                <span class="modal-step-index">04</span>
                <span class="workflow-icon"><x-icon name="clipboard-check" /></span>
                <div><strong>Completar la planificación</strong><p>Registra horarios y fechas, importa alumnos, prepara sesiones y genera los formatos.</p></div>
            </div>
        </div>
    </div>
    <div class="dialog-footer"><button class="btn" type="button" data-modal-close>Entendido</button></div>
</dialog>

<script>
(() => {
    const root = document.documentElement;
    const body = document.body;
    const collapseButton = document.getElementById('sidebar-collapse');
    const widthInput = document.getElementById('sidebar-width');
    const widthOutput = document.getElementById('sidebar-width-value');
    const themeButtons = [...document.querySelectorAll('[data-theme-toggle]')];

    const storage = {
        get(key, fallback) { try { return localStorage.getItem(key) ?? fallback; } catch (_) { return fallback; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch (_) {} }
    };

    const savedWidth = Math.min(330, Math.max(200, Number(storage.get('gd-sidebar-width', '250')) || 250));
    root.style.setProperty('--sidebar-width', savedWidth + 'px');
    if (widthInput) widthInput.value = String(savedWidth);
    if (widthOutput) widthOutput.value = savedWidth + ' px';
    widthInput?.addEventListener('input', () => {
        const width = Math.min(330, Math.max(200, Number(widthInput.value) || 250));
        root.style.setProperty('--sidebar-width', width + 'px');
        if (widthOutput) widthOutput.value = width + ' px';
        storage.set('gd-sidebar-width', String(width));
    });

    const setCollapsed = collapsed => {
        body.classList.toggle('sidebar-collapsed', collapsed);
        if (collapseButton) {
            collapseButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            collapseButton.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
            collapseButton.title = collapsed ? 'Expandir menú' : 'Contraer menú';
        }
        storage.set('gd-sidebar-collapsed', collapsed ? '1' : '0');
    };
    setCollapsed(storage.get('gd-sidebar-collapsed', '0') === '1');
    collapseButton?.addEventListener('click', () => setCollapsed(!body.classList.contains('sidebar-collapsed')));

    const setTheme = dark => {
        root.dataset.theme = dark ? 'dark' : 'light';
        themeButtons.forEach(button => {
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            const icon = button.querySelector('.icon use');
            if (icon) icon.setAttribute('href', dark ? '#icon-sun' : '#icon-moon');
            const label = button.querySelector('#theme-toggle-label');
            const state = button.querySelector('#theme-toggle-state');
            const topLabel = button.querySelector('span:not(#theme-toggle-label):not(#theme-toggle-state)');
            if (label) label.textContent = dark ? 'Modo claro' : 'Modo oscuro';
            if (state) state.textContent = dark ? 'Activado' : 'Desactivado';
            if (topLabel) topLabel.textContent = dark ? 'Modo claro' : 'Modo oscuro';
        });
        storage.set('gd-theme', dark ? 'dark' : 'light');
    };
    setTheme(storage.get('gd-theme', root.dataset.theme || 'light') === 'dark');
    themeButtons.forEach(button => button.addEventListener('click', () => setTheme(root.dataset.theme !== 'dark')));

    let pendingForm = null;
    let lastModalTrigger = null;
    const dismissNotice = notice => {
        if (!notice || notice.classList.contains('is-leaving')) return;
        notice.classList.add('is-leaving');
        window.setTimeout(() => notice.remove(), 250);
    };
    const activeNotice = document.getElementById('app-notice');
    if (activeNotice && !activeNotice.classList.contains('app-notice-error')) {
        window.setTimeout(() => dismissNotice(activeNotice), 5200);
    }

    const confirmDialog = document.getElementById('app-confirm-dialog');
    const confirmMessage = document.getElementById('app-confirm-message');
    const confirmSubmit = document.getElementById('app-confirm-submit');

    document.addEventListener('click', event => {
        const opener = event.target.closest('[data-modal-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.modalOpen);
            if (dialog && typeof dialog.showModal === 'function') {
                lastModalTrigger = opener;
                dialog.showModal();
                window.requestAnimationFrame(() => dialog.querySelector('input:not([type="hidden"]),select,textarea,button:not([data-modal-close])')?.focus());
            }
        }
        const closer = event.target.closest('[data-modal-close]');
        if (closer) closer.closest('dialog')?.close();
        const alertClose = event.target.closest('[data-alert-close]');
        if (alertClose) {
            const notice = alertClose.closest('.app-notice');
            if (notice) dismissNotice(notice);
            else alertClose.closest('.flash')?.remove();
        }
    });

    // Protección centralizada para todos los formularios que modifican datos.
    // Cubre envíos rápidos por Enter, doble clic y acciones que requieren confirmación.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const method = (form.getAttribute('method') || 'get').toLowerCase();
        const isMutation = !['get', 'dialog'].includes(method);
        if (!isMutation && form.dataset.loadingForm !== '1') return;

        // Primero se confirma la acción; el bloqueo se aplica al confirmar.
        if (form.dataset.confirm && form.dataset.confirmed !== '1') {
            event.preventDefault();
            if (pendingForm === form) return;

            pendingForm = form;
            if (confirmMessage) confirmMessage.textContent = form.dataset.confirm;
            confirmDialog?.showModal();
            return;
        }

        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
        }

        // El primer submit bloquea los siguientes para este formulario.
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = '1';
        form.classList.add('form-submitting');
        form.setAttribute('aria-busy', 'true');

        const submitter = event.submitter;
        if (submitter instanceof HTMLElement) {
            submitter.classList.add('is-loading');
            submitter.setAttribute('aria-busy', 'true');
        }
    });

    confirmSubmit?.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = '1';
        confirmDialog.close();
        form.requestSubmit();
    });

    confirmDialog?.addEventListener('close', () => { pendingForm = null; });

    // Chrome puede restaurar páginas completas desde BFCache al usar Atrás/Adelante.
    // Al restaurarlas se descarta el toast antiguo y se liberan formularios pendientes.
    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;

        document.getElementById('app-notice')?.remove();

        document.querySelectorAll('form[data-submitting="1"]').forEach(form => {
            delete form.dataset.submitting;
            form.classList.remove('form-submitting');
            form.removeAttribute('aria-busy');

            form.querySelectorAll('.is-loading[aria-busy="true"]').forEach(button => {
                button.classList.remove('is-loading');
                button.removeAttribute('aria-busy');
            });
        });
    });

    document.querySelectorAll('dialog').forEach(dialog => {
        // Escape no cierra el modal: debe usarse el botón Cerrar o Cancelar.
        dialog.addEventListener('cancel', event => event.preventDefault());
        dialog.addEventListener('close', () => {
            if (lastModalTrigger?.isConnected) lastModalTrigger.focus();
        });
    });

    document.querySelectorAll('dialog[data-auto-open]').forEach(dialog => {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
            window.requestAnimationFrame(() => dialog.querySelector('input:not([type="hidden"]),select,textarea')?.focus());
        }
    });
})();
</script>
@stack('scripts')
</body>
</html>