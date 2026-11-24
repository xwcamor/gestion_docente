@extends('layouts.app')
@section('title','Prompt para analizar malla')
@section('content')
<div class="page-head">
    <div>
        <h1>Prompt preparado para ChatGPT</h1>
        <p class="subhead">El PDF ya fue leído. Completa los pasos y vuelve aquí con el JSON que genere ChatGPT.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('curricula.show',$assignment) }}"><x-icon name="arrow-left" /> Volver</a>
</div>

<div class="grid grid-3">
    <div class="card"><div class="stat-label">Versión del prompt</div><div class="stat" style="font-size:20px">{{ $promptVersion }}</div></div>
    <div class="card"><div class="stat-label">Fecha y hora de generación</div><strong>{{ $promptGeneratedAt->format('d/m/Y H:i:s P') }}</strong></div>
    <div class="card"><div class="stat-label">Curso de destino</div><strong>{{ $assignment->course->name }}</strong></div>
</div>

@if($textExtractionWarning)
    <div class="alert alert-warning" role="alert">
        El PDF contiene poco texto extraíble. No se canceló el análisis: adjunta el PDF original a ChatGPT y pídele que lea visualmente el Cuadro Programa y la Hoja de Programación, incluso si aparecen como imágenes.
    </div>
@endif

<section class="card curriculum-source-check" aria-labelledby="curriculum-source-check-title">
    <div class="card-head">
        <div>
            <h2 id="curriculum-source-check-title">Tablas curriculares detectadas</h2>
            <p class="card-description">El análisis cruza las dos tablas principales del documento.</p>
        </div>
    </div>
    <div class="curriculum-source-grid">
        @foreach(['cuadro_programa' => 'Cuadro Programa', 'hoja_programacion' => 'Hoja de Programación'] as $key => $label)
            @php($section = $sections[$key] ?? [])
            <div class="curriculum-source-item">
                <strong>{{ $label }}</strong>
                @if($section['detected'] ?? false)
                    <span class="badge badge-green">Encabezado detectado</span>
                    <small>Página(s): {{ implode(', ', $section['pages'] ?? []) }}</small>
                @else
                    <span class="badge badge-orange">No detectado automáticamente</span>
                    <small>Localiza esta tabla visualmente en el PDF que adjuntarás a ChatGPT.</small>
                @endif
            </div>
        @endforeach
    </div>
    <p class="help">La detección por texto no garantiza que una tabla esté completa. Adjunta siempre el PDF original para que ChatGPT también pueda leer tablas como imagen.</p>
</section>

<section class="card prompt-checklist" aria-labelledby="prompt-checklist-title">
    <div class="prompt-checklist-head">
        <div>
            <span class="eyebrow">Asistente de importación</span>
            <h2 id="prompt-checklist-title">Pasos completados</h2>
            <p class="muted small">Marca cada tarea cuando la termines. El avance queda guardado en este navegador.</p>
        </div>
        <strong class="prompt-progress-value" id="prompt-progress-value">1 de 5</strong>
    </div>
    <div class="prompt-progress-track"><span id="prompt-progress-fill"></span></div>
    <div class="prompt-step-list">
        <div class="prompt-step is-complete" data-step-row="1">
            <span class="prompt-step-number"><x-icon name="check" size="15" /></span>
            <div class="prompt-step-copy"><strong>PDF cargado y leído</strong><small>Gestión Docente extrajo el contenido y preparó el prompt.</small></div>
            <span class="prompt-step-status">Completado</span>
        </div>
        <label class="prompt-step" data-step-row="2">
            <input class="prompt-step-check" type="checkbox" data-step-check="2">
            <span class="prompt-step-number">2</span>
            <span class="prompt-step-copy"><strong>Adjuntar el PDF a ChatGPT</strong><small>Abre ChatGPT y adjunta el mismo PDF que subiste aquí.</small></span>
            <span class="prompt-step-status">Pendiente</span>
        </label>
        <div class="prompt-step" data-step-row="3">
            <span class="prompt-step-number">3</span>
            <div class="prompt-step-copy"><strong>Copiar el prompt</strong><small>Usa el botón “Copiar prompt” de la sección siguiente.</small></div>
            <span class="prompt-step-status">Pendiente</span>
        </div>
        <label class="prompt-step" data-step-row="4">
            <input class="prompt-step-check" type="checkbox" data-step-check="4">
            <span class="prompt-step-number">4</span>
            <span class="prompt-step-copy"><strong>Enviar el prompt y obtener el JSON</strong><small>Pega el prompt en el mismo chat y solicita únicamente el JSON.</small></span>
            <span class="prompt-step-status">Pendiente</span>
        </label>
        <div class="prompt-step" data-step-row="5">
            <span class="prompt-step-number">5</span>
            <div class="prompt-step-copy"><strong>Validar e importar el resultado</strong><small>Pega el JSON en Gestión Docente y revisa la previsualización antes de confirmar.</small></div>
            <span class="prompt-step-status">Pendiente</span>
        </div>
    </div>
</section>

<section class="card">
    <div class="page-head">
        <div><h2>Prompt completo</h2><p class="card-description">Cópialo completo y pégalo en el chat donde adjuntaste la malla.</p></div>
        <button class="btn" id="copyPromptButton" type="button"><x-icon name="copy" /> Copiar prompt</button>
    </div>
    <textarea id="promptText" class="field mono" rows="22" readonly>{{ $prompt }}</textarea>
    <p class="help" id="copyPromptStatus">Versión: {{ $promptVersion }} · Generado: {{ $promptGeneratedAt->toIso8601String() }}</p>
</section>

<section class="card">
    <h2>Pegar resultado JSON de ChatGPT</h2>
    <p class="card-description">Cuando ChatGPT termine, pega aquí el objeto JSON completo o selecciona el archivo generado.</p>
    <form method="POST" enctype="multipart/form-data" action="{{ route('curricula.preview',$assignment) }}" id="curriculumJsonForm">
        @csrf
        <input type="hidden" name="source_token" value="{{ $token }}">
        <label for="curriculumJsonContent">JSON generado por ChatGPT</label>
        <textarea id="curriculumJsonContent" class="field mono" name="json_content" rows="10" placeholder='Pega aquí el objeto JSON completo, desde la primera llave hasta la última.'></textarea>
        <label for="curriculumJsonFile">O selecciona un archivo .json (opcional)</label>
        <input class="field" id="curriculumJsonFile" type="file" name="json_file" accept=".json,.txt">
        <button class="btn mt-16" type="submit"><x-icon name="check" /> Validar y revisar resultado</button>
    </form>
</section>
@endsection

@push('styles')
<style>
.curriculum-source-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.curriculum-source-item{display:grid;gap:6px;align-content:start;padding:12px;border:1px solid var(--line);border-radius:8px;background:var(--surface)}
.curriculum-source-item strong{font-size:12px;color:var(--ink)}
.curriculum-source-item small{color:var(--muted);font-size:10px;line-height:1.45}
@media(max-width:600px){.curriculum-source-grid{grid-template-columns:1fr}}
.prompt-checklist{border:1px solid #d9e7ed;background:#f8fbfd}
.prompt-checklist-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.prompt-checklist-head h2{margin:4px 0}
.prompt-progress-value{font-size:18px;color:#28677d;white-space:nowrap}
.prompt-progress-track{height:9px;background:#e1ebf0;border-radius:20px;overflow:hidden;margin:14px 0}
.prompt-progress-track span{display:block;width:20%;height:100%;background:linear-gradient(90deg,#367da0,#39a18d);border-radius:20px;transition:width .2s ease}
.prompt-step-list{display:grid;gap:8px}
.prompt-step{display:flex;align-items:center;gap:12px;min-width:0;padding:12px;border:1px solid #dfe9ee;border-radius:10px;background:#fff;color:inherit}
label.prompt-step{cursor:pointer}
.prompt-step-check{width:18px;height:18px;flex:0 0 18px;accent-color:#36866f}
.prompt-step-number{width:30px;height:30px;flex:0 0 30px;display:grid;place-items:center;border-radius:50%;background:#edf3f6;color:#527182;font-size:12px;font-weight:800}
.prompt-step-copy{flex:1;min-width:0}
.prompt-step-copy strong{display:block;font-size:13px}
.prompt-step-copy small{display:block;margin-top:3px;color:#71818a;font-size:11px;line-height:1.45}
.prompt-step-status{font-size:11px;color:#7a8990;white-space:nowrap}
.prompt-step.is-complete{border-color:#cce4d7;background:#f5fbf7}
.prompt-step.is-complete .prompt-step-number{background:#dff2e6;color:#28764f}
.prompt-step.is-complete .prompt-step-status{color:#28764f;font-weight:700}
@media(max-width:600px){.prompt-step{align-items:flex-start;flex-wrap:wrap}.prompt-step-status{margin-left:42px}.prompt-checklist-head{align-items:flex-start}.prompt-progress-value{font-size:16px}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const token = @json($token);
    const storageKey = 'gestion-docente-prompt-steps:' + token;
    const completed = new Set([1]);
    const rows = [...document.querySelectorAll('[data-step-row]')];
    const progressValue = document.getElementById('prompt-progress-value');
    const progressFill = document.getElementById('prompt-progress-fill');

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
        saved.forEach(step => {
            const number = Number(step);
            if ([2, 3, 4, 5].includes(number)) completed.add(number);
        });
    } catch (error) {
        // El checklist sigue funcionando durante la sesión aunque el navegador bloquee localStorage.
    }

    const persist = () => {
        try { localStorage.setItem(storageKey, JSON.stringify([...completed])); } catch (error) {}
    };

    const render = () => {
        rows.forEach(row => {
            const number = Number(row.dataset.stepRow);
            const done = completed.has(number);
            row.classList.toggle('is-complete', done);
            const status = row.querySelector('.prompt-step-status');
            if (status) status.textContent = done ? 'Completado' : 'Pendiente';
            const marker = row.querySelector('.prompt-step-number');
            if (marker && number !== 1) marker.textContent = done ? '✓' : String(number);
            const checkbox = row.querySelector('[data-step-check]');
            if (checkbox) checkbox.checked = done;
        });
        const count = completed.size;
        if (progressValue) progressValue.textContent = count + ' de 5';
        if (progressFill) progressFill.style.width = (count / 5 * 100) + '%';
    };

    document.querySelectorAll('[data-step-check]').forEach(input => {
        input.addEventListener('change', () => {
            const number = Number(input.dataset.stepCheck);
            if (input.checked) completed.add(number); else completed.delete(number);
            persist();
            render();
        });
    });

    document.getElementById('copyPromptButton')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        const textarea = document.getElementById('promptText');
        const status = document.getElementById('copyPromptStatus');
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(textarea.value);
            } else {
                textarea.focus();
                textarea.select();
                if (!document.execCommand('copy')) throw new Error('No se pudo copiar automáticamente.');
            }
            completed.add(3);
            persist();
            render();
            button.textContent = '✓ Copiado';
            if (status) status.textContent = 'Prompt copiado correctamente. Ya puedes pegarlo en ChatGPT.';
        } catch (error) {
            if (status) status.textContent = 'No se pudo copiar automáticamente. Selecciona el texto y usa Ctrl+C.';
        }
    });

    document.getElementById('curriculumJsonForm')?.addEventListener('submit', event => {
        const content = document.getElementById('curriculumJsonContent')?.value.trim();
        const file = document.getElementById('curriculumJsonFile')?.files?.length;
        if (!content && !file) return;
        completed.add(5);
        persist();
        render();
    });

    render();
})();
</script>
@endpush
