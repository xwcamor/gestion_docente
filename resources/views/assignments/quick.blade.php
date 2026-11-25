@extends('layouts.app')
@section('title', ($editingAssignment ?? null) ? 'Editar curso y horario' : 'Crear cursos y horarios')
@section('content')
@php($isEditing = ($editingAssignment ?? null) !== null)
@php($rows = old('rows', $rows ?? [[]]))
@php($weekdayLabels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'])

<div class="breadcrumbs">
    <a href="{{ route('cycles.index') }}">Cargas laborales</a><x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</a><x-icon name="chevron-right" size="12" />
    <a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><x-icon name="chevron-right" size="12" />
    <span class="current">{{ $isEditing ? 'Editar curso' : 'Cursos y horarios' }}</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">{{ $group->name }} · {{ $group->cycle->academic_year }} / {{ $group->cycle->cycle_code }}</p>
        <h1>{{ $isEditing ? 'Editar curso y horario' : 'Crear cursos y horarios' }}</h1>
        <p class="subhead">Pega el alias institucional. El sistema detecta el nombre, la modalidad y el NRC; después configura fechas y repetición semanal.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Hay datos que revisar.</strong>
        Corrige los campos señalados; no se guardaron cambios incompletos.
    </div>
@endif

<form method="POST"
      action="{{ $isEditing ? route('assignments.quick.update', [$group, $editingAssignment]) : route('assignments.quick.store', $group) }}"
      id="quick-course-form"
      data-loading-form="1">
    @csrf
    @if($isEditing)
        @method('PATCH')
    @endif

    <section class="card quick-register-card">
        <div class="card-head">
            <div>
                <h2>{{ $isEditing ? $editingAssignment->course->name : 'Datos del curso' }}</h2>
                <p class="card-description">
                    {{ $isEditing ? 'Modifica el alias, NRC, bloques, fechas y horarios sin salir del grupo.' : 'Una fila por curso. Puedes registrar teoría, taller o ambos, cada uno con sus propios datos.' }}
                </p>
            </div>
            @unless($isEditing)
                <span class="badge badge-blue">Hasta 60 cursos</span>
            @endunless
        </div>

        <div class="alert alert-info quick-alias-help">
            <x-icon name="info" size="16" />
            <div>
                <strong>Pega el alias completo</strong>
                <p>Ejemplo: <code>202620-PIAD-224-TEC-NRC_11523: SISTEMAS OPERATIVOS Y COMUNICACIÓN DE DATOS</code></p>
                <p>Se completarán automáticamente el nombre, el código base, TEC/TALL y el NRC. El bloque institucional, ambiente y horario se pueden añadir debajo.</p>
            </div>
        </div>

        <div class="quick-course-table-wrap">
            <table class="quick-course-table">
                <thead>
                    <tr>
                        <th class="quick-number-heading">#</th>
                        <th class="quick-course-heading">Nombre del curso</th>
                        <th class="quick-modality-heading">Teoría · TEC</th>
                        <th class="quick-modality-heading">Taller / práctica · TALL</th>
                        <th class="quick-actions-heading" aria-label="Acciones"></th>
                    </tr>
                </thead>
                <tbody id="quick-course-rows">
                    @foreach($rows as $index => $row)
                        <tr class="quick-course-row" data-row>
                            <td class="quick-row-number">{{ $index + 1 }}</td>
                            <td class="quick-course-name-cell">
                                <label class="quick-field-label">
                                    <span>Nombre detectado / editable</span>
                                    <input class="field" data-course-name name="rows[{{ $index }}][course_name]" value="{{ $row['course_name'] ?? '' }}" maxlength="255" required placeholder="Se detecta desde el alias">
                                </label>
                                <small class="quick-row-preview" data-row-preview aria-live="polite">Pega el alias TEC o TALL para reconocer el curso.</small>
                                @error("rows.$index.course_name")<small class="field-error">{{ $message }}</small>@enderror
                                @error("rows.$index.row_error")<small class="field-error quick-row-error">{{ $message }}</small>@enderror
                            </td>

                            @foreach(['TEC' => 'tec', 'TALL' => 'tall'] as $type => $prefix)
                                @php($codeField = $prefix.'_code')
                                @php($blockField = $prefix.'_block_code')
                                @php($scheduleField = $prefix.'_schedules')
                                @php($scheduleRows = $row[$scheduleField] ?? [])
                                @php($defaultType = $type === 'TEC' ? 'THEORY' : 'LAB')
                                <td class="quick-modality-cell">
                                    <div class="quick-modality-fields">
                                        <label class="quick-field-label quick-code-field">
                                            <span>Alias {{ $type }}</span>
                                            <input class="field mono quick-modality-code"
                                                   data-code-input
                                                   data-modality="{{ $type }}"
                                                   name="rows[{{ $index }}][{{ $codeField }}]"
                                                   value="{{ $row[$codeField] ?? '' }}"
                                                   maxlength="255"
                                                   placeholder="{{ $type === 'TEC' ? '...-TEC-NRC_11523: Nombre del curso' : '...-TAL-NRC_11524: Nombre del curso' }}"
                                                   aria-label="Alias institucional {{ $type }}">
                                            <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                                            @error("rows.$index.$codeField")<small class="field-error">{{ $message }}</small>@enderror
                                        </label>

                                        <label class="quick-field-label">
                                            <span>Bloque institucional (opcional)</span>
                                            <input class="field" name="rows[{{ $index }}][{{ $blockField }}]" value="{{ $row[$blockField] ?? '' }}" maxlength="100" placeholder="Ej. {{ $type }}-01">
                                        </label>

                                        <div class="quick-date-fields">
                                            <label class="quick-field-label">
                                                <span>Inicio</span>
                                                <input class="field" type="date" name="rows[{{ $index }}][{{ $prefix }}_start_date]" value="{{ $row[$prefix.'_start_date'] ?? '' }}">
                                                @error('rows.'.$index.'.'.$prefix.'_start_date')<small class="field-error">{{ $message }}</small>@enderror
                                            </label>
                                            <label class="quick-field-label">
                                                <span>Fin</span>
                                                <input class="field" type="date" name="rows[{{ $index }}][{{ $prefix }}_end_date]" value="{{ $row[$prefix.'_end_date'] ?? '' }}">
                                                @error('rows.'.$index.'.'.$prefix.'_end_date')<small class="field-error">{{ $message }}</small>@enderror
                                            </label>
                                        </div>

                                        <details class="quick-schedule-editor">
                                            <summary>
                                                <span>Horario semanal</span>
                                                <small><span data-schedule-count>{{ count($scheduleRows) }}</span> bloques</small>
                                                <x-icon name="chevron-right" class="quick-schedule-chevron" size="14" />
                                            </summary>
                                            <div class="quick-schedule-body">
                                                <p class="help">Marca los días, indica las horas y el intervalo de repetición. El calendario se detiene en la fecha Fin.</p>
                                                <div class="quick-schedule-list" data-schedule-list>
                                                    @foreach($scheduleRows as $scheduleIndex => $schedule)
                                                        @include('assignments.partials.schedule-row', [
                                                            'rowIndex' => $index,
                                                            'prefix' => $prefix,
                                                            'scheduleIndex' => $scheduleIndex,
                                                            'schedule' => $schedule,
                                                            'defaultInstructionType' => $defaultType,
                                                        ])
                                                    @endforeach
                                                </div>
                                                <button class="btn btn-secondary btn-sm quick-add-schedule" type="button" data-prefix="{{ $prefix }}" data-row-index="{{ $index }}">
                                                    <x-icon name="plus" /> Agregar bloque semanal
                                                </button>
                                                @if($isEditing && count($scheduleRows) > 0)
                                                    <label class="quick-clear-schedules">
                                                        <input type="checkbox" name="rows[{{ $index }}][{{ $prefix }}_clear_schedules]" value="1">
                                                        <span>Quitar todos los horarios de {{ $type }}</span>
                                                    </label>
                                                @endif
                                                @error("rows.$index.$scheduleField")<small class="field-error">{{ $message }}</small>@enderror
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            @endforeach

                            <td class="quick-remove-cell">
                                @unless($isEditing)
                                    <button class="btn btn-ghost btn-sm quick-remove-row" type="button" aria-label="Quitar curso" title="Quitar curso"><x-icon name="x" /></button>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <template id="quick-course-row-template">
            <tr class="quick-course-row" data-row>
                <td class="quick-row-number"></td>
                <td class="quick-course-name-cell">
                    <label class="quick-field-label">
                        <span>Nombre detectado / editable</span>
                        <input class="field" data-course-name name="rows[__INDEX__][course_name]" maxlength="255" required placeholder="Se detecta desde el alias">
                    </label>
                    <small class="quick-row-preview" data-row-preview aria-live="polite">Pega el alias TEC o TALL para reconocer el curso.</small>
                </td>
                @foreach(['TEC' => 'tec', 'TALL' => 'tall'] as $type => $prefix)
                    <td class="quick-modality-cell">
                        <div class="quick-modality-fields">
                            <label class="quick-field-label quick-code-field">
                                <span>Alias {{ $type }}</span>
                                <input class="field mono quick-modality-code" data-code-input data-modality="{{ $type }}" name="rows[__INDEX__][{{ $prefix }}_code]" maxlength="255" placeholder="{{ $type === 'TEC' ? '...-TEC-NRC_11523: Nombre' : '...-TAL-NRC_11524: Nombre' }}">
                                <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                            </label>
                            <label class="quick-field-label">
                                <span>Bloque institucional (opcional)</span>
                                <input class="field" name="rows[__INDEX__][{{ $prefix }}_block_code]" maxlength="100" placeholder="Ej. {{ $type }}-01">
                            </label>
                            <div class="quick-date-fields">
                                <label class="quick-field-label"><span>Inicio</span><input class="field" type="date" name="rows[__INDEX__][{{ $prefix }}_start_date]"></label>
                                <label class="quick-field-label"><span>Fin</span><input class="field" type="date" name="rows[__INDEX__][{{ $prefix }}_end_date]"></label>
                            </div>
                            <details class="quick-schedule-editor">
                                <summary><span>Horario semanal</span><small><span data-schedule-count>0</span> bloques</small><x-icon name="chevron-right" class="quick-schedule-chevron" size="14" /></summary>
                                <div class="quick-schedule-body">
                                    <p class="help">Marca los días, indica las horas y el intervalo de repetición. El calendario se detiene en la fecha Fin.</p>
                                    <div class="quick-schedule-list" data-schedule-list></div>
                                    <button class="btn btn-secondary btn-sm quick-add-schedule" type="button" data-prefix="{{ $prefix }}" data-row-index="__INDEX__"><x-icon name="plus" /> Agregar bloque semanal</button>
                                </div>
                            </details>
                        </div>
                    </td>
                @endforeach
                <td class="quick-remove-cell"><button class="btn btn-ghost btn-sm quick-remove-row" type="button" aria-label="Quitar curso" title="Quitar curso"><x-icon name="x" /></button></td>
            </tr>
        </template>

        <template id="quick-schedule-row-template">
            @include('assignments.partials.schedule-row', [
                'rowIndex' => '__ROW_INDEX__',
                'prefix' => '__PREFIX__',
                'scheduleIndex' => '__SCHEDULE_INDEX__',
                'schedule' => [],
                'defaultInstructionType' => '__DEFAULT_INSTRUCTION__',
            ])
        </template>

        @error('rows')<p class="field-error">{{ $message }}</p>@enderror
        <div class="quick-register-actions">
            @unless($isEditing)
                <button class="btn btn-secondary" type="button" id="quick-add-row"><x-icon name="plus" /> Agregar curso</button>
                <span class="help">Pega un alias por modalidad. Si el curso tiene TEC y TALL, colócalos en la misma fila.</span>
            @else
                <span class="help">El nombre se actualiza en todos los grupos donde se reutiliza este curso. Los NRC y horarios solo cambian en este grupo.</span>
            @endunless
        </div>

        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}"><x-icon name="x" /> Cancelar</a>
            <button class="btn" type="submit">
                <x-icon name="check" />
                {{ $isEditing ? 'Guardar cambios del curso' : 'Guardar cursos y horarios' }}
            </button>
        </div>
    </section>
</form>
@endsection

@push('styles')
<style>
.quick-register-card{padding:18px}
.quick-alias-help{align-items:flex-start;line-height:1.55}
.quick-alias-help>div{min-width:0}
.quick-alias-help p{margin:3px 0 0}
.quick-alias-help code{font-size:10px;overflow-wrap:anywhere}
.quick-course-table-wrap{overflow-x:auto;margin-top:14px;border:1px solid var(--line);border-radius:10px}
.quick-course-table{width:100%;min-width:1160px;table-layout:fixed;border-collapse:separate;border-spacing:0;font-size:12px}
.quick-course-table th{padding:11px;background:var(--surface-soft);color:var(--muted);text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.3px;border-bottom:1px solid var(--line)}
.quick-course-table th.quick-modality-heading{text-align:center;color:var(--info);background:var(--info-bg)}
.quick-course-table td{padding:12px 10px;border-bottom:1px solid var(--line);vertical-align:top}
.quick-course-table tbody tr:last-child td{border-bottom:0}
.quick-course-table .field{width:100%;min-width:0;font-size:11px}
.quick-number-heading,.quick-row-number{width:38px;text-align:center}
.quick-row-number{padding-top:18px!important;color:var(--muted);font-weight:750}
.quick-course-heading,.quick-course-name-cell{width:235px}
.quick-field-label{display:grid;gap:5px;min-width:0;margin-bottom:8px}
.quick-field-label>span,.quick-schedule-field>span{color:var(--muted);font-size:10px;font-weight:700}
.quick-field-label .field{min-height:36px;padding:8px 9px}
.quick-code-field .field{font-size:10px}
.quick-modality-cell{border-left:1px solid var(--line)}
.quick-modality-fields{display:grid;gap:4px;min-width:0}
.quick-date-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
.quick-date-fields .quick-field-label{margin-bottom:0}
.quick-code-preview,.quick-row-preview{display:block;min-height:13px;margin:3px 0 7px;color:var(--muted);font-size:10px;line-height:1.4;overflow-wrap:anywhere}
.quick-code-preview.is-warning,.quick-row-preview.is-warning,.quick-row-error{color:var(--danger)}
.quick-row-preview.is-valid{color:var(--success)}
.quick-remove-cell{width:42px;text-align:center}
.quick-row-error{display:block;margin-top:6px;padding:7px;border:1px solid var(--line);border-radius:7px;background:var(--danger-bg);font-size:10px}
.quick-schedule-editor{margin-top:10px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);overflow:hidden}
.quick-schedule-editor>summary{display:flex;align-items:center;gap:7px;padding:9px;cursor:pointer;list-style:none}
.quick-schedule-editor>summary::-webkit-details-marker{display:none}
.quick-schedule-editor>summary>span{font-size:11px;font-weight:750;color:var(--ink)}
.quick-schedule-editor>summary>small{margin-left:auto;color:var(--muted);font-size:10px;white-space:nowrap}
.quick-schedule-chevron{color:var(--muted);transition:transform .15s ease}
.quick-schedule-editor[open] .quick-schedule-chevron{transform:rotate(90deg)}
.quick-schedule-body{padding:10px;border-top:1px solid var(--line)}
.quick-schedule-body>.help{margin:0 0 9px;font-size:10px}
.quick-schedule-list{display:grid;gap:8px}
.quick-schedule-row{display:grid;grid-template-columns:minmax(160px,1.4fr) minmax(90px,.65fr) minmax(90px,.65fr) minmax(90px,.7fr) minmax(115px,.8fr) minmax(110px,.8fr) auto;align-items:end;gap:7px;padding:9px;border:1px solid var(--line);border-radius:8px;background:var(--surface)}
.quick-schedule-days{min-width:0}
.quick-schedule-label{display:block;margin-bottom:6px;color:var(--muted);font-size:10px;font-weight:700}
.quick-weekdays{display:flex;gap:4px;flex-wrap:wrap}
.quick-weekday{display:grid;place-items:center;cursor:pointer}
.quick-weekday input{position:absolute;opacity:0;pointer-events:none}
.quick-weekday span{display:grid;place-items:center;width:25px;height:25px;border:1px solid var(--line-strong);border-radius:50%;background:var(--surface);color:var(--ink-soft);font-size:10px;font-weight:700}
.quick-weekday input:checked+span{border-color:var(--accent);background:var(--accent);color:#fff}
.quick-weekday input:focus-visible+span{outline:2px solid var(--accent);outline-offset:2px}
.quick-schedule-field{display:grid;gap:5px;min-width:0;margin:0}
.quick-schedule-field .field{min-height:34px;padding:6px 7px;font-size:10px}
.quick-schedule-remove{min-height:34px;padding:6px}
.quick-add-schedule{margin-top:9px}
.quick-clear-schedules{display:flex;align-items:center;gap:7px;margin-top:10px;color:var(--danger);font-size:10px}
.quick-clear-schedules input{width:14px;height:14px}
.quick-register-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px}
.quick-register-actions .help{max-width:70ch}
@media(max-width:1100px){
 .quick-course-table-wrap{overflow:visible;border:0}
 .quick-course-table{display:block;min-width:0;table-layout:auto}
 .quick-course-table thead{display:none}
 .quick-course-table tbody{display:grid;gap:12px}
 .quick-course-table tr{display:grid;grid-template-columns:1fr;gap:11px;padding:13px;border:1px solid var(--line);border-radius:10px;background:var(--surface)}
 .quick-course-table td{display:block;width:auto!important;min-width:0;padding:0;border:0}
 .quick-course-table td.quick-row-number{display:none}
 .quick-course-table td.quick-modality-cell{padding-top:11px;border-top:1px solid var(--line)}
 .quick-course-table td.quick-modality-cell:before{display:block;margin-bottom:9px;padding:6px 8px;border-radius:6px;background:var(--info-bg);color:var(--info);font-size:10px;font-weight:800;content:"TEORÍA · TEC"}
 .quick-course-table td.quick-modality-cell:nth-of-type(4):before{content:"TALLER / PRÁCTICA · TALL"}
 .quick-schedule-row{grid-template-columns:repeat(3,minmax(0,1fr))}
 .quick-schedule-days{grid-column:1/-1}
 .quick-schedule-remove{justify-self:end}
}
@media(max-width:560px){
 .quick-register-card{padding:12px}
 .quick-date-fields{grid-template-columns:repeat(2,minmax(0,1fr))}
 .quick-schedule-row{grid-template-columns:repeat(2,minmax(0,1fr))}
 .quick-schedule-days{grid-column:1/-1}
 .quick-schedule-room{grid-column:1/-1}
 .quick-schedule-remove{grid-column:2}
 .quick-register-actions{align-items:stretch;flex-direction:column}
 .quick-register-actions .btn{width:100%}
 .quick-register-card .form-actions{align-items:stretch;flex-direction:column-reverse}
 .quick-register-card .form-actions .btn{width:100%}
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const body = document.getElementById('quick-course-rows');
    const addCourseButton = document.getElementById('quick-add-row');
    const courseTemplate = document.getElementById('quick-course-row-template');
    const scheduleTemplate = document.getElementById('quick-schedule-row-template');
    if (!body || !courseTemplate || !scheduleTemplate) return;

    const parseAlias = value => {
        const colon = value.indexOf(':');
        const code = (colon >= 0 ? value.slice(0, colon) : value).trim();
        const name = colon >= 0 ? value.slice(colon + 1).trim() : '';
        const pattern = /(?:(20\d{4})-)?([A-Z][A-Z0-9]{1,9})-([A-Z0-9]{1,12})-(TEC|TALL|TAL)(?:[-_\s]*NRC[_\s-]*(\d{3,12}))?/gi;
        const entries = [...code.matchAll(pattern)].map(match => ({
            base: [match[1], match[2], match[3]].filter(Boolean).join('-'),
            period: match[1] || '',
            program: match[2],
            course: match[3],
            type: match[4].toUpperCase() === 'TAL' ? 'TALL' : match[4].toUpperCase(),
            nrc: match[5] || ''
        }));
        return { code, name, entries };
    };

    const updateRow = row => {
        const nameInput = row.querySelector('[data-course-name]');
        const parsedByType = {};
        const aliasNames = [];

        row.querySelectorAll('[data-code-input]').forEach(input => {
            const result = parseAlias(input.value);
            const type = input.dataset.modality;
            const preview = input.closest('.quick-field-label').querySelector('[data-code-preview]');
            parsedByType[type] = result;

            if (result.name) aliasNames.push(result.name);
            if (!input.value.trim()) {
                preview.textContent = '';
                preview.classList.remove('is-warning');
                return;
            }
            if (result.entries.length !== 1 || result.entries[0].type !== type) {
                preview.textContent = type === 'TEC'
                    ? 'Pega un alias TEC con NRC, por ejemplo ...-TEC-NRC_11523: Nombre.'
                    : 'Pega un alias TAL/TALL con NRC, por ejemplo ...-TAL-NRC_11524: Nombre.';
                preview.classList.add('is-warning');
                return;
            }

            const entry = result.entries[0];
            preview.textContent = [
                entry.period ? 'Periodo '+entry.period : '',
                'Programa '+entry.program,
                'Curso '+entry.course,
                entry.nrc ? 'NRC '+entry.nrc : 'NRC pendiente'
            ].filter(Boolean).join(' · ');
            preview.classList.remove('is-warning');
        });

        const uniqueNames = [...new Set(aliasNames)];
        if (uniqueNames.length === 1 && (!nameInput.dataset.userEdited || nameInput.value === nameInput.dataset.autoValue || !nameInput.value.trim())) {
            nameInput.value = uniqueNames[0];
            nameInput.dataset.autoValue = uniqueNames[0];
        }

        const tec = parsedByType.TEC?.entries?.length === 1 ? parsedByType.TEC.entries[0] : null;
        const tall = parsedByType.TALL?.entries?.length === 1 ? parsedByType.TALL.entries[0] : null;
        const preview = row.querySelector('[data-row-preview]');

        if (uniqueNames.length > 1) {
            preview.textContent = 'Los alias TEC y TALL tienen nombres distintos. Verifica que correspondan al mismo curso.';
            preview.classList.add('is-warning');
            preview.classList.remove('is-valid');
        } else if (tec && tall && tec.base !== tall.base) {
            preview.textContent = 'Los alias TEC y TALL deben pertenecer al mismo periodo, programa y número de curso.';
            preview.classList.add('is-warning');
            preview.classList.remove('is-valid');
        } else if (tec || tall) {
            preview.textContent = 'Código base '+(tec || tall).base+'. NRC detectado por separado para cada modalidad.';
            preview.classList.add('is-valid');
            preview.classList.remove('is-warning');
        } else if (preview) {
            preview.textContent = 'Pega el alias TEC o TALL para detectar sus datos.';
            preview.classList.remove('is-valid', 'is-warning');
        }
    };

    const updateScheduleCount = details => {
        const list = details.querySelector('[data-schedule-list]');
        const count = details.querySelector('[data-schedule-count]');
        if (count && list) count.textContent = list.querySelectorAll('[data-schedule-row]').length;
    };

    const bindRow = row => {
        row.querySelectorAll('[data-code-input]').forEach(input => {
            input.addEventListener('input', () => updateRow(row));
        });
        row.querySelector('[data-course-name]')?.addEventListener('input', event => {
            const input = event.currentTarget;
            if (input.value !== input.dataset.autoValue) input.dataset.userEdited = '1';
        });
        row.querySelector('.quick-remove-row')?.addEventListener('click', () => {
            if (body.querySelectorAll('[data-row]').length <= 1) return;
            row.remove();
            renumber();
        });
        row.querySelectorAll('.quick-schedule-editor').forEach(updateScheduleCount);
        updateRow(row);
    };

    const renumber = () => {
        body.querySelectorAll('[data-row]').forEach((row, index) => {
            const number = row.querySelector('.quick-row-number');
            if (number) number.textContent = index + 1;
        });
        if (addCourseButton) addCourseButton.disabled = body.querySelectorAll('[data-row]').length >= 60;
    };

    body.querySelectorAll('[data-row]').forEach(bindRow);

    addCourseButton?.addEventListener('click', () => {
        if (body.querySelectorAll('[data-row]').length >= 60) return;
        const index = Math.max(-1, ...[...body.querySelectorAll('[data-row]')].map(row => {
            const input = row.querySelector('[name]');
            const match = input?.name.match(/^rows\[(\d+)\]/);
            return match ? Number(match[1]) : -1;
        })) + 1;
        const html = courseTemplate.innerHTML.replaceAll('__INDEX__', String(index));
        body.insertAdjacentHTML('beforeend', html);
        bindRow(body.lastElementChild);
        renumber();
    });

    document.addEventListener('click', event => {
        const addSchedule = event.target.closest('.quick-add-schedule');
        if (addSchedule) {
            const editor = addSchedule.closest('.quick-schedule-editor');
            const list = editor.querySelector('[data-schedule-list]');
            const rowIndex = addSchedule.dataset.rowIndex;
            const prefix = addSchedule.dataset.prefix;
            const fieldPrefix = 'rows['+rowIndex+']['+prefix+'_schedules][';
            const existingIndices = [...list.querySelectorAll('[name]')].map(input => {
                if (!input.name.startsWith(fieldPrefix)) return -1;
                const match = input.name.slice(fieldPrefix.length).match(/^(\d+)\]/);
                return match ? Number(match[1]) : -1;
            });
            const scheduleIndex = Math.max(-1, ...existingIndices) + 1;
            const defaultInstruction = prefix === 'tec' ? 'THEORY' : 'LAB';
            const html = scheduleTemplate.innerHTML
                .replaceAll('__ROW_INDEX__', rowIndex)
                .replaceAll('__PREFIX__', prefix)
                .replaceAll('__SCHEDULE_INDEX__', String(scheduleIndex))
                .replaceAll('__DEFAULT_INSTRUCTION__', defaultInstruction);
            list.insertAdjacentHTML('beforeend', html);
            editor.open = true;
            updateScheduleCount(editor);
            return;
        }

        const removeSchedule = event.target.closest('.quick-schedule-remove');
        if (removeSchedule) {
            const editor = removeSchedule.closest('.quick-schedule-editor');
            removeSchedule.closest('[data-schedule-row]')?.remove();
            updateScheduleCount(editor);
        }
    });

    renumber();
})();
</script>
@endpush
