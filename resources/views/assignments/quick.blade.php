@extends('layouts.app')
@section('title', 'Carga rápida de cursos')
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('cycles.index') }}">Ciclos</a><x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</a><x-icon name="chevron-right" size="12" />
    <a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><x-icon name="chevron-right" size="12" />
    <span class="current">Carga rápida</span>
</div>

<div class="page-head">
    <div>
        <p class="eyebrow">Registro académico · {{ $group->name }}</p>
        <h1>Carga rápida de cursos</h1>
        <p class="subhead">Registra cada curso una sola vez y configura por separado sus componentes de teoría y taller.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Revisa los datos antes de guardar.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('assignments.quick.store', $group) }}" id="quick-course-form" data-loading-form="1">
    @csrf
    <section class="card quick-register-card">
        <div class="card-head">
            <div>
                <h2>{{ $group->name }} · {{ $group->cycle->academic_year }} / {{ $group->cycle->cycle_code }}</h2>
                <p class="card-description">Una fila por curso. TEC y TAL pueden tener códigos NRC y periodos diferentes.</p>
            </div>
            <span class="badge badge-blue">Hasta 60 cursos</span>
        </div>

        <div class="alert alert-info quick-code-help">
            <x-icon name="info" size="16" />
            <div>
                <strong>Cómo ingresar los cursos</strong>
                <p>Coloca el código completo en su componente correspondiente. Un curso puede tener solo TEC, solo TAL o ambos. Las fechas Desde/Hasta se guardan por separado para cada modalidad; puedes dejarlas vacías y completarlas después.</p>
                <p class="quick-code-example">
                    <code>202620-PIAD-222-TEC-NRC_11518</code>
                    <span>y</span>
                    <code>202620-PIAD-222-TAL-NRC_11520</code>
                    <span>son dos códigos del mismo curso.</span>
                </p>
            </div>
        </div>

        @php($rows = old('rows', array_fill(0, 5, [])))
        <div class="quick-course-table-wrap">
            <table class="quick-course-table">
                <thead>
                    <tr>
                        <th class="quick-number-heading">#</th>
                        <th class="quick-course-heading">Curso</th>
                        <th class="quick-modality-heading quick-tec-heading">Teoría (TEC)</th>
                        <th class="quick-modality-heading quick-tall-heading">Taller (TAL)</th>
                        <th class="quick-actions-heading" aria-label="Acciones"></th>
                    </tr>
                </thead>
                <tbody id="quick-course-rows">
                    @foreach($rows as $index => $row)
                        <tr class="quick-course-row" data-row>
                            <td class="quick-row-number">{{ $index + 1 }}</td>
                            <td class="quick-course-name-cell">
                                <label class="quick-field-label">
                                    <span>Curso</span>
                                    <input class="field" name="rows[{{ $index }}][course_name]" value="{{ $row['course_name'] ?? '' }}" maxlength="255" placeholder="Nombre del curso" aria-label="Nombre del curso">
                                </label>
                                <small class="quick-row-preview" data-row-preview aria-live="polite">Ingresa uno o ambos componentes.</small>
                                @error("rows.$index.course_name")<small class="field-error">{{ $message }}</small>@enderror
                                @error("rows.$index.row_error")<small class="field-error quick-row-error">{{ $message }}</small>@enderror
                            </td>
                            <td class="quick-modality-cell">
                                <div class="quick-modality-fields">
                                    <label class="quick-field-label quick-code-field">
                                        <span>Código institucional TEC</span>
                                        <input class="field mono quick-modality-code" data-code-input data-modality="TEC" name="rows[{{ $index }}][tec_code]" value="{{ $row['tec_code'] ?? '' }}" maxlength="200" placeholder="202620-PIAD-222-TEC-NRC_11518" aria-label="Código completo TEC">
                                        <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                                        @error("rows.$index.tec_code")<small class="field-error">{{ $message }}</small>@enderror
                                    </label>
                                    <div class="quick-date-fields">
                                        <label class="quick-field-label">
                                            <span>Desde</span>
                                            <input class="field" type="date" name="rows[{{ $index }}][tec_start_date]" value="{{ $row['tec_start_date'] ?? '' }}" aria-label="Fecha Desde de TEC">
                                            @error("rows.$index.tec_start_date")<small class="field-error">{{ $message }}</small>@enderror
                                        </label>
                                        <label class="quick-field-label">
                                            <span>Hasta</span>
                                            <input class="field" type="date" name="rows[{{ $index }}][tec_end_date]" value="{{ $row['tec_end_date'] ?? '' }}" aria-label="Fecha Hasta de TEC">
                                            @error("rows.$index.tec_end_date")<small class="field-error">{{ $message }}</small>@enderror
                                        </label>
                                    </div>
                                </div>
                            </td>
                            <td class="quick-modality-cell">
                                <div class="quick-modality-fields">
                                    <label class="quick-field-label quick-code-field">
                                        <span>Código institucional TAL</span>
                                        <input class="field mono quick-modality-code" data-code-input data-modality="TALL" name="rows[{{ $index }}][tall_code]" value="{{ $row['tall_code'] ?? '' }}" maxlength="200" placeholder="202620-PIAD-222-TAL-NRC_11520" aria-label="Código completo TAL">
                                        <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                                        @error("rows.$index.tall_code")<small class="field-error">{{ $message }}</small>@enderror
                                    </label>
                                    <div class="quick-date-fields">
                                        <label class="quick-field-label">
                                            <span>Desde</span>
                                            <input class="field" type="date" name="rows[{{ $index }}][tall_start_date]" value="{{ $row['tall_start_date'] ?? '' }}" aria-label="Fecha Desde de TAL">
                                            @error("rows.$index.tall_start_date")<small class="field-error">{{ $message }}</small>@enderror
                                        </label>
                                        <label class="quick-field-label">
                                            <span>Hasta</span>
                                            <input class="field" type="date" name="rows[{{ $index }}][tall_end_date]" value="{{ $row['tall_end_date'] ?? '' }}" aria-label="Fecha Hasta de TAL">
                                            @error("rows.$index.tall_end_date")<small class="field-error">{{ $message }}</small>@enderror
                                        </label>
                                    </div>
                                </div>
                            </td>
                            <td class="quick-remove-cell">
                                <button class="btn btn-ghost btn-sm quick-remove-row" type="button" aria-label="Quitar curso"><x-icon name="x" size="13" /></button>
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
                        <span>Curso</span>
                        <input class="field" name="rows[__INDEX__][course_name]" maxlength="255" placeholder="Nombre del curso" aria-label="Nombre del curso">
                    </label>
                    <small class="quick-row-preview" data-row-preview aria-live="polite">Ingresa uno o ambos componentes.</small>
                </td>
                <td class="quick-modality-cell">
                    <div class="quick-modality-fields">
                        <label class="quick-field-label quick-code-field">
                            <span>Código institucional TEC</span>
                            <input class="field mono quick-modality-code" data-code-input data-modality="TEC" name="rows[__INDEX__][tec_code]" maxlength="200" placeholder="202620-PIAD-222-TEC-NRC_11518" aria-label="Código completo TEC">
                            <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                        </label>
                        <div class="quick-date-fields">
                            <label class="quick-field-label"><span>Desde</span><input class="field" type="date" name="rows[__INDEX__][tec_start_date]" aria-label="Fecha Desde de TEC"></label>
                            <label class="quick-field-label"><span>Hasta</span><input class="field" type="date" name="rows[__INDEX__][tec_end_date]" aria-label="Fecha Hasta de TEC"></label>
                        </div>
                    </div>
                </td>
                <td class="quick-modality-cell">
                    <div class="quick-modality-fields">
                        <label class="quick-field-label quick-code-field">
                            <span>Código institucional TAL</span>
                            <input class="field mono quick-modality-code" data-code-input data-modality="TALL" name="rows[__INDEX__][tall_code]" maxlength="200" placeholder="202620-PIAD-222-TAL-NRC_11520" aria-label="Código completo TAL">
                            <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                        </label>
                        <div class="quick-date-fields">
                            <label class="quick-field-label"><span>Desde</span><input class="field" type="date" name="rows[__INDEX__][tall_start_date]" aria-label="Fecha Desde de TAL"></label>
                            <label class="quick-field-label"><span>Hasta</span><input class="field" type="date" name="rows[__INDEX__][tall_end_date]" aria-label="Fecha Hasta de TAL"></label>
                        </div>
                    </div>
                </td>
                <td class="quick-remove-cell">
                    <button class="btn btn-ghost btn-sm quick-remove-row" type="button" aria-label="Quitar curso"><x-icon name="x" size="13" /></button>
                </td>
            </tr>
        </template>

        @error('rows')<p class="field-error">{{ $message }}</p>@enderror

        <div class="quick-register-actions">
            <button class="btn btn-secondary" type="button" id="quick-add-row"><x-icon name="plus" /> Agregar curso</button>
            <span class="help">Cada fila admite un código TEC y un código TAL. Los NRC pueden ser diferentes.</span>
        </div>

        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}">Cancelar</a>
            <button class="btn" type="submit"><x-icon name="check" /> Guardar cursos</button>
        </div>
    </section>
</form>
@endsection

@push('styles')
<style>
.quick-register-card{padding:18px}
.quick-code-help{align-items:flex-start;line-height:1.55}
.quick-code-help>div{min-width:0}
.quick-code-help p{margin:3px 0 0}
.quick-code-example{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.quick-code-example code{font-size:10px}
.quick-course-table-wrap{overflow-x:auto;margin-top:14px;border:1px solid var(--line);border-radius:10px}
.quick-course-table{width:100%;min-width:1040px;table-layout:fixed;border-collapse:separate;border-spacing:0;font-size:12px}
.quick-course-table th{padding:12px 11px;background:var(--surface-soft);color:var(--muted);text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.3px;border-bottom:1px solid var(--line);white-space:nowrap}
.quick-course-table th.quick-modality-heading{text-align:center;color:var(--info);background:var(--info-bg);font-size:11px}
.quick-course-table th.quick-tec-heading{border-left:1px solid var(--line)}
.quick-course-table th.quick-tall-heading{border-left:1px solid var(--line)}
.quick-course-table td{padding:13px 11px;border-bottom:1px solid var(--line);vertical-align:top}
.quick-course-table tbody tr:last-child td{border-bottom:0}
.quick-course-table .field{min-width:0;width:100%;font-size:11px}
.quick-number-heading,.quick-row-number{width:38px;text-align:center}
.quick-row-number{padding-top:20px!important;color:var(--muted);font-weight:750}
.quick-course-heading{width:250px}
.quick-course-name-cell{width:250px}
.quick-field-label{display:grid;gap:5px;min-width:0}
.quick-field-label>span{color:var(--muted);font-size:10px;font-weight:700}
.quick-field-label .field{min-height:38px;padding:8px 10px}
.quick-modality-cell{border-left:1px solid var(--line)}
.quick-modality-fields{display:grid;grid-template-columns:minmax(0,1fr);gap:9px;min-width:0}
.quick-code-field{min-width:0}
.quick-code-field .field{min-width:0;font-size:11px}
.quick-date-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
.quick-date-fields .field{min-width:0}
.quick-code-preview,.quick-row-preview{display:block;min-height:14px;margin-top:4px;color:var(--muted);font-size:10px;line-height:1.45}
.quick-code-preview.is-warning,.quick-row-preview.is-warning,.quick-row-error{color:var(--danger)}
.quick-row-preview.is-valid{color:var(--info)}
.quick-remove-cell{width:46px;text-align:center}
.quick-remove-cell .btn{margin-top:1px}
.quick-row-error{display:block;margin-top:8px;padding:7px 9px;border:1px solid color-mix(in srgb,var(--danger) 22%,var(--line));border-radius:7px;background:var(--danger-bg);font-size:10px;line-height:1.45}
.quick-register-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px}
.quick-register-actions .help{max-width:70ch}
@media(max-width:1100px){
 .quick-course-table-wrap{overflow:visible;border:0}
 .quick-course-table{display:block;min-width:0;table-layout:auto}
 .quick-course-table thead{display:none}
 .quick-course-table tbody{display:grid;gap:12px}
 .quick-course-table tr{display:grid;grid-template-columns:minmax(0,1fr);gap:11px;padding:14px;border:1px solid var(--line);border-radius:10px;background:var(--surface)}
 .quick-course-table td{display:block;width:auto!important;min-width:0;padding:0;border:0}
 .quick-course-table td.quick-row-number{display:none}
 .quick-course-table td.quick-course-name-cell{grid-column:1}
 .quick-course-table td.quick-modality-cell{grid-column:1;padding-top:11px;border-top:1px solid var(--line)}
 .quick-course-table td.quick-modality-cell:before{display:block;margin-bottom:9px;padding:6px 8px;border-radius:6px;background:var(--info-bg);color:var(--info);font-size:10px;font-weight:800}
 .quick-course-table td.quick-modality-cell:nth-of-type(3):before{content:"TEORÍA · TEC"}
 .quick-course-table td.quick-modality-cell:nth-of-type(4):before{content:"TALLER · TAL"}
 .quick-modality-fields{grid-template-columns:minmax(0,1fr);gap:9px}
 .quick-code-field .field{min-width:0}
 .quick-course-table td.quick-remove-cell{display:flex;justify-content:flex-end}
}
@media(max-width:560px){
 .quick-register-card{padding:13px}
 .quick-date-fields{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
 .quick-code-example{align-items:flex-start;flex-direction:column;gap:3px}
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
    const addButton = document.getElementById('quick-add-row');
    const template = document.getElementById('quick-course-row-template');
    if (!body || !addButton || !template) return;

    let nextIndex = Math.max(-1, ...[...body.querySelectorAll('[name]')].map(input => {
        const match = input.name.match(/^rows\[(\d+)\]/);
        return match ? Number(match[1]) : -1;
    })) + 1;

    const parseCode = value => {
        const pattern = /(?:(20\d{4})-)?([A-Z][A-Z0-9]{1,9})-([A-Z0-9]{1,12})-(TEC|TALL|TAL)(?:[-_\s]*NRC[_\s-]*(\d{3,12}))?/gi;
        const entries = [...value.matchAll(pattern)].map(match => ({
            base: [match[1], match[2], match[3]].filter(Boolean).join('-'),
            period: match[1] || '',
            program: match[2],
            course: match[3],
            type: match[4].toUpperCase() === 'TAL' ? 'TALL' : match[4].toUpperCase(),
            nrc: match[5] || ''
        }));
        return { entries };
    };

    const updateRow = row => {
        const inputs = [...row.querySelectorAll('[data-code-input]')];
        const parsed = {};

        inputs.forEach(input => {
            const preview = input.closest('.quick-field-label').querySelector('[data-code-preview]');
            const expected = input.dataset.modality;
            const result = parseCode(input.value);
            parsed[expected] = result;

            if (!input.value.trim()) {
                preview.textContent = '';
                preview.classList.remove('is-warning');
                return;
            }

            if (!result.entries.length) {
                preview.textContent = 'No se reconoció el código institucional.';
                preview.classList.add('is-warning');
                return;
            }

            if (result.entries.length !== 1 || result.entries[0].type !== expected) {
                preview.textContent = expected === 'TEC'
                    ? 'Ingresa un único código de teoría (TEC).'
                    : 'Ingresa un único código de taller (TAL/TALL).';
                preview.classList.add('is-warning');
                return;
            }

            const entry = result.entries[0];
            preview.textContent = [
                entry.period ? 'Periodo ' + entry.period : '',
                'Programa ' + entry.program,
                'Curso ' + entry.course,
                entry.nrc ? 'NRC ' + entry.nrc : 'Sin NRC'
            ].filter(Boolean).join(' · ');
            preview.classList.remove('is-warning');
        });

        const rowPreview = row.querySelector('[data-row-preview]');
        const tecEntry = parsed.TEC?.entries?.length === 1 ? parsed.TEC.entries[0] : null;
        const tallEntry = parsed.TALL?.entries?.length === 1 ? parsed.TALL.entries[0] : null;
        const tec = tecEntry?.type === 'TEC' ? tecEntry : null;
        const tall = tallEntry?.type === 'TALL' ? tallEntry : null;

        if (tec && tall && tec.base !== tall.base) {
            rowPreview.textContent = 'Los códigos TEC y TAL deben pertenecer al mismo periodo, programa y curso.';
            rowPreview.classList.add('is-warning');
            rowPreview.classList.remove('is-valid');
        } else if (tec && tall) {
            rowPreview.textContent = 'TEC y TAL identifican el mismo curso. Cada uno conserva su NRC y sus fechas.';
            rowPreview.classList.add('is-valid');
            rowPreview.classList.remove('is-warning');
        } else {
            rowPreview.textContent = 'Ingresa TEC, TAL o ambos componentes.';
            rowPreview.classList.remove('is-warning', 'is-valid');
        }
    };

    const bindRow = row => {
        row.querySelectorAll('[data-code-input]').forEach(input => {
            input.addEventListener('input', () => updateRow(row));
        });
        row.querySelector('.quick-remove-row')?.addEventListener('click', () => {
            if (body.querySelectorAll('[data-row]').length <= 1) return;
            row.remove();
            renumber();
        });
        updateRow(row);
    };

    const renumber = () => {
        body.querySelectorAll('[data-row]').forEach((row, index) => {
            row.querySelector('.quick-row-number').textContent = index + 1;
        });
        addButton.disabled = body.querySelectorAll('[data-row]').length >= 60;
    };

    body.querySelectorAll('[data-row]').forEach(bindRow);
    addButton.addEventListener('click', () => {
        if (body.querySelectorAll('[data-row]').length >= 60) return;
        const index = nextIndex++;
        const rowHtml = template.innerHTML.replaceAll('__INDEX__', String(index));
        body.insertAdjacentHTML('beforeend', rowHtml);
        bindRow(body.lastElementChild);
        renumber();
    });

    renumber();
})();
</script>
@endpush
