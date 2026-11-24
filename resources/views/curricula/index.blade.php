@extends('layouts.app')
@section('title','Control de malla curricular')
@section('content')
<div class="page-head">
    <div>
        <p class="muted small"><a href="{{ route('groups.show',$assignment->group) }}">{{ $assignment->group->name }}</a> / {{ $assignment->course->name }}</p>
        <h1>Programación curricular</h1>
        <p class="subhead">Carga la malla oficial y revisa las propuestas antes de importarlas.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('groups.show',$assignment->group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>

<section class="card" id="upload">
    <div class="card-head"><div><h2>Malla curricular</h2><p class="card-description">Sube el PDF oficial. Gestión Docente extrae el texto y prepara el prompt general para ChatGPT.</p></div><span class="badge {{ $activeCurriculum ? 'badge-green' : '' }}">{{ $activeCurriculum ? 'Importada' : 'Pendiente' }}</span></div>
    <form method="POST" enctype="multipart/form-data" action="{{ route('curricula.extract',$assignment) }}">@csrf
        <label for="curriculum_pdf">Documento PDF de la malla</label><input class="field" id="curriculum_pdf" type="file" name="curriculum_pdf" accept=".pdf,application/pdf" required>
        <div class="actions mt-16"><button class="btn" type="submit"><x-icon name="sparkles" /> Analizar PDF y preparar prompt</button><a class="btn btn-secondary" href="{{ route('curricula.example',$assignment) }}"><x-icon name="file-text" /> Ver JSON de ejemplo</a></div>
    </form>
    <p class="help">La malla es la fuente oficial. La IA debe separar teoría y práctica, conservar las horas T/P/V y proponer una planificación independiente para cada modalidad.</p>
</section>
<section class="card" id="suggestions">
    <div class="card-head"><div><h2>Propuestas de teoría y taller</h2><p class="card-description">Filtra, selecciona y aprueba en pocos clics. TEC no se mezcla con TALL.</p></div><span class="badge">{{ $suggestionStats['pending'] }} pendientes</span></div>
    @if(!$activeCurriculum)
        <div class="empty">Primero importa una malla curricular para habilitar la revisión de propuestas.</div>
    @elseif($suggestionStats['total'] === 0)
        <div class="empty">La malla está importada, pero no contiene propuestas didácticas de IA para revisar.</div>
    @else
        <div class="quick-toolbar">
            <button type="button" class="btn btn-secondary btn-sm filter-button active" data-filter="ALL"><x-icon name="layers" /> Todas</button><button type="button" class="btn btn-secondary btn-sm filter-button" data-filter="TEC"><x-icon name="book-open" /> Solo teoría</button><button type="button" class="btn btn-secondary btn-sm filter-button" data-filter="TALL"><x-icon name="graduation-cap" /> Solo taller</button><button type="button" class="btn btn-secondary btn-sm filter-button" data-filter="PENDING"><x-icon name="clock" /> Pendientes</button>
            <span class="toolbar-spacer"></span><button type="button" class="btn btn-secondary btn-sm" id="selectVisible"><x-icon name="check" /> Seleccionar visibles</button><button type="button" class="btn btn-secondary btn-sm" id="clearVisible"><x-icon name="x" /> Limpiar selección</button>
        </div>
        <form method="POST" action="{{ route('curricula.approve',$activeCurriculum) }}" id="approvalForm">@csrf
            <div class="suggestion-list">
                @foreach($activeCurriculum->weeks as $week)
                    @foreach($week->contentItems->where('source_kind','AI_SUGGESTION') as $item)
                        @php($target = data_get($item->metadata,'target_modality','BOTH'))
                        <label class="suggestion-row" data-target="{{ $target }}" data-approved="{{ $item->approved ? '1' : '0' }}">
                            <input type="hidden" name="reviewed_items[]" value="{{ $item->id }}">
                            <input type="checkbox" name="approved_items[]" value="{{ $item->id }}" {{ $item->approved ? 'checked' : '' }}>
                            <span class="suggestion-copy"><span class="suggestion-meta">Bloque {{ $week->week_number }} · <b>{{ $target === 'BOTH' ? 'TEC + TALL' : ($target === 'TEC' ? 'TEORÍA' : 'TALLER') }}</b> · {{ str_replace('_',' ',$item->category) }}</span><strong>{{ $item->content }}</strong></span>
                            <span class="badge {{ $item->approved ? 'badge-green' : '' }}">{{ $item->approved ? 'Aprobada' : 'Pendiente' }}</span>
                        </label>
                    @endforeach
                @endforeach
            </div>
            <div class="form-actions sticky-actions"><span class="help" id="selectionCount">Selecciona las propuestas que deseas aprobar.</span><button class="btn" type="submit"><x-icon name="check" /> Guardar aprobación</button></div>
        </form>
    @endif
</section>
<section class="card">
    <div class="card-head"><div><h2>Versiones de malla importadas</h2><p class="card-description">Historial de fuentes y versiones.</p></div><span class="badge">{{ $curricula->count() }} versiones</span></div>
    @forelse($curricula as $curriculum)
        <div class="version-row"><div><strong>{{ $curriculum->course_name ?: $assignment->course->name }}</strong><div class="muted small">Versión {{ $curriculum->version }} · {{ $curriculum->source_filename }} · {{ $curriculum->created_at?->format('d/m/Y H:i') }}</div><div class="muted small">Prompt: {{ data_get($curriculum->source_metadata,'prompt_version','—') }} · {{ $curriculum->weeks->count() }} bloques · {{ $curriculum->tasks->count() }} tareas HT</div></div><span class="badge {{ $curriculum->is_active ? 'badge-green' : '' }}">{{ $curriculum->is_active ? 'Activa' : 'Histórica' }}</span></div>
    @empty
        <div class="empty">Todavía no hay mallas curriculares asociadas a este curso.</div>
    @endforelse
</section>
@endsection
@push('styles')
<style>
.quick-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:7px;margin:12px 0}
.toolbar-spacer{flex:1}
.filter-button.active{background:#e8f3f8;border-color:#b9d5e0;color:#28677d}
.suggestion-list{display:grid;gap:7px}
.suggestion-row{display:flex;align-items:center;gap:10px;min-width:0;padding:11px;border:1px solid #dce7ec;border-radius:10px;background:#fff;cursor:pointer}
.suggestion-row[hidden]{display:none}
.suggestion-row input{width:17px;height:17px;flex:0 0 auto}
.suggestion-copy{flex:1;min-width:0}
.suggestion-copy strong{display:block;font-size:13px;font-weight:600;line-height:1.45}
.suggestion-meta{display:block;margin-bottom:4px;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.03em}
.sticky-actions{position:sticky;bottom:8px;z-index:2;padding:12px;border:1px solid #cddfe7;border-radius:12px;background:#f5fafc}
.version-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid var(--line)}
.version-row:last-child{border-bottom:0}
@media(max-width:700px){
    .page-head>.actions{width:100%;justify-content:stretch;flex-wrap:wrap}
    .page-head>.actions .btn{flex:1}
    .quick-toolbar .toolbar-spacer{display:none}
    .quick-toolbar button{flex:1}
    .suggestion-row{align-items:flex-start}
    .suggestion-row>.badge{font-size:9px}
    .version-row{align-items:flex-start;flex-direction:column}
    .sticky-actions{align-items:flex-start;flex-direction:column}
    .sticky-actions button{width:100%}
}
</style>
@endpush
@push('scripts')
<script>
(() => {
    const rows = Array.from(document.querySelectorAll('.suggestion-row'));
    if (!rows.length) return;
    const visibleRows = () => rows.filter(row => !row.hidden);
    const updateCount = () => {
        const selected = visibleRows().filter(row => row.querySelector('input').checked).length;
        const total = visibleRows().length;
        const label = document.getElementById('selectionCount');
        if (label) label.textContent = selected + ' seleccionadas de ' + total + ' visibles';
    };
    const applyFilter = filter => {
        rows.forEach(row => {
            const target = row.dataset.target;
            const matches = filter === 'ALL' || (filter === 'TEC' && (target === 'TEC' || target === 'BOTH')) || (filter === 'TALL' && (target === 'TALL' || target === 'BOTH')) || (filter === 'PENDING' && row.dataset.approved === '0');
            row.hidden = !matches;
        });
        document.querySelectorAll('.filter-button').forEach(button => button.classList.toggle('active', button.dataset.filter === filter));
        updateCount();
    };
    document.querySelectorAll('.filter-button').forEach(button => button.addEventListener('click', () => applyFilter(button.dataset.filter)));
    document.getElementById('selectVisible')?.addEventListener('click', () => {
        visibleRows().forEach(row => { if (row.dataset.approved === '0') row.querySelector('input').checked = true; });
        updateCount();
    });
    document.getElementById('clearVisible')?.addEventListener('click', () => { visibleRows().forEach(row => row.querySelector('input').checked = false); updateCount(); });
    rows.forEach(row => row.querySelector('input').addEventListener('change', updateCount));
    document.getElementById('approvalForm')?.addEventListener('submit', event => {
        if (!confirm('¿Guardar el estado de aprobación de las propuestas visibles? Las propuestas ocultas conservarán su estado actual.')) {
            event.preventDefault();
            return;
        }
        rows.filter(row => row.hidden).forEach(row => row.querySelectorAll('input').forEach(input => input.disabled = true));
    });
    updateCount();
})();
</script>
@endpush