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
    <div><p class="eyebrow">Registro académico · {{ $group->name }}</p><h1>Carga rápida de cursos</h1><p class="subhead">Registra varios cursos en una tabla. Cada fila usa los mismos tres datos del registro individual.</p></div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>
@if($errors->any())
    <div class="alert alert-error" role="alert"><strong>Revisa los cursos antes de guardar.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('assignments.quick.store', $group) }}" id="quick-course-form" data-loading-form="1">
    @csrf
    <section class="card quick-register-card">
        <div class="card-head">
            <div><h2>{{ $group->name }} · {{ $group->cycle->academic_year }} / {{ $group->cycle->cycle_code }}</h2><p class="card-description">Código completo, nombre y componente. No ingreses NRC, bloques, fechas ni horarios por separado.</p></div>
            <span class="badge badge-blue">Hasta 60 cursos</span>
        </div>
        <div class="alert alert-info quick-code-help">
            <strong>Interpretación automática:</strong> <code>202620-PIAD-224-TAL-NRC_11524</code> detecta periodo, programa, curso, Taller y NRC 11524.
            Para ambos componentes con NRC diferentes, pega ambos códigos en la misma celda separados por punto y coma.
        </div>
        @php($rows = old('rows', array_fill(0, 5, [])))
        <div class="quick-course-table-wrap">
            <table class="quick-course-table">
                <thead><tr><th>#</th><th>Código completo</th><th>Nombre del curso</th><th>Qué se dicta</th><th></th></tr></thead>
                <tbody id="quick-course-rows">
                    @foreach($rows as $index => $row)
                        <tr class="quick-course-row" data-row>
                            <td class="quick-row-number">{{ $index + 1 }}</td>
                            <td>
                                <input class="field mono quick-code-input" name="rows[{{ $index }}][course_code]" value="{{ $row['course_code'] ?? '' }}" maxlength="200" placeholder="202620-PIAD-224-TAL-NRC_11524" aria-label="Código completo del curso">
                                <small class="quick-code-preview" data-code-preview aria-live="polite"></small>
                            </td>
                            <td><input class="field" name="rows[{{ $index }}][course_name]" value="{{ $row['course_name'] ?? '' }}" maxlength="255" placeholder="Nombre del curso" aria-label="Nombre del curso"></td>
                            <td>
                                <select class="field quick-component-select" name="rows[{{ $index }}][component]" data-manual="{{ !empty($row['component']) ? '1' : '0' }}" aria-label="Componente del curso">
                                    <option value="">Detectar del código</option>
                                    <option value="TEC" @selected(($row['component'] ?? '') === 'TEC')>Teoría (TEC)</option>
                                    <option value="TALL" @selected(($row['component'] ?? '') === 'TALL')>Taller (TAL)</option>
                                    <option value="BOTH" @selected(($row['component'] ?? '') === 'BOTH')>Ambos</option>
                                </select>
                            </td>
                            <td><button class="btn btn-ghost btn-sm quick-remove-row" type="button" aria-label="Quitar curso"><x-icon name="x" size="13" /></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('rows')<p class="field-error">{{ $message }}</p>@enderror
        <div class="quick-register-actions"><button class="btn btn-secondary" type="button" id="quick-add-row"><x-icon name="plus" /> Agregar fila</button><span class="help">El código propone TEC, TALL o Ambos. Puedes corregir la selección.</span></div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('groups.show', $group) }}">Cancelar</a><button class="btn" type="submit"><x-icon name="check" /> Guardar cursos</button></div>
    </section>
</form>
@endsection

@push('styles')
<style>
.quick-register-card{padding:18px}.quick-code-help{line-height:1.6}.quick-code-help code{font-size:11px}
.quick-course-table-wrap{overflow-x:auto;margin-top:14px}
.quick-course-table{width:100%;min-width:830px;border-collapse:separate;border-spacing:0;font-size:12px}
.quick-course-table th{padding:10px 9px;background:var(--surface-soft);color:var(--muted);text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.35px;border-bottom:1px solid var(--line)}
.quick-course-table td{padding:9px 7px;border-bottom:1px solid var(--line);vertical-align:top}
.quick-course-table .field{min-width:0;width:100%;font-size:12px}
.quick-course-table td:nth-child(2){width:34%}.quick-course-table td:nth-child(3){width:31%}.quick-course-table td:nth-child(4){width:20%}
.quick-row-number{padding-top:17px!important;color:var(--muted);font-weight:750;text-align:center}
.quick-code-preview{display:block;min-height:14px;margin-top:5px;color:var(--muted);font-size:9px;line-height:1.4}
.quick-code-preview.is-warning{color:var(--warning)}
.quick-register-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px}
@media(max-width:760px){
 .quick-course-table-wrap{overflow:visible}.quick-course-table{display:block;min-width:0}.quick-course-table thead{display:none}
 .quick-course-table tbody{display:grid;gap:10px}
 .quick-course-table tr{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:9px;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface)}
 .quick-course-table td{display:grid;gap:5px;width:auto!important;padding:0;border:0;min-width:0}
 .quick-course-table td:nth-child(1){display:none}
 .quick-course-table td:nth-child(2){grid-column:1/-1}
 .quick-course-table td:nth-child(2):before{content:"Código completo";font-size:10px;font-weight:750;color:var(--muted)}
 .quick-course-table td:nth-child(3):before{content:"Nombre del curso";font-size:10px;font-weight:750;color:var(--muted)}
 .quick-course-table td:nth-child(4):before{content:"Qué se dicta";font-size:10px;font-weight:750;color:var(--muted)}
 .quick-course-table td:nth-child(5){grid-column:1/-1;justify-items:end}
 .quick-course-table td:nth-child(5) .btn{width:100%}
}
@media(max-width:420px){.quick-course-table tr{grid-template-columns:1fr}.quick-course-table td:nth-child(2),.quick-course-table td:nth-child(5){grid-column:1}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const body=document.getElementById('quick-course-rows');
    const addButton=document.getElementById('quick-add-row');
    if(!body||!addButton)return;

    let nextIndex=Math.max(-1,...[...body.querySelectorAll('[name]')].map(input=>{
        const match=input.name.match(/^rows\[(\d+)\]/);
        return match?Number(match[1]):-1;
    }))+1;

    const parseCode=value=>{
        const pattern=/(?:(20\d{4})-)?([A-Z][A-Z0-9]{1,9})-([A-Z0-9]{1,12})-(TEC|TALL|TAL)(?:[-_\s]*NRC[_\s-]*(\d{3,12}))?/gi;
        const entries=[...value.matchAll(pattern)].map(match=>({
            base:[match[1],match[2],match[3]].filter(Boolean).join('-'),
            period:match[1]||'',
            program:match[2],
            course:match[3],
            type:match[4].toUpperCase()==='TAL'?'TALL':match[4].toUpperCase(),
            nrc:match[5]||''
        }));
        return {entries,bases:[...new Set(entries.map(item=>item.base))],types:[...new Set(entries.map(item=>item.type))]};
    };

    const updateRow=row=>{
        const code=row.querySelector('.quick-code-input');
        const select=row.querySelector('.quick-component-select');
        const preview=row.querySelector('[data-code-preview]');
        const result=parseCode(code.value);

        if(!result.entries.length){
            preview.textContent=code.value.trim()?'No se detectó TEC/TAL; selecciona el componente.':'';
            preview.classList.remove('is-warning');
            if(select.dataset.manual!=='1')select.value='';
            return;
        }

        const labels=result.entries.map(item=>item.type+(item.nrc?' · NRC '+item.nrc:''));
        const first=result.entries[0];
        const details=[];
        if(first.period)details.push('Periodo '+first.period);
        details.push('Programa '+first.program,'Curso '+first.course);
        preview.textContent=details.join(' · ')+' · Detectado: '+[...new Set(labels)].join(' / ');
        preview.classList.toggle('is-warning',result.bases.length>1);
        if(result.bases.length>1)preview.textContent+=' · Revisa que sean códigos del mismo curso.';

        if(select.dataset.manual!=='1'&&result.bases.length===1){
            select.value=result.types.length>1?'BOTH':result.types[0];
        }
    };

    const bindRow=row=>{
        const code=row.querySelector('.quick-code-input');
        const select=row.querySelector('.quick-component-select');
        code.addEventListener('input',()=>updateRow(row));
        select.addEventListener('change',()=>{
            select.dataset.manual=select.value?'1':'0';
            updateRow(row);
        });
        if(select.value)select.dataset.manual='1';
        updateRow(row);
    };

    const renumber=()=>[...body.querySelectorAll('[data-row]')].forEach((row,index)=>{
        row.querySelector('.quick-row-number').textContent=index+1;
    });

    body.querySelectorAll('[data-row]').forEach(bindRow);
    addButton.addEventListener('click',()=>{
        if(body.querySelectorAll('[data-row]').length>=60)return;
        const index=nextIndex++;
        const row=document.createElement('tr');
        row.className='quick-course-row';
        row.dataset.row='';

        const number=document.createElement('td');
        number.className='quick-row-number';
        number.textContent=body.querySelectorAll('[data-row]').length+1;

        const codeCell=document.createElement('td');
        codeCell.innerHTML='<input class="field mono quick-code-input" name="rows['+index+'][course_code]" maxlength="200" placeholder="202620-PIAD-224-TAL-NRC_11524" aria-label="Código completo del curso"><small class="quick-code-preview" data-code-preview aria-live="polite"></small>';

        const nameCell=document.createElement('td');
        nameCell.innerHTML='<input class="field" name="rows['+index+'][course_name]" maxlength="255" placeholder="Nombre del curso" aria-label="Nombre del curso">';

        const componentCell=document.createElement('td');
        componentCell.innerHTML='<select class="field quick-component-select" name="rows['+index+'][component]" data-manual="0" aria-label="Componente del curso"><option value="">Detectar del código</option><option value="TEC">Teoría (TEC)</option><option value="TALL">Taller (TAL)</option><option value="BOTH">Ambos</option></select>';

        const actionsCell=document.createElement('td');
        actionsCell.innerHTML='<button class="btn btn-ghost btn-sm quick-remove-row" type="button" aria-label="Quitar curso">× Quitar</button>';

        row.append(number,codeCell,nameCell,componentCell,actionsCell);
        body.appendChild(row);
        bindRow(row);
        renumber();
    });

    body.addEventListener('click',event=>{
        const button=event.target.closest('.quick-remove-row');
        if(!button||body.querySelectorAll('[data-row]').length<=1)return;
        button.closest('[data-row]').remove();
        renumber();
    });
})();
</script>
@endpush
