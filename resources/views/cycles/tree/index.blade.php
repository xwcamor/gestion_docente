@extends('layouts.app')
@section('title', $cycle->academic_year.' · Ciclo '.$cycle->cycle_code.' · Árbol de trabajo')
@section('content')
<div class="workload-tree-page">
    <div class="breadcrumbs">
        <a href="{{ route('cycles.index') }}">Cargas laborales</a>
        <x-icon name="chevron-right" size="13" />
        <span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span>
    </div>

    <section class="workload-tree-hero">
        <div class="workload-tree-hero-copy">
            <p class="eyebrow">ORGANIZACIÓN ACADÉMICA</p>
            <h1>{{ $cycle->academic_year }} <span>· Ciclo {{ $cycle->cycle_code }}</span></h1>
            <div class="workload-tree-context">
                <span>Periodo: {{ $cycle->start_date?->format('d/m/Y') ?: 'Sin inicio' }} – {{ $cycle->end_date?->format('d/m/Y') ?: 'Sin fin' }}</span>
                <span class="tree-context-hint">Carrera y semestre se configuran en cada grupo</span>
            </div>
        </div>
        <div class="workload-tree-hero-actions">
            <a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}"><x-icon name="settings" size="16" /> Datos del ciclo</a>
        </div>
    </section>

    <section class="card tree-structure-card">
        <div class="tree-panel-heading">
            <div>
                <p class="eyebrow">ÁRBOL DE TRABAJO</p>
                <h2>Grupos, cursos y componentes</h2>
                <p>Despliega un grupo para gestionar sus cursos, componentes y horarios.</p>
            </div>
            <span class="workload-tree-total" data-tree-group-total>{{ $cycleGroupCount }} {{ (int) $cycleGroupCount === 1 ? 'grupo' : 'grupos' }}</span>
        </div>

        <form method="GET" action="{{ route('cycles.tree', $cycle) }}" class="workload-tree-search">
            <label class="workload-tree-search-field">
                <x-icon name="search" size="19" />
                <input type="search" name="q" value="{{ $term }}" placeholder="Buscar grupo, curso o NRC…" aria-label="Buscar en el árbol">
            </label>
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if($term !== '')
                <a class="btn btn-ghost" href="{{ route('cycles.tree', $cycle) }}"><x-icon name="x" size="16" /> Limpiar</a>
            @endif
        </form>

        <div class="workload-tree-root" aria-label="Estructura académica">
            <div class="tree-cycle-root" data-tree-cycle-root>
                <strong>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</strong>
                <small>{{ $cycle->groups_count ?? $cycleGroupCount }} {{ (int) ($cycle->groups_count ?? $cycleGroupCount) === 1 ? 'grupo' : 'grupos' }}</small>
            </div>
            @include('cycles.tree.groups', ['groups' => $groups, 'cycle' => $cycle])
        </div>

        <form method="POST" action="{{ route('groups.store', $cycle) }}"
              class="tree-group-draft-form" data-tree-group-form hidden>
            @csrf
            <input type="hidden" name="return_to_tree" value="1">
            <div class="tree-draft-heading">
                <strong><x-icon name="folder-plus" size="17" /> Nuevo grupo</strong>
                <span>Curso inicial automático</span>
            </div>
            <div class="tree-draft-fields">
                <label class="tree-course-name-input">
                    <span>Nombre del grupo</span>
                    <input class="field" name="name" maxlength="100" required placeholder="Ej. Grupo 01">
                </label>
            </div>
            <div class="tree-draft-footer">
                <span class="tree-draft-note">El grupo tendrá un curso inicial con TEC y TAL activos.</span>
                <span class="tree-draft-error" data-group-form-error hidden role="alert"></span>
                <div>
                    <button class="btn btn-ghost" type="button" data-cancel-group-draft>Cancelar</button>
                    <button class="btn" type="submit" data-group-submit><x-icon name="check" size="16" /> Guardar grupo</button>
                </div>
            </div>
        </form>
        <div class="tree-add-row tree-add-group-row" data-group-add-row>
            <button type="button" data-show-group-draft><x-icon name="plus" size="17" /> Agregar grupo</button>
        </div>
    </section>

    <p class="tree-live-feedback" id="tree-live-feedback" role="status" aria-live="polite" hidden></p>
</div>


@push('styles')
<style>.workload-tree-page{width:100%;max-width:none}.workload-tree-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:0 0 14px;padding:clamp(19px,2.2vw,30px);border:1px solid color-mix(in srgb,var(--accent) 34%,var(--line));border-radius:16px;background:linear-gradient(120deg,var(--accent-soft),var(--surface) 72%);box-shadow:0 5px 22px rgba(25,56,78,.055)}.workload-tree-hero-copy{min-width:0}.workload-tree-hero .eyebrow{margin:0 0 8px;color:var(--accent);font-size:9px;font-weight:850;letter-spacing:.13em}.workload-tree-hero h1{margin:0;color:var(--ink);font-size:clamp(29px,3.4vw,44px);font-weight:850;letter-spacing:-.055em;line-height:1.04}.workload-tree-hero h1 span{color:var(--muted);font-size:.68em;font-weight:650;letter-spacing:-.035em}.workload-tree-context{display:flex;align-items:center;gap:8px 18px;flex-wrap:wrap;margin-top:13px;color:var(--muted);font-size:11px}.workload-tree-context span{display:inline-flex;align-items:center;gap:6px}.workload-tree-hero-actions{display:flex;align-items:flex-start;gap:8px;flex:none;flex-wrap:wrap}.workload-tree-hero-actions>.btn{min-height:40px}.tree-inline-create-group{position:relative}.tree-inline-create-group>summary{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:10px 14px;border:1px solid color-mix(in srgb,var(--accent) 45%,var(--line));border-radius:9px;background:var(--accent);color:#fff;font-size:11px;font-weight:800;cursor:pointer;list-style:none;box-shadow:0 3px 10px color-mix(in srgb,var(--accent) 22%,transparent);transition:transform .15s ease,box-shadow .15s ease}.tree-inline-create-group>summary::-webkit-details-marker{display:none}.tree-inline-create-group>summary:hover{transform:translateY(-1px);box-shadow:0 6px 15px color-mix(in srgb,var(--accent) 28%,transparent)}.tree-group-create-form{position:absolute;z-index:20;top:calc(100% + 9px);right:0;display:grid;grid-template-columns:minmax(190px,1fr) auto;align-items:end;gap:10px;width:min(480px,calc(100vw - 28px));padding:14px;border:1px solid color-mix(in srgb,var(--accent) 35%,var(--line));border-radius:12px;background:var(--surface);box-shadow:0 18px 45px rgba(15,30,45,.18)}.tree-entry-field{display:grid;gap:6px;min-width:0;color:var(--ink-soft);font-size:10px;font-weight:750}.tree-entry-field .field{width:100%;min-width:0}.tree-entry-field small{color:var(--muted);font-size:9px;font-weight:450}.tree-group-create-form .btn{min-height:39px;white-space:nowrap}.workload-tree-browser{padding:clamp(14px,1.8vw,22px);border-color:var(--line-strong);border-radius:14px}.workload-tree-browser-head{align-items:flex-end;margin-bottom:13px}.workload-tree-browser-head .eyebrow{margin-bottom:5px;color:var(--accent);font-size:9px;font-weight:850;letter-spacing:.12em}.workload-tree-browser-head h2{color:var(--ink);font-size:21px;font-weight:800;letter-spacing:-.03em}.workload-tree-total{flex:none;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:10px;font-weight:750}.workload-tree-search{display:flex;gap:8px;margin-bottom:14px}.workload-tree-search-field{display:flex;align-items:center;gap:9px;flex:1;min-width:0;height:42px;margin:0;padding:0 12px;border:1px solid var(--line-strong);border-radius:9px;background:var(--surface);color:var(--muted);transition:border-color .15s ease,box-shadow .15s ease}.workload-tree-search-field:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 15%,transparent)}.workload-tree-search-field input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--ink);font-size:12px}.workload-tree-search-field input:focus{box-shadow:none}.workload-tree-search-field input::placeholder{color:var(--faint)}.workload-tree-search .btn{min-height:42px}.workload-tree-root{display:grid;gap:10px}.workload-tree-root>.workload-tree-group-shell{margin:0}.workload-tree-root>.tree-empty-child{padding:34px 16px;border:1px dashed var(--line-strong);border-radius:10px;text-align:center}.workload-tree-group-shell{position:relative;overflow:visible;border:1px solid var(--line-strong);border-radius:12px;background:var(--surface);transition:border-color .16s ease,box-shadow .16s ease,background .16s ease}.workload-tree-group-shell.is-focused{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent),0 8px 22px rgba(28,63,88,.075)}.workload-tree-group{margin:0;border:0;border-radius:12px;background:var(--surface);overflow:visible}.workload-group-summary{min-height:66px;padding:12px 158px 12px 14px;border-radius:12px;background:var(--surface);transition:background .15s ease}.workload-tree-summary{display:flex;align-items:center;gap:10px;min-width:0;list-style:none;cursor:pointer}.workload-tree-summary::-webkit-details-marker{display:none}.workload-tree-summary:focus-visible{outline:2px solid var(--accent);outline-offset:-2px;border-radius:8px}.workload-tree-summary:hover{background:var(--accent-soft)}.workload-tree-group[open]>.workload-group-summary{border-bottom:1px solid color-mix(in srgb,var(--accent) 28%,var(--line));border-radius:12px 12px 0 0;background:linear-gradient(90deg,var(--accent-soft),var(--surface))}.workload-tree-group[open]>.workload-group-summary .tree-node-icon{background:var(--accent);color:#fff}.workload-tree-children{position:relative;margin-left:25px;padding:10px 13px 14px 17px;border-left:2px solid color-mix(in srgb,var(--accent) 36%,var(--line))}.workload-group-children{margin:0 0 0 22px;padding:11px 12px 14px 16px}.workload-tree-group-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:0 0 9px}.workload-tree-group-actions .btn{min-height:32px;padding:6px 9px;font-size:10px}.tree-node-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;transition:background .15s ease,color .15s ease}.tree-group-icon{background:var(--info-bg);color:var(--info)}.tree-course-icon{background:var(--accent-soft);color:var(--accent)}.tree-node-copy{display:grid;gap:3px;min-width:0;flex:1}.tree-node-copy strong{color:var(--ink);font-size:13px;font-weight:800;line-height:1.35;overflow-wrap:anywhere}.tree-node-copy small{color:var(--muted);font-size:10px;line-height:1.45;overflow-wrap:anywhere}.tree-branch-mark{position:relative;flex:0 0 11px;width:11px;height:1px;background:var(--line-strong)}.tree-chevron{display:grid;place-items:center;flex:none;color:var(--muted);transition:transform .16s ease,color .16s ease}.workload-tree-group[open]>.workload-tree-summary .tree-chevron,.workload-tree-course[open]>.workload-tree-summary .tree-chevron{transform:rotate(90deg);color:var(--accent)}.workload-tree-add-course-inline{position:absolute;z-index:5;top:11px;right:11px}.workload-tree-add-course-inline>summary{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:36px;padding:8px 11px;border:1px solid color-mix(in srgb,var(--accent) 40%,var(--line));border-radius:9px;background:var(--accent-soft);color:var(--accent);font-size:10px;font-weight:850;cursor:pointer;list-style:none;transition:background .15s ease,transform .15s ease}.workload-tree-add-course-inline>summary::-webkit-details-marker{display:none}.workload-tree-add-course-inline>summary:hover{transform:translateY(-1px);background:color-mix(in srgb,var(--accent) 18%,var(--surface))}.workload-tree-add-course-inline[open]{position:relative;top:auto;right:auto;padding:13px;border-top:1px solid color-mix(in srgb,var(--accent) 24%,var(--line));border-radius:0 0 12px 12px;background:linear-gradient(120deg,var(--accent-soft),var(--surface) 72%);animation:tree-reveal .18s ease-out}.workload-tree-add-course-inline[open]>summary{margin-bottom:12px;background:var(--accent);color:#fff}.tree-course-entry-form{display:grid;gap:12px}.tree-course-entry-heading{display:flex;align-items:center;justify-content:space-between;gap:12px}.tree-course-entry-heading>div{display:grid;gap:3px}.tree-course-entry-heading strong{color:var(--ink);font-size:13px;font-weight:850}.tree-course-entry-heading small{color:var(--muted);font-size:10px}.tree-inline-step{padding:5px 8px;border:1px solid var(--line);border-radius:6px;background:var(--surface);color:var(--muted);font-size:8px;font-weight:850;letter-spacing:.1em}.tree-course-entry-error{padding:10px 12px;border:1px solid var(--danger);border-radius:8px;background:var(--danger-bg);color:var(--danger);font-size:11px;line-height:1.5}.tree-entry-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.tree-entry-grid-course{grid-template-columns:minmax(190px,.7fr) minmax(240px,1.3fr)}.tree-entry-grid-three{grid-template-columns:repeat(3,minmax(0,1fr))}.tree-entry-field .field{height:39px}.tree-shared-course-note{margin:0;color:var(--muted);font-size:9px;line-height:1.5}.tree-entry-field .field:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 13%,transparent)}.tree-entry-components{display:flex;align-items:center;gap:10px 18px;flex-wrap:wrap;margin:0;padding:12px 13px;border:1px solid var(--line-strong);border-radius:10px;background:var(--surface)}.tree-entry-components legend{padding:0 5px;color:var(--muted);font-size:9px;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.tree-entry-components label{display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--ink);font-size:11px;font-weight:700;cursor:pointer}.tree-entry-components input,.tree-entry-days input{accent-color:var(--accent)}.tree-entry-components>small{flex:1 1 100%;color:var(--muted);font-size:9px}.tree-component-choice{display:inline-grid;place-items:center;min-width:37px;height:25px;padding:0 7px;border-radius:6px;font-size:9px;font-weight:900}.tree-component-choice.is-tec{background:var(--info-bg);color:var(--info)}.tree-component-choice.is-tall{background:var(--accent-soft);color:var(--accent)}.tree-entry-component{display:grid;gap:11px;padding:13px;border:1px solid color-mix(in srgb,var(--accent) 25%,var(--line));border-radius:10px;background:var(--surface)}.tree-entry-component[hidden]{display:none!important}.tree-entry-component-title{display:flex;align-items:center;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--line);color:var(--ink);font-size:11px}.tree-entry-schedule{padding-top:10px;border-top:1px solid var(--line)}.tree-entry-schedule>summary{color:var(--accent);font-size:10px;font-weight:800;cursor:pointer}.tree-entry-days{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:10px 0}.tree-entry-days label{display:inline-flex;align-items:center;gap:4px;margin:0;padding:6px 8px;border:1px solid var(--line);border-radius:7px;background:var(--surface-soft);color:var(--ink-soft);font-size:10px;cursor:pointer}.tree-course-entry-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:10px;border-top:1px solid var(--line)}.tree-course-entry-footer>span{color:var(--muted);font-size:9px;line-height:1.4}.tree-course-entry-footer .btn{min-height:39px}.workload-tree-course{margin:8px 0;border:1px solid var(--line);border-radius:10px;background:var(--surface);overflow:hidden;transition:border-color .15s ease,box-shadow .15s ease}.workload-tree-course.is-focused{border-color:color-mix(in srgb,var(--accent) 48%,var(--line));box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 10%,transparent)}.workload-course-summary{min-height:57px;padding:10px 12px}.workload-tree-course[open]>.workload-course-summary{border-bottom:1px solid color-mix(in srgb,var(--accent) 24%,var(--line));background:var(--accent-soft)}.workload-tree-course[open]>.workload-course-summary .tree-course-icon{background:var(--accent);color:#fff}.workload-course-children{margin-left:17px;padding:10px 11px 13px 14px}.tree-course-components{display:flex;align-items:center;gap:5px;flex:none}.tree-component-chip{display:inline-flex;align-items:center;padding:5px 8px;border-radius:7px;font-size:9px;font-weight:900;letter-spacing:.04em}.tree-component-chip.is-present{background:var(--success-bg);color:var(--success)}.tree-component-chip.is-missing{background:var(--surface-soft);color:var(--muted);border:1px dashed var(--line-strong)}.tree-course-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 10px}.tree-course-action-inline{position:relative}.tree-course-action-inline>summary{display:inline-flex;align-items:center;gap:5px;min-height:31px;padding:6px 9px;border:1px solid var(--line);border-radius:7px;background:var(--surface);color:var(--ink-soft);font-size:10px;font-weight:750;cursor:pointer;list-style:none}.tree-course-action-inline>summary::-webkit-details-marker{display:none}.tree-course-action-inline>summary:hover{border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}.tree-course-action-inline[open]{padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}.tree-course-action-inline[open]>summary{margin-bottom:9px;border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}.tree-course-action-inline form{display:grid;gap:9px;min-width:min(360px,calc(100vw - 80px))}.tree-inline-form-actions,.tree-remove-confirm form{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.tree-remove-confirm{max-width:360px}.tree-remove-confirm p{margin:0 0 9px;color:var(--muted);font-size:10px;line-height:1.5}.tree-course-remove[open]{border-color:var(--danger)}.tree-course-remove[open]>summary{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-course-hint{margin:0 0 8px;color:var(--muted);font-size:9px;line-height:1.4}.tree-live-feedback{margin:0 0 12px;padding:9px 11px;border:1px solid var(--success);border-radius:8px;background:var(--success-bg);color:var(--success);font-size:11px;line-height:1.45}.tree-live-feedback.is-error{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.workload-tree-modality{position:relative;display:flex;align-items:center;gap:10px;min-width:0;padding:12px 5px;border-top:1px solid var(--line)}.tree-modality-tag{display:grid;place-items:center;flex:0 0 42px;height:31px;border-radius:8px;font-size:10px;font-weight:900}.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}.tree-modality-copy{display:grid;gap:4px;flex:1;min-width:0}.tree-modality-copy strong{color:var(--ink);font-size:11px;font-weight:800;line-height:1.4;overflow-wrap:anywhere}.tree-modality-copy small{color:var(--muted);font-size:10px;line-height:1.5;overflow-wrap:anywhere}.tree-course-status{display:inline-flex;align-items:center;gap:4px;flex:none;padding:5px 8px;border-radius:6px;font-size:9px;font-weight:800;white-space:nowrap}.tree-course-status.is-ready{background:var(--success-bg);color:var(--success)}.tree-course-status.is-pending{background:var(--warning-bg);color:var(--warning)}.tree-inline-editable{display:inline-flex;align-items:center;min-height:20px;padding:1px 4px;border-radius:4px;border-bottom:1px dashed var(--line-strong);cursor:pointer}.tree-inline-editable:hover{background:var(--accent-soft);border-bottom-color:var(--accent);color:var(--accent)}.tree-cycle-inline{display:inline-flex;align-items:center;min-height:22px;padding:1px 4px;border-radius:4px;border-bottom:1px dashed var(--line-strong);cursor:text}.tree-cycle-inline:hover{background:var(--accent-soft);border-bottom-color:var(--accent);color:var(--accent)}.tree-inline-date,.tree-inline-nrc{display:inline-flex;align-items:center;min-height:23px;padding:2px 5px;border-radius:5px;border-bottom:1px dashed var(--line-strong);color:var(--ink-soft);cursor:text}.tree-inline-date:hover,.tree-inline-nrc:hover{background:var(--accent-soft);border-bottom-color:var(--accent);color:var(--accent)}.tree-inline-editor{width:auto;min-width:112px;max-width:160px;min-height:30px;padding:4px 6px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;outline:none}.tree-inline-hint{margin-left:4px;color:var(--faint);font-size:9px}.tree-inline-saving{opacity:.55;pointer-events:none}.tree-open-modality{flex:none}.tree-empty-child{padding:12px;color:var(--muted);font-size:11px}.tree-loading-state,.tree-load-error{padding:12px 14px;color:var(--muted);font-size:11px;line-height:1.5}.tree-load-error{color:var(--danger);background:var(--danger-bg);border-radius:8px}
@keyframes tree-reveal{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}html[data-theme="dark"] .workload-tree-hero{background:linear-gradient(115deg,color-mix(in srgb,var(--accent) 19%,#15232d),#141f28 76%);border-color:color-mix(in srgb,var(--accent) 35%,var(--line))}
@media(max-width:980px){.workload-tree-hero{align-items:flex-start;flex-direction:column}.workload-tree-hero-actions{width:100%}}
@media(max-width:700px){.tree-group-create-form{grid-template-columns:1fr}.tree-group-create-form .btn{width:100%}.workload-tree-children{margin-left:12px;padding-left:8px;padding-right:7px}.workload-group-children,.workload-course-children{margin-left:8px;padding-left:8px}.workload-group-summary{padding-right:52px}.workload-tree-add-course-inline{top:12px;right:10px}.workload-tree-add-course-inline>summary{width:36px;height:36px;min-height:36px;padding:7px}.workload-tree-add-course-inline>summary span{display:none}.workload-tree-add-course-inline[open]{position:relative;top:auto;right:auto}.workload-tree-add-course-inline[open]>summary{width:auto;height:auto;margin-bottom:12px}.workload-tree-add-course-inline[open]>summary span{display:inline}.tree-entry-grid-course,.tree-entry-grid,.tree-entry-grid-three{grid-template-columns:1fr 1fr}.tree-entry-grid-course .tree-entry-field:nth-child(2){grid-column:1/-1}.tree-course-entry-footer{align-items:stretch;flex-direction:column}.tree-course-entry-footer .btn{width:100%}.tree-course-components{gap:3px}.tree-component-chip{padding:4px 6px;font-size:8px}.workload-tree-modality{align-items:flex-start;flex-wrap:wrap}.tree-modality-copy{flex:1 1 calc(100% - 100px)}.tree-open-modality{margin-left:49px}
}
@media(max-width:430px){.workload-tree-hero h1{font-size:30px}.workload-tree-context{display:grid;gap:7px}.workload-tree-browser{padding:12px 9px}.workload-tree-browser-head{align-items:flex-start;flex-direction:column}.workload-tree-search{display:grid;grid-template-columns:1fr 1fr}.workload-tree-search-field{grid-column:1/-1}.workload-tree-search .btn,.workload-tree-search>a{width:100%}.tree-entry-grid,.tree-entry-grid-three{grid-template-columns:1fr}.tree-entry-grid-course .tree-entry-field:nth-child(2){grid-column:auto}.tree-course-actions{align-items:stretch;flex-direction:column}.tree-course-action-inline form{min-width:0}.tree-course-action-inline[open]{width:100%}
}
@media(prefers-reduced-motion:reduce){.workload-tree-summary,.tree-inline-create-group>summary,.workload-tree-add-course-inline>summary,.workload-tree-group-shell,.workload-tree-course{transition:none!important;animation:none!important}}.tree-group-action-inline{position:relative}.tree-group-action-inline>summary{display:inline-flex;align-items:center;gap:5px;min-height:32px;padding:6px 9px;border:1px solid var(--line);border-radius:7px;background:var(--surface);color:var(--ink-soft);font-size:10px;font-weight:750;cursor:pointer;list-style:none}.tree-group-action-inline>summary::-webkit-details-marker{display:none}.tree-group-action-inline>summary:hover{border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}.tree-group-action-inline[open]{padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}.tree-group-action-inline[open]>summary{margin-bottom:9px;border-color:var(--accent);background:var(--accent-soft);color:var(--accent)}.tree-group-action-inline form{display:grid;gap:9px;min-width:min(320px,calc(100vw - 70px))}.tree-group-remove[open]{border-color:var(--danger)}.tree-group-remove[open]>summary{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-group-remove-confirm{max-width:440px}.tree-group-remove-confirm p{margin:0 0 10px;color:var(--muted);font-size:10px;line-height:1.55}.tree-group-remove-confirm p strong{color:var(--ink)}.tree-group-remove-confirm form{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.tree-group-delete-disabled{opacity:.6;cursor:not-allowed}
@media(max-width:700px){.tree-group-action-inline form{min-width:0}.tree-group-action-inline[open]{width:100%}.workload-tree-group-actions{align-items:stretch}.workload-tree-group-actions>.btn,.workload-tree-group-actions>.tree-group-action-inline{flex:1 1 auto}.tree-group-remove-confirm form{display:grid;grid-template-columns:1fr}.tree-group-remove-confirm .btn{width:100%}
}.workload-tree-group-actions{align-items:center;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--line)}.workload-tree-group-actions .btn{min-height:34px}.tree-group-delete-trigger,.tree-course-delete-trigger{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-height:32px;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface);color:var(--muted);font-size:10px;font-weight:750;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease}.tree-group-delete-trigger:hover,.tree-course-delete-trigger:hover{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-last-group-note{color:var(--muted);font-size:9px}.tree-delete-confirm{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:10px 0;padding:11px 12px;border:1px solid color-mix(in srgb,var(--danger) 35%,var(--line));border-radius:9px;background:var(--danger-bg);animation:tree-reveal .16s ease-out}.tree-delete-confirm[hidden]{display:none!important}.tree-delete-confirm p{margin:0;color:var(--ink-soft);font-size:10px;line-height:1.55}.tree-delete-confirm p strong{color:var(--danger)}.tree-delete-confirm form{display:flex;align-items:center;gap:7px;flex:none}.tree-course-entry-area{margin-top:8px}.workload-tree-add-course{position:static;display:inline-flex;align-items:center;gap:7px;min-height:36px;padding:8px 11px;border:1px solid color-mix(in srgb,var(--accent) 34%,var(--line));border-radius:9px;background:var(--accent-soft);color:var(--accent);font-size:10px;font-weight:850;cursor:pointer;transition:transform .15s ease,background .15s ease,border-color .15s ease}.workload-tree-add-course:hover,.workload-tree-add-course[aria-expanded="true"]{transform:translateY(-1px);border-color:var(--accent);background:var(--accent);color:#fff}.tree-course-entry-form[hidden]{display:none!important}.tree-course-entry-form{margin-top:9px;padding:15px;border:1px solid color-mix(in srgb,var(--accent) 38%,var(--line));border-left:4px solid var(--accent);border-radius:12px;background:linear-gradient(120deg,var(--accent-soft),var(--surface) 78%);box-shadow:0 5px 18px rgba(25,56,78,.05);animation:tree-reveal .18s ease-out}.tree-course-entry-heading{margin-bottom:3px}.tree-entry-components{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--accent) 5%,transparent)}.tree-course-entry-footer>div{display:flex;align-items:center;gap:7px}.tree-course-head{display:flex;align-items:center;gap:10px;min-width:0;padding:12px 13px;background:var(--surface)}.workload-tree-course{display:block;margin:8px 0;border:1px solid var(--line-strong);border-radius:11px;background:var(--surface);overflow:visible;transition:border-color .15s ease,box-shadow .15s ease}.workload-tree-course:hover{border-color:color-mix(in srgb,var(--accent) 42%,var(--line))}.workload-tree-course:focus-within{border-color:color-mix(in srgb,var(--accent) 60%,var(--line));box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 9%,transparent)}.tree-course-head .tree-node-copy{flex:1}.tree-course-head .tree-node-copy strong{display:inline-flex;align-items:center;min-height:27px;padding:2px 4px;border-radius:5px;cursor:text}.tree-inline-name:hover,.tree-inline-code:hover{background:var(--accent-soft);color:var(--accent)}.tree-inline-code{display:inline-flex;min-height:22px;align-items:center;padding:1px 4px;border-radius:5px;cursor:text}.tree-course-components{display:flex;align-items:center;gap:5px;flex:none}.tree-component-chip{display:inline-flex;align-items:center;justify-content:center;min-width:36px;padding:5px 7px;border:1px solid transparent;border-radius:7px;font-size:9px;font-weight:900;letter-spacing:.04em}.tree-component-chip.is-present{background:var(--success-bg);color:var(--success)}.tree-component-chip.is-disabled{border-color:var(--line-strong);background:var(--surface-soft);color:var(--muted);text-decoration:line-through}.tree-component-chip.is-missing{border:1px dashed var(--line-strong);background:var(--surface-soft);color:var(--faint)}.tree-course-delete-trigger{margin-left:5px}.tree-course-delete-confirm{margin:0 12px 10px}.tree-course-modalities{padding:0 12px 9px}.tree-course-modalities .workload-tree-modality:first-child{border-top:1px solid var(--line)}.workload-tree-modality{display:grid;grid-template-columns:11px 42px minmax(0,1fr) auto auto auto;align-items:center;gap:10px;min-height:76px;padding:11px 5px;border-top:1px solid var(--line);transition:background .15s ease,opacity .15s ease}.workload-tree-modality:hover{background:var(--surface-soft)}.workload-tree-modality.is-disabled{background:color-mix(in srgb,var(--surface-soft) 70%,var(--surface));}.workload-tree-modality.is-disabled .tree-modality-copy{opacity:.72}.tree-modality-copy{min-width:0}.tree-modality-copy strong{display:flex;align-items:center;gap:5px;flex-wrap:wrap}.tree-modality-copy small{display:block}.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}.tree-modality-toggle{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:105px;min-height:34px;padding:6px 9px;border:1px solid var(--line-strong);border-radius:8px;background:var(--surface);color:var(--ink-soft);font-size:9px;font-weight:800;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease}.tree-modality-toggle.is-on{border-color:color-mix(in srgb,var(--success) 35%,var(--line));background:var(--success-bg);color:var(--success)}.tree-modality-toggle.is-off{border-color:color-mix(in srgb,var(--accent) 35%,var(--line));background:var(--accent-soft);color:var(--accent)}.tree-modality-toggle:hover:not(:disabled){filter:brightness(.97);transform:translateY(-1px)}.tree-modality-toggle:disabled{opacity:.75;cursor:not-allowed;filter:none}.tree-toggle-dot{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px color-mix(in srgb,currentColor 14%,transparent)}.tree-course-status.is-disabled{background:var(--surface-soft);color:var(--muted)}.tree-disabled-value{display:inline-flex;align-items:center;min-height:22px;padding:1px 4px;color:var(--muted)}.tree-inline-editor{width:auto;min-width:105px;max-width:min(220px,60vw);min-height:30px;padding:4px 7px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 12%,transparent);outline:none}.tree-inline-name .tree-inline-editor{min-width:min(220px,60vw);font-size:12px;font-weight:750}.tree-inline-saving{opacity:.6;pointer-events:none}.tree-inline-name:focus-visible,.tree-inline-date:focus-visible,.tree-inline-nrc:focus-visible{outline:2px solid var(--accent);outline-offset:2px}html[data-theme="dark"] .workload-tree-group[open]>.workload-group-summary{background:linear-gradient(90deg,color-mix(in srgb,var(--accent) 16%,var(--surface)),var(--surface))}html[data-theme="dark"] .tree-course-entry-form{background:linear-gradient(120deg,color-mix(in srgb,var(--accent) 14%,var(--surface)),var(--surface) 78%)}
@media(max-width:850px){.workload-tree-modality{grid-template-columns:10px 38px minmax(0,1fr) auto;gap:8px}.workload-tree-modality .tree-course-status{display:none}.workload-tree-modality .tree-open-modality{grid-column:3;margin:0;justify-self:start}.tree-modality-toggle{grid-column:4;grid-row:1/3;min-width:88px}
}
@media(max-width:600px){.tree-course-head{align-items:flex-start;flex-wrap:wrap;padding:10px}.tree-course-head .tree-node-copy{flex:1 1 calc(100% - 60px)}.tree-course-components{order:4;margin-left:43px}.tree-course-delete-trigger{order:5;margin-left:auto}.tree-course-modalities{padding:0 8px 8px}.workload-tree-modality{grid-template-columns:32px minmax(0,1fr) auto;gap:7px;padding:11px 2px}.workload-tree-modality .tree-branch-mark{display:none}.workload-tree-modality .tree-modality-tag{grid-column:1;grid-row:1;flex-basis:32px;width:32px;height:29px}.workload-tree-modality .tree-modality-copy{grid-column:2;grid-row:1;min-width:0}.workload-tree-modality .tree-modality-toggle{grid-column:3;grid-row:1;min-width:82px;padding:6px 7px}.workload-tree-modality .tree-open-modality{grid-column:2/4;grid-row:2;margin:0}.tree-delete-confirm{align-items:stretch;flex-direction:column}.tree-delete-confirm form{display:grid;grid-template-columns:1fr 1fr}.tree-delete-confirm form .btn{width:100%}.tree-course-entry-form{padding:12px}.tree-course-entry-footer>div{display:grid;grid-template-columns:1fr 1fr;width:100%}.tree-course-entry-footer>div .btn{width:100%}.workload-tree-group-actions{align-items:stretch}.workload-tree-group-actions>.btn{flex:1}
}.tree-workspace-grid{display:grid;grid-template-columns:minmax(0,1.04fr) minmax(350px,.96fr);align-items:start;gap:16px}.tree-structure-card,.tree-editor-card{min-width:0;padding:18px;border:1px solid var(--line-strong);border-radius:14px;background:var(--surface);box-shadow:0 5px 18px rgba(25,56,78,.045)}.tree-panel-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin:0 0 15px}.tree-panel-heading .eyebrow{margin:0 0 5px;color:var(--accent);font-size:8px;font-weight:900;letter-spacing:.13em}.tree-panel-heading h2{margin:0;color:var(--ink);font-size:21px;font-weight:850;letter-spacing:-.035em}.tree-panel-heading p:not(.eyebrow){margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.5}.tree-add-group-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;margin-bottom:13px;padding:10px;border:1px solid color-mix(in srgb,var(--accent) 26%,var(--line));border-radius:10px;background:var(--accent-soft)}.tree-add-group-form .field{height:38px;min-width:0;border-color:var(--line-strong);background:var(--surface);font-size:11px}.tree-add-group-form .btn{min-height:38px;white-space:nowrap}.tree-structure-card .workload-tree-search{margin-bottom:12px}.tree-structure-card .workload-tree-root{display:grid;gap:8px}.tree-structure-card .workload-tree-group-shell{border:1px solid var(--line-strong);border-radius:10px;background:var(--surface);overflow:hidden}.tree-structure-card .workload-tree-group-shell.is-selected{border-color:var(--accent);box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 13%,transparent)}.tree-structure-card .workload-group-summary{min-height:59px;padding:11px 12px;background:var(--surface)}.tree-structure-card .workload-group-summary:hover{background:var(--accent-soft)}.tree-structure-card .workload-tree-group[open]>.workload-group-summary{border-bottom:1px solid var(--line);background:var(--accent-soft)}.tree-structure-card .tree-group-selected-label{margin-left:auto;padding:4px 7px;border-radius:6px;background:var(--surface-soft);color:var(--muted);font-size:8px;font-weight:800}.tree-structure-card .workload-tree-group-shell.is-selected .tree-group-selected-label{background:var(--accent);color:#fff}.tree-structure-card .workload-tree-children{margin:0;padding:10px 11px;border:0}.tree-structure-card .workload-tree-group-actions{margin:0 0 9px;padding:0 0 8px;border-bottom:1px solid var(--line)}.tree-course-entry-form{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:9px;margin:9px 0;padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}.tree-course-entry-form[hidden]{display:none!important}.tree-course-name-input{display:grid;gap:5px;min-width:0;color:var(--ink-soft);font-size:9px;font-weight:800}.tree-course-name-input .field{height:37px;min-width:0;background:var(--surface);font-size:11px}.tree-entry-components{grid-column:1/-1;display:flex;gap:7px;margin:0;padding:7px;border:0;background:transparent}.tree-entry-components legend{padding:0 4px;color:var(--muted);font-size:8px;font-weight:850}.tree-component-option{display:flex;align-items:center;gap:6px;min-width:0;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface);cursor:pointer}.tree-component-option span{font-size:9px;font-weight:900}.tree-component-option small{color:var(--muted);font-size:9px}.tree-component-option.is-tec span{color:var(--info)}.tree-component-option.is-tall span{color:var(--accent)}.tree-component-option input{accent-color:var(--accent)}.tree-course-create-footer{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:8px}.tree-course-create-footer>small{color:var(--muted);font-size:8px;line-height:1.4}.tree-course-create-button{min-height:34px;padding:7px 10px;font-size:9px;white-space:nowrap}.tree-course-entry-error{grid-column:1/-1;padding:8px;border:1px solid var(--danger);border-radius:7px;background:var(--danger-bg);color:var(--danger);font-size:9px}.tree-course-entry-error[hidden]{display:none!important}.tree-structure-card .tree-loading-state{padding:12px 8px;color:var(--muted);font-size:10px}.tree-structure-card .workload-tree-course{position:relative;margin:6px 0;border:1px solid var(--line);border-radius:9px;background:var(--surface);overflow:hidden}.tree-structure-card .workload-tree-course.is-selected{border-color:var(--accent);box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 13%,transparent)}.tree-course-head{display:flex;width:100%;align-items:center;gap:9px;padding:10px 38px 10px 11px;border:0;background:transparent;text-align:left;cursor:pointer}.tree-course-head:hover{background:var(--accent-soft)}.tree-course-node{display:grid;place-items:center;flex:0 0 31px;width:31px;height:31px;border-radius:8px;background:var(--accent-soft);color:var(--accent)}.tree-course-head .tree-node-copy{flex:1;min-width:0}.tree-course-head .tree-node-copy strong{display:block;color:var(--ink);font-size:11px;font-weight:800;line-height:1.35;overflow-wrap:anywhere}.tree-course-head .tree-node-copy small{display:block;margin-top:3px;color:var(--muted);font-size:9px}.tree-course-components{display:flex;flex:none;gap:4px}.tree-component-chip{min-width:30px;justify-content:center;padding:4px 6px;border-radius:5px;font-size:8px}.tree-component-chip.is-disabled{text-decoration:line-through}.tree-course-selected-label{display:inline-flex;align-items:center;gap:2px;color:var(--accent);font-size:8px;font-weight:850}.tree-course-remove-trigger{position:absolute;top:12px;right:8px;display:grid;place-items:center;width:25px;height:25px;border:1px solid transparent;border-radius:6px;background:transparent;color:var(--muted);cursor:pointer}.tree-course-remove-trigger:hover{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-course-delete-confirm{margin:0 9px 9px}.tree-editor-card{position:sticky;top:18px;min-height:340px}.tree-editor-indicator{display:inline-flex;align-items:center;gap:6px;flex:none;padding:5px 8px;border:1px solid var(--line);border-radius:20px;background:var(--surface-soft);color:var(--muted);font-size:8px;font-weight:800}.tree-editor-indicator span{width:6px;height:6px;border-radius:50%;background:var(--success)}.tree-editor-empty{display:grid;justify-items:center;gap:9px;min-height:240px;align-content:center;padding:25px 16px;border:1px dashed var(--line-strong);border-radius:11px;background:var(--surface-soft);text-align:center}.tree-editor-empty-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:14px;background:var(--accent-soft);color:var(--accent)}.tree-editor-empty strong{color:var(--ink);font-size:14px;font-weight:850}.tree-editor-empty p{max-width:290px;margin:0;color:var(--muted);font-size:10px;line-height:1.55}.tree-editor-content[hidden],.tree-editor-empty[hidden]{display:none!important}.tree-editor-breadcrumb{margin-bottom:6px;color:var(--accent);font-size:8px;font-weight:900;letter-spacing:.13em}.tree-editor-content h3{margin:0;color:var(--ink);font-size:19px;font-weight:850;line-height:1.3;overflow-wrap:anywhere}.tree-editor-description{margin:5px 0 14px;color:var(--muted);font-size:10px;line-height:1.5}.tree-editor-fields{display:grid;gap:12px}.tree-editor-field{display:grid;gap:5px;padding:10px 11px;border:1px solid var(--line);border-radius:9px;background:var(--surface)}.tree-editor-field>label{color:var(--muted);font-size:8px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.tree-editor-value{display:inline-flex;align-items:center;min-height:27px;max-width:100%;padding:2px 5px;border-radius:5px;color:var(--ink);font-size:12px;font-weight:750;overflow-wrap:anywhere;cursor:text}.tree-editor-value:hover{background:var(--accent-soft);color:var(--accent)}.tree-editor-value.is-empty{color:var(--muted);font-style:italic;font-weight:550}.tree-editor-hint{color:var(--muted);font-size:8px}.tree-editor-modality-list{display:grid;gap:9px;margin-top:2px}.tree-editor-modality{padding:11px;border:1px solid var(--line-strong);border-radius:10px;background:var(--surface)}.tree-editor-modality.is-disabled{background:var(--surface-soft)}.tree-editor-modality-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:9px}.tree-editor-modality-title{display:flex;align-items:center;gap:8px;color:var(--ink);font-size:11px;font-weight:850}.tree-editor-modality-title .tree-modality-tag{width:34px;height:28px;flex-basis:34px}.tree-editor-modality .tree-modality-toggle{min-width:92px;min-height:31px}.tree-editor-modality-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}.tree-editor-modality-fields .tree-editor-field{min-width:0;padding:8px}.tree-editor-modality-fields .tree-editor-value{font-size:10px}.tree-editor-schedule{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;padding-top:8px;border-top:1px solid var(--line);color:var(--muted);font-size:9px}.tree-editor-add-component{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px;border:1px dashed var(--line-strong);border-radius:9px;background:var(--surface-soft)}.tree-editor-add-component strong{color:var(--ink);font-size:10px}.tree-editor-add-component small{display:block;margin-top:3px;color:var(--muted);font-size:8px}.tree-editor-add-component .btn{min-height:31px;padding:6px 9px;font-size:9px}.tree-editor-remove-course{display:flex;justify-content:flex-end;margin-top:12px}.tree-editor-remove-course button{min-height:30px;padding:6px 9px;border:1px solid var(--line);border-radius:7px;background:var(--surface);color:var(--danger);font-size:9px;font-weight:750;cursor:pointer}.tree-live-feedback{margin:12px 0 0;padding:9px 11px;border:1px solid var(--success);border-radius:8px;background:var(--success-bg);color:var(--success);font-size:10px;line-height:1.45}.tree-live-feedback.is-error{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-live-feedback[hidden]{display:none!important}.tree-inline-editor{width:auto;min-width:100px;max-width:min(260px,65vw);min-height:30px;padding:4px 7px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 12%,transparent);outline:none}
@media(max-width:980px){.tree-workspace-grid{grid-template-columns:minmax(0,1fr)}.tree-editor-card{position:static;min-height:280px}.tree-editor-empty{min-height:160px}}
@media(max-width:560px){.tree-structure-card,.tree-editor-card{padding:12px}.tree-add-group-form{grid-template-columns:minmax(0,1fr)}.tree-add-group-form .btn{width:100%}.tree-course-entry-form{grid-template-columns:minmax(0,1fr)}.tree-entry-components,.tree-course-create-footer,.tree-course-entry-error{grid-column:1}.tree-course-create-footer{align-items:stretch;flex-direction:column}.tree-course-create-button{width:100%}.tree-course-head{padding-right:35px;flex-wrap:wrap}.tree-course-components{margin-left:40px}.tree-course-selected-label{margin-left:auto}.tree-editor-modality-fields{grid-template-columns:1fr}.tree-editor-modality-head{align-items:flex-start}
}
@media(prefers-reduced-motion:reduce){.tree-structure-card *,.tree-editor-card *{scroll-behavior:auto!important;transition:none!important}}.tree-workspace-grid{display:block}.tree-structure-card{display:block;width:100%;min-width:0;padding:clamp(18px,2.2vw,30px);border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:0 7px 24px rgba(25,56,78,.05)}.tree-structure-card .tree-panel-heading{align-items:center;margin-bottom:20px}.tree-structure-card .tree-panel-heading .eyebrow{margin:0 0 7px;font-size:11px}.tree-structure-card .tree-panel-heading h2{display:flex;align-items:center;gap:10px;font-size:clamp(22px,2.1vw,29px);font-weight:900}.tree-structure-card .tree-panel-heading p:not(.eyebrow){margin-top:7px;font-size:13px;line-height:1.55}.tree-structure-card .workload-tree-total{padding:8px 12px;font-size:13px}.tree-add-group-form{display:grid;grid-template-columns:minmax(190px,1.35fr) minmax(170px,.85fr) auto;align-items:end;gap:12px;margin:0 0 16px;padding:14px;border:1px solid color-mix(in srgb,var(--accent) 28%,var(--line));border-radius:12px;background:var(--accent-soft)}.tree-add-group-form label{display:grid;flex:1;gap:7px;min-width:0;color:var(--ink-soft);font-size:13px;font-weight:800}.tree-add-group-form label>span{display:flex;align-items:center;gap:8px}.tree-add-group-form .field{height:46px;min-width:0;background:var(--surface);font-size:15px}.tree-add-group-form>.btn{min-height:46px;padding:10px 16px;font-size:13px;font-weight:850;white-space:nowrap}.tree-structure-card .workload-tree-search{gap:10px;margin-bottom:18px}.tree-structure-card .workload-tree-search-field{height:47px}.tree-structure-card .workload-tree-search-field input{font-size:14px}.tree-structure-card .workload-tree-search .btn{min-height:47px;font-size:13px}.tree-structure-card .workload-tree-root{display:grid;gap:8px;padding:3px 0}.tree-structure-card .workload-tree-group-shell{overflow:visible;border:0;border-left:2px solid color-mix(in srgb,var(--accent) 26%,var(--line));border-radius:0;background:transparent;box-shadow:none}.tree-structure-card .workload-tree-group-shell.is-selected{border-color:var(--accent);box-shadow:none}.tree-structure-card .workload-tree-group{overflow:visible;background:transparent}.tree-structure-card .workload-group-summary{display:flex;align-items:flex-start;gap:13px;min-height:0;padding:14px 15px;border:1px solid var(--line);border-radius:12px;background:var(--surface)}.tree-structure-card .workload-group-summary:hover{border-color:color-mix(in srgb,var(--accent) 45%,var(--line));background:var(--accent-soft)}.tree-structure-card .workload-tree-group[open]>.workload-group-summary{border-color:color-mix(in srgb,var(--accent) 48%,var(--line));border-radius:12px 12px 0 0;background:linear-gradient(90deg,var(--accent-soft),var(--surface))}.tree-expand-symbol{display:grid;place-items:center;flex:0 0 31px;width:31px;height:31px;margin-top:5px;border:1px solid var(--line-strong);border-radius:8px;background:var(--surface-soft);color:var(--accent)}.tree-expand-symbol [data-tree-collapsed],.tree-expand-symbol [data-tree-expanded]{display:grid;place-items:center}.tree-expand-symbol [data-tree-expanded]{display:none;font-size:24px;font-weight:550;line-height:1}.workload-tree-group[open]>.workload-group-summary .tree-expand-symbol [data-tree-collapsed],.tree-course-branch[open]>.tree-course-summary .tree-expand-symbol [data-tree-collapsed]{display:none}.workload-tree-group[open]>.workload-group-summary .tree-expand-symbol [data-tree-expanded],.tree-course-branch[open]>.tree-course-summary .tree-expand-symbol [data-tree-expanded]{display:grid}.tree-node-icon,.tree-course-node{display:grid;place-items:center;flex:0 0 47px;width:47px;height:47px;border-radius:12px}.tree-group-icon{background:var(--info-bg);color:var(--info)}.workload-tree-group[open]>.workload-group-summary .tree-group-icon{background:var(--accent);color:#fff}.tree-course-node{background:var(--accent-soft);color:var(--accent)}.tree-node-copy{display:grid;flex:1;gap:5px;min-width:0;padding-top:1px}.tree-node-copy strong{color:var(--ink);font-size:clamp(16px,1.4vw,19px);font-weight:900;line-height:1.35;overflow-wrap:anywhere}.tree-node-copy small{display:flex;align-items:center;gap:6px;flex-wrap:wrap;color:var(--muted);font-size:12px;line-height:1.55;overflow-wrap:anywhere}.tree-meta-separator{color:var(--line-strong)}.tree-group-row-actions{display:flex;align-items:center;gap:7px;margin-left:auto}.tree-icon-action{display:grid;place-items:center;flex:none;width:36px;height:36px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--muted);cursor:pointer;transition:all .15s ease}.tree-icon-action:hover:not(:disabled){border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-icon-action:disabled{opacity:.35;cursor:not-allowed}.tree-structure-card .workload-tree-children{position:relative;margin:0 0 0 31px;padding:14px 15px 17px 30px;border:0;border-left:2px solid color-mix(in srgb,var(--accent) 24%,var(--line));border-radius:0 0 0 12px}.tree-structure-card .workload-tree-group-actions{display:flex;margin:0 0 10px;padding:0;border:0}.tree-structure-card .tree-course-entry-form{display:grid;grid-template-columns:minmax(220px,1fr) auto;align-items:end;gap:11px;margin:0 0 14px;padding:13px;border:1px dashed color-mix(in srgb,var(--accent) 42%,var(--line));border-radius:11px;background:var(--surface-soft)}.tree-course-name-input{display:grid;gap:7px;min-width:0;color:var(--ink-soft);font-size:13px;font-weight:850}.tree-course-name-input>span{display:flex;align-items:center;gap:7px;color:var(--accent)}.tree-course-name-input .field{height:43px;min-width:0;background:var(--surface);font-size:14px}.tree-structure-card .tree-entry-components{grid-column:1/-1;display:flex;gap:9px;margin:0;padding:4px 0;border:0;background:transparent}.tree-structure-card .tree-entry-components legend{padding:0 4px 5px;color:var(--muted);font-size:11px;font-weight:850}.tree-structure-card .tree-component-option{display:flex;align-items:center;gap:8px;min-width:0;padding:8px 13px;border:1px solid var(--line);border-radius:9px;background:var(--surface);cursor:pointer}.tree-structure-card .tree-component-option span{font-size:13px;font-weight:950}.tree-structure-card .tree-component-option small{color:var(--muted);font-size:11px}.tree-structure-card .tree-component-option input{width:17px;height:17px;accent-color:var(--accent)}.tree-course-create-footer{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:12px}.tree-course-create-footer>small{color:var(--muted);font-size:11px;line-height:1.45}.tree-course-create-button{min-height:42px;padding:9px 14px;font-size:13px;font-weight:850;white-space:nowrap}.tree-course-entry-error{grid-column:1/-1;padding:10px 12px;border:1px solid var(--danger);border-radius:8px;background:var(--danger-bg);color:var(--danger);font-size:12px}.tree-course-entry-error[hidden]{display:none!important}.tree-loading-state{padding:17px;color:var(--muted);font-size:13px}.tree-structure-card .workload-tree-course{position:relative;margin:0 0 10px;border:0;border-radius:0;background:transparent;overflow:visible}.tree-course-branch{overflow:visible}.tree-course-summary{display:flex;align-items:flex-start;gap:11px;min-height:0;padding:12px 13px;border:1px solid var(--line);border-radius:11px;background:var(--surface);list-style:none;cursor:pointer;transition:border-color .15s ease,background .15s ease}.tree-course-summary::-webkit-details-marker{display:none}.tree-course-summary:hover{border-color:color-mix(in srgb,var(--accent) 40%,var(--line));background:var(--accent-soft)}.tree-course-branch[open]>.tree-course-summary{border-color:color-mix(in srgb,var(--accent) 42%,var(--line));border-radius:11px 11px 0 0;background:var(--accent-soft)}.tree-course-branch[open]>.tree-course-summary .tree-course-node{background:var(--accent);color:#fff}.tree-course-summary .tree-expand-symbol{margin-top:4px}.tree-course-summary .tree-node-copy{padding-top:1px}.tree-course-summary .tree-node-copy strong{font-size:16px}.tree-course-summary .tree-node-copy small{font-size:12px}.tree-course-components{display:flex;align-items:center;gap:6px;flex:none;padding-top:5px}.tree-component-chip{min-width:39px;justify-content:center;padding:6px 9px;border-radius:7px;font-size:11px}.tree-component-chip.is-present{background:var(--success-bg);color:var(--success)}.tree-component-chip.is-missing{border:1px dashed var(--line-strong);background:var(--surface-soft);color:var(--muted)}.tree-component-chip.is-disabled{background:var(--surface-soft);color:var(--muted);text-decoration:line-through}.tree-course-children{position:relative;margin:0 0 0 30px;padding:13px 14px 16px 28px;border-left:2px solid color-mix(in srgb,var(--accent) 23%,var(--line));border-bottom:1px solid var(--line);border-radius:0 0 0 10px}.tree-course-tools{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 5px;color:var(--muted);font-size:12px;font-weight:800}.tree-course-tools>span{display:inline-flex;align-items:center;gap:7px}.tree-course-tools .tree-icon-action{width:33px;height:33px}.tree-course-delete-confirm,.tree-group-delete-confirm{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 12px;padding:12px;border:1px solid var(--danger);border-radius:9px;background:var(--danger-bg)}.tree-course-delete-confirm[hidden],.tree-group-delete-confirm[hidden]{display:none!important}.tree-delete-confirm p{margin:0;color:var(--danger);font-size:12px;line-height:1.5}.tree-delete-confirm form{display:flex;gap:7px;flex-wrap:wrap}.tree-delete-confirm .btn{min-height:35px;font-size:11px}.tree-structure-card .workload-tree-modality{display:flex;align-items:center;gap:12px;min-width:0;margin:0;padding:13px 5px;border-top:1px solid var(--line)}.tree-modality-tag{display:flex;align-items:center;justify-content:center;gap:6px;flex:0 0 65px;height:40px;border-radius:9px;font-size:12px;font-weight:950}.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}.tree-modality-copy{display:grid;flex:1;gap:5px;min-width:0}.tree-modality-copy strong{color:var(--ink);font-size:14px;font-weight:850;line-height:1.4;overflow-wrap:anywhere}.tree-modality-copy small{color:var(--muted);font-size:12px;line-height:1.55;overflow-wrap:anywhere}.tree-inline-nrc,.tree-inline-date,.tree-inline-text{display:inline-flex;min-height:22px;align-items:center;padding:1px 4px;border-radius:4px;cursor:text}.tree-inline-nrc:hover,.tree-inline-date:hover,.tree-inline-text:hover{background:var(--accent-soft);color:var(--accent)}.tree-course-status{padding:6px 9px;border-radius:7px;font-size:10px}.tree-modality-toggle{min-height:36px;padding:7px 10px;font-size:11px}.tree-structure-card .tree-add-modality{min-height:37px;font-size:11px}.tree-modality-copy [data-modality-description]{font-size:11px}.tree-structure-card .tree-empty-child{display:grid;justify-items:center;gap:8px;padding:34px 18px;border:1px dashed var(--line-strong);border-radius:12px;color:var(--muted);text-align:center}.tree-empty-child strong{color:var(--ink);font-size:16px}.tree-empty-child span{font-size:13px}.tree-live-feedback{display:block;margin:14px 0 0;padding:12px 14px;border:1px solid var(--success);border-radius:9px;background:var(--success-bg);color:var(--success);font-size:13px;line-height:1.5}.tree-live-feedback[hidden]{display:none!important}.tree-live-feedback.is-error{border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-inline-editor{width:auto;min-width:110px;max-width:min(320px,75vw);min-height:33px;padding:5px 8px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:14px;box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 12%,transparent);outline:none}
@media(max-width:760px){.tree-structure-card{padding:14px}.tree-add-group-form{grid-template-columns:minmax(0,1fr)}.tree-add-group-form>.btn{width:100%}.tree-structure-card .tree-course-entry-form{grid-template-columns:minmax(0,1fr)}.tree-structure-card .tree-entry-components,.tree-course-create-footer,.tree-course-entry-error{grid-column:1}.tree-course-create-footer{align-items:stretch;flex-direction:column}.tree-course-create-button{width:100%}.tree-course-summary{flex-wrap:wrap}.tree-course-components{margin-left:43px;padding:0}.tree-course-children{margin-left:20px;padding-left:15px}.tree-structure-card .workload-tree-children{margin-left:20px;padding-left:15px}.tree-structure-card .workload-tree-modality{display:grid;grid-template-columns:65px minmax(0,1fr);align-items:start;gap:9px}.tree-modality-copy{grid-column:2}.tree-course-status,.tree-modality-toggle,.tree-open-modality,.tree-add-modality{grid-column:2;justify-self:start}.tree-course-delete-confirm,.tree-group-delete-confirm{align-items:stretch;flex-direction:column}.tree-delete-confirm form{display:grid;grid-template-columns:1fr 1fr}.tree-group-row-actions{margin-left:auto}
}
@media(max-width:480px){.tree-structure-card .workload-group-summary{gap:8px;padding:10px}.tree-expand-symbol{flex-basis:27px;width:27px;height:27px}.tree-node-icon,.tree-course-node{flex-basis:38px;width:38px;height:38px}.tree-node-copy strong{font-size:15px}.tree-node-copy small{font-size:11px}.tree-group-row-actions .tree-icon-action{width:31px;height:31px}.tree-course-summary{gap:8px;padding:10px}.tree-course-summary .tree-node-copy strong{font-size:14px}.tree-course-summary .tree-node-copy small{font-size:11px}.tree-modality-copy strong{font-size:13px}.tree-modality-copy small{font-size:11px}.tree-modality-tag{height:36px;flex-basis:58px;font-size:11px}
}
@media(prefers-reduced-motion:reduce){.tree-structure-card *{scroll-behavior:auto!important;transition:none!important}}.tree-structure-card .workload-tree-root{
    display:block;
    padding:0;
    overflow:hidden;
    border:1px solid var(--line);
    border-radius:12px;
    background:var(--surface);
}.tree-structure-card .workload-tree-group-shell{
    overflow:hidden;
    border:0;
    border-bottom:1px solid var(--line);
    border-radius:0;
    background:var(--surface);
}.tree-structure-card .workload-tree-group-shell:last-child{border-bottom:0}.tree-structure-card .workload-tree-group{margin:0;border-radius:0;background:transparent}.tree-structure-card .workload-group-summary{
    align-items:center;
    gap:12px;
    min-height:64px;
    padding:13px 16px;
    border:0;
    border-radius:0;
    background:var(--surface);
}.tree-structure-card .workload-group-summary:hover,.tree-structure-card .workload-tree-group[open]>.workload-group-summary{
    border:0;
    border-radius:0;
    background:var(--accent-soft);
}.tree-expand-symbol{
    display:grid;
    place-items:center;
    flex:0 0 26px;
    width:26px;
    height:26px;
    margin:0;
    border:0;
    border-radius:6px;
    background:transparent;
    color:var(--muted);
    transition:transform .16s ease,color .16s ease;
}.tree-expand-symbol svg{width:18px;height:18px}.workload-tree-group[open]>.workload-group-summary .tree-expand-symbol,.tree-course-branch[open]>.tree-course-summary .tree-expand-symbol{
    transform:rotate(90deg);
    color:var(--accent);
}.tree-structure-card .workload-tree-children{
    margin:0;
    padding:10px 14px 13px 38px;
    border:0;
    border-top:1px solid var(--line);
    border-radius:0;
    background:var(--surface-soft);
}.tree-structure-card .tree-course-entry-form{
    margin:0 0 10px;
    padding:11px;
    border:1px solid var(--line);
    border-radius:9px;
    background:var(--surface);
}.tree-structure-card .workload-tree-course{
    margin:0;
    border:0;
    border-bottom:1px solid var(--line);
    border-radius:0;
    background:transparent;
}.tree-structure-card .workload-tree-course:last-child{border-bottom:0}.tree-course-branch{overflow:hidden}.tree-structure-card .tree-course-summary{
    align-items:center;
    gap:10px;
    min-height:58px;
    padding:10px 11px;
    border:0;
    border-radius:0;
    background:transparent;
}.tree-structure-card .tree-course-summary:hover,.tree-structure-card .tree-course-branch[open]>.tree-course-summary{
    border:0;
    border-radius:0;
    background:color-mix(in srgb,var(--accent-soft) 68%,var(--surface));
}.tree-course-summary .tree-expand-symbol{margin:0}.tree-structure-card .tree-course-children{
    margin:0;
    padding:8px 12px 12px 34px;
    border:0;
    border-top:1px solid var(--line);
    background:var(--surface);
}.tree-course-tools{margin-bottom:3px}.tree-structure-card .workload-tree-modality{
    padding:10px 4px;
    border-top:1px solid var(--line);
}.tree-structure-card .tree-modality-tag{
    flex-basis:48px;
    height:33px;
    border-radius:7px;
    font-size:11px;
}.tree-structure-card .tree-modality-copy{gap:3px}.tree-structure-card .tree-modality-copy strong{font-size:12px}.tree-structure-card .tree-modality-copy small{font-size:11px}.tree-structure-card .tree-course-status{font-size:9px}.tree-structure-card .tree-modality-toggle{min-height:32px;font-size:10px}.tree-structure-card .tree-open-modality,.tree-structure-card .tree-add-modality{min-height:32px;font-size:10px}
@media(max-width:760px){.tree-structure-card .workload-tree-children{padding:9px 8px 11px 18px}.tree-structure-card .tree-course-children{padding:7px 6px 10px 12px}.tree-structure-card .workload-group-summary{padding:11px 10px}.tree-structure-card .tree-course-summary{padding:9px 8px}.tree-structure-card .tree-node-icon,.tree-structure-card .tree-course-node{flex-basis:36px;width:36px;height:36px}
}.tree-add-row{display:flex;justify-content:center;padding:9px 10px;border-top:1px dashed var(--line-strong);background:var(--surface)}.tree-add-row>button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:36px;padding:7px 15px;border:1px dashed color-mix(in srgb,var(--accent) 55%,var(--line));border-radius:8px;background:var(--surface);color:var(--accent);font-size:12px;font-weight:850;cursor:pointer;transition:background .15s ease,border-color .15s ease,transform .15s ease}.tree-add-row>button:hover{border-style:solid;background:var(--accent-soft);transform:translateY(-1px)}.tree-group-draft-form,.tree-course-draft-form{display:grid;gap:12px;margin:10px 0;padding:14px;border:1px solid color-mix(in srgb,var(--accent) 35%,var(--line));border-left:4px solid var(--accent);border-radius:10px;background:var(--accent-soft)}.tree-group-draft-form[hidden],.tree-course-draft-form[hidden],.tree-add-row[hidden]{display:none!important}.tree-draft-heading{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap}.tree-draft-heading strong{display:inline-flex;align-items:center;gap:7px;color:var(--ink);font-size:13px;font-weight:900}.tree-draft-heading strong svg{color:var(--accent)}.tree-draft-heading>span{padding:4px 7px;border-radius:6px;background:var(--surface);color:var(--muted);font-size:9px;font-weight:750}.tree-draft-fields{display:grid;grid-template-columns:minmax(0,1fr);gap:10px}.tree-draft-fields .field{height:40px;min-width:0;background:var(--surface);font-size:12px}.tree-course-name-input{display:grid;gap:5px;min-width:0;color:var(--ink-soft);font-size:10px;font-weight:800}.tree-course-name-input .field{width:100%;height:40px;min-width:0;background:var(--surface);font-size:12px}.tree-draft-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.tree-draft-footer>div{display:flex;align-items:center;gap:7px}.tree-draft-footer .btn{min-height:38px}.tree-draft-note{color:var(--muted);font-size:9px;line-height:1.45}.tree-draft-error{color:var(--danger);font-size:11px;line-height:1.4}.tree-draft-error[hidden]{display:none!important}.tree-course-draft-form .tree-course-entry-error{margin:0}
@media(max-width:650px){.tree-draft-footer{align-items:stretch;flex-direction:column}.tree-draft-footer>div{display:grid;grid-template-columns:1fr 1fr;width:100%}.tree-draft-footer .btn{width:100%}
}.tree-structure-card .workload-tree-course{position:relative}.tree-structure-card .tree-course-summary{padding-right:54px}.tree-course-delete-trigger{position:absolute;z-index:3;top:12px;right:12px;width:32px;height:32px;border-radius:8px}.tree-course-delete-trigger:hover:not(:disabled){border-color:var(--danger);background:var(--danger-bg);color:var(--danger)}.tree-course-delete-trigger:disabled{opacity:.3;cursor:not-allowed}.tree-course-delete-confirm{position:relative;z-index:2;margin:0 12px 10px;padding:12px;border:1px solid color-mix(in srgb,var(--danger) 40%,var(--line));border-radius:9px;background:var(--danger-bg)}.tree-course-delete-confirm p{margin:0 0 9px;color:var(--ink-soft);font-size:11px;line-height:1.5}.tree-course-delete-confirm form{display:flex;gap:7px;flex-wrap:wrap}.workload-tree-page{
    display:grid;
    gap:14px;
    width:100%;
    min-width:0;
}.workload-tree-page>.breadcrumbs{margin:0 0 -2px}.workload-tree-hero{
    position:relative;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    margin:0;
    padding:20px 22px;
    border:1px solid var(--line);
    border-radius:12px;
    background:var(--surface);
    box-shadow:none;
}.workload-tree-hero:after{display:none}.workload-tree-hero .eyebrow{display:flex;align-items:center;gap:7px;margin-bottom:7px}.workload-tree-hero h1{font-size:clamp(26px,3vw,38px);line-height:1.12}.workload-tree-context{display:flex;gap:7px 15px;margin-top:10px}.workload-tree-context>span{min-width:0;max-width:100%;font-size:11px;overflow-wrap:anywhere}.tree-context-hint{color:var(--muted);font-size:10px}.workload-tree-hero-actions{align-items:center}.workload-tree-hero-actions>.btn{min-height:38px;border-radius:8px}.tree-structure-card{
    min-width:0;
    margin:0;
    padding:clamp(13px,1.8vw,22px);
    border:1px solid var(--line);
    border-radius:12px;
    background:var(--surface);
    box-shadow:none;
}.tree-structure-card .tree-panel-heading{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
}.tree-structure-card .tree-panel-heading>div{min-width:0}.tree-structure-card .tree-panel-heading .eyebrow{margin:0 0 5px;font-size:9px}.tree-structure-card .tree-panel-heading h2{
    display:flex;
    align-items:center;
    gap:8px;
    margin:0;
    font-size:clamp(19px,2vw,25px);
    font-weight:800;
    line-height:1.25;
    letter-spacing:-.025em;
}.tree-structure-card .tree-panel-heading p:not(.eyebrow){
    margin:5px 0 0;
    color:var(--muted);
    font-size:11px;
    line-height:1.45;
}.tree-structure-card .workload-tree-total{
    flex:none;
    padding:6px 9px;
    border:1px solid var(--line);
    border-radius:7px;
    background:var(--surface-soft);
    color:var(--ink-soft);
    font-size:10px;
    font-weight:750;
}.tree-structure-card .workload-tree-search{
    display:flex;
    align-items:stretch;
    gap:7px;
    margin:0 0 12px;
}.tree-structure-card .workload-tree-search-field{
    display:flex;
    align-items:center;
    flex:1;
    gap:8px;
    width:auto;
    min-width:0;
    height:41px;
    padding:0 11px;
    border:1px solid var(--line-strong);
    border-radius:8px;
    background:var(--surface);
}.tree-structure-card .workload-tree-search-field:focus-within{
    border-color:var(--accent);
    box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
}.tree-structure-card .workload-tree-search-field input{
    flex:1;
    width:100%;
    min-width:0;
    border:0;
    outline:0;
    background:transparent;
    color:var(--ink);
    font-size:12px;
}.tree-structure-card .workload-tree-search .btn{
    min-width:76px;
    min-height:41px;
    border-radius:8px;
}.tree-structure-card .workload-tree-root{
    display:block;
    width:100%;
    min-width:0;
    padding:0;
    overflow:hidden;
    border:1px solid var(--line);
    border-radius:10px;
    background:var(--surface);
}.tree-structure-card .workload-tree-group-shell{
    min-width:0;
    overflow:hidden;
    border:0;
    border-bottom:1px solid var(--line);
    border-radius:0;
    background:var(--surface);
    box-shadow:none;
}.tree-structure-card .workload-tree-group-shell:last-child{border-bottom:0}.tree-structure-card .workload-tree-group{min-width:0;margin:0;overflow:hidden;background:transparent}.tree-structure-card .workload-group-summary{
    display:grid;
    grid-template-columns:22px 38px minmax(0,1fr) 30px;
    align-items:center;
    gap:10px;
    min-height:68px;
    padding:11px 13px;
    border:0;
    border-radius:0;
    background:var(--surface);
    cursor:pointer;
    transition:background .16s ease;
}.tree-structure-card .workload-group-summary:hover,.tree-structure-card .workload-tree-group[open]>.workload-group-summary{
    border:0;
    border-radius:0;
    background:var(--surface-soft);
}.tree-structure-card .tree-expand-symbol{
    display:grid;
    place-items:center;
    width:22px;
    height:22px;
    margin:0;
    border:0;
    border-radius:6px;
    background:transparent;
    color:var(--muted);
    transition:transform .16s ease,color .16s ease;
}.tree-structure-card .tree-expand-symbol svg{width:17px;height:17px}.tree-structure-card .workload-tree-group[open]>.workload-group-summary .tree-expand-symbol,.tree-structure-card .tree-course-branch[open]>.tree-course-summary .tree-expand-symbol{
    transform:rotate(90deg);
    color:var(--accent);
}.tree-structure-card .tree-node-icon,.tree-structure-card .tree-course-node{
    display:grid;
    place-items:center;
    width:38px;
    height:38px;
    border-radius:9px;
    background:var(--accent-soft);
    color:var(--accent);
}.tree-structure-card .tree-group-icon{background:var(--info-bg);color:var(--info)}.tree-structure-card .workload-tree-group[open]>.workload-group-summary .tree-group-icon{
    background:var(--accent-soft);
    color:var(--accent);
}.tree-structure-card .tree-node-copy{
    display:grid;
    grid-column:3;
    grid-row:1;
    gap:5px;
    min-width:0;
    padding:0;
}.tree-group-title-row{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:6px 10px;
    min-width:0;
}.tree-group-title-row>strong{
    min-width:0;
    color:var(--ink);
    font-size:15px;
    font-weight:800;
    line-height:1.3;
    overflow-wrap:anywhere;
}.tree-group-counts{display:flex;align-items:center;flex-wrap:wrap;gap:4px}.tree-meta-pill{
    display:inline-flex;
    align-items:center;
    gap:3px;
    max-width:100%;
    padding:3px 6px;
    border:1px solid var(--line);
    border-radius:5px;
    background:var(--surface-soft);
    color:var(--muted);
    font-size:9px;
    font-weight:650;
    line-height:1.3;
}.tree-group-code-row{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:4px 7px;
    min-width:0;
    color:var(--muted);
    font-size:10px;
    line-height:1.4;
}.tree-meta-label{
    flex:none;
    color:var(--muted);
    font-size:9px;
    font-weight:700;
}.tree-inline-editable{
    display:inline-flex;
    align-items:center;
    max-width:100%;
    min-width:0;
    min-height:22px;
    padding:2px 5px;
    border:1px dashed transparent;
    border-radius:5px;
    color:var(--ink-soft);
    cursor:pointer;
    overflow-wrap:anywhere;
    white-space:normal;
    transition:background .14s ease,border-color .14s ease,color .14s ease;
}.tree-group-row-actions{
    display:flex;
    grid-column:4;
    grid-row:1;
    align-items:center;
    justify-content:center;
    margin:0;
}.tree-group-row-actions .tree-icon-action{
    width:30px;
    height:30px;
    border:1px solid transparent;
    border-radius:7px;
    background:transparent;
    color:var(--muted);
}.tree-group-row-actions .tree-icon-action:hover:not(:disabled){
    border-color:var(--danger);
    background:var(--danger-bg);
    color:var(--danger);
}.tree-group-row-actions .tree-icon-action:disabled{opacity:.25;cursor:not-allowed}.tree-structure-card .workload-tree-children{
    min-width:0;
    margin:0;
    padding:11px 13px 13px 34px;
    border:0;
    border-top:1px solid var(--line);
    border-radius:0;
    background:var(--surface-soft);
}.tree-group-schedule-item{grid-column:1/-1}.tree-group-schedule-value,.tree-group-schedule-pending{
    color:var(--ink-soft);
    font-size:10px;
    line-height:1.45;
    overflow-wrap:anywhere;
}.tree-group-schedule-pending{color:var(--muted)}.tree-delete-confirm{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    min-width:0;
    margin:0 0 10px;
    padding:10px;
    border:1px solid color-mix(in srgb,var(--danger) 30%,var(--line));
    border-radius:8px;
    background:var(--danger-bg);
}.tree-delete-confirm[hidden]{display:none!important}.tree-delete-confirm p{
    min-width:0;
    margin:0;
    color:var(--ink-soft);
    font-size:10px;
    line-height:1.45;
    overflow-wrap:anywhere;
}.tree-delete-confirm form{display:flex;flex:none;gap:6px;flex-wrap:wrap}.tree-delete-confirm .btn{min-height:33px;padding:6px 9px;border-radius:7px;font-size:10px}.tree-structure-card .workload-tree-course{
    position:relative;
    min-width:0;
    margin:0;
    overflow:visible;
    border:0;
    border-bottom:1px solid var(--line);
    border-radius:0;
    background:transparent;
}.tree-structure-card .workload-tree-course:last-child{border-bottom:0}.tree-course-branch{min-width:0;overflow:hidden}.tree-structure-card .tree-course-summary{
    display:grid;
    grid-template-columns:20px 34px minmax(0,1fr) auto;
    align-items:center;
    gap:8px;
    min-height:58px;
    padding:9px 44px 9px 8px;
    border:0;
    border-radius:0;
    background:transparent;
    cursor:pointer;
    transition:background .14s ease;
}.tree-structure-card .tree-course-summary:hover,.tree-structure-card .tree-course-branch[open]>.tree-course-summary{
    border:0;
    border-radius:0;
    background:color-mix(in srgb,var(--accent-soft) 55%,var(--surface));
}.tree-structure-card .tree-course-node{
    width:34px;
    height:34px;
    background:var(--surface-soft);
    color:var(--accent);
}.tree-structure-card .tree-course-branch[open]>.tree-course-summary .tree-course-node{
    background:var(--accent-soft);
}.tree-course-summary .tree-node-copy{
    display:grid;
    grid-column:3;
    grid-row:1;
    gap:3px;
    min-width:0;
}.tree-course-summary .tree-node-copy>strong{
    min-width:0;
    color:var(--ink);
    font-size:13px;
    font-weight:750;
    line-height:1.35;
    overflow-wrap:anywhere;
}.tree-course-code-row{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:3px 7px;
    min-width:0;
    color:var(--muted);
    font-size:10px;
}.tree-course-period{
    color:var(--muted);
    font-size:9px;
    line-height:1.35;
    overflow-wrap:anywhere;
}.tree-course-components{
    display:flex;
    grid-column:4;
    grid-row:1;
    align-items:center;
    flex-wrap:wrap;
    gap:4px;
    min-width:0;
    padding:0;
}.tree-component-chip{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:32px;
    padding:4px 6px;
    border:1px solid transparent;
    border-radius:5px;
    font-size:9px;
    font-weight:800;
    line-height:1.25;
}.tree-component-chip.is-present{border-color:#cfe4d6;background:#eef7f0;color:#28623d}.tree-component-chip.is-missing{border-color:var(--line);background:var(--surface);color:var(--muted)}.tree-component-chip.is-disabled{border-color:var(--line);background:var(--surface-soft);color:var(--muted)}.tree-course-delete-trigger{
    position:absolute;
    z-index:4;
    top:11px;
    right:7px;
    display:grid;
    place-items:center;
    width:28px;
    height:28px;
    border:1px solid transparent;
    border-radius:7px;
    background:transparent;
    color:var(--muted);
    cursor:pointer;
}.tree-course-delete-trigger:hover:not(:disabled){
    border-color:var(--danger);
    background:var(--danger-bg);
    color:var(--danger);
}.tree-course-delete-trigger:disabled{opacity:.2;cursor:not-allowed}.tree-structure-card .tree-course-children{
    min-width:0;
    margin:0;
    padding:7px 8px 10px 27px;
    border:0;
    border-top:1px solid var(--line);
    background:var(--surface);
}.tree-course-tools{
    display:flex;
    align-items:center;
    justify-content:flex-start;
    gap:7px;
    margin:0 0 5px;
    color:var(--muted);
    font-size:10px;
    font-weight:700;
}.tree-course-tools>span{display:inline-flex;align-items:center;gap:6px}.tree-structure-card .workload-tree-modality{
    display:grid;
    grid-template-columns:48px minmax(0,1fr) 172px;
    align-items:center;
    gap:10px;
    min-width:0;
    margin:0;
    padding:10px 5px;
    border-top:1px solid var(--line);
}.tree-branch-mark{display:none}.tree-modality-tag{
    display:grid;
    place-items:center;
    width:44px;
    height:38px;
    border-radius:8px;
    font-size:10px;
    font-weight:850;
}.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}.tree-modality-copy{
    display:grid;
    gap:4px;
    min-width:0;
    color:var(--ink-soft);
}.tree-modality-copy>strong{
    display:flex;
    align-items:baseline;
    flex-wrap:wrap;
    gap:3px 6px;
    min-width:0;
    color:var(--ink);
    font-size:11px;
    font-weight:700;
    line-height:1.4;
    overflow-wrap:anywhere;
}.tree-modality-copy>strong .tree-inline-nrc{
    min-width:0;
    font-weight:700;
    overflow-wrap:anywhere;
}.tree-modality-dates{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:5px 12px;
    min-width:0;
    color:var(--muted);
    font-size:9px;
    line-height:1.4;
}.tree-date-field{display:inline-flex;align-items:center;flex-wrap:wrap;gap:4px;min-width:0}.tree-date-label{color:var(--muted);font-size:9px}.tree-inline-date,.tree-inline-nrc{
    display:inline-flex;
    align-items:center;
    min-width:0;
    min-height:22px;
    padding:1px 4px;
    border:1px dashed transparent;
    border-radius:4px;
    color:var(--ink-soft);
    cursor:pointer;
    overflow-wrap:anywhere;
    transition:background .14s ease,border-color .14s ease,color .14s ease;
}.tree-disabled-value{color:var(--muted);overflow-wrap:anywhere}.tree-modality-copy>small{
    display:block;
    min-width:0;
    color:var(--muted);
    font-size:9px;
    line-height:1.4;
    overflow-wrap:anywhere;
}.tree-modality-actions{
    display:grid;
    justify-items:end;
    gap:6px;
    min-width:0;
}.tree-course-status{
    display:inline-flex;
    align-items:center;
    max-width:100%;
    padding:3px 7px;
    border:1px solid var(--line);
    border-radius:5px;
    background:var(--surface-soft);
    color:var(--muted);
    font-size:9px;
    font-weight:700;
    line-height:1.3;
}.tree-course-status.is-ready{border-color:#cfe4d6;background:#eef7f0;color:#28623d}.tree-course-status.is-pending{border-color:#eadfbe;background:#faf6e9;color:#85651f}.tree-course-status.is-disabled{border-color:var(--line);background:var(--surface-soft);color:var(--muted)}.tree-modality-buttons{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    flex-wrap:wrap;
    gap:5px;
    min-width:0;
}.tree-modality-toggle{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-width:85px;
    min-height:32px;
    padding:6px 8px;
    border:1px solid var(--line-strong);
    border-radius:7px;
    background:var(--surface);
    color:var(--ink-soft);
    font-size:9px;
    font-weight:700;
    line-height:1.25;
    text-align:center;
    cursor:pointer;
    transition:background .14s ease,border-color .14s ease,color .14s ease;
}.tree-modality-toggle.is-on{border-color:#d9e3dc;background:var(--surface-soft);color:var(--ink-soft)}.tree-modality-toggle.is-off{border-color:color-mix(in srgb,var(--accent) 28%,var(--line));background:var(--accent-soft);color:var(--accent)}.tree-modality-toggle:disabled{opacity:.58;cursor:not-allowed}.tree-toggle-dot{width:7px;height:7px;flex:none;border-radius:50%;background:currentColor;opacity:.6}.tree-open-modality,.tree-add-modality{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:5px;
    min-height:32px;
    padding:6px 9px;
    border-radius:7px;
    font-size:9px;
    font-weight:700;
    line-height:1.25;
    white-space:normal;
}.tree-structure-card .tree-open-modality{
    border-color:var(--line-strong);
    background:var(--surface);
    color:var(--ink-soft)!important;
    box-shadow:none;
}.tree-structure-card .tree-open-modality:hover{
    border-color:var(--accent);
    background:var(--accent-soft);
    color:var(--accent)!important;
}.tree-modality-actions.is-missing-actions{justify-items:end}.tree-modality-actions.is-missing-actions .tree-add-modality{justify-self:end}.tree-structure-card .tree-empty-child{
    display:grid;
    justify-items:center;
    gap:6px;
    min-height:130px;
    padding:22px 14px;
    color:var(--muted);
    text-align:center;
}.tree-empty-child strong{color:var(--ink);font-size:13px}.tree-empty-child span{max-width:42ch;font-size:10px;line-height:1.45}.tree-course-entry-form,.tree-group-draft-form,.tree-course-draft-form{
    display:grid;
    gap:10px;
    min-width:0;
    margin:9px 0;
    padding:11px;
    border:1px solid var(--line);
    border-radius:9px;
    background:var(--surface);
    box-shadow:none;
    animation:tree-form-in .16s ease-out both;
}.tree-course-entry-form[hidden],.tree-group-draft-form[hidden],.tree-course-draft-form[hidden],.tree-add-row[hidden]{display:none!important}.tree-draft-heading{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:6px;
    min-width:0;
}.tree-draft-heading strong{display:inline-flex;align-items:center;gap:6px;color:var(--ink);font-size:12px;font-weight:800}.tree-draft-heading>span{color:var(--muted);font-size:9px}.tree-draft-fields{display:grid;grid-template-columns:minmax(0,1fr);gap:7px;min-width:0}.tree-course-name-input{display:grid;gap:5px;min-width:0;color:var(--ink-soft);font-size:10px;font-weight:700}.tree-course-name-input .field,.tree-draft-fields .field{
    width:100%;
    min-width:0;
    min-height:42px;
    border:1px solid var(--line-strong);
    border-radius:8px;
    background:var(--surface);
    font-size:12px;
}.tree-draft-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:8px;
    min-width:0;
}.tree-draft-footer>div{display:flex;align-items:center;gap:6px}.tree-draft-footer .btn{min-height:36px;border-radius:7px}.tree-draft-note{color:var(--muted);font-size:9px;line-height:1.4}.tree-draft-error,.tree-course-entry-error{
    min-width:0;
    color:var(--danger);
    font-size:10px;
    line-height:1.4;
    overflow-wrap:anywhere;
}.tree-draft-error[hidden],.tree-course-entry-error[hidden]{display:none!important}.tree-add-row{
    display:flex;
    justify-content:center;
    min-width:0;
    padding:8px;
    border-top:1px dashed var(--line);
    background:transparent;
}.tree-add-row>button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-height:36px;
    padding:7px 13px;
    border:1px solid var(--line-strong);
    border-radius:8px;
    background:var(--surface);
    color:var(--accent);
    font-size:10px;
    font-weight:750;
    cursor:pointer;
    transition:background .14s ease,border-color .14s ease,color .14s ease;
}.tree-add-row>button:hover{border-color:var(--accent);background:var(--accent-soft)}.tree-live-feedback{
    position:relative;
    display:flex;
    align-items:flex-start;
    gap:8px;
    min-width:0;
    margin:10px 0 0;
    padding:10px 12px;
    border:1px solid color-mix(in srgb,var(--success) 28%,var(--line));
    border-radius:8px;
    background:var(--success-bg);
    color:var(--success);
    font-size:11px;
    line-height:1.45;
    overflow-wrap:anywhere;
    animation:tree-feedback-in .18s ease-out both;
}.tree-live-feedback[hidden]{display:none!important}.tree-live-feedback.is-error{border-color:color-mix(in srgb,var(--danger) 30%,var(--line));background:var(--danger-bg);color:var(--danger)}.tree-live-feedback.is-leaving{animation:tree-feedback-out .16s ease-in both}.tree-loading-state{
    position:relative;
    min-height:46px;
    padding:14px 12px 14px 34px;
    border-radius:7px;
    background:var(--surface-soft);
    color:var(--muted);
    font-size:10px;
}.tree-loading-state:before{
    position:absolute;
    top:50%;
    left:12px;
    width:11px;
    height:11px;
    margin-top:-6px;
    border:2px solid var(--line-strong);
    border-top-color:var(--accent);
    border-radius:50%;
    content:"";
    animation:tree-spinner .7s linear infinite;
}.tree-inline-editor{
    display:block;
    width:100%;
    max-width:100%;
    min-width:0;
    min-height:36px;
    padding:5px 7px;
    border:1px solid var(--accent);
    border-radius:7px;
    background:var(--surface);
    color:var(--ink);
    font:inherit;
    font-size:12px;
    box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 10%,transparent);
    outline:0;
}.tree-inline-saving{opacity:.65;pointer-events:none}.tree-structure-card button,.tree-structure-card a,.tree-structure-card summary{touch-action:manipulation}.tree-structure-card button:focus-visible,.tree-structure-card a:focus-visible,.tree-structure-card summary:focus-visible,.tree-inline-editable:focus-visible{
    outline:2px solid var(--accent);
    outline-offset:2px;
}
@keyframes tree-spinner{to{transform:rotate(360deg)}}
@keyframes tree-form-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
@keyframes tree-feedback-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
@keyframes tree-feedback-out{to{opacity:0;transform:translateY(-3px)}}
@media(hover:hover) and (pointer:fine){.tree-structure-card .workload-group-summary:hover .tree-group-icon{background:var(--accent-soft);color:var(--accent)}.tree-inline-editable:hover,.tree-inline-date:hover,.tree-inline-nrc:hover{
        border-color:color-mix(in srgb,var(--accent) 35%,var(--line));
        background:var(--accent-soft);
        color:var(--accent);
    }.tree-icon-action:hover:not(:disabled){background:var(--surface-soft)}
}
@media(max-width:760px){.workload-tree-page{gap:11px}.workload-tree-hero{align-items:flex-start;padding:16px}.workload-tree-hero h1{font-size:29px}.workload-tree-hero-actions>.btn{min-height:38px}.tree-structure-card{padding:12px}.tree-structure-card .tree-panel-heading{align-items:flex-start;gap:8px;margin-bottom:12px}.tree-structure-card .tree-panel-heading h2{font-size:19px}.tree-structure-card .tree-panel-heading p:not(.eyebrow){font-size:10px}.tree-structure-card .workload-tree-search-field{height:40px}.tree-structure-card .workload-tree-search .btn{min-height:40px}.tree-structure-card .workload-group-summary{
        grid-template-columns:20px 34px minmax(0,1fr) 28px;
        gap:7px;
        min-height:62px;
        padding:9px 9px;
    }.tree-structure-card .tree-node-icon{width:34px;height:34px}.tree-group-title-row{gap:4px 7px}.tree-group-title-row>strong{font-size:14px}.tree-meta-pill{padding:2px 5px;font-size:8px}.tree-group-code-row{font-size:9px}.tree-group-row-actions .tree-icon-action{width:28px;height:28px}.tree-structure-card .workload-tree-children{padding:9px 8px 10px 16px}.tree-structure-card .tree-course-summary{
        grid-template-columns:18px 30px minmax(0,1fr);
        gap:6px;
        padding:8px 38px 8px 5px;
    }.tree-course-summary .tree-node-copy{grid-column:3;grid-row:1;gap:3px}.tree-course-summary .tree-node-copy>strong{font-size:12px}.tree-course-summary .tree-course-components{
        grid-column:3;
        grid-row:2;
        justify-content:flex-start;
        margin:0;
    }.tree-structure-card .tree-course-node{width:30px;height:30px}.tree-course-delete-trigger{top:8px;right:5px;width:27px;height:27px}.tree-structure-card .tree-course-children{padding:6px 5px 8px 10px}.tree-structure-card .workload-tree-modality{
        grid-template-columns:40px minmax(0,1fr);
        align-items:start;
        gap:7px;
        padding:9px 2px;
    }.tree-structure-card .tree-modality-tag{
        grid-column:1;
        grid-row:1;
        width:38px;
        height:33px;
        font-size:9px;
    }.tree-modality-copy{grid-column:2;grid-row:1}.tree-modality-actions{
        grid-column:2;
        grid-row:2;
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:6px;
        width:100%;
    }.tree-modality-actions.is-missing-actions{justify-content:flex-start}.tree-modality-buttons{justify-content:flex-start}.tree-modality-toggle,.tree-open-modality,.tree-add-modality{min-height:34px}.tree-delete-confirm{align-items:stretch;flex-direction:column}.tree-delete-confirm form{display:flex;flex-wrap:wrap}.tree-course-entry-form,.tree-group-draft-form,.tree-course-draft-form{padding:10px}.tree-draft-footer{align-items:flex-start;flex-direction:column}.tree-draft-footer>div{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));width:100%}.tree-draft-footer .btn{width:100%}
}
@media(max-width:520px){.workload-tree-hero{display:grid;gap:10px;padding:14px}.workload-tree-hero h1{font-size:27px}.workload-tree-context{display:flex;gap:6px 10px;margin-top:8px}.tree-context-hint{display:none}.workload-tree-hero-actions{width:100%}.workload-tree-hero-actions>.btn{width:100%}.tree-structure-card{padding:10px;border-radius:10px}.tree-structure-card .tree-panel-heading{
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        align-items:start;
        gap:7px;
    }.tree-structure-card .tree-panel-heading h2{font-size:17px;gap:6px}.tree-structure-card .tree-panel-heading p:not(.eyebrow){font-size:10px}.tree-structure-card .workload-tree-total{align-self:start;padding:5px 7px;font-size:9px}.tree-structure-card .workload-tree-search{display:grid;grid-template-columns:minmax(0,1fr) 72px;gap:6px}.tree-structure-card .workload-tree-search-field{grid-column:1;grid-row:1;width:100%;padding:0 8px}.tree-structure-card .workload-tree-search-field input{font-size:11px}.tree-structure-card .workload-tree-search .btn{grid-column:2;grid-row:1;width:100%;min-width:0;padding:6px;font-size:10px}.tree-structure-card .workload-group-summary{
        grid-template-columns:17px 29px minmax(0,1fr) 25px;
        gap:5px;
        padding:8px 6px;
    }.tree-structure-card .tree-expand-symbol{width:17px;height:20px}.tree-structure-card .tree-node-icon{width:29px;height:29px;border-radius:7px}.tree-structure-card .tree-node-icon svg{width:18px;height:18px}.tree-node-copy{gap:4px}.tree-group-title-row{align-items:flex-start;flex-direction:column;gap:3px}.tree-group-title-row>strong{font-size:13px;line-height:1.3}.tree-group-counts{gap:3px}.tree-meta-pill{font-size:8px}.tree-group-code-row{align-items:flex-start;gap:3px 5px;font-size:9px}.tree-group-row-actions .tree-icon-action{width:25px;height:25px}.tree-group-row-actions .tree-icon-action svg{width:14px;height:14px}.tree-structure-card .workload-tree-children{padding:8px 5px 9px 10px}.tree-group-schedule-item{grid-column:1}.tree-structure-card .tree-course-summary{
        grid-template-columns:16px 27px minmax(0,1fr);
        gap:5px;
        padding:7px 34px 7px 3px;
    }.tree-structure-card .tree-course-node{width:27px;height:27px}.tree-structure-card .tree-course-node svg{width:16px;height:16px}.tree-structure-card .tree-course-summary .tree-node-copy>strong{font-size:11px;line-height:1.3}.tree-course-code-row{font-size:9px}.tree-course-period{font-size:8px}.tree-component-chip{min-width:29px;padding:3px 5px;font-size:8px}.tree-course-delete-trigger{top:7px;right:3px;width:25px;height:25px}.tree-course-delete-trigger svg{width:14px;height:14px}.tree-structure-card .tree-course-children{padding:5px 3px 7px 7px}.tree-structure-card .workload-tree-modality{
        grid-template-columns:35px minmax(0,1fr);
        gap:6px;
        padding:8px 1px;
    }.tree-structure-card .tree-modality-tag{width:33px;height:30px;font-size:8px}.tree-modality-copy>strong{font-size:10px;gap:2px 4px}.tree-modality-dates{gap:4px 8px;font-size:8px}.tree-date-label{font-size:8px}.tree-modality-copy>small{font-size:8px;line-height:1.4}.tree-modality-actions{gap:5px}.tree-course-status{padding:2px 5px;font-size:8px}.tree-modality-buttons{gap:5px}.tree-modality-toggle,.tree-open-modality,.tree-add-modality{
        min-height:32px;
        padding:5px 7px;
        border-radius:6px;
        font-size:8px;
    }.tree-modality-toggle{min-width:0}.tree-structure-card .tree-open-modality{min-width:0}.tree-delete-confirm{padding:8px;gap:8px}.tree-delete-confirm p{font-size:9px}.tree-delete-confirm form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:5px}.tree-delete-confirm .btn{min-width:0;min-height:32px;padding:5px;font-size:9px}.tree-draft-heading strong{font-size:11px}.tree-draft-heading>span,.tree-draft-note{font-size:8px}.tree-draft-footer .btn{min-height:36px;font-size:10px}.tree-add-row{padding:7px 5px}.tree-add-row>button{width:100%;min-height:38px}.tree-live-feedback{padding:9px;font-size:10px}.tree-empty-child{min-height:110px;padding:16px 9px}.tree-empty-child strong{font-size:11px}.tree-empty-child span{font-size:9px}.tree-inline-editor{min-height:37px;font-size:11px}
}
@media(max-width:360px){.tree-structure-card .tree-panel-heading{grid-template-columns:minmax(0,1fr)}.tree-structure-card .workload-tree-total{justify-self:start}.tree-structure-card .workload-group-summary{
        grid-template-columns:15px 26px minmax(0,1fr) 23px;
        gap:4px;
        padding:7px 4px;
    }.tree-structure-card .tree-node-icon{width:26px;height:26px}.tree-group-title-row>strong{font-size:12px}.tree-meta-pill{font-size:7px;padding:2px 4px}.tree-structure-card .tree-course-summary{
        grid-template-columns:14px 24px minmax(0,1fr);
        gap:4px;
        padding-right:31px;
    }.tree-structure-card .tree-course-node{width:24px;height:24px}.tree-course-delete-trigger{right:2px;width:23px;height:23px}.tree-modality-toggle,.tree-open-modality,.tree-add-modality{font-size:8px;padding:4px 6px}
}
@media(prefers-reduced-motion:reduce){.workload-tree-page *,.tree-live-feedback,.tree-course-entry-form,.tree-group-draft-form,.tree-course-draft-form{
        scroll-behavior:auto!important;
        animation:none!important;
        transition:none!important;
    }.tree-loading-state:before{animation:none!important}
}.tree-inline-editable[data-edit-value=""]{
    color:var(--accent)!important;
    background:var(--accent-soft);
    border:1px dashed color-mix(in srgb,var(--accent) 42%,var(--line));
    border-radius:6px;
    padding:3px 7px;
    font-weight:750;
}.tree-inline-editable[data-edit-value=""]:hover{
    background:color-mix(in srgb,var(--accent) 16%,var(--surface));
    border-color:var(--accent);
}.tree-inline-editor[type="time"]{
    min-width:124px;
    max-width:150px;
    font-variant-numeric:tabular-nums;
}
@media(max-width:520px){.tree-inline-editor[type="time"]{min-width:115px;max-width:135px}
}

/* Legibilidad adicional en pantallas estrechas: texto táctil sin microetiquetas */
@media(max-width:520px){.tree-meta-pill{font-size:9px}.tree-course-status{font-size:9px}.tree-modality-copy>strong{font-size:11px}.tree-modality-copy>small,.tree-modality-dates{font-size:9px}.tree-date-label{font-size:9px}.tree-modality-toggle,.tree-open-modality,.tree-add-modality{
        min-height:35px;
        padding:6px 8px;
        font-size:9px;
    }.tree-draft-note,.tree-draft-heading>span{font-size:9px}.tree-structure-card .tree-group-schedule-value,.tree-structure-card .tree-group-schedule-pending{font-size:10px}
}
@media(max-width:360px){.tree-group-title-row>strong{font-size:13px}.tree-meta-pill{font-size:8px}.tree-modality-toggle,.tree-open-modality,.tree-add-modality{font-size:9px}
}

/* Datos académicos del grupo en una sola línea compacta bajo el código. */
.tree-group-inline-details{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:3px 13px;
    min-width:0;
    margin-top:1px;
    color:var(--muted);
    font-size:9px;
    line-height:1.35;
}
.tree-group-inline-field{
    display:inline-flex;
    align-items:center;
    flex-wrap:wrap;
    gap:3px 5px;
    min-width:0;
}
.tree-group-inline-label{
    color:var(--muted);
    font-size:9px;
    font-weight:750;
}
.tree-group-inline-days{
    color:var(--ink-soft);
    font-size:9px;
}
.tree-group-inline-time{
    display:inline-flex;
    align-items:center;
    flex-wrap:wrap;
    gap:3px;
}
.tree-group-inline-sub-label{
    color:var(--muted);
    font-size:8px;
    font-weight:650;
}
.tree-group-inline-details .tree-inline-editable{
    min-height:20px;
    padding:1px 4px;
    font-size:9px;
    line-height:1.3;
}
.tree-group-inline-details .tree-inline-editor[type="time"]{
    min-width:105px;
    max-width:120px;
    min-height:30px;
    font-size:10px;
}
@media(max-width:720px){
    .tree-group-inline-details{gap:4px 9px;font-size:9px}
    .tree-group-inline-field{gap:3px 4px}
    .tree-group-inline-label{font-size:8px}
    .tree-group-inline-days{font-size:8px}
    .tree-group-inline-details .tree-inline-editable{font-size:9px}
}
@media(max-width:380px){
    .tree-group-inline-details{column-gap:7px}
    .tree-group-inline-schedule{flex-basis:100%}
}

/* Periodo propio del curso: editable en línea y sin aumentar demasiado el nodo. */
.tree-course-period{
    display:flex!important;
    align-items:center;
    flex-wrap:wrap;
    gap:4px 13px;
    margin-top:3px;
    color:var(--muted);
    font-size:10px;
    line-height:1.35;
}
.tree-course-period .tree-date-field{display:inline-flex;align-items:center;gap:4px}
.tree-course-period .tree-date-label{font-size:9px;font-weight:750}
.tree-course-period .tree-inline-date{min-height:21px;padding:2px 6px;font-size:9px}
.tree-course-period .tree-inline-date.is-empty{
    border:1px dashed color-mix(in srgb,var(--accent) 45%,var(--line));
    border-radius:6px;
    border-bottom-style:dashed;
    background:var(--accent-soft);
    color:var(--accent);
    font-weight:750;
}
.tree-course-period .tree-inline-date.is-empty:hover{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 15%,var(--surface))}
.tree-course-period .tree-inline-editor{min-width:130px;max-width:155px;min-height:29px;font-size:10px}

/* Estado de programación: reemplaza el mensaje repetido "Horario pendiente". */
.tree-modality-schedule-summary{
    display:inline-flex!important;
    align-items:center;
    flex-wrap:wrap;
    gap:5px;
    width:max-content;
    max-width:100%;
    margin:0;
    padding:4px 7px;
    border:1px solid var(--line);
    border-radius:6px;
    font-size:9px!important;
    font-weight:650;
    line-height:1.35!important;
}
.tree-modality-schedule-summary .icon{flex:none;width:13px;height:13px}
.tree-modality-schedule-summary.is-pending{border-color:color-mix(in srgb,var(--accent) 24%,var(--line));background:var(--accent-soft);color:var(--accent)}
.tree-modality-schedule-summary.is-ready{border-color:#cfe4d6;background:#eff7f0;color:#28623d}
.tree-modality-schedule-summary.is-disabled{background:var(--surface-soft);color:var(--muted)}
.tree-modality-extra-meta{display:inline-flex;align-items:center;color:var(--muted);font-size:8px}

/* Conectores del árbol: grupo -> curso -> componentes TEC/TAL. */
.tree-structure-card .workload-tree-root{
    position:relative!important;
    display:block!important;
    padding:12px 0 12px 22px!important;
}
.tree-structure-card .workload-tree-root::before{
    content:"";
    position:absolute;
    top:25px;
    bottom:25px;
    left:8px;
    width:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 42%,var(--line-strong));
}
.tree-structure-card .workload-tree-root>.workload-tree-group-shell{
    position:relative!important;
    min-width:0;
    margin:0 0 9px!important;
    padding:0 0 0 16px!important;
    overflow:visible!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
    box-shadow:none!important;
}
.tree-structure-card .workload-tree-root>.workload-tree-group-shell:last-child{margin-bottom:0!important}
.tree-structure-card .workload-tree-root>.workload-tree-group-shell::before{
    content:"";
    position:absolute;
    top:33px;
    left:-14px;
    width:30px;
    height:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 42%,var(--line-strong));
}
.tree-structure-card .workload-tree-group{
    min-width:0;
    margin:0!important;
    overflow:visible!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
}
.tree-structure-card .workload-group-summary{
    position:relative;
    min-width:0;
    border:1px solid var(--line-strong)!important;
    border-radius:10px!important;
    background:var(--surface)!important;
}
.tree-structure-card .workload-tree-group[open]>.workload-group-summary{
    border-color:color-mix(in srgb,var(--accent) 42%,var(--line))!important;
    border-radius:10px 10px 0 0!important;
    background:var(--accent-soft)!important;
}
.tree-structure-card .workload-tree-children.workload-group-children{
    position:relative!important;
    min-width:0;
    margin:0 0 0 18px!important;
    padding:9px 7px 12px 22px!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
}
.tree-structure-card .workload-tree-children.workload-group-children::before{
    content:"";
    position:absolute;
    top:0;
    bottom:18px;
    left:8px;
    width:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 36%,var(--line-strong));
}
.tree-structure-card .workload-tree-children.workload-group-children::after{
    content:"";
    position:absolute;
    top:0;
    left:-18px;
    width:26px;
    height:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 36%,var(--line-strong));
}
.tree-structure-card .workload-tree-children.workload-group-children>[data-tree-content]{
    position:relative;
    min-width:0;
    margin:0;
    padding:0;
}
.tree-structure-card .workload-tree-course{
    position:relative!important;
    min-width:0;
    margin:0 0 7px!important;
    padding:0 0 0 16px!important;
    overflow:visible!important;
    border:0!important;
    background:transparent!important;
}
.tree-structure-card .workload-tree-course:last-child{margin-bottom:0!important}
.tree-structure-card .workload-tree-course::before{
    content:"";
    position:absolute;
    top:29px;
    left:-14px;
    width:30px;
    height:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 36%,var(--line-strong));
}
.tree-structure-card .tree-course-branch{
    min-width:0;
    overflow:visible!important;
}
.tree-structure-card .tree-course-summary{
    position:relative;
    min-width:0;
    border:1px solid var(--line)!important;
    border-radius:9px!important;
    background:var(--surface)!important;
}
.tree-structure-card .tree-course-branch[open]>.tree-course-summary{
    border-color:color-mix(in srgb,var(--accent) 40%,var(--line))!important;
    border-radius:9px 9px 0 0!important;
    background:color-mix(in srgb,var(--accent-soft) 55%,var(--surface))!important;
}
.tree-structure-card .tree-course-children{
    position:relative!important;
    min-width:0;
    margin:0 0 0 22px!important;
    padding:9px 7px 12px 26px!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
}
.tree-structure-card .tree-course-children::before{
    content:"";
    position:absolute;
    top:0;
    bottom:20px;
    left:11px;
    width:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 32%,var(--line-strong));
}
.tree-structure-card .tree-course-children::after{
    content:"";
    position:absolute;
    top:0;
    left:-22px;
    width:33px;
    height:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 32%,var(--line-strong));
}
.tree-structure-card .tree-course-tools{
    position:relative;
    z-index:1;
    display:flex;
    align-items:center;
    width:max-content;
    max-width:100%;
    margin:0 0 4px;
    padding:3px 7px;
    border:1px solid var(--line);
    border-radius:6px;
    background:var(--surface-soft);
    color:var(--muted);
    font-size:9px;
}
.tree-structure-card .workload-tree-modality{
    position:relative!important;
    min-width:0;
    margin:0 0 0 14px!important;
    padding:10px 4px 10px 2px!important;
    border:0!important;
    background:transparent!important;
}
.tree-structure-card .workload-tree-modality::before{
    content:"";
    position:absolute;
    top:26px;
    left:-29px;
    width:29px;
    height:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 32%,var(--line-strong));
}
.tree-structure-card .tree-branch-mark{display:none!important}
.tree-structure-card .workload-tree-modality+.workload-tree-modality{
    border-top:1px solid color-mix(in srgb,var(--line) 72%,transparent)!important;
}
@media(max-width:760px){
    .tree-structure-card .workload-tree-root{padding-left:15px!important}
    .tree-structure-card .workload-tree-root::before{left:5px}
    .tree-structure-card .workload-tree-root>.workload-tree-group-shell{padding-left:11px!important}
    .tree-structure-card .workload-tree-root>.workload-tree-group-shell::before{left:-10px;width:21px}
    .tree-structure-card .workload-tree-children.workload-group-children{margin-left:10px!important;padding-left:17px!important}
    .tree-structure-card .workload-tree-children.workload-group-children::before{left:5px}
    .tree-structure-card .workload-tree-children.workload-group-children::after{left:-10px;width:15px}
    .tree-structure-card .workload-tree-course{padding-left:11px!important}
    .tree-structure-card .workload-tree-course::before{left:-12px;width:23px}
    .tree-structure-card .tree-course-children{margin-left:13px!important;padding-left:20px!important}
    .tree-structure-card .tree-course-children::before{left:8px}
    .tree-structure-card .tree-course-children::after{left:-13px;width:21px}
    .tree-structure-card .workload-tree-modality{margin-left:10px!important}
    .tree-structure-card .workload-tree-modality::before{left:-22px;width:22px}
    .tree-course-period{gap:3px 8px;font-size:9px}
    .tree-course-period .tree-date-label{font-size:8px}
}
@media(prefers-reduced-motion:reduce){
    .tree-structure-card .workload-tree-group-shell::before,
    .tree-structure-card .workload-tree-course::before,
    .tree-structure-card .workload-tree-modality::before{transition:none}
}


/* El ciclo es el nodo raíz; sus grupos cuelgan de un único tronco. */
.tree-structure-card .workload-tree-root{
    display:block!important;
    position:relative!important;
    padding:12px 0 12px 22px!important;
}
.tree-structure-card .workload-tree-root::before{
    content:"";
    position:absolute;
    top:32px;
    bottom:25px;
    left:8px;
    width:2px;
    border-radius:2px;
    background:color-mix(in srgb,var(--accent) 42%,var(--line-strong));
}
.tree-cycle-root{
    position:relative;
    display:flex;
    align-items:center;
    gap:10px;
    width:max-content;
    max-width:calc(100% - 18px);
    min-height:42px;
    margin:0 0 12px 16px;
    padding:8px 13px;
    border:1px solid color-mix(in srgb,var(--accent) 42%,var(--line-strong));
    border-radius:9px;
    background:var(--accent-soft);
    color:var(--ink);
}
.tree-cycle-root::before{
    content:"";
    position:absolute;
    top:50%;
    left:-30px;
    width:30px;
    height:2px;
    background:color-mix(in srgb,var(--accent) 42%,var(--line-strong));
}
.tree-cycle-root strong{font-size:12px;font-weight:850;line-height:1.3}
.tree-cycle-root small{color:var(--muted);font-size:9px;font-weight:700;white-space:nowrap}

/* El indicador de despliegue es una marca simple, no un icono de biblioteca. */
.tree-expand-symbol{
    display:grid!important;
    place-items:center;
    flex:0 0 18px!important;
    width:18px!important;
    height:24px!important;
    margin:0!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
    color:var(--muted)!important;
}
.tree-expand-symbol::before{
    content:"";
    width:7px;
    height:7px;
    border-top:2px solid currentColor;
    border-right:2px solid currentColor;
    transform:rotate(45deg);
    transition:transform .16s ease,color .16s ease;
}
.workload-tree-group[open]>.workload-tree-summary .tree-expand-symbol::before,
.tree-course-branch[open]>.tree-course-summary .tree-expand-symbol::before{
    transform:rotate(135deg);
    color:var(--accent);
}
.workload-tree-summary,.tree-course-summary{gap:8px!important}
.tree-node-copy{margin-left:0!important}
.tree-modality-tag{font-size:10px;font-weight:850;letter-spacing:.04em}
.tree-modality-schedule-summary .icon{display:none!important}
@media(max-width:760px){
    .tree-structure-card .workload-tree-root{padding-left:15px!important}
    .tree-structure-card .workload-tree-root::before{left:5px}
    .tree-cycle-root{margin-left:11px;max-width:calc(100% - 12px);padding:7px 10px}
    .tree-cycle-root::before{left:-21px;width:21px}
}

</style>
@endpush

@push('scripts')
<script>
(() => {
    const root = document.querySelector('.workload-tree-page');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const feedback = root.querySelector('#tree-live-feedback');
    const groupSearch = root.querySelector('.workload-tree-search [name="q"]');
    let feedbackTimer = null;
    let feedbackExitTimer = null;

    const showFeedback = (message, error = false) => {
        if (!feedback || !message) return;

        window.clearTimeout(feedbackTimer);
        window.clearTimeout(feedbackExitTimer);
        feedback.classList.remove('is-leaving');
        feedback.classList.toggle('is-error', error);
        feedback.textContent = message;
        feedback.hidden = false;

        feedbackTimer = window.setTimeout(() => {
            feedback.classList.add('is-leaving');
            feedbackExitTimer = window.setTimeout(() => {
                feedback.hidden = true;
                feedback.classList.remove('is-leaving');
                feedbackTimer = null;
                feedbackExitTimer = null;
            }, 240);
        }, error ? 8500 : 5200);
    };

    const responsePayload = async response => {
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = Object.values(payload.errors || {}).flat();
            throw new Error(messages.join(' ') || payload.message || 'No se pudo guardar el cambio.');
        }
        return payload;
    };

    const post = async (url, body = new FormData()) => {
        return responsePayload(await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            }
        }));
    };

    const patch = async (url, values) => {
        // PHP procesa multipart/form-data de forma fiable en POST.
        // Laravel transforma este POST en PATCH mediante _method.
        const body = new FormData();
        body.append('_method', 'PATCH');
        Object.entries(values).forEach(([key, value]) => body.append(key, value ?? ''));
        return responsePayload(await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            }
        }));
    };

    const releaseAjaxForm = (form, submit) => {
        if (form) {
            delete form.dataset.submitting;
            form.classList.remove('form-submitting');
            form.removeAttribute('aria-busy');
        }
        if (submit) {
            submit.classList.remove('is-loading');
            submit.removeAttribute('aria-busy');
        }
    };

    const displayDate = value => {
        if (!value) return 'Sin fecha';
        const parts = value.split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    };

    const groupNodeById = id => root.querySelector('[data-tree-group-id="' + id + '"]');
    const courseNodeById = id => root.querySelector('[data-assignment-id="' + id + '"]');

    const updateCourseDeleteGuards = (groupNode, count) => {
        if (!groupNode) return;
        const countNode = groupNode.querySelector('[data-group-course-count]');
        if (countNode && Number.isFinite(Number(count))) countNode.textContent = String(count);
        const countLabel = groupNode.querySelector('[data-group-course-label]');
        if (countLabel && Number.isFinite(Number(count))) countLabel.textContent = Number(count) === 1 ? 'curso' : 'cursos';
        const mustKeepOne = Number(count) <= 1;
        groupNode.querySelectorAll('[data-toggle-course-delete], [data-tree-delete-course] button[type="submit"]').forEach(button => {
            button.disabled = mustKeepOne;
            if (button.matches('[data-toggle-course-delete]')) {
                button.title = mustKeepOne ? 'El grupo debe conservar al menos un curso' : 'Retirar curso de este grupo';
            }
        });
    };

    const loadGroup = async (groupNode, force = false) => {
        if (!groupNode || (!force && groupNode.dataset.treeLoaded === '1') || groupNode.dataset.treeLoading === '1') return;
        const content = groupNode.querySelector('[data-tree-content]');
        if (!content) return;

        groupNode.dataset.treeLoading = '1';
        content.innerHTML = '<div class="tree-loading-state">Cargando cursos…</div>';

        const url = new URL(groupNode.dataset.treeLoad, window.location.href);
        if (groupSearch?.value.trim()) url.searchParams.set('q', groupSearch.value.trim());

        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
            });
            if (!response.ok || response.redirected) throw new Error('No se pudieron cargar los cursos.');
            content.innerHTML = await response.text();
            groupNode.dataset.treeLoaded = '1';

            const count = groupNode.querySelector('[data-group-course-count]')?.textContent || 0;
            updateCourseDeleteGuards(groupNode, count);
        } catch (error) {
            content.innerHTML = '<div class="tree-load-error"></div>';
            content.querySelector('.tree-load-error').textContent = error.message;
            groupNode.dataset.treeLoaded = '0';
        } finally {
            groupNode.dataset.treeLoading = '0';
        }
    };

    const refreshGroup = async (groupId, assignmentId = null) => {
        const groupNode = groupNodeById(groupId);
        if (!groupNode) return null;
        groupNode.open = true;
        groupNode.dataset.treeLoaded = '0';
        await loadGroup(groupNode, true);

        const course = assignmentId ? courseNodeById(assignmentId) : null;
        if (course) {
            const branch = course.querySelector('[data-course-branch]');
            if (branch) branch.open = true;
            course.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        }
        return course;
    };

    root.querySelectorAll('[data-tree-load]').forEach(groupNode => {
        groupNode.addEventListener('toggle', () => {
            if (!groupNode.open) return;
            loadGroup(groupNode);
        });
    });

    const newlyCreatedGroupId = new URLSearchParams(window.location.search).get('open_group');
    if (newlyCreatedGroupId) {
        const newlyCreatedGroup = groupNodeById(newlyCreatedGroupId);
        if (newlyCreatedGroup) {
            newlyCreatedGroup.open = true;
            loadGroup(newlyCreatedGroup);
            newlyCreatedGroup.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('open_group');
        window.history.replaceState({}, '', cleanUrl.toString());
    }

    document.addEventListener('click', async event => {
        const showGroupDraft = event.target.closest('[data-show-group-draft]');
        if (showGroupDraft) {
            event.preventDefault();
            const form = root.querySelector('[data-tree-group-form]');
            const row = root.querySelector('[data-group-add-row]');
            if (form) {
                form.hidden = false;
                if (row) row.hidden = true;
                form.querySelector('[name="name"]')?.focus();
            }
            return;
        }

        const showCourseDraft = event.target.closest('[data-show-course-draft]');
        if (showCourseDraft) {
            event.preventDefault();
            const group = showCourseDraft.closest('[data-tree-group-id]');
            const form = group?.querySelector('[data-tree-course-form]');
            const row = group?.querySelector('[data-course-add-row]');
            if (form) {
                form.hidden = false;
                if (row) row.hidden = true;
                form.querySelector('[name="course_name"]')?.focus();
            }
            return;
        }

        const cancelGroupDraft = event.target.closest('[data-cancel-group-draft]');
        if (cancelGroupDraft) {
            const form = cancelGroupDraft.closest('[data-tree-group-form]');
            form?.reset();
            const errorBox = form?.querySelector('[data-group-form-error]');
            if (errorBox) { errorBox.hidden = true; errorBox.textContent = ''; }
            if (form) form.hidden = true;
            const row = root.querySelector('[data-group-add-row]');
            if (row) row.hidden = false;
            return;
        }

        const cancelCourseDraft = event.target.closest('[data-cancel-course-draft]');
        if (cancelCourseDraft) {
            const form = cancelCourseDraft.closest('[data-tree-course-form]');
            form?.reset();
            const errorBox = form?.querySelector('[data-course-form-error]');
            if (errorBox) { errorBox.hidden = true; errorBox.textContent = ''; }
            if (form) form.hidden = true;
            const group = cancelCourseDraft.closest('[data-tree-group-id]');
            const row = group?.querySelector('[data-course-add-row]');
            if (row) row.hidden = false;
            return;
        }
        const nrcCell = event.target.closest('[data-inline-nrc]');
        if (nrcCell) {
            event.preventDefault();
            event.stopPropagation();
            startNrcEditor(nrcCell);
            return;
        }

        const dateCell = event.target.closest('[data-inline-date]');
        if (dateCell) {
            event.preventDefault();
            event.stopPropagation();
            const wrapper = dateCell.closest('[data-modality-dates], [data-course-dates]');
            if (wrapper) startDateEditor(dateCell, wrapper);
            return;
        }

        // Los campos dentro de los encabezados no deben desplegar el acordeón al editar.
        if (event.target.closest('[data-inline-text], [data-inline-cycle]')) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        const groupDeleteToggle = event.target.closest('[data-toggle-group-delete]');
        if (groupDeleteToggle) {
            event.preventDefault();
            event.stopPropagation();
            const group = groupDeleteToggle.closest('[data-tree-group-id]');
            const confirm = group?.querySelector('[data-group-delete-confirm]');
            if (confirm) confirm.hidden = !confirm.hidden;
            return;
        }

        const courseDeleteToggle = event.target.closest('[data-toggle-course-delete]');
        if (courseDeleteToggle) {
            event.preventDefault();
            event.stopPropagation();
            const course = courseDeleteToggle.closest('[data-tree-course-item]');
            const confirm = course?.querySelector('[data-course-delete-confirm]');
            if (confirm) confirm.hidden = !confirm.hidden;
            return;
        }

        const cancel = event.target.closest('[data-inline-cancel]');
        if (cancel) {
            cancel.closest('.tree-delete-confirm')?.setAttribute('hidden', '');
            return;
        }

        const addModality = event.target.closest('[data-open-inline-course-entry]');
        if (addModality) {
            const button = addModality;
            button.disabled = true;
            try {
                const payload = await post(button.dataset.storeUrl);
                const course = button.closest('[data-tree-course-item]');
                const groupId = button.dataset.groupId || course?.dataset.groupId;
                const assignmentId = course?.dataset.assignmentId;
                await refreshGroup(groupId, assignmentId);
                showFeedback(payload.message || 'Componente agregado.');
            } catch (error) {
                button.disabled = false;
                showFeedback(error.message, true);
            }
            return;
        }

        const toggleModality = event.target.closest('[data-toggle-modality]');
        if (toggleModality) {
            const button = toggleModality;
            const active = button.dataset.active === '1';
            button.disabled = true;
            try {
                const payload = await patch(button.dataset.modalityUrl, {active: active ? 0 : 1});
                const course = button.closest('[data-tree-course-item]');
                const groupId = course?.dataset.groupId;
                const assignmentId = course?.dataset.assignmentId;
                await refreshGroup(groupId, assignmentId);
                showFeedback(payload.message || 'Estado del componente actualizado.', Boolean(payload.warning));
            } catch (error) {
                button.disabled = false;
                showFeedback(error.message, true);
            }
            return;
        }
    });

    document.addEventListener('submit', async event => {
        const groupForm = event.target.closest('[data-tree-group-form]');
        if (groupForm) {
            event.preventDefault();
            const submit = groupForm.querySelector('[data-group-submit]');
            const errorBox = groupForm.querySelector('[data-group-form-error]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Guardando…';
            }
            if (errorBox) errorBox.hidden = true;

            try {
                const payload = await post(groupForm.action, new FormData(groupForm));
                const destination = new URL(window.location.href);
                destination.searchParams.delete('q');
                destination.searchParams.set('open_group', String(payload.group.id));
                window.location.assign(destination.toString());
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = error.message;
                    errorBox.hidden = false;
                } else {
                    showFeedback(error.message, true);
                }
            } finally {
                if (submit) {
                    submit.disabled = false;
                    submit.innerHTML = '<span>✓</span> Guardar grupo';
                }
            }
            return;
        }

        const courseForm = event.target.closest('[data-tree-course-form]');
        if (courseForm) {
            event.preventDefault();
            const submit = courseForm.querySelector('[data-course-submit]');
            const errorBox = courseForm.querySelector('[data-course-form-error]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Agregando…';
            }
            if (errorBox) errorBox.hidden = true;

            try {
                const payload = await post(courseForm.action, new FormData(courseForm));
                const groupId = courseForm.dataset.groupId;
                courseForm.reset();
                courseForm.hidden = true;
                const addRow = courseForm.closest('[data-tree-group-id]')?.querySelector('[data-course-add-row]');
                if (addRow) addRow.hidden = false;
                await refreshGroup(groupId, payload.assignment_id);
                updateCourseDeleteGuards(groupNodeById(groupId), payload.assignments_count);
                showFeedback(payload.message || 'Curso agregado.');
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = error.message;
                    errorBox.hidden = false;
                }
            } finally {
                releaseAjaxForm(courseForm, submit);
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Guardar curso';
                }
            }
            return;
        }

        const groupDelete = event.target.closest('[data-tree-delete-group]');
        if (groupDelete) {
            event.preventDefault();
            const submit = groupDelete.querySelector('[type="submit"]');
            const shell = groupDelete.closest('[data-group-shell]');
            if (submit) submit.disabled = true;
            try {
                const payload = await post(groupDelete.action, new FormData(groupDelete));
                shell?.remove();
                const remaining = Number(payload.remaining_groups || 0);
                const total = root.querySelector('[data-tree-group-total]');
                if (total) total.textContent = remaining + (remaining === 1 ? ' grupo' : ' grupos');
                if (!root.querySelector('[data-group-shell]')) {
                    root.querySelector('.workload-tree-root').innerHTML = '<div class="tree-empty-child"><strong>No hay grupos todavía</strong><span>Usa la fila ＋ Agregar grupo que aparece debajo.</span></div>';
                }
                root.querySelectorAll('[data-toggle-group-delete]').forEach(button => {
                    button.disabled = remaining <= 1;
                    button.title = remaining <= 1 ? 'El ciclo debe conservar al menos un grupo' : 'Eliminar grupo';
                });
                showFeedback(payload.message || 'Grupo eliminado.');
            } catch (error) {
                releaseAjaxForm(groupDelete, submit);
                if (submit) submit.disabled = false;
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
            const groupId = groupNode?.dataset.treeGroupId || course?.dataset.groupId;
            if (submit) submit.disabled = true;
            try {
                const payload = await post(courseDelete.action, new FormData(courseDelete));
                course?.remove();
                const actualGroup = groupNodeById(groupId);
                updateCourseDeleteGuards(actualGroup, payload.assignments_count);
                showFeedback(payload.message || 'Curso retirado del grupo.');
            } catch (error) {
                releaseAjaxForm(courseDelete, submit);
                if (submit) submit.disabled = false;
                showFeedback(error.message, true);
            }
        }
    });

    const startTextEditor = (node, config) => {
        if (node.querySelector('input, select')) return;

        const options = Array.isArray(config.options) ? config.options : null;
        const rawOriginal = String(config.value ?? '').trim();
        const original = options
            ? (options.find(option => option.toLowerCase() === rawOriginal.toLowerCase()
                || (option + ' semestre').toLowerCase() === rawOriginal.toLowerCase()) || '')
            : rawOriginal;
        const originalDisplay = node.textContent.trim();
        const inputType = config.inputType === 'time' ? 'time' : 'text';
        const input = document.createElement(options ? 'select' : 'input');
        input.className = 'tree-inline-editor';
        input.setAttribute('aria-label', config.label || 'Editar texto');

        if (options) {
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Seleccionar semestre';
            placeholder.disabled = true;
            placeholder.selected = !original;
            input.appendChild(placeholder);
            options.forEach(option => {
                const item = document.createElement('option');
                item.value = option;
                item.textContent = option;
                input.appendChild(item);
            });
            input.value = original;
        } else {
            input.type = inputType;
            if (inputType === 'text') input.maxLength = config.maxLength || 255;
            input.value = original;
        }

        node.replaceChildren(input);
        input.focus();
        if (!options && inputType === 'text') input.select?.();

        let finished = false;
        const restore = () => {
            if (finished) return;
            finished = true;
            node.textContent = originalDisplay || (config.emptyLabel && !original ? config.emptyLabel : original);
        };
        const save = async () => {
            if (finished) return;
            const value = input.value.trim();
            if (value === original) {
                restore();
                return;
            }
            if (!value && !config.allowEmpty) {
                restore();
                showFeedback(options ? 'Selecciona un semestre.' : (inputType === 'time' ? 'Selecciona una hora.' : 'El valor no puede quedar vacío.'), true);
                return;
            }
            finished = true;
            node.classList.add('tree-inline-saving');
            try {
                const payload = await patch(config.url, {[config.field]: value});
                const saved = config.responseField ? payload[config.responseField] : (config.field === 'name' ? payload.name : payload.code);
                node.dataset.editValue = saved || '';
                node.textContent = saved || config.emptyLabel || '';
                config.onSaved?.(saved, payload);
                showFeedback(payload.message || 'Dato actualizado.');
            } catch (error) {
                node.textContent = originalDisplay || original || config.emptyLabel || '';
                showFeedback(error.message, true);
            } finally {
                node.classList.remove('tree-inline-saving');
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
        if (options || inputType === 'time') input.addEventListener('change', save, {once: true});
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

        let finished = false;
        const restore = () => {
            if (finished) return;
            finished = true;
            cell.textContent = original || 'NRC pendiente';
        };
        const save = async () => {
            if (finished) return;
            const value = input.value.trim();
            if (value === original) {
                restore();
                return;
            }
            finished = true;
            try {
                const payload = await patch(cell.dataset.nrcUrl, {nrc: value});
                cell.dataset.nrc = payload.nrc || '';
                cell.textContent = payload.nrc || 'NRC pendiente';
                showFeedback(payload.message || 'NRC actualizado.');
            } catch (error) {
                cell.textContent = original || 'NRC pendiente';
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
        const startField = wrapper.dataset.startField || 'start_date';
        const endField = wrapper.dataset.endField || 'end_date';
        const isStart = field === startField;
        const key = isStart ? 'startDate' : 'endDate';
        const original = wrapper.dataset[key] || '';
        const emptyLabel = cell.dataset.emptyLabel || 'Sin fecha';
        const input = document.createElement('input');

        input.type = 'date';
        input.className = 'tree-inline-editor';
        input.value = original;
        input.setAttribute('aria-label', isStart ? 'Fecha de inicio' : 'Fecha de fin');
        cell.replaceChildren(input);
        input.focus();
        input.showPicker?.();

        let finished = false;
        const restore = () => {
            if (finished) return;
            finished = true;
            cell.textContent = original ? displayDate(original) : emptyLabel;
        };

        const save = async () => {
            if (finished) return;
            const value = input.value;
            if (value === original) {
                restore();
                return;
            }

            const values = {
                [startField]: isStart ? value : (wrapper.dataset.startDate || ''),
                [endField]: isStart ? (wrapper.dataset.endDate || '') : value
            };

            finished = true;
            cell.classList.add('tree-inline-saving');
            try {
                const payload = await patch(wrapper.dataset.datesUrl, values);
                const savedStart = payload[startField] || '';
                const savedEnd = payload[endField] || '';
                wrapper.dataset.startDate = savedStart;
                wrapper.dataset.endDate = savedEnd;

                wrapper.querySelectorAll('[data-inline-date]').forEach(dateNode => {
                    const dateValue = payload[dateNode.dataset.inlineDate] || '';
                    const fallback = dateNode.dataset.emptyLabel || 'Sin fecha';
                    dateNode.textContent = dateValue ? displayDate(dateValue) : fallback;
                    dateNode.classList.toggle('is-empty', !dateValue);
                });

                showFeedback(payload.message || 'Fechas actualizadas.');
            } catch (error) {
                cell.textContent = original ? displayDate(original) : emptyLabel;
                showFeedback(error.message, true);
            } finally {
                cell.classList.remove('tree-inline-saving');
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

    const editTextNode = editable => {
        if (!editable) return;
        const group = editable.closest('[data-tree-group-id]');
        const course = editable.closest('[data-tree-course-item]');
        const field = editable.dataset.editField;
        const kind = editable.dataset.editKind || '';
        startTextEditor(editable, {
            value: editable.dataset.editValue || '',
            options: editable.dataset.editOptions ? editable.dataset.editOptions.split('|').filter(Boolean) : null,
            inputType: editable.dataset.editInputType || 'text',
            url: editable.dataset.editUrl,
            field,
            label: editable.dataset.editLabel,
            responseField: editable.dataset.editResponse || '',
            maxLength: field === 'code' ? 80 : 255,
            emptyLabel: editable.dataset.editEmptyLabel || (['code', 'career', 'semester'].includes(field) ? 'Falta' : ''),
            allowEmpty: kind === 'group-code' || kind === 'course-code' || kind === 'group-time',
            onSaved: saved => {
                if (kind === 'group' || kind === 'group-code') {
                    if (group) {
                        if (field === 'name') {
                            group.dataset.groupName = saved;
                            group.dataset.groupLabel = 'Grupo ' + saved;
                        } else {
                            group.dataset.groupCode = saved || '';
                        }
                    }
                } else if (kind === 'group-career' || kind === 'group-semester') {
                    if (group) {
                        const isCareer = kind === 'group-career';
                        const key = isCareer ? 'groupCareer' : 'groupSemester';
                        const selector = isCareer ? '[data-group-career-display]' : '[data-group-semester-display]';
                        group.dataset[key] = saved || '';
                        group.querySelector(selector)?.setAttribute('data-edit-value', saved || '');
                    }
                } else if (kind === 'group-time') {
                    if (group) {
                        const key = field === 'workload_start_time' ? 'groupStartTime' : 'groupEndTime';
                        group.dataset[key] = saved || '';
                    }
                } else if (kind === 'cycle-career' || kind === 'cycle-semester') {
                    root.querySelectorAll(kind === 'cycle-career' ? '[data-cycle-career-display]' : '[data-cycle-semester-display]').forEach(node => {
                        node.textContent = saved || (kind === 'cycle-career' ? 'Pendiente' : '—');
                    });
                } else if (course) {
                    course.dataset[field === 'name' ? 'courseName' : 'courseCode'] = saved || '';
                    if (kind === 'course-name') {
                        const title = course.querySelector('.tree-course-delete-confirm p strong');
                        if (title) title.textContent = '¿Retirar ' + saved + ' de este grupo? Se retirarán sus modalidades y horarios del grupo. El historial curricular se conserva.';
                    }
                }
            }
        });
    };

    document.addEventListener('click', event => {
        const quickEditable = event.target.closest('[data-edit-on-click]');
        if (quickEditable) {
            event.preventDefault();
            event.stopPropagation();
            editTextNode(quickEditable);
        }
    });

    document.addEventListener('dblclick', event => {
        const cycleEditable = event.target.closest('[data-inline-cycle]');
        if (cycleEditable) {
            event.preventDefault();
            event.stopPropagation();
            editTextNode(cycleEditable);
            return;
        }
        const editable = event.target.closest('[data-inline-text]');
        if (editable) {
            event.preventDefault();
            event.stopPropagation();
            editTextNode(editable);
            return;
        }


    });
})();
</script>
@endpush