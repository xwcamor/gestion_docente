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
            <a class="btn" href="{{ route('groups.create', $cycle) }}"><x-icon name="plus" /> Agregar grupo</a>
        </div>
    </section>

    <section class="workload-tree-metrics" aria-label="Resumen del ciclo">
        <article class="workload-tree-metric workload-tree-metric-primary">
            <span class="workload-tree-metric-icon"><x-icon name="users" size="18" /></span>
            <div><small>Grupos</small><strong>{{ number_format($groupCount) }}</strong><span>Secciones del ciclo</span></div>
        </article>
        <article class="workload-tree-metric">
            <span class="workload-tree-metric-icon"><x-icon name="book-open" size="18" /></span>
            <div><small>Cursos</small><strong>{{ number_format($courseCount) }}</strong><span>Asignaciones registradas</span></div>
        </article>
        <article class="workload-tree-metric">
            <span class="workload-tree-metric-icon"><x-icon name="book-check" size="18" /></span>
            <div><small>Modalidades</small><strong>{{ number_format($modalityCount) }}</strong><span>{{ number_format($scheduledModalityCount) }} con fechas y horario</span></div>
        </article>
        <article class="workload-tree-metric">
            <span class="workload-tree-metric-icon"><x-icon name="user-round" size="18" /></span>
            <div><small>Alumnos</small><strong>{{ number_format($studentCount) }}</strong><span>Personas únicas en el ciclo</span></div>
        </article>
    </section>

    <section class="card workload-tree-browser">
        <div class="card-head workload-tree-browser-head">
            <div>
                <p class="eyebrow">Estructura académica</p>
                <h2>Grupos y cursos</h2>
                <p class="card-description">Abre un grupo para ver sus cursos. Cada curso contiene sus NRC, modalidades, fechas y horarios.</p>
            </div>
            <span class="workload-tree-total">{{ $groups->count() }} de {{ $groupCount }} grupos</span>
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
</div>
@endsection

@push('styles')
<style>
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
.workload-tree-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.workload-tree-metric{display:flex;align-items:flex-start;gap:13px;min-width:0;padding:17px;border:1px solid var(--line);border-radius:12px;background:var(--surface)}
.workload-tree-metric-primary{border-color:color-mix(in srgb,var(--accent) 35%,var(--line));background:var(--accent-soft)}
.workload-tree-metric-icon{display:grid;place-items:center;width:38px;height:38px;flex:none;border-radius:10px;background:color-mix(in srgb,var(--accent) 12%,var(--surface));color:var(--accent)}
.workload-tree-metric>div{display:grid;gap:3px;min-width:0}
.workload-tree-metric small{color:var(--muted);font-size:10px;font-weight:700}
.workload-tree-metric strong{color:var(--ink);font-size:clamp(27px,3vw,36px);font-weight:830;line-height:1;font-variant-numeric:tabular-nums;letter-spacing:-.045em}
.workload-tree-metric span:not(.workload-tree-metric-icon){color:var(--muted);font-size:10px;line-height:1.4}
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
.workload-tree-root>.workload-tree-group{margin:0}
.workload-tree-root>.tree-empty-child{padding:30px 16px;border:1px dashed var(--line-strong);border-radius:10px;text-align:center}
.workload-tree-group,.workload-tree-course{border-color:var(--line);background:var(--surface)}
.workload-tree-summary{transition:background .14s ease}
.workload-tree-summary:hover{background:var(--accent-soft)}
.workload-tree-children{border-color:var(--line-strong)}
.tree-node-copy strong{font-size:13px}
.tree-node-copy small{font-size:10px}
.tree-course-status{font-size:10px;padding:5px 8px}
.workload-tree-actions .btn{min-height:34px;padding:7px 10px;font-size:10px}
.workload-tree-modality{padding:12px 4px}
.tree-modality-tag{height:32px;font-size:11px}
.tree-modality-copy strong{font-size:12px}
.tree-modality-copy small{font-size:10px}
.tree-open-modality{min-height:34px}
.tree-empty-child{font-size:11px}
.tree-loading-state,.tree-load-error{padding:12px 14px;color:var(--muted);font-size:11px;line-height:1.5}
.tree-load-error{color:var(--danger);background:var(--danger-bg);border-radius:8px}
html[data-theme="dark"] .workload-tree-hero{background:linear-gradient(115deg,color-mix(in srgb,var(--accent) 18%,#15232d),#141f28 76%);border-color:color-mix(in srgb,var(--accent) 30%,var(--line))}
html[data-theme="dark"] .workload-tree-metric-primary{background:color-mix(in srgb,var(--accent) 16%,#141f28)}
html[data-theme="dark"] .workload-tree-metric-icon{background:color-mix(in srgb,var(--accent) 18%,#141f28)}
@media(max-width:980px){.workload-tree-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.workload-tree-hero{align-items:flex-start;flex-direction:column}.workload-tree-hero-actions{width:100%}}
@media(max-width:600px){
 .workload-tree-page .breadcrumbs{margin-bottom:9px}
 .workload-tree-hero{gap:16px;padding:19px 16px;border-radius:12px}
 .workload-tree-hero h1{font-size:32px}
 .workload-tree-context{display:grid;gap:7px}
 .workload-tree-hero-actions{display:grid;grid-template-columns:1fr 1fr}
 .workload-tree-hero-actions .btn{width:100%;padding:9px 8px;font-size:10px}
 .workload-tree-metrics{gap:8px}
 .workload-tree-metric{gap:9px;padding:12px 10px;border-radius:10px}
 .workload-tree-metric-icon{width:31px;height:31px;border-radius:8px}
 .workload-tree-metric strong{font-size:27px}
 .workload-tree-metric span:not(.workload-tree-metric-icon){font-size:9px}
 .workload-tree-browser{padding:13px 10px}
 .workload-tree-browser-head{align-items:flex-start;flex-direction:column}
 .workload-tree-search{display:grid;grid-template-columns:1fr 1fr}
 .workload-tree-search-field{grid-column:1/-1}
 .workload-tree-search .btn,.workload-tree-search>a{width:100%}
}
@media(max-width:380px){.workload-tree-metric{align-items:flex-start;flex-direction:column}.workload-tree-metric strong{font-size:26px}}
@media(prefers-reduced-motion:reduce){.workload-tree-summary{transition:none}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const bindTreeNodes = (scope) => {
        scope.querySelectorAll('[data-tree-load]').forEach((node) => {
            if (node.dataset.treeBound === 'true') return;
            node.dataset.treeBound = 'true';

            node.addEventListener('toggle', async () => {
                if (!node.open || node.dataset.treeLoaded === 'true' || node.dataset.treeLoading === 'true') return;
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
            });
        });
    };
    bindTreeNodes(document);
})();
</script>
@endpush
