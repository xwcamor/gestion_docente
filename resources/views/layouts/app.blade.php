<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Gestión Docente</title>
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
    </style>
    @stack('styles')
</head>
<body>

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
</svg>

<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">
        <span class="brand-mark">GD</span>
        <span>Gestión Docente<small>Organización académica</small></span>
    </a>
    <div class="top-actions">
        <span class="top-user">{{ auth()->user()->name ?? '' }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit"><x-icon name="log-out" size="15" />Cerrar sesión</button></form>
    </div>
</header>
<div class="app-shell">
    <aside class="sidebar">
        <div>
            <div class="nav-label">Espacio de trabajo</div>
            <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><x-icon name="layout-dashboard" />Resumen</a>
            <a class="nav-link {{ request()->routeIs('cycles.*','groups.*','assignments.*','modalities.*','evaluations.*','curricula.*','students.*','documents.*','distribution.*') ? 'active' : '' }}" href="{{ route('cycles.index') }}"><x-icon name="calendar-days" />Ciclos y cursos</a>
            <a class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}" href="{{ route('holidays.index') }}"><x-icon name="calendar-range" />Feriados</a>
            @if(auth()->user()->isAdmin())
                <div class="nav-label" style="margin-top:15px">Administración</div>
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.professors.index') }}"><x-icon name="users-round" />Profesores y cuentas</a>
            @endif
            <div class="sidebar-note">
                <strong><x-icon name="clipboard-check" size="13" /> Tu flujo de trabajo</strong>
                <div><x-icon name="calendar-days" size="12" /> 1. Ciclo académico</div>
                <div><x-icon name="users" size="12" /> 2. Grupo asignado</div>
                <div><x-icon name="book-open" size="12" /> 3. Curso y modalidades</div>
                <div><x-icon name="file-text" size="12" /> 4. Alumnos y documentos</div>
            </div>
        </div>
    </aside>
    <main class="main">
        @if(session('success') || session('error') || session('info') || ($errors->any() && !request()->routeIs('holidays.index', 'admin.professors.index', 'cycles.index', 'cycles.show')))
        <div class="flash-stack" aria-live="polite" aria-atomic="false">
            @if(session('success'))
                <div class="flash flash-success" data-auto-dismiss role="status"><span class="flash-mark" aria-hidden="true"><x-icon name="check" size="12" /></span><div class="flash-content"><strong>Listo</strong>{{ session('success') }}</div><button class="flash-close" type="button" data-alert-close aria-label="Cerrar mensaje"><x-icon name="x" size="13" /></button></div>
            @endif
            @if(session('error'))
                <div class="flash flash-error" role="alert"><span class="flash-mark" aria-hidden="true"><x-icon name="circle-alert" size="12" /></span><div class="flash-content"><strong>No se pudo completar</strong>{{ session('error') }}</div><button class="flash-close" type="button" data-alert-close aria-label="Cerrar mensaje">×</button></div>
            @endif
            @if(session('info'))
                <div class="flash flash-info" data-auto-dismiss role="status"><span class="flash-mark" aria-hidden="true"><x-icon name="info" size="12" /></span><div class="flash-content">{{ session('info') }}</div><button class="flash-close" type="button" data-alert-close aria-label="Cerrar mensaje">×</button></div>
            @endif
            @if($errors->any() && !request()->routeIs('holidays.index', 'admin.professors.index', 'cycles.index', 'cycles.show'))
                <div class="flash flash-error" role="alert"><span class="flash-mark" aria-hidden="true"><x-icon name="circle-alert" size="12" /></span><div class="flash-content"><strong>Revisa los datos ingresados</strong><ul style="margin:5px 0 0;padding-left:17px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button class="flash-close" type="button" data-alert-close aria-label="Cerrar mensaje">×</button></div>
            @endif
        </div>
        @endif
        @yield('content')
    </main>
</div>

<dialog class="ui-dialog confirm-dialog" id="app-confirm-dialog" aria-labelledby="app-confirm-title" data-backdrop-close>
    <div class="dialog-head"><div><h2 id="app-confirm-title">Confirmar acción</h2><p id="app-confirm-message">¿Deseas continuar?</p></div><button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" size="15" /></button></div>
    <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="button" id="app-confirm-submit"><x-icon name="check" /> Continuar</button></div>
</dialog>


<dialog class="ui-dialog sheet-dialog" id="workflow-help-dialog" aria-labelledby="workflow-help-title" data-backdrop-close>
    <div class="dialog-head">
        <div>
            <p class="eyebrow">Ayuda rápida</p>
            <h2 id="workflow-help-title">¿Cómo se organiza tu trabajo?</h2>
            <p>El sistema conecta tus periodos, grupos y cursos en un mismo lugar.</p>
        </div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <div class="dialog-body">
        <div class="modal-help-grid">
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="calendar-days" /></span><div><strong>Ciclos</strong><p>Registra el año, el código y las fechas del periodo académico.</p></div></div>
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="users" /></span><div><strong>Grupos</strong><p>Agrega las secciones que tienes asignadas dentro de cada ciclo.</p></div></div>
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="book-open" /></span><div><strong>Cursos</strong><p>Asigna cursos a cada grupo y define si trabajan con TEC, TALL o ambas.</p></div></div>
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="clipboard-check" /></span><div><strong>Gestión académica</strong><p>Configura horarios, importa alumnos, registra notas y genera documentos.</p></div></div>
        </div>
    </div>
    <div class="dialog-footer"><button class="btn" type="button" data-modal-close>Entendido</button></div>
</dialog>

<script>
(() => {
    let pendingForm = null;
    let lastModalTrigger = null;
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
        if (alertClose) alertClose.closest('.flash')?.remove();
    });

    document.addEventListener('click', event => {
        if (event.target instanceof HTMLDialogElement && event.target.hasAttribute('data-backdrop-close')) {
            event.target.close();
        }
    });

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        pendingForm = form;
        confirmMessage.textContent = form.dataset.confirm;
        confirmDialog?.showModal();
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
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.addEventListener('close', () => {
            if (lastModalTrigger?.isConnected) lastModalTrigger.focus();
        });
    });

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.loadingForm !== '1') return;
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = '1';
        const submit = form.querySelector('button[type="submit"]:not([disabled])');
        if (submit) {
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
        }
    });

    document.querySelectorAll('.flash[data-auto-dismiss]').forEach(message => {
        window.setTimeout(() => {
            if (message.isConnected) {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-3px)';
                window.setTimeout(() => message.remove(), 130);
            }
        }, 7500);
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