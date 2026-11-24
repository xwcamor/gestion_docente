@extends('layouts.app')
@section('title','Control de malla curricular')
@section('content')
<div class="page-head">
    <div>
        <p class="muted small"><a href="{{ route('groups.show',$assignment->group) }}">{{ $assignment->group->name }}</a> / {{ $assignment->course->name }}</p>
        <h1>Control de programación curricular</h1>
        <p class="subhead">Una malla alimenta la planificación de teoría y taller por separado.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('groups.show',$assignment->group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>
<section class="card workflow-card">
    <div class="workflow-heading"><div><span class="eyebrow">Panel de control</span><h2>Tu avance</h2><p class="muted small">Progreso de la programación actual.</p></div><div class="actions"><button class="btn btn-secondary btn-sm" type="button" data-modal-open="curriculum-help-dialog"><x-icon name="circle-help" /> Cómo funciona</button><div class="workflow-score">{{ $progress }}<small>%</small></div></div></div>
    <div class="progress-track"><span style="width:{{ $progress }}%"></span></div>
    <div class="workflow-stats">
        <div><strong>{{ $activeCurriculum?->weeks->count() ?? 0 }}</strong><span>Bloques de malla</span></div>
        <div><strong>{{ $suggestionStats['approved'] }}/{{ $suggestionStats['total'] }}</strong><span>Propuestas aprobadas</span></div>
        <div><strong>{{ number_format($totalHoursTheory,1) }} h</strong><span>Horas de teoría</span></div>
        <div><strong>{{ number_format($totalHoursPractice,1) }} h</strong><span>Horas de práctica</span></div>
    </div>
</section>
<section class="grid grid-2 modality-control">
    <article class="card modality-card theory">
        <div class="modality-title"><span class="modality-icon">T</span><div><h2>TEORÍA</h2><p>Conocimientos tecnológicos y sesiones de aula</p></div></div>
        <div class="modality-metrics"><div><strong>{{ $suggestionStats['tec_approved'] }}/{{ $suggestionStats['tec_total'] }}</strong><span>Propuestas aprobadas</span></div><div><strong>{{ data_get($modalityStats,'TEC.sessions',0) }}</strong><span>Sesiones calculadas</span></div></div>
        <span class="status-line {{ $workflow['tec_configured'] ? 'is-done' : 'is-pending' }}">{{ !$workflow['has_tec_modality'] ? 'Modalidad TEC no asignada' : ($workflow['tec_configured'] ? 'Calendario configurado' : 'Falta configurar el calendario TEC') }}</span>
        <div class="actions"><a class="btn btn-secondary btn-sm" href="{{ route('groups.show',$assignment->group) }}"><x-icon name="book-open" /> Ir a cursos</a><a class="btn btn-secondary btn-sm" href="#suggestions" data-filter="TEC"><x-icon name="book-open" /> Revisar teoría</a></div>
    </article>
    <article class="card modality-card workshop">
        <div class="modality-title"><span class="modality-icon">P</span><div><h2>TALLER / PRÁCTICA</h2><p>Tareas, operaciones y evidencias de taller</p></div></div>
        <div class="modality-metrics"><div><strong>{{ $suggestionStats['tall_approved'] }}/{{ $suggestionStats['tall_total'] }}</strong><span>Propuestas aprobadas</span></div><div><strong>{{ data_get($modalityStats,'TALL.sessions',0) }}</strong><span>Sesiones calculadas</span></div></div>
        <span class="status-line {{ $workflow['tall_configured'] ? 'is-done' : 'is-pending' }}">{{ !$workflow['has_tall_modality'] ? 'Modalidad TALL no asignada' : ($workflow['tall_configured'] ? 'Calendario configurado' : 'Falta configurar el calendario TALL') }}</span>
        <div class="actions"><a class="btn btn-secondary btn-sm" href="{{ route('groups.show',$assignment->group) }}"><x-icon name="book-open" /> Ir a cursos</a><a class="btn btn-secondary btn-sm" href="#suggestions" data-filter="TALL"><x-icon name="graduation-cap" /> Revisar taller</a></div>
    </article>
</section>
<dialog class="ui-dialog sheet-dialog" id="curriculum-help-dialog" aria-labelledby="curriculum-help-title">
    <div class="dialog-head">
        <div><p class="eyebrow">Ayuda de malla curricular</p><h2 id="curriculum-help-title">Cómo completar la programación</h2><p>Este resumen te orienta; puedes cerrar y seguir trabajando cuando quieras.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <div class="dialog-body">
        <div class="modal-help-grid">
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="file-text" /></span><div><strong>Cargar la malla oficial</strong><p>Sube el PDF original para extraer la estructura curricular.</p></div></div>
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="sparkles" /></span><div><strong>Revisar propuestas</strong><p>Revisa por separado teoría (TEC) y taller (TALL), y aprueba lo que corresponda.</p></div></div>
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="calendar-days" /></span><div><strong>Configurar horarios</strong><p>Define fechas y horarios propios para cada modalidad.</p></div></div>
            <div class="modal-help-item"><span class="workflow-icon"><x-icon name="clipboard-check" /></span><div><strong>Comprobar sesiones</strong><p>Verifica que las sesiones se hayan generado correctamente.</p></div></div>
        </div>
    </div>
    <div class="dialog-footer"><button class="btn" type="button" data-modal-close>Entendido</button></div>
</dialog>

<section class="card" id="upload">
    <div class="card-head"><div><h2>Malla curricular</h2><p class="card-description">Sube el PDF oficial. Gestión Docente extrae el texto y prepara el prompt general para ChatGPT.</p></div><span class="badge {{ $workflow['has_curriculum'] ? 'badge-green' : '' }}">{{ $workflow['has_curriculum'] ? 'Importada' : 'Pendiente' }}</span></div>
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
            <button type="button" class="btn btn-secondary btn-sm filter-button active" data-filter="ALL">Todas</button><button type="button" class="btn btn-secondary btn-sm filter-button" data-filter="TEC">Solo teoría</button><button type="button" class="btn btn-secondary btn-sm filter-button" data-filter="TALL">Solo taller</button><button type="button" class="btn btn-secondary btn-sm filter-button" data-filter="PENDING">Pendientes</button>
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
.workflow-card{background:#fff}.workflow-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.workflow-heading h2{margin:4px 0}.workflow-score{font-size:36px;font-weight:720;color:#40584a;white-space:normal;overflow-wrap:anywhere;line-height:1.1}.workflow-score small{font-size:16px}.progress-track{height:10px;background:#e7ebe5;border-radius:6px;overflow:hidden;margin:12px 0 18px}.progress-track span{display:block;height:100%;background:#55745e;border-radius:20px}.workflow-stats,.modality-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.workflow-stats>div,.modality-metrics>div{padding:12px;background:#fff;border:1px solid #e6ebe5;border-radius:10px}.workflow-stats strong,.modality-metrics strong{display:block;font-size:20px}.workflow-stats span,.modality-metrics span{font-size:11px;color:#768078}.modality-control{margin:16px 0}.modality-card{margin:0}.modality-title{display:flex;align-items:center;gap:12px;margin-bottom:16px}.modality-title h2{margin:0}.modality-title p{margin:3px 0 0;color:#768078;font-size:12px}.modality-icon{width:38px;height:38px;border-radius:8px;display:grid;place-items:center;font-size:19px;font-weight:800;background:#eff3ef;color:#526b58}.workshop .modality-icon{background:#edf4ed;color:#4e7556}.modality-metrics{grid-template-columns:repeat(2,minmax(0,1fr));margin-bottom:12px}.status-line{display:block;font-size:12px;margin:8px 0 14px}.status-line:before{content:"";display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:7px;background:#977548}.status-line.is-done:before{background:#55745e}.step-list{display:grid;gap:0}.step-row{display:flex;align-items:center;gap:12px;padding:13px 2px;border-bottom:1px solid #edf0eb;text-decoration:none;color:inherit}.step-row:last-child{border-bottom:0}.step-number{width:29px;height:29px;flex:0 0 29px;border-radius:50%;display:grid;place-items:center;background:#f1f3f0;color:#657168;font-size:12px;font-weight:800}.step-number.complete{background:#edf4ed;color:#4e7556}.step-row small{display:block;color:#768078;margin-top:3px;font-size:11px}.step-action{margin-left:auto;max-width:160px;color:#526b58;font-size:11px;font-weight:680;white-space:normal;text-align:right;overflow-wrap:anywhere}.quick-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:7px;margin:12px 0}.toolbar-spacer{flex:1}.filter-button.active{background:#eef3ed;border-color:#cbd8ca;color:#40584a}.suggestion-list{display:grid;gap:7px}.suggestion-row{display:flex;align-items:center;gap:10px;min-width:0;padding:11px;border:1px solid #e6ebe5;border-radius:10px;cursor:pointer}.suggestion-row[hidden]{display:none}.suggestion-row input{width:17px;height:17px;flex:0 0 auto}.suggestion-copy{flex:1;min-width:0}.suggestion-copy strong{display:block;font-size:13px;font-weight:600;line-height:1.45}.suggestion-meta{display:block;font-size:10px;color:#768078;text-transform:uppercase;letter-spacing:.03em;margin-bottom:4px}.sticky-actions{position:sticky;bottom:8px;background:#fff;padding:12px;border:1px solid #e2e7e0;border-radius:12px;box-shadow:none;z-index:2}.version-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid #edf0eb}.version-row:last-child{border-bottom:0}@media(max-width:700px){.workflow-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.workflow-score{font-size:30px}.modality-control{grid-template-columns:1fr}.quick-toolbar .toolbar-spacer{display:none}.quick-toolbar button{flex:1}.suggestion-row{align-items:flex-start}.suggestion-row>.badge{font-size:9px}.version-row{align-items:flex-start;flex-direction:column}.sticky-actions{align-items:flex-start;flex-direction:column}.sticky-actions button{width:100%}}

/* Currícula: teoría y taller se distinguen por color */
.workflow-score{color:#28677d}
.progress-track{background:#e4edf2}
.progress-track span{background:linear-gradient(90deg,#367da0,#39a18d)}
.workflow-stats>div,.modality-metrics>div{border-color:#dce8ed;background:#f8fbfc}
.workflow-stats strong,.modality-metrics strong{color:#315c70}
.workflow-stats span,.modality-metrics span{color:#6a8491}
.modality-card.theory{border-top:3px solid #5187b2;background:#f8fbfe}
.modality-card.workshop{border-top:3px solid #3d9b8d;background:#f5fbf9}
.modality-card.theory .modality-icon{background:#e6f0fa;color:#3c73a2}
.modality-card.workshop .modality-icon{background:#e2f4ef;color:#2e8275}
.status-line:before{background:#d09a50}
.status-line.is-done:before{background:#3d9b72}
.step-row{border-bottom-color:#e5edf1}
.step-number{background:#e9f1f5;color:#466b7d}
.step-number.complete{background:#e1f3e9;color:#2d8155}
.step-action{color:#34738b}
.filter-button.active{background:#e8f3f8;border-color:#b9d5e0;color:#28677d}
.suggestion-row{border-color:#dce7ec;background:#fff}
.suggestion-row:hover{border-color:#9fc6d4;background:#f7fbfc}
.sticky-actions{border-color:#cddfe7;background:#f5fafc}

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
    document.querySelectorAll('a[data-filter="TEC"],a[data-filter="TALL"]').forEach(link => link.addEventListener('click', () => applyFilter(link.dataset.filter)));
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