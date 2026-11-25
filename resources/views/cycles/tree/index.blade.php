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
            <details class="tree-inline-create-group" @if($errors->has('groups.0.name')) open @endif>
                <summary><x-icon name="plus" /> Agregar grupo</summary>
                <form method="POST" action="{{ route('groups.store', $cycle) }}" class="tree-group-create-form">
                    @csrf
                    <input type="hidden" name="return_to_tree" value="1">
                    <label class="tree-entry-field">
                        <span>Nombre del nuevo grupo</span>
                        <input class="field" name="name" value="{{ old('name') }}" maxlength="100" required placeholder="Ej. IA 201">
                        @error('groups.0.name')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                    <button class="btn" type="submit"><x-icon name="check" /> Crear grupo</button>
                </form>
            </details>
        </div>
    </section>

    <section class="card workload-tree-browser">
        <div class="card-head workload-tree-browser-head">
            <div>
                <p class="eyebrow">ESTRUCTURA ACADÉMICA</p>
                <h2>Grupos y cursos</h2>
                <p class="card-description">Despliega un grupo para administrar sus cursos y modalidades. Doble clic en NRC o fechas para modificarlos.</p>
            </div>
            <span class="workload-tree-total" data-tree-group-total>{{ $groups->count() }} grupos</span>
        </div>

        <form method="GET" action="{{ route('cycles.tree', $cycle) }}" class="workload-tree-search">
            <label class="workload-tree-search-field">
                <x-icon name="search" size="17" />
                <input type="search" name="q" value="{{ $term }}" placeholder="Buscar grupo, curso, código, NRC o bloque…" aria-label="Buscar en el árbol">
            </label>
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if($term !== '')
                <a class="btn btn-ghost" href="{{ route('cycles.tree', $cycle) }}"><x-icon name="x" /> Limpiar</a>
            @endif
        </form>
        <p class="tree-live-feedback" id="tree-live-feedback" role="status" hidden></p>

        <div class="workload-tree-root">
            @include('cycles.tree.groups', ['groups' => $groups, 'cycle' => $cycle])
        </div>
    </section>
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
.tree-inline-name:hover{background:var(--accent-soft);color:var(--accent)}
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
</style>
@endpush

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const liveFeedback = document.getElementById('tree-live-feedback');

    const showFeedback = (message, isError = false) => {
        if (!liveFeedback || !message) return;
        liveFeedback.textContent = message;
        liveFeedback.classList.toggle('is-error', isError);
        liveFeedback.hidden = false;
    };

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
            const errors = Object.values(payload.errors || {}).flat();
            throw new Error(errors.join(' ') || payload.message || 'No se pudo guardar el cambio.');
        }
        return payload;
    };

    const syncCourseForm = form => {
        ['TEC', 'TALL'].forEach(type => {
            const selected = [...form.querySelectorAll('[data-course-component]')]
                .some(input => input.value === type && input.checked);
            const panel = form.querySelector('[data-course-config="' + type + '"]');
            if (panel) panel.hidden = !selected;
            panel?.querySelectorAll('input').forEach(input => input.disabled = !selected);
        });
    };

    document.querySelectorAll('[data-tree-course-form]').forEach(syncCourseForm);

    document.addEventListener('change', event => {
        if (!event.target.matches('[data-course-component]')) return;
        const form = event.target.closest('[data-tree-course-form]');
        if (form) syncCourseForm(form);
    });

    const bindTreeNodes = scope => {
        scope.querySelectorAll('[data-tree-load]').forEach(node => {
            if (node.dataset.treeBound === 'true') return;
            node.dataset.treeBound = 'true';
            node.addEventListener('toggle', () => {
                node.classList.toggle('is-focused', node.open);
                node.closest('.workload-tree-group-shell')?.classList.toggle('is-focused', node.open);
                if (node.open) loadTreeNode(node);
            });
        });
    };

    const loadTreeNode = async (node, force = false) => {
        if (!node || (!force && node.dataset.treeLoaded === 'true') || node.dataset.treeLoading === 'true') return;
        const content = node.querySelector('[data-tree-content]');
        if (!content) return;

        node.dataset.treeLoading = 'true';
        content.innerHTML = '<div class="tree-loading-state" role="status">Cargando cursos y modalidades…</div>';
        const url = new URL(node.dataset.treeLoad, window.location.href);
        const search = document.querySelector('.workload-tree-search [name="q"]');
        if (search?.value.trim()) url.searchParams.set('q', search.value.trim());

        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
            });
            if (!response.ok || response.redirected) throw new Error('No se pudo cargar este grupo.');
            content.innerHTML = await response.text();
            node.dataset.treeLoaded = 'true';
        } catch (error) {
            content.innerHTML = '<div class="tree-load-error" role="alert">No se pudo cargar. Cierra y vuelve a abrir para intentar otra vez.</div>';
            node.dataset.treeLoaded = 'false';
        } finally {
            node.dataset.treeLoading = 'false';
        }
    };

    const refreshGroup = async (groupNode, count) => {
        if (!groupNode) return;
        if (count !== undefined) {
            const countNode = groupNode.querySelector('[data-group-course-count]');
            if (countNode) countNode.textContent = count;
        }
        groupNode.open = true;
        groupNode.dataset.treeLoaded = 'false';
        await loadTreeNode(groupNode, true);
    };

    const openCourseEntry = (shell, preset = null) => {
        const groupNode = shell?.querySelector('[data-tree-group-id]');
        const form = shell?.querySelector('[data-tree-course-form]');
        const button = shell?.querySelector('[data-toggle-course-entry]');
        if (!form) return;

        if (preset) {
            form.reset();
            form.querySelector('[name="course_code"]').value = preset.courseCode || '';
            form.querySelector('[name="course_name"]').value = preset.courseName || '';
            form.querySelectorAll('[data-course-component]').forEach(input => {
                input.checked = input.value === preset.component;
            });
            syncCourseForm(form);
        }

        form.hidden = false;
        button?.setAttribute('aria-expanded', 'true');
        groupNode && (groupNode.open = true);
        const fieldName = preset?.component === 'TALL' ? 'tall_nrc' : 'tec_nrc';
        const focusTarget = preset ? form.querySelector('[name="' + fieldName + '"]') : form.querySelector('[name="course_code"]');
        setTimeout(() => focusTarget?.focus(), 60);
        form.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    };

    const closeCourseEntry = form => {
        if (!form) return;
        form.hidden = true;
        form.reset();
        syncCourseForm(form);
        form.closest('.tree-course-entry-area')?.querySelector('[data-toggle-course-entry]')?.setAttribute('aria-expanded', 'false');
    };

    const displayDate = value => {
        if (!value) return 'Sin fecha';
        const [year, month, day] = value.split('-');
        return day + '/' + month + '/' + year;
    };

    const updateCourseComponentChips = course => {
        if (!course) return;
        ['TEC', 'TALL'].forEach(type => {
            const chip = course.querySelector('[data-component-chip="' + type + '"]');
            const row = course.querySelector('[data-modality-row][data-modality-type="' + type + '"]');
            if (!chip) return;
            chip.classList.toggle('is-missing', !row);
            chip.classList.toggle('is-disabled', Boolean(row && row.dataset.active !== '1'));
            chip.classList.toggle('is-present', Boolean(row && row.dataset.active === '1'));
        });
    };

    const syncModalitySwitches = course => {
        if (!course) return;
        const buttons = [...course.querySelectorAll('[data-toggle-modality]')];
        const activeCount = buttons.filter(button => button.dataset.active === '1').length;

        buttons.forEach(button => {
            const isActive = button.dataset.active === '1';
            const label = button.querySelector('[data-toggle-label]');
            button.disabled = isActive && activeCount <= 1;
            button.classList.toggle('is-on', isActive);
            button.classList.toggle('is-off', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.title = isActive && activeCount <= 1
                ? 'Debes mantener al menos una modalidad activa'
                : (isActive ? 'Desactivar esta modalidad' : 'Activar esta modalidad');
            if (label) label.textContent = isActive && activeCount <= 1
                ? 'Obligatoria'
                : (isActive ? 'Desactivar' : 'Activar');
        });
        updateCourseComponentChips(course);
    };

    const refreshGroupLastDeleteGuard = remaining => {
        const shouldDisable = Number(remaining) <= 1;
        document.querySelectorAll('[data-toggle-group-delete]').forEach(button => {
            button.disabled = shouldDisable;
            button.textContent = shouldDisable ? 'Único grupo' : 'Eliminar grupo';
            button.title = shouldDisable ? 'Cada ciclo debe conservar al menos un grupo' : 'Eliminar este grupo';
        });
        document.querySelectorAll('[data-group-delete-confirm] button[type="submit"]').forEach(button => {
            button.disabled = shouldDisable;
        });
    };

    document.addEventListener('click', event => {
        const groupName = event.target.closest('[data-inline-group-name]');
        if (groupName) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        const toggleEntry = event.target.closest('[data-toggle-course-entry]');
        if (toggleEntry) {
            const shell = toggleEntry.closest('.workload-tree-group-shell');
            const form = shell?.querySelector('[data-tree-course-form]');
            if (!form) return;
            if (form.hidden) openCourseEntry(shell);
            else closeCourseEntry(form);
            return;
        }

        const missingModality = event.target.closest('[data-open-inline-course-entry]');
        if (missingModality) {
            const shell = document.querySelector('[data-group-shell="' + missingModality.dataset.groupId + '"]');
            openCourseEntry(shell, {
                courseCode: missingModality.dataset.courseCode,
                courseName: missingModality.dataset.courseName,
                component: missingModality.dataset.component
            });
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

        const closeEntry = event.target.closest('[data-close-course-entry]');
        if (closeEntry) {
            closeCourseEntry(closeEntry.closest('[data-tree-course-form]'));
            return;
        }

        const cancel = event.target.closest('[data-inline-cancel]');
        if (cancel) {
            const confirm = cancel.closest('.tree-delete-confirm');
            if (confirm) confirm.hidden = true;
            return;
        }

        const toggleModality = event.target.closest('[data-toggle-modality]');
        if (toggleModality) {
            event.preventDefault();
            const course = toggleModality.closest('[data-tree-course-item]');
            const row = toggleModality.closest('[data-modality-row]');
            const currentlyActive = toggleModality.dataset.active === '1';
            toggleModality.disabled = true;
            const label = toggleModality.querySelector('[data-toggle-label]');
            if (label) label.textContent = 'Guardando…';

            sendInlineUpdate(toggleModality.dataset.modalityUrl, {active: currentlyActive ? 0 : 1})
                .then(payload => {
                    toggleModality.dataset.active = payload.active ? '1' : '0';
                    row.dataset.active = payload.active ? '1' : '0';
                    row.classList.toggle('is-active', payload.active);
                    row.classList.toggle('is-disabled', !payload.active);

                    const status = row.querySelector('[data-modality-status]');
                    if (status) {
                        status.className = 'tree-course-status ' + (!payload.active ? 'is-disabled' : 'is-pending');
                        status.textContent = payload.active ? 'Activa' : 'Desactivada';
                    }

                    const description = row.querySelector('[data-modality-description]');
                    if (description) {
                        description.textContent = payload.active
                            ? (description.dataset.activeDescription || 'Modalidad activa.')
                            : 'Modalidad desactivada · Horarios e historial conservados';
                    }
                    const link = row.querySelector('.tree-open-modality');
                    if (link) link.hidden = !payload.active;

                    syncModalitySwitches(course);
                    showFeedback(payload.message || 'Modalidad actualizada.', Boolean(payload.warning));
                })
                .catch(error => {
                    syncModalitySwitches(course);
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
            const error = courseForm.querySelector('[data-course-form-error]');
            if (error) {
                error.hidden = true;
                error.textContent = '';
            }
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Guardando…';
            }

            try {
                const response = await fetch(courseForm.action, {
                    method: 'POST',
                    body: new FormData(courseForm),
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const messages = Object.values(payload.errors || {}).flat();
                    throw new Error(messages.join(' ') || payload.message || 'No se pudo guardar el curso.');
                }

                const shell = courseForm.closest('.workload-tree-group-shell');
                const groupNode = shell?.querySelector('[data-tree-group-id]');
                closeCourseEntry(courseForm);
                await refreshGroup(groupNode, payload.assignments_count);
                showFeedback(payload.warnings?.length ? payload.message + ' ' + payload.warnings.join(' ') : payload.message);
            } catch (error) {
                if (courseForm.querySelector('[data-course-form-error]')) {
                    const box = courseForm.querySelector('[data-course-form-error]');
                    box.textContent = error.message || 'No se pudo guardar el curso.';
                    box.hidden = false;
                }
            } finally {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Guardar curso';
                }
            }
            return;
        }

        const deleteGroupForm = event.target.closest('[data-tree-delete-group]');
        if (deleteGroupForm) {
            event.preventDefault();
            const submit = deleteGroupForm.querySelector('button[type="submit"]');
            const shell = deleteGroupForm.closest('.workload-tree-group-shell');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Eliminando…';
            }

            try {
                const response = await fetch(deleteGroupForm.action, {
                    method: 'POST',
                    body: new FormData(deleteGroupForm),
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'No se pudo eliminar el grupo.');
                shell?.remove();

                const total = document.querySelector('[data-tree-group-total]');
                const visibleCount = document.querySelectorAll('.workload-tree-group-shell').length;
                if (total) total.textContent = visibleCount + ' grupos';
                const root = document.querySelector('.workload-tree-root');
                if (root && visibleCount === 0) root.innerHTML = '<div class="tree-empty-child">No quedan grupos en este ciclo.</div>';
                refreshGroupLastDeleteGuard(payload.remaining_groups);
                showFeedback(payload.message || 'Grupo eliminado.');
            } catch (error) {
                showFeedback(error.message, true);
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Confirmar eliminación';
                }
            }
            return;
        }

        const deleteCourseForm = event.target.closest('[data-tree-delete-course]');
        if (deleteCourseForm) {
            event.preventDefault();
            const submit = deleteCourseForm.querySelector('button[type="submit"]');
            const course = deleteCourseForm.closest('[data-tree-course-item]');
            const groupNode = deleteCourseForm.closest('.workload-tree-group-shell')?.querySelector('[data-tree-group-id]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Retirando…';
            }

            try {
                const response = await fetch(deleteCourseForm.action, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'No se pudo retirar el curso.');
                course?.remove();

                const countNode = groupNode?.querySelector('[data-group-course-count]');
                if (countNode) countNode.textContent = payload.assignments_count ?? Math.max(0, Number(countNode.textContent) - 1);
                const content = groupNode?.querySelector('[data-tree-content]');
                if (content && content.querySelectorAll('[data-tree-course-item]').length === 0) {
                    content.innerHTML = '<div class="tree-empty-child">Este grupo todavía no tiene cursos. Usa «Agregar curso» para comenzar.</div>';
                }
                showFeedback(payload.message || 'Curso retirado.');
            } catch (error) {
                showFeedback(error.message, true);
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Confirmar retiro';
                }
            }
            return;
        }
    });

    const startTextEditor = (target, config) => {
        if (target.querySelector('input')) return;
        const original = config.value;
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = config.maxLength || 255;
        input.className = 'tree-inline-editor';
        input.value = original;
        input.setAttribute('aria-label', config.label);
        target.replaceChildren(input);
        target.classList.add('tree-inline-saving');
        input.focus();
        input.select();

        let settled = false;
        const restore = () => {
            if (settled) return;
            settled = true;
            config.restore(original);
            target.classList.remove('tree-inline-saving');
        };
        const save = async () => {
            if (settled) return;
            const value = input.value.trim();
            if (!value || value === original) {
                restore();
                return;
            }

            settled = true;
            target.classList.remove('tree-inline-saving');
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

        input.addEventListener('keydown', keyEvent => {
            if (keyEvent.key === 'Escape') {
                keyEvent.preventDefault();
                restore();
            }
            if (keyEvent.key === 'Enter') {
                keyEvent.preventDefault();
                save();
            }
        });
        input.addEventListener('blur', save, {once: true});
    };

    const startDateEditor = (cell, wrapper) => {
        if (cell.querySelector('input')) return;
        const field = cell.dataset.inlineDate;
        const original = wrapper.dataset[field === 'start_date' ? 'startDate' : 'endDate'] || '';
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
            const next = input.value;
            if (next === original) {
                restore();
                return;
            }
            settled = true;
            wrapper.classList.add('tree-inline-saving');
            const values = {
                start_date: field === 'start_date' ? next : wrapper.dataset.startDate,
                end_date: field === 'end_date' ? next : wrapper.dataset.endDate
            };
            try {
                const payload = await sendInlineUpdate(wrapper.dataset.datesUrl, values);
                wrapper.dataset.startDate = payload.start_date || '';
                wrapper.dataset.endDate = payload.end_date || '';
                wrapper.querySelectorAll('[data-inline-date]').forEach(date => {
                    date.textContent = payload[date.dataset.inlineDate]
                        ? displayDate(payload[date.dataset.inlineDate])
                        : 'Sin fecha';
                });
                showFeedback(payload.message);
            } catch (error) {
                cell.textContent = original ? displayDate(original) : 'Sin fecha';
                showFeedback(error.message, true);
            } finally {
                wrapper.classList.remove('tree-inline-saving');
            }
        };

        input.addEventListener('keydown', keyEvent => {
            if (keyEvent.key === 'Escape') {
                keyEvent.preventDefault();
                restore();
            }
            if (keyEvent.key === 'Enter') {
                keyEvent.preventDefault();
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
            cell.textContent = original || 'NRC pendiente';
        };
        const save = async () => {
            if (settled) return;
            const value = input.value.trim();
            if (value === original) {
                restore();
                return;
            }
            settled = true;
            cell.classList.add('tree-inline-saving');
            try {
                const payload = await sendInlineUpdate(cell.dataset.nrcUrl, {nrc: value});
                cell.dataset.nrc = payload.nrc || '';
                cell.textContent = payload.nrc || 'NRC pendiente';
                showFeedback(payload.message);
            } catch (error) {
                cell.textContent = original || 'NRC pendiente';
                showFeedback(error.message, true);
            } finally {
                cell.classList.remove('tree-inline-saving');
            }
        };
        input.addEventListener('keydown', keyEvent => {
            if (keyEvent.key === 'Escape') {
                keyEvent.preventDefault();
                restore();
            }
            if (keyEvent.key === 'Enter') {
                keyEvent.preventDefault();
                save();
            }
        });
        input.addEventListener('blur', save, {once: true});
    };

    document.addEventListener('dblclick', event => {
        const groupName = event.target.closest('[data-inline-group-name]');
        if (groupName) {
            startTextEditor(groupName, {
                value: groupName.dataset.groupName || '',
                url: groupName.dataset.groupNameUrl,
                field: 'name',
                label: 'Nombre del grupo',
                maxLength: 100,
                restore: value => {
                    groupName.textContent = 'Grupo ' + value;
                    groupName.dataset.groupName = value;
                },
                success: payload => {
                    groupName.textContent = 'Grupo ' + payload.name;
                    groupName.dataset.groupName = payload.name;
                    groupName.closest('[data-tree-group-id]').dataset.groupLabel = 'Grupo ' + payload.name;
                }
            });
            return;
        }

        const courseName = event.target.closest('[data-inline-course-name]');
        if (courseName) {
            startTextEditor(courseName, {
                value: courseName.dataset.courseName || '',
                url: courseName.dataset.courseNameUrl,
                field: 'name',
                label: 'Nombre del curso',
                restore: value => {
                    courseName.textContent = value;
                    courseName.dataset.courseName = value;
                },
                success: payload => {
                    courseName.textContent = payload.name;
                    courseName.dataset.courseName = payload.name;
                    const course = courseName.closest('[data-tree-course-item]');
                    course?.querySelector('.tree-course-delete-confirm p strong')?.replaceChildren('¿Retirar ', payload.name, ' de este grupo?');
                }
            });
            return;
        }

        const dateCell = event.target.closest('[data-inline-date]');
        if (dateCell) {
            const wrapper = dateCell.closest('[data-modality-dates]');
            if (wrapper) startDateEditor(dateCell, wrapper);
            return;
        }

        const nrcCell = event.target.closest('[data-inline-nrc]');
        if (nrcCell) startNrcEditor(nrcCell);
    });

    bindTreeNodes(document);
})();
</script>
@endpush