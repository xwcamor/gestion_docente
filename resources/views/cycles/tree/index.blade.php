@extends('layouts.app')
@section('title', $cycle->academic_year.' · Ciclo '.$cycle->cycle_code.' · Árbol de trabajo')
@section('content')
<div class="workload-tree-page">
    <div class="breadcrumbs">
        <a href="{{ route('cycles.index') }}">Cargas laborales</a>
        <x-icon name="chevron-right" size="12" />
        <span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span>
    </div>

    <section class="workload-tree-hero">
        <div class="workload-tree-hero-copy">
            <p class="eyebrow"><x-icon name="git-branch" size="13" /> EDITOR DE CARGA LABORAL</p>
            <h1>{{ $cycle->academic_year }} <span>· Ciclo {{ $cycle->cycle_code }}</span></h1>
            <div class="workload-tree-context">
                <span><x-icon name="graduation-cap" size="14" /> {{ $cycle->career ?: 'Carrera pendiente' }}</span>
                <span>Semestre {{ $cycle->semester ?: '—' }}</span>
                <span><x-icon name="calendar-range" size="14" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="workload-tree-hero-actions">
            <a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}"><x-icon name="settings" /> Datos del ciclo</a>

        </div>
    </section>

    <div class="tree-workspace-grid">
        <section class="card tree-structure-card">
            <div class="tree-panel-heading">
                <div>
                    <p class="eyebrow">PANEL 1</p>
                    <h2>Estructura</h2>
                    <p>Organiza los grupos y agrega los cursos. No necesitas completar códigos ni horarios aquí.</p>
                </div>
                <span class="workload-tree-total" data-tree-group-total>{{ $cycleGroupCount }} grupos</span>
            </div>

            <form method="POST" action="{{ route('groups.store', $cycle) }}" class="tree-add-group-form">
                @csrf
                <input type="hidden" name="return_to_tree" value="1">
                <input class="field" name="name" maxlength="100" required placeholder="Nombre del grupo nuevo…">
                <button class="btn" type="submit"><x-icon name="plus" size="14" /> Crear grupo</button>
            </form>

            <form method="GET" action="{{ route('cycles.tree', $cycle) }}" class="workload-tree-search">
                <label class="workload-tree-search-field">
                    <x-icon name="search" size="17" />
                    <input type="search" name="q" value="{{ $term }}" placeholder="Buscar grupo o curso…" aria-label="Buscar en el árbol">
                </label>
                <button class="btn btn-secondary" type="submit">Buscar</button>
                @if($term !== '')
                    <a class="btn btn-ghost" href="{{ route('cycles.tree', $cycle) }}"><x-icon name="x" /> Limpiar</a>
                @endif
            </form>

            <div class="workload-tree-root">
                @include('cycles.tree.groups', ['groups' => $groups, 'cycle' => $cycle])
            </div>
        </section>

        <section class="card tree-editor-card" aria-live="polite">
            <div class="tree-panel-heading">
                <div>
                    <p class="eyebrow">PANEL 2</p>
                    <h2>Edición rápida</h2>
                    <p>Selecciona un grupo o curso a la izquierda. Doble clic sobre un dato para cambiarlo.</p>
                </div>
                <span class="tree-editor-indicator"><span></span> Editor</span>
            </div>

            <div class="tree-editor-empty" id="tree-editor-empty">
                <span class="tree-editor-empty-icon"><x-icon name="info" size="22" /></span>
                <strong>¿Qué deseas editar?</strong>
                <p>Selecciona un grupo para cambiar su nombre o un curso para editar su nombre, código, TEC, TAL, NRC y fechas.</p>
            </div>

            <div class="tree-editor-content" id="tree-editor-content" hidden>
                <div class="tree-editor-breadcrumb" id="tree-editor-kind">GRUPO</div>
                <h3 id="tree-editor-title">Seleccionado</h3>
                <p class="tree-editor-description" id="tree-editor-description"></p>
                <div class="tree-editor-fields" id="tree-editor-fields"></div>
            </div>
        </section>
    </div>
    <p class="tree-live-feedback" id="tree-live-feedback" role="status" hidden></p>
</div>
@endsection

@push('styles')
<style>
.workload-tree-page{width:100%;max-width:none}
.workload-tree-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:0 0 14px;padding:clamp(19px,2.2vw,30px);border:1px solid color-mix(in srgb,var(--accent) 34%,var(--line));border-radius:16px;background:linear-gradient(120deg,var(--accent-soft),var(--surface) 72%);box-shadow:0 5px 22px rgba(25,56,78,.055)}
.workload-tree-hero-copy{min-width:0}
.workload-tree-hero .eyebrow{margin:0 0 8px;color:var(--accent);font-size:9px;font-weight:850;letter-spacing:.13em}
.workload-tree-hero h1{margin:0;color:var(--ink);font-size:clamp(29px,3.4vw,44px);font-weight:850;letter-spacing:-.055em;line-height:1.04}
.workload-tree-hero h1 span{color:var(--muted);font-size:.68em;font-weight:650;letter-spacing:-.035em}
.workload-tree-context{display:flex;align-items:center;gap:8px 18px;flex-wrap:wrap;margin-top:13px;color:var(--muted);font-size:11px}
.workload-tree-context span{display:inline-flex;align-items:center;gap:6px}
.workload-tree-hero-actions{display:flex;align-items:flex-start;gap:8px;flex:none;flex-wrap:wrap}
.workload-tree-hero-actions>.btn{min-height:40px}
.tree-inline-create-group{position:relative}
.tree-inline-create-group>summary{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:10px 14px;border:1px solid color-mix(in srgb,var(--accent) 45%,var(--line));border-radius:9px;background:var(--accent);color:#fff;font-size:11px;font-weight:800;cursor:pointer;list-style:none;box-shadow:0 3px 10px color-mix(in srgb,var(--accent) 22%,transparent);transition:transform .15s ease,box-shadow .15s ease}
.tree-inline-create-group>summary::-webkit-details-marker{display:none}
.tree-inline-create-group>summary:hover{transform:translateY(-1px);box-shadow:0 6px 15px color-mix(in srgb,var(--accent) 28%,transparent)}
.tree-group-create-form{position:absolute;z-index:20;top:calc(100% + 9px);right:0;display:grid;grid-template-columns:minmax(190px,1fr) auto;align-items:end;gap:10px;width:min(480px,calc(100vw - 28px));padding:14px;border:1px solid color-mix(in srgb,var(--accent) 35%,var(--line));border-radius:12px;background:var(--surface);box-shadow:0 18px 45px rgba(15,30,45,.18)}
.tree-entry-field{display:grid;gap:6px;min-width:0;color:var(--ink-soft);font-size:10px;font-weight:750}
.tree-entry-field .field{width:100%;min-width:0}
.tree-entry-field small{color:var(--muted);font-size:9px;font-weight:450}
.tree-group-create-form .btn{min-height:39px;white-space:nowrap}
.workload-tree-browser{padding:clamp(14px,1.8vw,22px);border-color:var(--line-strong);border-radius:14px}
.workload-tree-browser-head{align-items:flex-end;margin-bottom:13px}
.workload-tree-browser-head .eyebrow{margin-bottom:5px;color:var(--accent);font-size:9px;font-weight:850;letter-spacing:.12em}
.workload-tree-browser-head h2{color:var(--ink);font-size:21px;font-weight:800;letter-spacing:-.03em}
.workload-tree-total{flex:none;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:10px;font-weight:750}
.workload-tree-search{display:flex;gap:8px;margin-bottom:14px}
.workload-tree-search-field{display:flex;align-items:center;gap:9px;flex:1;min-width:0;height:42px;margin:0;padding:0 12px;border:1px solid var(--line-strong);border-radius:9px;background:var(--surface);color:var(--muted);transition:border-color .15s ease,box-shadow .15s ease}
.workload-tree-search-field:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 15%,transparent)}
.workload-tree-search-field input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--ink);font-size:12px}
.workload-tree-search-field input:focus{box-shadow:none}
.workload-tree-search-field input::placeholder{color:var(--faint)}
.workload-tree-search .btn{min-height:42px}
.workload-tree-root{display:grid;gap:10px}
.workload-tree-root>.workload-tree-group-shell{margin:0}
.workload-tree-root>.tree-empty-child{padding:34px 16px;border:1px dashed var(--line-strong);border-radius:10px;text-align:center}
.workload-tree-group-shell{position:relative;overflow:visible;border:1px solid var(--line-strong);border-radius:12px;background:var(--surface);transition:border-color .16s ease,box-shadow .16s ease,background .16s ease}
.workload-tree-group-shell.is-focused{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent),0 8px 22px rgba(28,63,88,.075)}
.workload-tree-group{margin:0;border:0;border-radius:12px;background:var(--surface);overflow:visible}
.workload-group-summary{min-height:66px;padding:12px 158px 12px 14px;border-radius:12px;background:var(--surface);transition:background .15s ease}
.workload-tree-summary{display:flex;align-items:center;gap:10px;min-width:0;list-style:none;cursor:pointer}
.workload-tree-summary::-webkit-details-marker{display:none}
.workload-tree-summary:focus-visible{outline:2px solid var(--accent);outline-offset:-2px;border-radius:8px}
.workload-tree-summary:hover{background:var(--accent-soft)}
.workload-tree-group[open]>.workload-group-summary{border-bottom:1px solid color-mix(in srgb,var(--accent) 28%,var(--line));border-radius:12px 12px 0 0;background:linear-gradient(90deg,var(--accent-soft),var(--surface))}
.workload-tree-group[open]>.workload-group-summary .tree-node-icon{background:var(--accent);color:#fff}
.workload-tree-children{position:relative;margin-left:25px;padding:10px 13px 14px 17px;border-left:2px solid color-mix(in srgb,var(--accent) 36%,var(--line))}
.workload-group-children{margin:0 0 0 22px;padding:11px 12px 14px 16px}
.workload-tree-group-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:0 0 9px}
.workload-tree-group-actions .btn{min-height:32px;padding:6px 9px;font-size:10px}
.tree-node-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;transition:background .15s ease,color .15s ease}
.tree-group-icon{background:var(--info-bg);color:var(--info)}
.tree-course-icon{background:var(--accent-soft);color:var(--accent)}
.tree-node-copy{display:grid;gap:3px;min-width:0;flex:1}
.tree-node-copy strong{color:var(--ink);font-size:13px;font-weight:800;line-height:1.35;overflow-wrap:anywhere}
.tree-node-copy small{color:var(--muted);font-size:10px;line-height:1.45;overflow-wrap:anywhere}
.tree-branch-mark{position:relative;flex:0 0 11px;width:11px;height:1px;background:var(--line-strong)}
.tree-chevron{display:grid;place-items:center;flex:none;color:var(--muted);transition:transform .16s ease,color .16s ease}
.workload-tree-group[open]>.workload-tree-summary .tree-chevron,
.workload-tree-course[open]>.workload-tree-summary .tree-chevron{transform:rotate(90deg);color:var(--accent)}
.workload-tree-add-course-inline{position:absolute;z-index:5;top:11px;right:11px}
.workload-tree-add-course-inline>summary{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:36px;padding:8px 11px;border:1px solid color-mix(in srgb,var(--accent) 40%,var(--line));border-radius:9px;background:var(--accent-soft);color:var(--accent);font-size:10px;font-weight:850;cursor:pointer;list-style:none;transition:background .15s ease,transform .15s ease}
.workload-tree-add-course-inline>summary::-webkit-details-marker{display:none}
.workload-tree-add-course-inline>summary:hover{transform:translateY(-1px);background:color-mix(in srgb,var(--accent) 18%,var(--surface))}
.workload-tree-add-course-inline[open]{position:relative;top:auto;right:auto;padding:13px;border-top:1px solid color-mix(in srgb,var(--accent) 24%,var(--line));border-radius:0 0 12px 12px;background:linear-gradient(120deg,var(--accent-soft),var(--surface) 72%);animation:tree-reveal .18s ease-out}
.workload-tree-add-course-inline[open]>summary{margin-bottom:12px;background:var(--accent);color:#fff}
.tree-course-entry-form{display:grid;gap:12px}
.tree-course-entry-heading{display:flex;align-items:center;justify-content:space-between;gap:12px}
.tree-course-entry-heading>div{display:grid;gap:3px}
.tree-course-entry-heading strong{color:var(--ink);font-size:13px;font-weight:850}
.tree-course-entry-heading small{color:var(--muted);font-size:10px}
.tree-inline-step{padding:5px 8px;border:1px solid var(--line);border-radius:6px;background:var(--surface);color:var(--muted);font-size:8px;font-weight:850;letter-spacing:.1em}
.tree-course-entry-error{padding:10px 12px;border:1px solid var(--danger);border-radius:8px;background:var(--danger-bg);color:var(--danger);font-size:11px;line-height:1.5}
.tree-entry-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.tree-entry-grid-course{grid-template-columns:minmax(190px,.7fr) minmax(240px,1.3fr)}
.tree-entry-grid-three{grid-template-columns:repeat(3,minmax(0,1fr))}
.tree-entry-field .field{height:39px}
.tree-shared-course-note{margin:0;color:var(--muted);font-size:9px;line-height:1.5}
.tree-entry-field .field:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 13%,transparent)}
.tree-entry-components{display:flex;align-items:center;gap:10px 18px;flex-wrap:wrap;margin:0;padding:12px 13px;border:1px solid var(--line-strong);border-radius:10px;background:var(--surface)}
.tree-entry-components legend{padding:0 5px;color:var(--muted);font-size:9px;font-weight:850;letter-spacing:.08em;text-transform:uppercase}
.tree-entry-components label{display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--ink);font-size:11px;font-weight:700;cursor:pointer}
.tree-entry-components input,.tree-entry-days input{accent-color:var(--accent)}
.tree-entry-components>small{flex:1 1 100%;color:var(--muted);font-size:9px}
.tree-component-choice{display:inline-grid;place-items:center;min-width:37px;height:25px;padding:0 7px;border-radius:6px;font-size:9px;font-weight:900}
.tree-component-choice.is-tec{background:var(--info-bg);color:var(--info)}
.tree-component-choice.is-tall{background:var(--accent-soft);color:var(--accent)}
.tree-entry-component{display:grid;gap:11px;padding:13px;border:1px solid color-mix(in srgb,var(--accent) 25%,var(--line));border-radius:10px;background:var(--surface)}
.tree-entry-component[hidden]{display:none!important}
.tree-entry-component-title{display:flex;align-items:center;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--line);color:var(--ink);font-size:11px}
.tree-entry-schedule{padding-top:10px;border-top:1px solid var(--line)}
.tree-entry-schedule>summary{color:var(--accent);font-size:10px;font-weight:800;cursor:pointer}
.tree-entry-days{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:10px 0}
.tree-entry-days label{display:inline-flex;align-items:center;gap:4px;margin:0;padding:6px 8px;border:1px solid var(--line);border-radius:7px;background:var(--surface-soft);color:var(--ink-soft);font-size:10px;cursor:pointer}
.tree-course-entry-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:10px;border-top:1px solid var(--line)}
.tree-course-entry-footer>span{color:var(--muted);font-size:9px;line-height:1.4}
.tree-course-entry-footer .btn{min-height:39px}
.workload-tree-course{margin:8px 0;border:1px solid var(--line);border-radius:10px;background:var(--surface);overflow:hidden;transition:border-color .15s ease,box-shadow .15s ease}
.workload-tree-course.is-focused{border-color:color-mix(in srgb,var(--accent) 48%,var(--line));box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 10%,transparent)}
.workload-course-summary{min-height:57px;padding:10px 12px}
.workload-tree-course[open]>.workload-course-summary{border-bottom:1px solid color-mix(in srgb,var(--accent) 24%,var(--line));background:var(--accent-soft)}
.workload-tree-course[open]>.workload-course-summary .tree-course-icon{background:var(--accent);color:#fff}
.workload-course-children{margin-left:17px;padding:10px 11px 13px 14px}
.tree-course-components{display:flex;align-items:center;gap:5px;flex:none}
.tree-component-chip{display:inline-flex;align-items:center;padding:5px 8px;border-radius:7px;font-size:9px;font-weight:900;letter-spacing:.04em}
.tree-component-chip.is-present{background:var(--success-bg);color:var(--success)}
.tree-component-chip.is-missing{background:var(--surface-soft);color:var(--muted);border:1px dashed var(--line-strong)}
.tree-course-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 10px}
.tree-course-action-inline{position:relative}
.tree-course-action-inline>summary{display:inline-flex;align-items:center;gap:5px;min-height:31px;padding:6px 9px;border:1px solid var(--line);border-radius:7px;background:var(--surface);color:var(--ink-soft);font-size:10px;font-weight:750;cursor:pointer;list-style:none}
.tree-course-action-inline>summary::-webkit-details-marker{display:none}
.tree-course-action-inline>summary:hover{border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}
.tree-course-action-inline[open]{padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}
.tree-course-action-inline[open]>summary{margin-bottom:9px;border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}
.tree-course-action-inline form{display:grid;gap:9px;min-width:min(360px,calc(100vw - 80px))}
.tree-inline-form-actions,.tree-remove-confirm form{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.tree-remove-confirm{max-width:360px}
.tree-remove-confirm p{margin:0 0 9px;color:var(--muted);font-size:10px;line-height:1.5}
.tree-course-remove[open]{border-color:var(--danger)}
.tree-course-remove[open]>summary{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}
.tree-course-hint{margin:0 0 8px;color:var(--muted);font-size:9px;line-height:1.4}
.tree-live-feedback{margin:0 0 12px;padding:9px 11px;border:1px solid var(--success);border-radius:8px;background:var(--success-bg);color:var(--success);font-size:11px;line-height:1.45}
.tree-live-feedback.is-error{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}
.workload-tree-modality{position:relative;display:flex;align-items:center;gap:10px;min-width:0;padding:12px 5px;border-top:1px solid var(--line)}
.tree-modality-tag{display:grid;place-items:center;flex:0 0 42px;height:31px;border-radius:8px;font-size:10px;font-weight:900}
.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}
.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}
.tree-modality-copy{display:grid;gap:4px;flex:1;min-width:0}
.tree-modality-copy strong{color:var(--ink);font-size:11px;font-weight:800;line-height:1.4;overflow-wrap:anywhere}
.tree-modality-copy small{color:var(--muted);font-size:10px;line-height:1.5;overflow-wrap:anywhere}
.tree-course-status{display:inline-flex;align-items:center;gap:4px;flex:none;padding:5px 8px;border-radius:6px;font-size:9px;font-weight:800;white-space:nowrap}
.tree-course-status.is-ready{background:var(--success-bg);color:var(--success)}
.tree-course-status.is-pending{background:var(--warning-bg);color:var(--warning)}
.tree-inline-date,.tree-inline-nrc{display:inline-flex;align-items:center;min-height:23px;padding:2px 5px;border-radius:5px;border-bottom:1px dashed var(--line-strong);color:var(--ink-soft);cursor:text}
.tree-inline-date:hover,.tree-inline-nrc:hover{background:var(--accent-soft);border-bottom-color:var(--accent);color:var(--accent)}
.tree-inline-editor{width:auto;min-width:112px;max-width:160px;min-height:30px;padding:4px 6px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;outline:none}
.tree-inline-hint{margin-left:4px;color:var(--faint);font-size:9px}
.tree-inline-saving{opacity:.55;pointer-events:none}
.tree-open-modality{flex:none}
.tree-empty-child{padding:12px;color:var(--muted);font-size:11px}
.tree-loading-state,.tree-load-error{padding:12px 14px;color:var(--muted);font-size:11px;line-height:1.5}
.tree-load-error{color:var(--danger);background:var(--danger-bg);border-radius:8px}
@keyframes tree-reveal{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
html[data-theme="dark"] .workload-tree-hero{background:linear-gradient(115deg,color-mix(in srgb,var(--accent) 19%,#15232d),#141f28 76%);border-color:color-mix(in srgb,var(--accent) 35%,var(--line))}
@media(max-width:980px){.workload-tree-hero{align-items:flex-start;flex-direction:column}.workload-tree-hero-actions{width:100%}}
@media(max-width:700px){
 .tree-group-create-form{grid-template-columns:1fr}
 .tree-group-create-form .btn{width:100%}
 .workload-tree-children{margin-left:12px;padding-left:8px;padding-right:7px}
 .workload-group-children,.workload-course-children{margin-left:8px;padding-left:8px}
 .workload-group-summary{padding-right:52px}
 .workload-tree-add-course-inline{top:12px;right:10px}
 .workload-tree-add-course-inline>summary{width:36px;height:36px;min-height:36px;padding:7px}
 .workload-tree-add-course-inline>summary span{display:none}
 .workload-tree-add-course-inline[open]{position:relative;top:auto;right:auto}
 .workload-tree-add-course-inline[open]>summary{width:auto;height:auto;margin-bottom:12px}
 .workload-tree-add-course-inline[open]>summary span{display:inline}
 .tree-entry-grid-course,.tree-entry-grid,.tree-entry-grid-three{grid-template-columns:1fr 1fr}
 .tree-entry-grid-course .tree-entry-field:nth-child(2){grid-column:1/-1}
 .tree-course-entry-footer{align-items:stretch;flex-direction:column}
 .tree-course-entry-footer .btn{width:100%}
 .tree-course-components{gap:3px}
 .tree-component-chip{padding:4px 6px;font-size:8px}
 .workload-tree-modality{align-items:flex-start;flex-wrap:wrap}
 .tree-modality-copy{flex:1 1 calc(100% - 100px)}
 .tree-open-modality{margin-left:49px}
}
@media(max-width:430px){
 .workload-tree-hero h1{font-size:30px}
 .workload-tree-context{display:grid;gap:7px}
 .workload-tree-browser{padding:12px 9px}
 .workload-tree-browser-head{align-items:flex-start;flex-direction:column}
 .workload-tree-search{display:grid;grid-template-columns:1fr 1fr}
 .workload-tree-search-field{grid-column:1/-1}
 .workload-tree-search .btn,.workload-tree-search>a{width:100%}
 .tree-entry-grid,.tree-entry-grid-three{grid-template-columns:1fr}
 .tree-entry-grid-course .tree-entry-field:nth-child(2){grid-column:auto}
 .tree-course-actions{align-items:stretch;flex-direction:column}
 .tree-course-action-inline form{min-width:0}
 .tree-course-action-inline[open]{width:100%}
}
@media(prefers-reduced-motion:reduce){.workload-tree-summary,.tree-inline-create-group>summary,.workload-tree-add-course-inline>summary,.workload-tree-group-shell,.workload-tree-course{transition:none!important;animation:none!important}}

.tree-group-action-inline{position:relative}
.tree-group-action-inline>summary{display:inline-flex;align-items:center;gap:5px;min-height:32px;padding:6px 9px;border:1px solid var(--line);border-radius:7px;background:var(--surface);color:var(--ink-soft);font-size:10px;font-weight:750;cursor:pointer;list-style:none}
.tree-group-action-inline>summary::-webkit-details-marker{display:none}
.tree-group-action-inline>summary:hover{border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}
.tree-group-action-inline[open]{padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}
.tree-group-action-inline[open]>summary{margin-bottom:9px;border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}
.tree-group-action-inline form{display:grid;gap:9px;min-width:min(320px,calc(100vw - 70px))}
.tree-group-remove[open]{border-color:var(--danger)}
.tree-group-remove[open]>summary{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}
.tree-group-remove-confirm{max-width:440px}
.tree-group-remove-confirm p{margin:0 0 10px;color:var(--muted);font-size:10px;line-height:1.55}
.tree-group-remove-confirm p strong{color:var(--ink)}
.tree-group-remove-confirm form{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.tree-group-delete-disabled{opacity:.6;cursor:not-allowed}
@media(max-width:700px){
 .tree-group-action-inline form{min-width:0}
 .tree-group-action-inline[open]{width:100%}
 .workload-tree-group-actions{align-items:stretch}
 .workload-tree-group-actions>.btn,.workload-tree-group-actions>.tree-group-action-inline{flex:1 1 auto}
 .tree-group-remove-confirm form{display:grid;grid-template-columns:1fr}
 .tree-group-remove-confirm .btn{width:100%}
}
/* Tree v2: un solo nivel desplegable, cursos y modalidades siempre visibles */
.workload-tree-group-actions{align-items:center;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--line)}
.workload-tree-group-actions .btn{min-height:34px}
.tree-group-delete-trigger,.tree-course-delete-trigger{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-height:32px;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface);color:var(--muted);font-size:10px;font-weight:750;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease}
.tree-group-delete-trigger:hover,.tree-course-delete-trigger:hover{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}
.tree-last-group-note{color:var(--muted);font-size:9px}
.tree-delete-confirm{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:10px 0;padding:11px 12px;border:1px solid color-mix(in srgb,var(--danger) 35%,var(--line));border-radius:9px;background:var(--danger-bg);animation:tree-reveal .16s ease-out}
.tree-delete-confirm[hidden]{display:none!important}
.tree-delete-confirm p{margin:0;color:var(--ink-soft);font-size:10px;line-height:1.55}
.tree-delete-confirm p strong{color:var(--danger)}
.tree-delete-confirm form{display:flex;align-items:center;gap:7px;flex:none}
.tree-course-entry-area{margin-top:8px}
.workload-tree-add-course{position:static;display:inline-flex;align-items:center;gap:7px;min-height:36px;padding:8px 11px;border:1px solid color-mix(in srgb,var(--accent) 34%,var(--line));border-radius:9px;background:var(--accent-soft);color:var(--accent);font-size:10px;font-weight:850;cursor:pointer;transition:transform .15s ease,background .15s ease,border-color .15s ease}
.workload-tree-add-course:hover,.workload-tree-add-course[aria-expanded="true"]{transform:translateY(-1px);border-color:var(--accent);background:var(--accent);color:#fff}
.tree-course-entry-form[hidden]{display:none!important}
.tree-course-entry-form{margin-top:9px;padding:15px;border:1px solid color-mix(in srgb,var(--accent) 38%,var(--line));border-left:4px solid var(--accent);border-radius:12px;background:linear-gradient(120deg,var(--accent-soft),var(--surface) 78%);box-shadow:0 5px 18px rgba(25,56,78,.05);animation:tree-reveal .18s ease-out}
.tree-course-entry-heading{margin-bottom:3px}
.tree-entry-components{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--accent) 5%,transparent)}
.tree-course-entry-footer>div{display:flex;align-items:center;gap:7px}
.tree-course-head{display:flex;align-items:center;gap:10px;min-width:0;padding:12px 13px;background:var(--surface)}
.workload-tree-course{display:block;margin:8px 0;border:1px solid var(--line-strong);border-radius:11px;background:var(--surface);overflow:visible;transition:border-color .15s ease,box-shadow .15s ease}
.workload-tree-course:hover{border-color:color-mix(in srgb,var(--accent) 42%,var(--line))}
.workload-tree-course:focus-within{border-color:color-mix(in srgb,var(--accent) 60%,var(--line));box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 9%,transparent)}
.tree-course-head .tree-node-copy{flex:1}
.tree-course-head .tree-node-copy strong{display:inline-flex;align-items:center;min-height:27px;padding:2px 4px;border-radius:5px;cursor:text}
.tree-inline-name:hover,.tree-inline-code:hover{background:var(--accent-soft);color:var(--accent)}
.tree-inline-code{display:inline-flex;min-height:22px;align-items:center;padding:1px 4px;border-radius:5px;cursor:text}
.tree-course-components{display:flex;align-items:center;gap:5px;flex:none}
.tree-component-chip{display:inline-flex;align-items:center;justify-content:center;min-width:36px;padding:5px 7px;border:1px solid transparent;border-radius:7px;font-size:9px;font-weight:900;letter-spacing:.04em}
.tree-component-chip.is-present{background:var(--success-bg);color:var(--success)}
.tree-component-chip.is-disabled{border-color:var(--line-strong);background:var(--surface-soft);color:var(--muted);text-decoration:line-through}
.tree-component-chip.is-missing{border:1px dashed var(--line-strong);background:var(--surface-soft);color:var(--faint)}
.tree-course-delete-trigger{margin-left:5px}
.tree-course-delete-confirm{margin:0 12px 10px}
.tree-course-modalities{padding:0 12px 9px}
.tree-course-modalities .workload-tree-modality:first-child{border-top:1px solid var(--line)}
.workload-tree-modality{display:grid;grid-template-columns:11px 42px minmax(0,1fr) auto auto auto;align-items:center;gap:10px;min-height:76px;padding:11px 5px;border-top:1px solid var(--line);transition:background .15s ease,opacity .15s ease}
.workload-tree-modality:hover{background:var(--surface-soft)}
.workload-tree-modality.is-disabled{background:color-mix(in srgb,var(--surface-soft) 70%,var(--surface));}
.workload-tree-modality.is-disabled .tree-modality-copy{opacity:.72}
.tree-modality-copy{min-width:0}
.tree-modality-copy strong{display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.tree-modality-copy small{display:block}
.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}
.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}
.tree-modality-toggle{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:105px;min-height:34px;padding:6px 9px;border:1px solid var(--line-strong);border-radius:8px;background:var(--surface);color:var(--ink-soft);font-size:9px;font-weight:800;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease}
.tree-modality-toggle.is-on{border-color:color-mix(in srgb,var(--success) 35%,var(--line));background:var(--success-bg);color:var(--success)}
.tree-modality-toggle.is-off{border-color:color-mix(in srgb,var(--accent) 35%,var(--line));background:var(--accent-soft);color:var(--accent)}
.tree-modality-toggle:hover:not(:disabled){filter:brightness(.97);transform:translateY(-1px)}
.tree-modality-toggle:disabled{opacity:.75;cursor:not-allowed;filter:none}
.tree-toggle-dot{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px color-mix(in srgb,currentColor 14%,transparent)}
.tree-course-status.is-disabled{background:var(--surface-soft);color:var(--muted)}
.tree-disabled-value{display:inline-flex;align-items:center;min-height:22px;padding:1px 4px;color:var(--muted)}
.tree-inline-editor{width:auto;min-width:105px;max-width:min(220px,60vw);min-height:30px;padding:4px 7px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 12%,transparent);outline:none}
.tree-inline-name .tree-inline-editor{min-width:min(220px,60vw);font-size:12px;font-weight:750}
.tree-inline-saving{opacity:.6;pointer-events:none}
.tree-inline-name:focus-visible,.tree-inline-date:focus-visible,.tree-inline-nrc:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
html[data-theme="dark"] .workload-tree-group[open]>.workload-group-summary{background:linear-gradient(90deg,color-mix(in srgb,var(--accent) 16%,var(--surface)),var(--surface))}
html[data-theme="dark"] .tree-course-entry-form{background:linear-gradient(120deg,color-mix(in srgb,var(--accent) 14%,var(--surface)),var(--surface) 78%)}
@media(max-width:850px){
 .workload-tree-modality{grid-template-columns:10px 38px minmax(0,1fr) auto;gap:8px}
 .workload-tree-modality .tree-course-status{display:none}
 .workload-tree-modality .tree-open-modality{grid-column:3;margin:0;justify-self:start}
 .tree-modality-toggle{grid-column:4;grid-row:1/3;min-width:88px}
}
@media(max-width:600px){
 .tree-course-head{align-items:flex-start;flex-wrap:wrap;padding:10px}
 .tree-course-head .tree-node-copy{flex:1 1 calc(100% - 60px)}
 .tree-course-components{order:4;margin-left:43px}
 .tree-course-delete-trigger{order:5;margin-left:auto}
 .tree-course-modalities{padding:0 8px 8px}
 .workload-tree-modality{grid-template-columns:32px minmax(0,1fr) auto;gap:7px;padding:11px 2px}
 .workload-tree-modality .tree-branch-mark{display:none}
 .workload-tree-modality .tree-modality-tag{grid-column:1;grid-row:1;flex-basis:32px;width:32px;height:29px}
 .workload-tree-modality .tree-modality-copy{grid-column:2;grid-row:1;min-width:0}
 .workload-tree-modality .tree-modality-toggle{grid-column:3;grid-row:1;min-width:82px;padding:6px 7px}
 .workload-tree-modality .tree-open-modality{grid-column:2/4;grid-row:2;margin:0}
 .tree-delete-confirm{align-items:stretch;flex-direction:column}
 .tree-delete-confirm form{display:grid;grid-template-columns:1fr 1fr}
 .tree-delete-confirm form .btn{width:100%}
 .tree-course-entry-form{padding:12px}
 .tree-course-entry-footer>div{display:grid;grid-template-columns:1fr 1fr;width:100%}
 .tree-course-entry-footer>div .btn{width:100%}
 .workload-tree-group-actions{align-items:stretch}
 .workload-tree-group-actions>.btn{flex:1}
}

/* Simplified two-panel tree */
.tree-workspace-grid{display:grid;grid-template-columns:minmax(0,1.04fr) minmax(350px,.96fr);align-items:start;gap:16px}
.tree-structure-card,.tree-editor-card{min-width:0;padding:18px;border:1px solid var(--line-strong);border-radius:14px;background:var(--surface);box-shadow:0 5px 18px rgba(25,56,78,.045)}
.tree-panel-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin:0 0 15px}
.tree-panel-heading .eyebrow{margin:0 0 5px;color:var(--accent);font-size:8px;font-weight:900;letter-spacing:.13em}
.tree-panel-heading h2{margin:0;color:var(--ink);font-size:21px;font-weight:850;letter-spacing:-.035em}
.tree-panel-heading p:not(.eyebrow){margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.5}
.tree-add-group-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;margin-bottom:13px;padding:10px;border:1px solid color-mix(in srgb,var(--accent) 26%,var(--line));border-radius:10px;background:var(--accent-soft)}
.tree-add-group-form .field{height:38px;min-width:0;border-color:var(--line-strong);background:var(--surface);font-size:11px}
.tree-add-group-form .btn{min-height:38px;white-space:nowrap}
.tree-structure-card .workload-tree-search{margin-bottom:12px}
.tree-structure-card .workload-tree-root{display:grid;gap:8px}
.tree-structure-card .workload-tree-group-shell{border:1px solid var(--line-strong);border-radius:10px;background:var(--surface);overflow:hidden}
.tree-structure-card .workload-tree-group-shell.is-selected{border-color:var(--accent);box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 13%,transparent)}
.tree-structure-card .workload-group-summary{min-height:59px;padding:11px 12px;background:var(--surface)}
.tree-structure-card .workload-group-summary:hover{background:var(--accent-soft)}
.tree-structure-card .workload-tree-group[open]>.workload-group-summary{border-bottom:1px solid var(--line);background:var(--accent-soft)}
.tree-structure-card .tree-group-selected-label{margin-left:auto;padding:4px 7px;border-radius:6px;background:var(--surface-soft);color:var(--muted);font-size:8px;font-weight:800}
.tree-structure-card .workload-tree-group-shell.is-selected .tree-group-selected-label{background:var(--accent);color:#fff}
.tree-structure-card .workload-tree-children{margin:0;padding:10px 11px;border:0}
.tree-structure-card .workload-tree-group-actions{margin:0 0 9px;padding:0 0 8px;border-bottom:1px solid var(--line)}
.tree-course-entry-form{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:9px;margin:9px 0;padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}
.tree-course-entry-form[hidden]{display:none!important}
.tree-course-name-input{display:grid;gap:5px;min-width:0;color:var(--ink-soft);font-size:9px;font-weight:800}
.tree-course-name-input .field{height:37px;min-width:0;background:var(--surface);font-size:11px}
.tree-entry-components{grid-column:1/-1;display:flex;gap:7px;margin:0;padding:7px;border:0;background:transparent}
.tree-entry-components legend{padding:0 4px;color:var(--muted);font-size:8px;font-weight:850}
.tree-component-option{display:flex;align-items:center;gap:6px;min-width:0;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface);cursor:pointer}
.tree-component-option span{font-size:9px;font-weight:900}
.tree-component-option small{color:var(--muted);font-size:9px}
.tree-component-option.is-tec span{color:var(--info)}
.tree-component-option.is-tall span{color:var(--accent)}
.tree-component-option input{accent-color:var(--accent)}
.tree-course-create-footer{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:8px}
.tree-course-create-footer>small{color:var(--muted);font-size:8px;line-height:1.4}
.tree-course-create-button{min-height:34px;padding:7px 10px;font-size:9px;white-space:nowrap}
.tree-course-entry-error{grid-column:1/-1;padding:8px;border:1px solid var(--danger);border-radius:7px;background:var(--danger-bg);color:var(--danger);font-size:9px}
.tree-course-entry-error[hidden]{display:none!important}
.tree-structure-card .tree-loading-state{padding:12px 8px;color:var(--muted);font-size:10px}
.tree-structure-card .workload-tree-course{position:relative;margin:6px 0;border:1px solid var(--line);border-radius:9px;background:var(--surface);overflow:hidden}
.tree-structure-card .workload-tree-course.is-selected{border-color:var(--accent);box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 13%,transparent)}
.tree-course-head{display:flex;width:100%;align-items:center;gap:9px;padding:10px 38px 10px 11px;border:0;background:transparent;text-align:left;cursor:pointer}
.tree-course-head:hover{background:var(--accent-soft)}
.tree-course-node{display:grid;place-items:center;flex:0 0 31px;width:31px;height:31px;border-radius:8px;background:var(--accent-soft);color:var(--accent)}
.tree-course-head .tree-node-copy{flex:1;min-width:0}
.tree-course-head .tree-node-copy strong{display:block;color:var(--ink);font-size:11px;font-weight:800;line-height:1.35;overflow-wrap:anywhere}
.tree-course-head .tree-node-copy small{display:block;margin-top:3px;color:var(--muted);font-size:9px}
.tree-course-components{display:flex;flex:none;gap:4px}
.tree-component-chip{min-width:30px;justify-content:center;padding:4px 6px;border-radius:5px;font-size:8px}
.tree-component-chip.is-disabled{text-decoration:line-through}
.tree-course-selected-label{display:inline-flex;align-items:center;gap:2px;color:var(--accent);font-size:8px;font-weight:850}
.tree-course-remove-trigger{position:absolute;top:12px;right:8px;display:grid;place-items:center;width:25px;height:25px;border:1px solid transparent;border-radius:6px;background:transparent;color:var(--muted);cursor:pointer}
.tree-course-remove-trigger:hover{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}
.tree-course-delete-confirm{margin:0 9px 9px}
.tree-editor-card{position:sticky;top:18px;min-height:340px}
.tree-editor-indicator{display:inline-flex;align-items:center;gap:6px;flex:none;padding:5px 8px;border:1px solid var(--line);border-radius:20px;background:var(--surface-soft);color:var(--muted);font-size:8px;font-weight:800}
.tree-editor-indicator span{width:6px;height:6px;border-radius:50%;background:var(--success)}
.tree-editor-empty{display:grid;justify-items:center;gap:9px;min-height:240px;align-content:center;padding:25px 16px;border:1px dashed var(--line-strong);border-radius:11px;background:var(--surface-soft);text-align:center}
.tree-editor-empty-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:14px;background:var(--accent-soft);color:var(--accent)}
.tree-editor-empty strong{color:var(--ink);font-size:14px;font-weight:850}
.tree-editor-empty p{max-width:290px;margin:0;color:var(--muted);font-size:10px;line-height:1.55}
.tree-editor-content[hidden],.tree-editor-empty[hidden]{display:none!important}
.tree-editor-breadcrumb{margin-bottom:6px;color:var(--accent);font-size:8px;font-weight:900;letter-spacing:.13em}
.tree-editor-content h3{margin:0;color:var(--ink);font-size:19px;font-weight:850;line-height:1.3;overflow-wrap:anywhere}
.tree-editor-description{margin:5px 0 14px;color:var(--muted);font-size:10px;line-height:1.5}
.tree-editor-fields{display:grid;gap:12px}
.tree-editor-field{display:grid;gap:5px;padding:10px 11px;border:1px solid var(--line);border-radius:9px;background:var(--surface)}
.tree-editor-field>label{color:var(--muted);font-size:8px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}
.tree-editor-value{display:inline-flex;align-items:center;min-height:27px;max-width:100%;padding:2px 5px;border-radius:5px;color:var(--ink);font-size:12px;font-weight:750;overflow-wrap:anywhere;cursor:text}
.tree-editor-value:hover{background:var(--accent-soft);color:var(--accent)}
.tree-editor-value.is-empty{color:var(--muted);font-style:italic;font-weight:550}
.tree-editor-hint{color:var(--muted);font-size:8px}
.tree-editor-modality-list{display:grid;gap:9px;margin-top:2px}
.tree-editor-modality{padding:11px;border:1px solid var(--line-strong);border-radius:10px;background:var(--surface)}
.tree-editor-modality.is-disabled{background:var(--surface-soft)}
.tree-editor-modality-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:9px}
.tree-editor-modality-title{display:flex;align-items:center;gap:8px;color:var(--ink);font-size:11px;font-weight:850}
.tree-editor-modality-title .tree-modality-tag{width:34px;height:28px;flex-basis:34px}
.tree-editor-modality .tree-modality-toggle{min-width:92px;min-height:31px}
.tree-editor-modality-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
.tree-editor-modality-fields .tree-editor-field{min-width:0;padding:8px}
.tree-editor-modality-fields .tree-editor-value{font-size:10px}
.tree-editor-schedule{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;padding-top:8px;border-top:1px solid var(--line);color:var(--muted);font-size:9px}
.tree-editor-add-component{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px;border:1px dashed var(--line-strong);border-radius:9px;background:var(--surface-soft)}
.tree-editor-add-component strong{color:var(--ink);font-size:10px}
.tree-editor-add-component small{display:block;margin-top:3px;color:var(--muted);font-size:8px}
.tree-editor-add-component .btn{min-height:31px;padding:6px 9px;font-size:9px}
.tree-editor-remove-course{display:flex;justify-content:flex-end;margin-top:12px}
.tree-editor-remove-course button{min-height:30px;padding:6px 9px;border:1px solid var(--line);border-radius:7px;background:var(--surface);color:var(--danger);font-size:9px;font-weight:750;cursor:pointer}
.tree-live-feedback{margin:12px 0 0;padding:9px 11px;border:1px solid var(--success);border-radius:8px;background:var(--success-bg);color:var(--success);font-size:10px;line-height:1.45}
.tree-live-feedback.is-error{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}
.tree-live-feedback[hidden]{display:none!important}
.tree-inline-editor{width:auto;min-width:100px;max-width:min(260px,65vw);min-height:30px;padding:4px 7px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 12%,transparent);outline:none}
@media(max-width:980px){.tree-workspace-grid{grid-template-columns:minmax(0,1fr)}.tree-editor-card{position:static;min-height:280px}.tree-editor-empty{min-height:160px}}
@media(max-width:560px){
 .tree-structure-card,.tree-editor-card{padding:12px}
 .tree-add-group-form{grid-template-columns:minmax(0,1fr)}
 .tree-add-group-form .btn{width:100%}
 .tree-course-entry-form{grid-template-columns:minmax(0,1fr)}
 .tree-entry-components,.tree-course-create-footer,.tree-course-entry-error{grid-column:1}
 .tree-course-create-footer{align-items:stretch;flex-direction:column}
 .tree-course-create-button{width:100%}
 .tree-course-head{padding-right:35px;flex-wrap:wrap}
 .tree-course-components{margin-left:40px}
 .tree-course-selected-label{margin-left:auto}
 .tree-editor-modality-fields{grid-template-columns:1fr}
 .tree-editor-modality-head{align-items:flex-start}
}
@media(prefers-reduced-motion:reduce){.tree-structure-card *, .tree-editor-card *{scroll-behavior:auto!important;transition:none!important}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const feedback = document.getElementById('tree-live-feedback');
    const editorEmpty = document.getElementById('tree-editor-empty');
    const editorContent = document.getElementById('tree-editor-content');
    const editorKind = document.getElementById('tree-editor-kind');
    const editorTitle = document.getElementById('tree-editor-title');
    const editorDescription = document.getElementById('tree-editor-description');
    const editorFields = document.getElementById('tree-editor-fields');
    let selectedGroupId = null;
    let selectedAssignmentId = null;

    const showFeedback = (message, isError = false) => {
        if (!feedback || !message) return;
        feedback.textContent = message;
        feedback.classList.toggle('is-error', isError);
        feedback.hidden = false;
    };

    const showEditor = () => {
        editorEmpty.hidden = true;
        editorContent.hidden = false;
    };

    const clearEditor = () => {
        selectedGroupId = null;
        selectedAssignmentId = null;
        editorContent.hidden = true;
        editorEmpty.hidden = false;
        document.querySelectorAll('.tree-structure-card .is-selected').forEach(node => node.classList.remove('is-selected'));
    };

    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    };

    const editorField = (label, value, config = {}) => {
        const wrapper = element('div', 'tree-editor-field');
        wrapper.append(element('label', '', label));
        const valueNode = element('span', 'tree-editor-value' + (value ? '' : ' is-empty'), value || 'Doble clic para completar');
        if (config.editable) {
            valueNode.dataset.editorText = '1';
            valueNode.dataset.value = value || '';
            valueNode.dataset.url = config.url;
            valueNode.dataset.field = config.field;
            valueNode.dataset.label = label;
            valueNode.dataset.maxLength = String(config.maxLength || 255);
            valueNode.title = 'Doble clic para editar';
        }
        wrapper.append(valueNode);
        if (config.hint) wrapper.append(element('small', 'tree-editor-hint', config.hint));
        return wrapper;
    };

    document.addEventListener('change', event => {
        if (!event.target.matches('[data-course-component]')) return;
        const form = event.target.closest('[data-tree-course-form]');
        if (!form) return;
        const selected = [...form.querySelectorAll('[data-course-component]')].some(input => input.checked);
        if (!selected) {
            event.target.checked = true;
            showFeedback('Cada curso debe tener al menos un componente: TEC o TAL.', true);
        }
    });

    const sendInlineUpdate = async (url, values) => {
        const body = new FormData();
        Object.entries(values).forEach(([key, value]) => body.append(key, value ?? ''));
        const response = await fetch(url, {
            method: 'PATCH',
            body,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = Object.values(payload.errors || {}).flat();
            throw new Error(messages.join(' ') || payload.message || 'No se pudo guardar el cambio.');
        }
        return payload;
    };

    const sendPost = async (url, body = new FormData()) => {
        const response = await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = Object.values(payload.errors || {}).flat();
            throw new Error(messages.join(' ') || payload.message || 'No se pudo completar la acción.');
        }
        return payload;
    };

    const displayDate = value => {
        if (!value) return 'Sin fecha';
        const [year, month, day] = value.split('-');
        return day + '/' + month + '/' + year;
    };

    const startTextEditor = (target, config) => {
        if (target.querySelector('input')) return;
        const original = config.value ?? '';
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = config.maxLength || 255;
        input.className = 'tree-inline-editor';
        input.value = original;
        input.setAttribute('aria-label', config.label);
        target.replaceChildren(input);
        input.focus();
        input.select();

        let settled = false;
        const restore = () => {
            if (settled) return;
            settled = true;
            config.restore(original);
        };
        const save = async () => {
            if (settled) return;
            const value = input.value.trim();
            if (!value || value === original) {
                restore();
                return;
            }
            settled = true;
            target.classList.add('tree-inline-saving');
            try {
                const payload = await sendInlineUpdate(config.url, {[config.field]: value});
                config.success(payload);
                showFeedback(payload.message || 'Cambio guardado.');
            } catch (error) {
                config.restore(original);
                showFeedback(error.message, true);
            } finally {
                target.classList.remove('tree-inline-saving');
            }
        };
        input.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                restore();
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                save();
            }
        });
        input.addEventListener('blur', save, {once: true});
    };

    const startNrcEditor = cell => {
        if (cell.querySelector('input')) return;
        const original = cell.dataset.nrc || '';
        const input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.maxLength = 12;
        input.className = 'tree-inline-editor';
        input.value = original;
        input.placeholder = 'NRC';
        cell.replaceChildren(input);
        input.focus();
        input.select();

        let settled = false;
        const restore = () => {
            if (settled) return;
            settled = true;
            cell.textContent = original || 'Sin NRC';
        };
        const save = async () => {
            if (settled) return;
            const value = input.value.trim();
            if (value === original) {
                restore();
                return;
            }
            settled = true;
            try {
                const payload = await sendInlineUpdate(cell.dataset.nrcUrl, {nrc: value});
                cell.dataset.nrc = payload.nrc || '';
                cell.textContent = payload.nrc || 'Sin NRC';
                const source = selectedCourseNode()?.querySelector('[data-editor-modality][data-modality-id="' + cell.dataset.modalityId + '"]');
                if (source) source.dataset.nrc = payload.nrc || '';
                updateSelectedTreeData();
                showFeedback(payload.message || 'NRC actualizado.');
            } catch (error) {
                cell.textContent = original || 'Sin NRC';
                showFeedback(error.message, true);
            }
        };
        input.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                restore();
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                save();
            }
        });
        input.addEventListener('blur', save, {once: true});
    };

    const startDateEditor = (cell, wrapper) => {
        if (cell.querySelector('input')) return;
        const field = cell.dataset.inlineDate;
        const datasetKey = field === 'start_date' ? 'startDate' : 'endDate';
        const original = wrapper.dataset[datasetKey] || '';
        const input = document.createElement('input');
        input.type = 'date';
        input.className = 'tree-inline-editor';
        input.value = original;
        input.setAttribute('aria-label', field === 'start_date' ? 'Fecha de inicio' : 'Fecha de fin');
        cell.replaceChildren(input);
        input.focus();
        input.showPicker?.();

        let settled = false;
        const restore = () => {
            if (settled) return;
            settled = true;
            cell.textContent = original ? displayDate(original) : 'Sin fecha';
        };
        const save = async () => {
            if (settled) return;
            const value = input.value;
            if (value === original) {
                restore();
                return;
            }
            settled = true;
            const values = {
                start_date: field === 'start_date' ? value : wrapper.dataset.startDate,
                end_date: field === 'end_date' ? value : wrapper.dataset.endDate
            };
            try {
                const payload = await sendInlineUpdate(wrapper.dataset.datesUrl, values);
                wrapper.dataset.startDate = payload.start_date || '';
                wrapper.dataset.endDate = payload.end_date || '';
                const source = selectedCourseNode()?.querySelector('[data-editor-modality][data-modality-id="' + wrapper.dataset.modalityId + '"]');
                if (source) {
                    source.dataset.startDate = payload.start_date || '';
                    source.dataset.endDate = payload.end_date || '';
                }
                wrapper.querySelectorAll('[data-inline-date]').forEach(dateNode => {
                    dateNode.textContent = payload[dateNode.dataset.inlineDate]
                        ? displayDate(payload[dateNode.dataset.inlineDate])
                        : 'Sin fecha';
                });
                updateSelectedTreeData();
                showFeedback(payload.message || 'Fechas actualizadas.');
            } catch (error) {
                cell.textContent = original ? displayDate(original) : 'Sin fecha';
                showFeedback(error.message, true);
            }
        };
        input.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                restore();
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                save();
            }
        });
        input.addEventListener('blur', save, {once: true});
    };

    const selectedGroupNode = () => selectedGroupId
        ? document.querySelector('[data-tree-group-id="' + selectedGroupId + '"]')
        : null;

    const selectedCourseNode = () => selectedAssignmentId
        ? document.querySelector('[data-assignment-id="' + selectedAssignmentId + '"]')
        : null;

    const updateSelectedTreeData = () => {
        const course = selectedCourseNode();
        if (!course) return;
        const nameValue = editorFields.querySelector('[data-editor-field="name"] [data-editor-text]');
        const codeValue = editorFields.querySelector('[data-editor-field="code"] [data-editor-text]');
        if (nameValue) course.dataset.courseName = nameValue.dataset.value || '';
        if (codeValue) course.dataset.courseCode = codeValue.dataset.value || '';
    };

    const selectGroup = groupNode => {
        if (!groupNode) return;
        selectedGroupId = groupNode.dataset.treeGroupId;
        selectedAssignmentId = null;
        document.querySelectorAll('.tree-structure-card .is-selected').forEach(node => node.classList.remove('is-selected'));
        groupNode.closest('.workload-tree-group-shell')?.classList.add('is-selected');

        editorKind.textContent = 'GRUPO';
        editorTitle.textContent = 'Grupo ' + (groupNode.dataset.groupName || '');
        editorDescription.textContent = 'Este grupo pertenece al ciclo seleccionado. Doble clic en el nombre para cambiarlo.';
        editorFields.replaceChildren(
            editorField('Nombre del grupo', groupNode.dataset.groupName, {
                editable: true, url: groupNode.dataset.groupNameUrl, field: 'name', maxLength: 100
            })
        );
        showEditor();
    };

    const makeModalityEditor = (course, source) => {
        const type = source.dataset.type;
        const exists = source.dataset.exists === '1';
        const active = source.dataset.active === '1';
        const wrapper = element('section', 'tree-editor-modality' + (exists && !active ? ' is-disabled' : ''));
        const head = element('div', 'tree-editor-modality-head');
        const title = element('div', 'tree-editor-modality-title');
        const tag = element('span', 'tree-modality-tag ' + (type === 'TEC' ? 'is-tec' : 'is-tall'), type);
        title.append(tag, element('span', '', type === 'TEC' ? 'Teoría' : 'Taller'));
        head.append(title);

        if (exists) {
            const toggle = element('button', 'tree-modality-toggle ' + (active ? 'is-on' : 'is-off'));
            toggle.type = 'button';
            toggle.dataset.editorModalityToggle = '1';
            toggle.dataset.modalityUrl = source.dataset.activeUrl;
            toggle.dataset.active = active ? '1' : '0';
            toggle.dataset.modalityId = source.dataset.modalityId;
            toggle.dataset.assignmentId = course.dataset.assignmentId;
            toggle.dataset.groupId = course.dataset.groupId;
            toggle.setAttribute('aria-pressed', active ? 'true' : 'false');
            toggle.append(element('span', 'tree-toggle-dot'));
            toggle.append(element('span', 'tree-toggle-label', active ? 'Desactivar' : 'Activar'));
            if (active) {
                const other = [...course.querySelectorAll('[data-editor-modality]')]
                    .filter(item => item.dataset.exists === '1' && item.dataset.active === '1').length;
                if (other <= 1) {
                    toggle.disabled = true;
                    toggle.querySelector('.tree-toggle-label').textContent = 'Obligatoria';
                    toggle.title = 'Debe quedar al menos una modalidad activa.';
                }
            }
            head.append(toggle);

            const fields = element('div', 'tree-editor-modality-fields');
            const nrc = element('div', 'tree-editor-field');
            nrc.append(element('label', '', 'NRC'));
            const nrcValue = element('span', 'tree-editor-value' + (source.dataset.nrc ? '' : ' is-empty'), source.dataset.nrc || 'Doble clic para agregar');
            nrcValue.dataset.inlineNrc = '1';
            nrcValue.dataset.nrc = source.dataset.nrc || '';
            nrcValue.dataset.nrcUrl = source.dataset.nrcUrl;
            nrcValue.dataset.modalityId = source.dataset.modalityId || '';
            nrcValue.title = 'Doble clic para editar NRC';
            nrc.append(nrcValue);
            fields.append(nrc);

            const dates = element('div', 'tree-editor-field tree-editor-dates');
            dates.append(element('label', '', 'Periodo'));
            const dateValues = element('div', 'tree-editor-date-values');
            dateValues.dataset.modalityDates = '1';
            dateValues.dataset.datesUrl = source.dataset.datesUrl;
            dateValues.dataset.modalityId = source.dataset.modalityId || '';
            dateValues.dataset.startDate = source.dataset.startDate || '';
            dateValues.dataset.endDate = source.dataset.endDate || '';
            const start = element('span', 'tree-editor-value' + (source.dataset.startDate ? '' : ' is-empty'), displayDate(source.dataset.startDate));
            start.dataset.inlineDate = 'start_date';
            const end = element('span', 'tree-editor-value' + (source.dataset.endDate ? '' : ' is-empty'), displayDate(source.dataset.endDate));
            end.dataset.inlineDate = 'end_date';
            dateValues.append(start, element('span', 'tree-date-separator', '–'), end);
            dates.append(dateValues);
            fields.append(dates);
            wrapper.append(head, fields);

            const schedule = element('div', 'tree-editor-schedule');
            schedule.append(element('span', '', source.dataset.scheduleLabel || 'Horario pendiente'));
            if (active && source.dataset.modalityUrl) {
                const link = element('a', 'btn btn-secondary btn-sm', 'Editar horario');
                link.href = source.dataset.modalityUrl;
                schedule.append(link);
            } else {
                schedule.append(element('span', 'tree-editor-hint', 'Se conserva su configuración.'));
            }
            wrapper.append(schedule);
        } else {
            head.append(element('span', 'tree-course-status is-pending', 'No agregada'));
            const add = element('button', 'btn btn-secondary btn-sm', 'Agregar ' + type);
            add.type = 'button';
            add.dataset.addEditorModality = '1';
            add.dataset.storeUrl = source.dataset.storeUrl;
            add.dataset.type = type;
            head.append(add);
            wrapper.append(head, element('small', 'tree-editor-hint', 'Agrega este componente cuando lo necesites.'));
        }

        return wrapper;
    };

    const selectCourse = course => {
        if (!course) return;
        const groupNode = course.closest('.workload-tree-group-shell')?.querySelector('[data-tree-group-id]');
        if (!groupNode) return;
        selectedGroupId = groupNode.dataset.treeGroupId;
        selectedAssignmentId = course.dataset.assignmentId;
        document.querySelectorAll('.tree-structure-card .is-selected').forEach(node => node.classList.remove('is-selected'));
        groupNode.closest('.workload-tree-group-shell')?.classList.add('is-selected');
        course.classList.add('is-selected');

        editorKind.textContent = 'CURSO';
        editorTitle.textContent = course.dataset.courseName || 'Curso';
        editorDescription.textContent = 'Doble clic en el nombre o código para editar. Activa los componentes que necesites; siempre debe quedar uno activo.';
        editorFields.replaceChildren();

        const identity = element('div', 'tree-editor-identity');
        identity.append(
            editorField('Nombre del curso', course.dataset.courseName, {
                editable: true, url: course.dataset.courseNameUrl, field: 'name'
            }),
            editorField('Código institucional (opcional)', course.dataset.courseCode, {
                editable: true, url: course.dataset.courseCodeUrl, field: 'code', maxLength: 80,
                hint: 'Opcional. Si el curso se comparte con otros grupos, el cambio también se reflejará allí.'
            })
        );
        editorFields.append(identity);

        const sourceContainer = course.querySelector('[data-editor-modalities]');
        const sources = sourceContainer ? [...sourceContainer.querySelectorAll('[data-editor-modality]')] : [];
        const modalityList = element('div', 'tree-editor-modality-list');
        modalityList.append(element('div', 'tree-editor-section-title', 'Componentes del curso'));
        ['TEC', 'TALL'].forEach(type => {
            const source = sources.find(item => item.dataset.type === type);
            if (source) modalityList.append(makeModalityEditor(course, source));
        });
        editorFields.append(modalityList);

        const remove = element('div', 'tree-editor-remove-course');
        const removeButton = element('button', '', 'Retirar curso de este grupo');
        removeButton.type = 'button';
        removeButton.dataset.editorDeleteCourse = '1';
        remove.append(removeButton);
        editorFields.append(remove);
        showEditor();
    };

    const bindTreeNodes = scope => {
        scope.querySelectorAll('[data-tree-load]').forEach(node => {
            if (node.dataset.treeBound === '1') return;
            node.dataset.treeBound = '1';
            node.addEventListener('toggle', () => {
                node.classList.toggle('is-focused', node.open);
                if (node.open) loadTreeNode(node);
            });
        });
    };

    const loadTreeNode = async (node, force = false) => {
        if (!node || (!force && node.dataset.treeLoaded === '1') || node.dataset.treeLoading === '1') return;
        const content = node.querySelector('[data-tree-content]');
        if (!content) return;
        node.dataset.treeLoading = '1';
        content.innerHTML = '<div class="tree-loading-state">Cargando cursos…</div>';
        const url = new URL(node.dataset.treeLoad, window.location.href);
        const search = document.querySelector('.workload-tree-search [name="q"]');
        if (search?.value.trim()) url.searchParams.set('q', search.value.trim());

        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
            });
            if (!response.ok || response.redirected) throw new Error('No se pudieron cargar los cursos.');
            content.innerHTML = await response.text();
            node.dataset.treeLoaded = '1';
        } catch (error) {
            content.innerHTML = '<div class="tree-load-error">' + error.message + '</div>';
            node.dataset.treeLoaded = '0';
        } finally {
            node.dataset.treeLoading = '0';
        }
    };

    const refreshGroup = async (groupId, assignmentId = null) => {
        const groupNode = document.querySelector('[data-tree-group-id="' + groupId + '"]');
        if (!groupNode) return null;
        groupNode.open = true;
        groupNode.dataset.treeLoaded = '0';
        await loadTreeNode(groupNode, true);
        const course = assignmentId
            ? groupNode.querySelector('[data-assignment-id="' + assignmentId + '"]')
            : null;
        if (course) selectCourse(course);
        return course;
    };

    document.addEventListener('click', event => {
        const groupSummary = event.target.closest('[data-select-group]');
        if (groupSummary) {
            const groupNode = groupSummary.closest('[data-tree-group-id]');
            if (groupNode) selectGroup(groupNode);
            return;
        }

        const courseButton = event.target.closest('[data-select-course]');
        if (courseButton) {
            const course = courseButton.closest('[data-tree-course-item]');
            if (course) selectCourse(course);
            return;
        }

        const toggleGroupDelete = event.target.closest('[data-toggle-group-delete]');
        if (toggleGroupDelete) {
            const confirm = toggleGroupDelete.closest('.workload-tree-group-children')?.querySelector('[data-group-delete-confirm]');
            if (confirm) confirm.hidden = !confirm.hidden;
            return;
        }

        const toggleCourseDelete = event.target.closest('[data-toggle-course-delete]');
        if (toggleCourseDelete) {
            const confirm = toggleCourseDelete.closest('[data-tree-course-item]')?.querySelector('[data-course-delete-confirm]');
            if (confirm) confirm.hidden = !confirm.hidden;
            return;
        }

        const cancel = event.target.closest('[data-inline-cancel]');
        if (cancel) {
            cancel.closest('.tree-delete-confirm')?.setAttribute('hidden', '');
            return;
        }

        const editorDelete = event.target.closest('[data-editor-delete-course]');
        if (editorDelete) {
            const source = selectedCourseNode();
            const deleteForm = source?.querySelector('[data-tree-delete-course]');
            const confirm = source?.querySelector('[data-course-delete-confirm]');
            if (confirm) {
                confirm.hidden = false;
                confirm.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }
            return;
        }

        const addModality = event.target.closest('[data-add-editor-modality]');
        if (addModality) {
            const button = addModality;
            button.disabled = true;
            button.textContent = 'Agregando…';
            sendPost(button.dataset.storeUrl)
                .then(async payload => {
                    const courseId = selectedAssignmentId;
                    const groupId = selectedGroupId;
                    await refreshGroup(groupId, courseId);
                    showFeedback(payload.message || 'Componente agregado.');
                })
                .catch(error => {
                    button.disabled = false;
                    button.textContent = 'Agregar ' + button.dataset.type;
                    showFeedback(error.message, true);
                });
            return;
        }

        const toggleModality = event.target.closest('[data-editor-modality-toggle]');
        if (toggleModality) {
            const button = toggleModality;
            const currentlyActive = button.dataset.active === '1';
            button.disabled = true;
            button.querySelector('.tree-toggle-label').textContent = 'Guardando…';
            sendInlineUpdate(button.dataset.modalityUrl, {active: currentlyActive ? 0 : 1})
                .then(async payload => {
                    const groupId = button.dataset.groupId;
                    const assignmentId = button.dataset.assignmentId;
                    await refreshGroup(groupId, assignmentId);
                    showFeedback(payload.message || 'Modalidad actualizada.', Boolean(payload.warning));
                })
                .catch(error => {
                    button.disabled = false;
                    button.querySelector('.tree-toggle-label').textContent = currentlyActive ? 'Desactivar' : 'Activar';
                    showFeedback(error.message, true);
                });
            return;
        }
    });

    document.addEventListener('submit', async event => {
        const courseForm = event.target.closest('[data-tree-course-form]');
        if (courseForm) {
            event.preventDefault();
            const submit = courseForm.querySelector('[data-course-submit]');
            const errorBox = courseForm.querySelector('[data-course-form-error]');
            if (errorBox) {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Creando…';
            }
            try {
                const payload = await sendPost(courseForm.action, new FormData(courseForm));
                courseForm.reset();
                const groupId = courseForm.dataset.groupId;
                await refreshGroup(groupId, payload.assignment_id);
                const groupNode = document.querySelector('[data-tree-group-id="' + groupId + '"]');
                const count = groupNode?.querySelector('[data-group-course-count]');
                if (count) count.textContent = payload.assignments_count;
                showFeedback(payload.message || 'Curso creado.');
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = error.message;
                    errorBox.hidden = false;
                }
            } finally {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Crear curso';
                }
            }
            return;
        }

        const groupDelete = event.target.closest('[data-tree-delete-group]');
        if (groupDelete) {
            event.preventDefault();
            const submit = groupDelete.querySelector('[type="submit"]');
            const shell = groupDelete.closest('[data-group-shell]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Eliminando…';
            }
            try {
                const payload = await sendPost(groupDelete.action, new FormData(groupDelete));
                shell?.remove();
                const total = document.querySelector('[data-tree-group-total]');
                const remainingVisible = document.querySelectorAll('[data-group-shell]').length;
                const remainingTotal = Number(payload.remaining_groups || 0);
                if (total) total.textContent = remainingTotal + ' grupos';
                if (remainingVisible === 0) {
                    const message = remainingTotal > 0
                        ? 'No hay grupos que coincidan con esta búsqueda.'
                        : 'No quedan grupos en este ciclo.';
                    document.querySelector('.workload-tree-root').innerHTML = '<div class="tree-empty-child">' + message + '</div>';
                }
                clearEditor();
                document.querySelectorAll('[data-toggle-group-delete]').forEach(button => {
                    button.disabled = remainingTotal <= 1;
                    const label = button.querySelector('[data-group-delete-label]');
                    if (label) label.textContent = button.disabled ? 'Único grupo' : 'Eliminar grupo';
                });
                document.querySelectorAll('[data-group-delete-confirm] button[type="submit"]').forEach(button => {
                    button.disabled = remainingTotal <= 1;
                });
                showFeedback(payload.message || 'Grupo eliminado.');
            } catch (error) {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Confirmar';
                }
                showFeedback(error.message, true);
            }
            return;
        }

        const courseDelete = event.target.closest('[data-tree-delete-course]');
        if (courseDelete) {
            event.preventDefault();
            const submit = courseDelete.querySelector('[type="submit"]');
            const course = courseDelete.closest('[data-tree-course-item]');
            const groupNode = courseDelete.closest('[data-tree-group-id]');
            const groupId = groupNode?.dataset.treeGroupId || selectedGroupId;
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Retirando…';
            }
            try {
                const payload = await sendPost(courseDelete.action, new FormData(courseDelete));
                course?.remove();
                const actualGroup = document.querySelector('[data-tree-group-id="' + groupId + '"]');
                const count = actualGroup?.querySelector('[data-group-course-count]');
                if (count) count.textContent = payload.assignments_count;
                clearEditor();
                showFeedback(payload.message || 'Curso retirado.');
            } catch (error) {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Confirmar';
                }
                showFeedback(error.message, true);
            }
        }
    });

    document.addEventListener('dblclick', event => {
        const editable = event.target.closest('[data-editor-text]');
        if (editable) {
            const config = {
                value: editable.dataset.value || '',
                url: editable.dataset.url,
                field: editable.dataset.field,
                label: editable.dataset.label,
                maxLength: Number(editable.dataset.maxLength || 255),
                restore: value => {
                    editable.textContent = value || 'Sin código';
                    editable.dataset.value = value;
                },
                success: payload => {
                    const field = editable.dataset.field;
                    editable.dataset.value = field === 'name' ? payload.name : (payload.code ?? '');
                    editable.textContent = editable.dataset.value || 'Sin código';
                    const groupNode = selectedGroupNode();
                    const course = selectedCourseNode();

                    if (selectedAssignmentId && course) {
                        const sharedCourses = document.querySelectorAll('[data-course-id="' + course.dataset.courseId + '"]');
                        sharedCourses.forEach(sharedCourse => {
                            if (field === 'name') {
                                sharedCourse.dataset.courseName = payload.name;
                                const nameNode = sharedCourse.querySelector('[data-select-course] .tree-node-copy strong');
                                if (nameNode) nameNode.textContent = payload.name;
                                const confirmName = sharedCourse.querySelector('.tree-course-delete-confirm p strong');
                                if (confirmName) confirmName.textContent = '¿Retirar ' + payload.name + ' de este grupo?';
                            } else {
                                sharedCourse.dataset.courseCode = payload.code || '';
                                const codeNode = sharedCourse.querySelector('[data-select-course] .tree-node-copy small');
                                if (codeNode) codeNode.textContent = payload.code || 'Código opcional';
                            }
                        });
                        if (field === 'name') editorTitle.textContent = payload.name;
                    } else if (groupNode && field === 'name') {
                        groupNode.dataset.groupName = payload.name;
                        groupNode.dataset.groupLabel = 'Grupo ' + payload.name;
                        groupNode.querySelector('[data-select-group] .tree-node-copy strong').textContent = 'Grupo ' + payload.name;
                        editorTitle.textContent = 'Grupo ' + payload.name;
                    }
                }
            };
            startTextEditor(editable, config);
            return;
        }

        const nrc = event.target.closest('[data-inline-nrc]');
        if (nrc) {
            startNrcEditor(nrc);
            return;
        }

        const date = event.target.closest('[data-inline-date]');
        if (date) {
            const wrapper = date.closest('[data-modality-dates]');
            if (wrapper) startDateEditor(date, wrapper);
        }
    });

    document.querySelectorAll('[data-tree-load]').forEach(node => {
        node.addEventListener('toggle', () => {
            if (node.open) loadTreeNode(node);
        });
    });

    // Deep-link access may request a group editor without expanding it automatically.
})();
</script>
@endpush