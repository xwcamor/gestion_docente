@extends('layouts.app')
@section('title', $cycle->academic_year.' · Ciclo '.$cycle->cycle_code.' · Árbol de carga')
@section('content')
<div class="workload-tree-page">
    <div class="breadcrumbs">
        <a href="{{ route('cycles.index') }}">Cargas laborales</a>
        <x-icon name="chevron-right" size="12" />
        <span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span>
    </div>

    <section class="workload-tree-hero">
        <div class="workload-tree-hero-copy">
            <p class="eyebrow"><x-icon name="git-branch" size="13" /> Explorador de carga laboral</p>
            <h1>{{ $cycle->academic_year }} <span>· Ciclo {{ $cycle->cycle_code }}</span></h1>
            <div class="workload-tree-context">
                <span><x-icon name="graduation-cap" size="14" /> {{ $cycle->career ?: 'Carrera pendiente' }}</span>
                <span>Semestre {{ $cycle->semester ?: '—' }}</span>
                <span><x-icon name="calendar-range" size="14" /> {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="workload-tree-hero-actions">
            <a class="btn btn-secondary" href="{{ route('cycles.show', $cycle) }}"><x-icon name="settings" /> Datos del ciclo</a>
            <button class="btn" type="button" data-open-group-dialog><x-icon name="plus" /> Agregar grupo</button>
        </div>
    </section>

    <section class="card workload-tree-browser">
        <div class="card-head workload-tree-browser-head">
            <div>
                <p class="eyebrow">Estructura académica</p>
                <h2>Grupos y cursos</h2>
                <p class="card-description">Administra grupos, cursos, modalidades, NRC, fechas y horarios desde aquí. Doble clic en una fecha o NRC para editarlo.</p>
            </div>
            <span class="workload-tree-total">{{ $groups->count() }} grupos</span>
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

        <div class="workload-tree-root">
            @include('cycles.tree.groups', ['groups' => $groups, 'cycle' => $cycle])
        </div>
    </section>

    <dialog class="ui-dialog tree-dialog" id="tree-group-dialog" aria-labelledby="tree-group-dialog-title">
        <div class="dialog-head">
            <div><h2 id="tree-group-dialog-title">Crear grupo</h2><p>El grupo se agregará directamente a este ciclo.</p></div>
            <button class="dialog-close" type="button" data-tree-dialog-close aria-label="Cerrar"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="{{ route('groups.store', $cycle) }}">
            @csrf
            <input type="hidden" name="return_to_tree" value="1">
            <div class="dialog-body">
                <label class="tree-form-label">Nombre del grupo
                    <input class="field" name="name" maxlength="100" required placeholder="Ej. I01">
                </label>
            </div>
            <div class="dialog-actions">
                <button class="btn btn-secondary" type="button" data-tree-dialog-close>Cancelar</button>
                <button class="btn" type="submit"><x-icon name="check" /> Crear grupo</button>
            </div>
        </form>
    </dialog>

    <dialog class="ui-dialog tree-dialog tree-course-dialog" id="tree-course-dialog" aria-labelledby="tree-course-dialog-title">
        <div class="dialog-head">
            <div><h2 id="tree-course-dialog-title">Agregar curso</h2><p id="tree-course-dialog-subtitle">Registra el curso y sus componentes sin salir del árbol.</p></div>
            <button class="dialog-close" type="button" data-tree-dialog-close aria-label="Cerrar"><x-icon name="x" /></button>
        </div>
        <form id="tree-course-form" method="POST" action="">
            @csrf
            <div class="dialog-body">
                <div class="tree-course-error" id="tree-course-error" hidden role="alert"></div>
                <div class="tree-form-grid">
                    <label class="tree-form-label tree-form-wide">Código base institucional
                        <input class="field mono" name="course_code" id="tree-course-code" maxlength="80" required placeholder="202620-PIAD-224">
                        <small>Escribe el código sin TEC/TAL ni NRC.</small>
                    </label>
                    <label class="tree-form-label tree-form-wide">Nombre del curso
                        <input class="field" name="course_name" id="tree-course-name" maxlength="255" required placeholder="Ej. Algoritmos y programación">
                    </label>
                </div>

                <fieldset class="tree-component-picker">
                    <legend>¿Qué componentes tiene este curso?</legend>
                    <label><input type="checkbox" name="components[]" value="TEC" data-tree-component-check> <span class="tree-component-choice is-tec">TEC</span> Teoría</label>
                    <label><input type="checkbox" name="components[]" value="TALL" data-tree-component-check> <span class="tree-component-choice is-tall">TAL</span> Taller / práctica</label>
                </fieldset>

                <div class="tree-component-config" data-tree-component-config="TEC" hidden>
                    <div class="tree-component-config-head"><span class="tree-component-choice is-tec">TEC</span><strong>Teoría</strong></div>
                    <div class="tree-form-grid">
                        <label class="tree-form-label">NRC
                            <input class="field" name="tec_nrc" inputmode="numeric" maxlength="12" placeholder="Ej. 11523">
                        </label>
                        <label class="tree-form-label">Fecha de inicio
                            <input class="field" type="date" name="tec_start_date">
                        </label>
                        <label class="tree-form-label">Fecha de fin
                            <input class="field" type="date" name="tec_end_date">
                        </label>
                    </div>
                    <details class="tree-schedule-optional">
                        <summary>Agregar horario de teoría (opcional)</summary>
                        <div class="tree-days-picker">
                            @foreach([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'] as $day => $label)
                                <label><input type="checkbox" name="tec_days[]" value="{{ $day }}"> {{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="tree-form-grid tree-form-grid-three">
                            <label class="tree-form-label">Desde<input class="field" type="time" name="tec_start_time"></label>
                            <label class="tree-form-label">Hasta<input class="field" type="time" name="tec_end_time"></label>
                            <label class="tree-form-label">Aula<input class="field" name="tec_classroom" maxlength="100" placeholder="Opcional"></label>
                        </div>
                    </details>
                </div>

                <div class="tree-component-config" data-tree-component-config="TALL" hidden>
                    <div class="tree-component-config-head"><span class="tree-component-choice is-tall">TAL</span><strong>Taller / práctica</strong></div>
                    <div class="tree-form-grid">
                        <label class="tree-form-label">NRC
                            <input class="field" name="tall_nrc" inputmode="numeric" maxlength="12" placeholder="Ej. 11524">
                        </label>
                        <label class="tree-form-label">Fecha de inicio
                            <input class="field" type="date" name="tall_start_date">
                        </label>
                        <label class="tree-form-label">Fecha de fin
                            <input class="field" type="date" name="tall_end_date">
                        </label>
                    </div>
                    <details class="tree-schedule-optional">
                        <summary>Agregar horario de taller (opcional)</summary>
                        <div class="tree-days-picker">
                            @foreach([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'] as $day => $label)
                                <label><input type="checkbox" name="tall_days[]" value="{{ $day }}"> {{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="tree-form-grid tree-form-grid-three">
                            <label class="tree-form-label">Desde<input class="field" type="time" name="tall_start_time"></label>
                            <label class="tree-form-label">Hasta<input class="field" type="time" name="tall_end_time"></label>
                            <label class="tree-form-label">Aula<input class="field" name="tall_classroom" maxlength="100" placeholder="Opcional"></label>
                        </div>
                    </details>
                </div>
                <p class="tree-dialog-note">Puedes registrar TEC, TAL o ambos. Las fechas y NRC son independientes para cada componente. Las fechas también se pueden editar después con doble clic en el árbol.</p>
            </div>
            <div class="dialog-actions">
                <button class="btn btn-secondary" type="button" data-tree-dialog-close>Cancelar</button>
                <button class="btn" type="submit" id="tree-course-submit"><x-icon name="check" /> Guardar curso</button>
            </div>
        </form>
    </dialog>

</div>
@endsection

@push('styles')
<style>

.tree-dialog{width:min(640px,calc(100vw - 24px));max-width:640px;max-height:min(88vh,820px);padding:0;border:1px solid var(--line);border-radius:15px;background:var(--surface);color:var(--ink);box-shadow:0 24px 80px rgba(15,23,42,.24)}
.tree-dialog::backdrop{background:rgba(12,20,28,.48);backdrop-filter:blur(2px)}
.tree-dialog .dialog-head{position:sticky;top:0;z-index:2;background:var(--surface)}
.tree-dialog .dialog-head h2{font-size:19px;font-weight:780;letter-spacing:-.025em}
.tree-dialog .dialog-head p{margin:4px 0 0;color:var(--muted);font-size:11px}
.tree-dialog .dialog-body{display:grid;gap:14px;padding:16px 19px}
.tree-dialog .dialog-actions{display:flex;justify-content:flex-end;gap:8px;padding:13px 19px;border-top:1px solid var(--line);background:var(--surface)}
.tree-form-label{display:grid;gap:6px;color:var(--ink-soft);font-size:11px;font-weight:700}
.tree-form-label .field{width:100%;min-width:0}
.tree-form-label small{color:var(--muted);font-size:10px;font-weight:450}
.tree-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.tree-form-wide{grid-column:1/-1}
.tree-component-picker{display:flex;align-items:center;gap:10px 18px;flex-wrap:wrap;margin:0;padding:12px;border:1px solid var(--line);border-radius:10px;background:var(--surface-soft)}
.tree-component-picker legend{padding:0 5px;color:var(--muted);font-size:10px;font-weight:750}
.tree-component-picker label{display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--ink);font-size:11px;font-weight:650;cursor:pointer}
.tree-component-picker input,.tree-days-picker input{accent-color:var(--accent)}
.tree-component-choice{display:inline-grid;place-items:center;min-width:37px;height:25px;padding:0 7px;border-radius:6px;font-size:9px;font-weight:850}
.tree-component-choice.is-tec{background:var(--info-bg);color:var(--info)}
.tree-component-choice.is-tall{background:var(--accent-soft);color:var(--accent)}
.tree-component-config{display:grid;gap:11px;padding:13px;border:1px solid var(--line);border-radius:10px;background:var(--surface)}
.tree-component-config-head{display:flex;align-items:center;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--line);color:var(--ink);font-size:12px}
.tree-schedule-optional{padding-top:10px;border-top:1px solid var(--line)}
.tree-schedule-optional summary{color:var(--accent);font-size:10px;font-weight:750;cursor:pointer}
.tree-days-picker{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin:10px 0}
.tree-days-picker label{display:inline-flex;align-items:center;gap:4px;margin:0;padding:6px 8px;border:1px solid var(--line);border-radius:7px;background:var(--surface-soft);color:var(--ink-soft);font-size:10px;cursor:pointer}
.tree-form-grid-three{grid-template-columns:repeat(3,minmax(0,1fr))}
.tree-dialog-note{margin:0;color:var(--muted);font-size:10px;line-height:1.5}
.tree-course-error{padding:10px 12px;border:1px solid var(--danger);border-radius:8px;background:var(--danger-bg);color:var(--danger);font-size:11px;line-height:1.5}
.tree-inline-date,.tree-inline-nrc{display:inline-flex;align-items:center;min-height:22px;padding:1px 4px;border-radius:5px;border-bottom:1px dashed var(--line-strong);color:var(--ink-soft);cursor:text}
.tree-inline-date:hover,.tree-inline-nrc:hover{background:var(--accent-soft);border-bottom-color:var(--accent);color:var(--accent)}
.tree-inline-editor{width:auto;min-width:112px;max-width:160px;min-height:30px;padding:4px 6px;border:1px solid var(--accent);border-radius:6px;background:var(--surface);color:var(--ink);font:inherit;font-size:11px;outline:none}
.tree-inline-hint{margin-left:4px;color:var(--faint);font-size:9px}
.tree-inline-saving{opacity:.55;pointer-events:none}
@media(max-width:600px){
 .tree-dialog{width:calc(100vw - 16px);max-height:92dvh;border-radius:12px}
 .tree-dialog .dialog-body{padding:13px}
 .tree-dialog .dialog-actions{padding:11px 13px}
 .tree-form-grid{grid-template-columns:1fr 1fr}
 .tree-form-wide{grid-column:1/-1}
 .tree-form-grid-three{grid-template-columns:1fr 1fr}
 .tree-component-picker{gap:9px}
 .tree-dialog .dialog-actions .btn{flex:1}
}
.workload-tree-summary{display:flex;align-items:center;gap:10px;min-width:0;list-style:none;cursor:pointer}
.workload-tree-summary::-webkit-details-marker{display:none}
.workload-tree-summary:focus-visible{outline:2px solid var(--accent);outline-offset:-2px;border-radius:7px}
.workload-tree-children{position:relative;margin-left:28px;padding:6px 12px 12px 18px;border-left:1px solid var(--line-strong)}
.workload-tree-group{margin:7px 0;border:1px solid var(--line);border-radius:9px;background:var(--surface);overflow:hidden}
.workload-group-summary{min-height:58px;padding:10px 12px;background:var(--surface)}
.workload-group-children{margin-left:18px;padding:9px 10px 11px 14px}
.workload-tree-course{margin:7px 0;border:1px solid var(--line);border-radius:8px;background:var(--surface);overflow:hidden}
.workload-course-summary{min-height:51px;padding:9px 11px}
.workload-course-children{margin-left:18px;padding:9px 10px 12px 14px}
.tree-node-icon{display:grid;place-items:center;flex:0 0 32px;width:32px;height:32px;border-radius:9px}
.tree-group-icon{background:var(--info-bg);color:var(--info)}
.tree-course-icon{background:var(--accent-soft);color:var(--accent)}
.tree-node-copy{display:grid;gap:3px;min-width:0;flex:1}
.tree-node-copy strong{color:var(--ink);font-size:13px;font-weight:750;line-height:1.35;overflow-wrap:anywhere}
.tree-node-copy small{color:var(--muted);font-size:10px;line-height:1.45;overflow-wrap:anywhere}
.tree-branch-mark{position:relative;flex:0 0 11px;width:11px;height:1px;background:var(--line-strong)}
.workload-tree-group>.workload-tree-summary .tree-branch-mark:before,
.workload-tree-course>.workload-tree-summary .tree-branch-mark:before,
.workload-tree-modality>.tree-branch-mark:before{content:"";position:absolute;left:0;top:0;width:1px;height:15px;background:var(--line-strong)}
.tree-chevron{display:grid;place-items:center;flex:none;color:var(--muted);transition:transform .14s ease}
.workload-tree-group[open]>.workload-tree-summary .tree-chevron,
.workload-tree-course[open]>.workload-tree-summary .tree-chevron{transform:rotate(90deg)}
.tree-course-status{display:inline-flex;align-items:center;gap:4px;flex:none;padding:5px 8px;border-radius:6px;font-size:9px;font-weight:750;white-space:nowrap}
.tree-course-status.is-ready{background:var(--success-bg);color:var(--success)}
.tree-course-status.is-pending{background:var(--warning-bg);color:var(--warning)}
.workload-tree-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:0 0 9px}
.workload-tree-actions form{margin:0}
.workload-tree-actions .btn{min-height:32px;padding:6px 9px;font-size:10px}
.tree-curriculum-note{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin:0 0 8px;padding:8px 10px;border:1px solid var(--line);border-radius:7px;background:var(--surface-soft);color:var(--success);font-size:10px}
.tree-curriculum-note strong{color:var(--ink)}
.tree-curriculum-note span{color:var(--muted)}
.workload-tree-modality{position:relative;display:flex;align-items:center;gap:10px;min-width:0;padding:11px 3px;border-top:1px solid var(--line)}
.tree-modality-tag{display:grid;place-items:center;flex:0 0 42px;height:30px;border-radius:7px;font-size:10px;font-weight:850}
.tree-modality-tag.is-tec{background:var(--info-bg);color:var(--info)}
.tree-modality-tag.is-tall{background:var(--accent-soft);color:var(--accent)}
.tree-modality-copy{display:grid;gap:4px;flex:1;min-width:0}
.tree-modality-copy strong{color:var(--ink);font-size:11px;font-weight:750;line-height:1.4;overflow-wrap:anywhere}
.tree-modality-copy small{color:var(--muted);font-size:10px;line-height:1.45;overflow-wrap:anywhere}
.tree-open-modality{flex:none}
.tree-empty-child{padding:11px 12px;color:var(--muted);font-size:11px;line-height:1.5}
.tree-empty-child a{display:inline-flex;align-items:center;gap:4px;margin-left:5px;color:var(--accent);font-weight:700}
.tree-loading-state,.tree-load-error{padding:12px 14px;color:var(--muted);font-size:11px;line-height:1.5}
.tree-load-error{color:var(--danger);background:var(--danger-bg);border-radius:8px}
html[data-theme="dark"] .tree-course-status.is-ready{color:#b9e9ca}
html[data-theme="dark"] .tree-course-status.is-pending{color:#f1d49a}
@media(max-width:700px){
 .workload-tree-children{margin-left:14px;padding-left:9px;padding-right:7px}
 .workload-group-children,.workload-course-children{margin-left:8px;padding-left:8px}
 .workload-tree-actions .btn{flex:1 1 auto}
 .workload-tree-add-course{position:absolute;top:8px;right:8px;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;min-height:36px;padding:8px;margin:0}
 .workload-tree-add-course span{display:none}
 .workload-tree-group-shell .workload-group-summary{padding-right:54px}
 .tree-course-components{gap:3px}
 .tree-component-chip{padding:4px 5px;font-size:8px}
 .tree-add-modality{width:100%;margin:4px 0 0}
 .workload-tree-modality.is-missing{align-items:stretch}
 .workload-tree-group-actions .btn{flex:1 1 auto}
 .workload-tree-modality{align-items:flex-start;flex-wrap:wrap}
 .tree-modality-copy{flex:1 1 calc(100% - 100px)}
 .tree-open-modality{margin-left:52px}
}
@media(max-width:420px){
 .workload-tree-summary{gap:7px}
 .tree-node-icon{width:29px;height:29px;flex-basis:29px}
 .tree-node-copy strong{font-size:11px}
 .tree-node-copy small{font-size:9px}
 .tree-course-status{font-size:8px;padding:4px 5px}
 .tree-course-components{display:flex}
 .tree-course-status{display:none}
 .workload-tree-modality{gap:7px}
 .tree-open-modality{margin-left:49px}
}
.workload-tree-page{width:100%;max-width:none}
.workload-tree-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:0 0 18px;padding:clamp(20px,2.4vw,34px);border:1px solid color-mix(in srgb,var(--accent) 20%,var(--line));border-radius:16px;background:linear-gradient(115deg,var(--accent-soft),var(--surface) 74%);box-shadow:0 3px 18px rgba(26,48,36,.035)}
.workload-tree-hero-copy{min-width:0}
.workload-tree-hero .eyebrow{margin-bottom:10px;color:var(--accent)}
.workload-tree-hero h1{margin:0;color:var(--ink);font-size:clamp(30px,3.6vw,48px);font-weight:820;letter-spacing:-.055em;line-height:1.03}
.workload-tree-hero h1 span{color:var(--muted);font-size:.68em;font-weight:650;letter-spacing:-.035em}
.workload-tree-context{display:flex;align-items:center;gap:8px 18px;flex-wrap:wrap;margin-top:14px;color:var(--muted);font-size:11px}
.workload-tree-context span{display:inline-flex;align-items:center;gap:6px}
.workload-tree-hero-actions{display:flex;gap:8px;flex:none;flex-wrap:wrap}
.workload-tree-hero-actions .btn{min-height:41px;padding:10px 14px}
.workload-tree-browser{padding:clamp(15px,1.8vw,24px)}
.workload-tree-browser-head{align-items:flex-end;margin-bottom:13px}
.workload-tree-browser-head .eyebrow{margin-bottom:5px}
.workload-tree-browser-head h2{font-size:21px;font-weight:780;letter-spacing:-.03em}
.workload-tree-total{flex:none;padding:6px 9px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:10px;font-weight:700}
.workload-tree-search{display:flex;gap:8px;margin-bottom:14px}
.workload-tree-search-field{display:flex;align-items:center;gap:9px;flex:1;min-width:0;height:42px;margin:0;padding:0 12px;border:1px solid var(--line-strong);border-radius:9px;background:var(--surface);color:var(--muted)}
.workload-tree-search-field:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent)}
.workload-tree-search-field input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--ink);font-size:12px}
.workload-tree-search-field input:focus{box-shadow:none}
.workload-tree-search-field input::placeholder{color:var(--faint)}
.workload-tree-search .btn{min-height:42px}
.workload-tree-root{display:grid;gap:8px}
.workload-tree-root>.workload-tree-group-shell{margin:0}
.workload-tree-root>.tree-empty-child{padding:30px 16px;border:1px dashed var(--line-strong);border-radius:10px;text-align:center}
.workload-tree-group,.workload-tree-course{border-color:var(--line);background:var(--surface)}
.workload-tree-summary{transition:background .14s ease}
.workload-tree-summary:hover{background:var(--accent-soft)}
.workload-tree-children{border-color:var(--line-strong)}
.tree-node-copy strong{font-size:13px}
.tree-node-copy small{font-size:10px}
.tree-course-status{font-size:10px;padding:5px 8px}
.workload-tree-actions .btn{min-height:34px;padding:7px 10px;font-size:10px}
.workload-tree-group-shell{position:relative;border:1px solid var(--line);border-radius:10px;background:var(--surface);overflow:hidden}
.workload-tree-group-shell .workload-tree-group{margin:0;border:0;border-radius:0;overflow:visible}
.workload-tree-group-shell .workload-group-summary{padding-right:148px}
.workload-tree-add-course{position:absolute;top:10px;right:11px;z-index:3;min-height:34px;padding:7px 10px;border:1px solid color-mix(in srgb,var(--accent) 28%,var(--line));border-radius:8px;background:var(--accent-soft);color:var(--accent);font-size:10px;font-weight:750;white-space:nowrap}
.workload-tree-add-course:hover{border-color:var(--accent);background:var(--accent);color:#fff;text-decoration:none}
.tree-course-components{display:flex;align-items:center;gap:5px;flex:none}
.tree-component-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 7px;border-radius:6px;font-size:9px;font-weight:800;letter-spacing:.02em}
.tree-component-chip.is-present{background:var(--success-bg);color:var(--success)}
.tree-component-chip.is-missing{background:var(--surface-soft);color:var(--muted);border:1px dashed var(--line-strong)}
.workload-tree-modality.is-missing{background:var(--surface-soft);border:1px dashed var(--line-strong);border-radius:9px;margin:6px 0;padding:12px}
.workload-tree-modality.is-missing .tree-modality-tag{opacity:.7}
.workload-tree-modality.is-missing .tree-modality-copy strong{color:var(--muted)}
.tree-add-modality{flex:none;min-height:34px;padding:7px 10px;font-size:10px}
.workload-tree-group-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:0 0 9px}
.workload-tree-group-actions .btn{min-height:32px;padding:6px 9px;font-size:10px}

.workload-tree-modality{padding:12px 4px}
.tree-modality-tag{height:32px;font-size:11px}
.tree-modality-copy strong{font-size:12px}
.tree-modality-copy small{font-size:10px}
.tree-open-modality{min-height:34px}
.tree-empty-child{font-size:11px}
.tree-loading-state,.tree-load-error{padding:12px 14px;color:var(--muted);font-size:11px;line-height:1.5}
.tree-load-error{color:var(--danger);background:var(--danger-bg);border-radius:8px}
html[data-theme="dark"] .workload-tree-hero{background:linear-gradient(115deg,color-mix(in srgb,var(--accent) 18%,#15232d),#141f28 76%);border-color:color-mix(in srgb,var(--accent) 30%,var(--line))}
@media(max-width:980px){.workload-tree-hero{align-items:flex-start;flex-direction:column}.workload-tree-hero-actions{width:100%}}
@media(max-width:600px){
 .workload-tree-page .breadcrumbs{margin-bottom:9px}
 .workload-tree-hero{gap:16px;padding:19px 16px;border-radius:12px}
 .workload-tree-hero h1{font-size:32px}
 .workload-tree-context{display:grid;gap:7px}
 .workload-tree-hero-actions{display:grid;grid-template-columns:1fr 1fr}
 .workload-tree-hero-actions .btn{width:100%;padding:9px 8px;font-size:10px}
 .workload-tree-browser{padding:13px 10px}
 .workload-tree-browser-head{align-items:flex-start;flex-direction:column}
 .workload-tree-search{display:grid;grid-template-columns:1fr 1fr}
 .workload-tree-search-field{grid-column:1/-1}
 .workload-tree-search .btn,.workload-tree-search>a{width:100%}
}
@media(prefers-reduced-motion:reduce){.workload-tree-summary{transition:none}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('#tree-course-form input[name="_token"]')?.value
        || '';
    const groupDialog = document.getElementById('tree-group-dialog');
    const courseDialog = document.getElementById('tree-course-dialog');
    const courseForm = document.getElementById('tree-course-form');
    const courseError = document.getElementById('tree-course-error');
    const courseSubmit = document.getElementById('tree-course-submit');

    const bindTreeNodes = (scope) => {
        scope.querySelectorAll('[data-tree-load]').forEach((node) => {
            if (node.dataset.treeBound === 'true') return;
            node.dataset.treeBound = 'true';
            node.addEventListener('toggle', () => {
                if (node.open) loadTreeNode(node);
            });
        });
    };

    const loadTreeNode = async (node, force = false) => {
        if (!node || (!force && node.dataset.treeLoaded === 'true') || node.dataset.treeLoading === 'true') return;
        const content = node.querySelector('[data-tree-content]');
        if (!content) return;

        node.dataset.treeLoading = 'true';
        content.innerHTML = '<div class="tree-loading-state" role="status">Cargando información…</div>';
        const url = new URL(node.dataset.treeLoad, window.location.href);
        const search = document.querySelector('.workload-tree-search [name="q"]');
        if (search && search.value.trim()) url.searchParams.set('q', search.value.trim());

        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
            });
            if (!response.ok || response.redirected) throw new Error('La solicitud no pudo completarse.');
            content.innerHTML = await response.text();
            node.dataset.treeLoaded = 'true';
            bindTreeNodes(content);
        } catch (error) {
            content.innerHTML = '<div class="tree-load-error" role="alert">No se pudo cargar este nivel. Cierra y vuelve a abrirlo para intentar nuevamente.</div>';
            node.dataset.treeLoaded = 'false';
        } finally {
            node.dataset.treeLoading = 'false';
        }
    };

    const openDialog = dialog => {
        if (!dialog) return;
        if (typeof dialog.showModal === 'function') dialog.showModal();
        else dialog.setAttribute('open', 'open');
    };
    const closeDialog = dialog => {
        if (!dialog) return;
        if (typeof dialog.close === 'function') dialog.close();
        else dialog.removeAttribute('open');
    };

    document.querySelector('[data-open-group-dialog]')?.addEventListener('click', () => openDialog(groupDialog));
    document.querySelectorAll('[data-tree-dialog-close]').forEach(button => {
        button.addEventListener('click', () => closeDialog(button.closest('dialog')));
    });

    const componentChecks = [...document.querySelectorAll('[data-tree-component-check]')];
    const syncComponentFields = () => {
        ['TEC', 'TALL'].forEach(type => {
            const selected = componentChecks.some(input => input.value === type && input.checked);
            const panel = document.querySelector('[data-tree-component-config="' + type + '"]');
            if (panel) panel.hidden = !selected;
            panel?.querySelectorAll('input').forEach(input => input.disabled = !selected);
        });
    };
    componentChecks.forEach(input => input.addEventListener('change', syncComponentFields));

    const resetCourseForm = () => {
        courseForm?.reset();
        courseForm?.querySelectorAll('[data-tree-component-config] input').forEach(input => input.disabled = true);
        if (courseError) {
            courseError.hidden = true;
            courseError.textContent = '';
        }
        syncComponentFields();
    };

    document.querySelectorAll('[data-open-course-dialog]').forEach(button => {
        button.addEventListener('click', () => {
            resetCourseForm();
            courseForm.action = button.dataset.createUrl;
            courseForm.dataset.groupId = button.dataset.groupId || '';
            document.getElementById('tree-course-code').value = button.dataset.courseCode || '';
            document.getElementById('tree-course-name').value = button.dataset.courseName || '';
            if (button.dataset.component) {
                componentChecks.forEach(input => input.checked = input.value === button.dataset.component);
            }
            syncComponentFields();
            const title = document.getElementById('tree-course-dialog-title');
            const subtitle = document.getElementById('tree-course-dialog-subtitle');
            if (button.dataset.component) {
                title.textContent = 'Agregar modalidad ' + (button.dataset.component === 'TALL' ? 'TAL' : 'TEC');
                subtitle.textContent = 'Completa el NRC, las fechas y el horario de este componente.';
            } else {
                title.textContent = 'Agregar curso';
                subtitle.textContent = 'Registra el curso y sus componentes sin salir del árbol.';
            }
            openDialog(courseDialog);
            document.getElementById('tree-course-code')?.focus();
        });
    });

    const showCourseErrors = errors => {
        if (!courseError) return;
        const messages = Object.values(errors || {}).flat();
        courseError.textContent = messages.length ? messages.join(' ') : 'No se pudo guardar el curso. Revisa los datos e inténtalo nuevamente.';
        courseError.hidden = false;
    };

    courseForm?.addEventListener('submit', async event => {
        event.preventDefault();
        if (!courseForm.action) return;
        if (courseSubmit) {
            courseSubmit.disabled = true;
            courseSubmit.textContent = 'Guardando…';
        }
        if (courseError) {
            courseError.hidden = true;
            courseError.textContent = '';
        }

        try {
            const response = await fetch(courseForm.action, {
                method: 'POST',
                body: new FormData(courseForm),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                }
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                showCourseErrors(payload.errors || {general: [payload.message || 'No se pudo guardar el curso.']});
                return;
            }

            const groupId = courseForm.dataset.groupId;
            closeDialog(courseDialog);
            const groupNode = [...document.querySelectorAll('[data-tree-group-id]')]
                .find(node => node.dataset.treeGroupId === String(groupId));
            if (groupNode) {
                groupNode.open = true;
                groupNode.dataset.treeLoaded = 'false';
                await loadTreeNode(groupNode, true);
            }
            window.dispatchEvent(new CustomEvent('tree-course-saved', {detail: payload}));
        } catch (error) {
            showCourseErrors({general: ['No se pudo conectar con el servidor. Revisa tu conexión e inténtalo nuevamente.']});
        } finally {
            if (courseSubmit) {
                courseSubmit.disabled = false;
                courseSubmit.innerHTML = '<svg class="icon" aria-hidden="true"><use href="#icon-check"></use></svg> Guardar curso';
            }
        }
    });

    const displayDate = value => {
        if (!value) return 'Sin fecha';
        const [year, month, day] = value.split('-');
        return day + '/' + month + '/' + year;
    };

    const sendInlineUpdate = async (url, data) => {
        const body = new FormData();
        Object.entries(data).forEach(([key, value]) => body.append(key, value ?? ''));
        const response = await fetch(url, {
            method: 'PATCH',
            body,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = Object.values(payload.errors || {}).flat();
            throw new Error(errors.join(' ') || payload.message || 'No se pudo guardar el cambio.');
        }
        return payload;
    };

    document.addEventListener('dblclick', async event => {
        const dateCell = event.target.closest('[data-inline-date]');
        if (dateCell) {
            if (dateCell.querySelector('input')) return;
            const wrapper = dateCell.closest('[data-modality-dates]');
            if (!wrapper) return;

            const field = dateCell.dataset.inlineDate;
            const original = wrapper.dataset[field] || '';
            const input = document.createElement('input');
            input.type = 'date';
            input.className = 'tree-inline-editor';
            input.value = original;
            input.setAttribute('aria-label', field === 'start_date' ? 'Fecha de inicio' : 'Fecha de fin');
            dateCell.replaceChildren(input);
            input.focus();
            input.showPicker?.();

            let finished = false;
            const restore = () => {
                if (finished) return;
                finished = true;
                dateCell.textContent = original ? displayDate(original) : 'Sin fecha';
            };
            input.addEventListener('keydown', async keyEvent => {
                if (keyEvent.key === 'Escape') {
                    keyEvent.preventDefault();
                    restore();
                }
                if (keyEvent.key === 'Enter') {
                    keyEvent.preventDefault();
                    input.blur();
                }
            });
            input.addEventListener('change', async () => {
                if (finished) return;
                const next = input.value;
                const values = {
                    start_date: field === 'start_date' ? next : wrapper.dataset.startDate,
                    end_date: field === 'end_date' ? next : wrapper.dataset.endDate
                };
                wrapper.classList.add('tree-inline-saving');
                try {
                    const payload = await sendInlineUpdate(wrapper.dataset.datesUrl, values);
                    wrapper.dataset.startDate = payload.start_date || '';
                    wrapper.dataset.endDate = payload.end_date || '';
                    wrapper.querySelectorAll('[data-inline-date]').forEach(cell => {
                        const value = payload[cell.dataset.inlineDate] || '';
                        cell.textContent = value ? displayDate(value) : 'Sin fecha';
                    });
                    finished = true;
                    wrapper.title = payload.message;
                } catch (error) {
                    dateCell.textContent = original ? displayDate(original) : 'Sin fecha';
                    dateCell.title = error.message;
                } finally {
                    wrapper.classList.remove('tree-inline-saving');
                }
            });
            input.addEventListener('blur', () => {
                if (!finished) restore();
            }, {once: true});
            return;
        }

        const nrcCell = event.target.closest('[data-inline-nrc]');
        if (!nrcCell || nrcCell.querySelector('input')) return;
        const original = nrcCell.dataset.nrc || '';
        const input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.maxLength = 12;
        input.className = 'tree-inline-editor';
        input.value = original;
        input.placeholder = 'NRC';
        nrcCell.replaceChildren(input);
        input.focus();
        input.select();

        let finished = false;
        const restoreNrc = () => {
            if (finished) return;
            finished = true;
            nrcCell.textContent = original || 'NRC pendiente';
        };
        input.addEventListener('keydown', keyEvent => {
            if (keyEvent.key === 'Escape') {
                keyEvent.preventDefault();
                restoreNrc();
            }
            if (keyEvent.key === 'Enter') {
                keyEvent.preventDefault();
                input.blur();
            }
        });
        input.addEventListener('change', async () => {
            if (finished) return;
            nrcCell.classList.add('tree-inline-saving');
            try {
                const payload = await sendInlineUpdate(nrcCell.dataset.nrcUrl, {nrc: input.value.trim()});
                nrcCell.dataset.nrc = payload.nrc || '';
                nrcCell.textContent = payload.nrc || 'NRC pendiente';
                nrcCell.title = payload.message;
                finished = true;
            } catch (error) {
                nrcCell.textContent = original || 'NRC pendiente';
                nrcCell.title = error.message;
                finished = true;
            } finally {
                nrcCell.classList.remove('tree-inline-saving');
            }
        });
        input.addEventListener('blur', restoreNrc, {once: true});
    });

    bindTreeNodes(document);
})();
</script>
@endpush
