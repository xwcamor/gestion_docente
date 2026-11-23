@extends('layouts.app')
@section('title', 'Carga rápida de cursos')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</a><span>›</span><a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><span>›</span><span class="current">Carga rápida</span></div>
<div class="page-head">
    <div><p class="eyebrow">Registro de cursos · Captura rápida</p><h1>Registrar cursos</h1><p class="subhead">Digita tus cursos en una sola tabla, como en tu Excel. No necesitas importar ningún archivo.</p></div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}">← Volver al grupo</a>
</div>
@if($errors->any())
<div class="alert alert-error"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('assignments.quick.store', $group) }}" id="quick-course-form">
    @csrf
    <section class="card">
        <div class="card-head"><div><h2>{{ $group->name }} · {{ $group->cycle->academic_year }} / {{ $group->cycle->cycle_code }}</h2><p class="card-description">Una fila por curso. Marca TEC, TALL o ambas y completa los datos de cada modalidad.</p></div><span class="badge badge-green">Edición tipo Excel</span></div>
        <div class="alert alert-info">Los campos de fecha se aplican a las modalidades seleccionadas. Los días, horas y ambientes se configuran después en cada modalidad; con esos datos se calcula el calendario y se excluyen los feriados.</div>
        <div class="quick-table-wrap">
            <table class="quick-table">
                <thead><tr><th>#</th><th>Curso</th><th>Código</th><th>Modalidad</th><th>Bloque TEC</th><th>NRC TEC</th><th>Bloque taller (referencia)</th><th>Bloque TALL</th><th>NRC TALL</th><th>Desde</th><th>Hasta</th><th></th></tr></thead>
                <tbody id="course-rows">
                @for($i=0;$i<5;$i++)
                    <tr class="course-row">
                        <td class="row-number">{{ $i+1 }}</td>
                        <td><input class="field" name="rows[{{ $i }}][course_name]" value="{{ old("rows.$i.course_name") }}" placeholder="Nombre del curso"></td>
                        <td><input class="field mono" name="rows[{{ $i }}][course_code]" value="{{ old("rows.$i.course_code") }}" placeholder="Código"></td>
                        <td><select class="field modality-select" name="rows[{{ $i }}][modality]"><option value="">Elegir</option><option value="TEC" @selected(old("rows.$i.modality")==='TEC')>TEC</option><option value="TALL" @selected(old("rows.$i.modality")==='TALL')>TALL</option><option value="BOTH" @selected(old("rows.$i.modality")==='BOTH')>Ambas</option></select></td>
                        <td><input class="field mono" name="rows[{{ $i }}][tec_block]" value="{{ old("rows.$i.tec_block") }}" placeholder="Bloque TEC"></td>
                        <td><input class="field mono" name="rows[{{ $i }}][tec_nrc]" value="{{ old("rows.$i.tec_nrc") }}" placeholder="NRC"></td>
                        <td><input class="field mono" name="rows[{{ $i }}][tall_block_reference]" value="{{ old("rows.$i.tall_block_reference") }}" placeholder="Código completo"></td>
                        <td><input class="field mono" name="rows[{{ $i }}][tall_block]" value="{{ old("rows.$i.tall_block") }}" placeholder="Bloque TALL"></td>
                        <td><input class="field mono" name="rows[{{ $i }}][tall_nrc]" value="{{ old("rows.$i.tall_nrc") }}" placeholder="NRC"></td>
                        <td><input class="field date-field" type="date" name="rows[{{ $i }}][start_date]" value="{{ old("rows.$i.start_date") }}"></td>
                        <td><input class="field date-field" type="date" name="rows[{{ $i }}][end_date]" value="{{ old("rows.$i.end_date") }}"></td>
                        <td><button class="btn btn-ghost btn-sm remove-row" type="button" title="Quitar fila" aria-label="Quitar fila">×</button></td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </div>
        <div class="quick-actions"><button class="btn btn-secondary" type="button" id="add-row">＋ Agregar fila</button><span class="help">Puedes registrar varios cursos antes de guardar.</span></div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('groups.show', $group) }}">Cancelar</a><button class="btn" type="submit">Guardar cursos</button></div>
    </section>
</form>
@endsection
@push('styles')
<style>
.quick-table-wrap{overflow-x:auto;margin-top:14px}
.quick-table{min-width:1500px;width:100%;border-collapse:separate;border-spacing:0;font-size:12px}
.quick-table th{position:sticky;top:0;background:#f7f8f6;color:#566158;text-align:left;padding:10px 8px;border-bottom:1px solid #e2e6e0;white-space:nowrap}
.quick-table td{padding:7px 5px;border-bottom:1px solid #edf0eb;vertical-align:middle}
.quick-table .field{min-width:105px;font-size:12px;padding:9px 8px}
.quick-table td:nth-child(2) .field{min-width:230px}
.quick-table td:nth-child(3) .field{min-width:170px}
.quick-table td:nth-child(4) .field{min-width:95px}
.quick-table .date-field{min-width:135px}
.quick-table .row-number{color:#667085;font-weight:700;text-align:center}
.quick-actions{display:flex;align-items:center;gap:12px;margin-top:14px}
@media(max-width:700px){.quick-actions{align-items:flex-start;flex-direction:column}}

/* En pantallas pequeñas, cada fila de captura se convierte en una ficha editable */
@media(max-width:760px){
 .quick-table-wrap{overflow:visible;margin-top:12px}
 .quick-table{min-width:0;width:100%;display:block;border:0}
 .quick-table thead{display:none}
 .quick-table tbody{display:grid;grid-template-columns:minmax(0,1fr);gap:12px}
 .quick-table tr.course-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;padding:13px;border:1px solid #e3e7e0;border-radius:8px;background:#fff;box-shadow:none}
 .quick-table td{display:flex;flex-direction:column;gap:5px;padding:0;border:0;min-width:0}
 .quick-table td:before{font-size:10px;font-weight:750;letter-spacing:.35px;text-transform:uppercase;color:#778178}
 .quick-table td:nth-child(1){grid-column:1/-1;display:none}
 .quick-table td:nth-child(2){grid-column:1/-1}
 .quick-table td:nth-child(2):before{content:"Curso"}
 .quick-table td:nth-child(3):before{content:"Código"}
 .quick-table td:nth-child(4):before{content:"Modalidad"}
 .quick-table td:nth-child(5):before{content:"Bloque TEC"}
 .quick-table td:nth-child(6):before{content:"NRC TEC"}
 .quick-table td:nth-child(7){grid-column:1/-1}
 .quick-table td:nth-child(7):before{content:"Referencia bloque TALL"}
 .quick-table td:nth-child(8):before{content:"Bloque TALL"}
 .quick-table td:nth-child(9):before{content:"NRC TALL"}
 .quick-table td:nth-child(10):before{content:"Desde"}
 .quick-table td:nth-child(11):before{content:"Hasta"}
 .quick-table td:nth-child(12){grid-column:1/-1;align-items:flex-end}
 .quick-table td:nth-child(12) .btn{width:100%}
 .quick-table td:nth-child(2) .field,.quick-table td:nth-child(3) .field,.quick-table .field,.quick-table .date-field{min-width:0;width:100%;font-size:13px}
 .quick-actions{position:sticky;bottom:8px;z-index:5;padding:10px;background:#fff;border:1px solid #e3e7e0;border-radius:8px;box-shadow:none}
 .quick-actions .btn{width:100%}
}
@media(max-width:380px){.quick-table tr.course-row{grid-template-columns:minmax(0,1fr)}.quick-table td:nth-child(n){grid-column:1/-1}}

</style>
@endpush
@push('scripts')
<script>
(() => {
    const body=document.getElementById('course-rows');
    const add=document.getElementById('add-row');
    const renumber=()=>[...body.querySelectorAll('tr')].forEach((row,i)=>row.querySelector('.row-number').textContent=i+1);
    const makeRow=(i)=> {
        const tr=document.createElement('tr');tr.className='course-row';
        const fields=[
            ['course_name','text','Nombre del curso'],['course_code','text','Código'],['modality','select',''],
            ['tec_block','text','Bloque TEC'],['tec_nrc','text','NRC'],['tall_block_reference','text','Código completo'],
            ['tall_block','text','Bloque TALL'],['tall_nrc','text','NRC'],['start_date','date',''],['end_date','date','']
        ];
        tr.innerHTML='<td class="row-number">'+(i+1)+'</td>'+fields.map(([name,type,placeholder])=>{
            const inputName='rows['+i+']['+name+']';
            if(name==='modality')return '<td><select class="field modality-select" name="'+inputName+'"><option value="">Elegir</option><option value="TEC">TEC</option><option value="TALL">TALL</option><option value="BOTH">Ambas</option></select></td>';
            return '<td><input class="field '+(type==='date'?'date-field':'mono')+'" type="'+type+'" name="'+inputName+'" placeholder="'+placeholder+'"></td>';
        }).join('')+'<td><button class="btn btn-ghost btn-sm remove-row" type="button" aria-label="Quitar fila">×</button></td>';
        return tr;
    };
    add.addEventListener('click',()=>{body.appendChild(makeRow(body.querySelectorAll('tr').length));renumber();});
    body.addEventListener('click',e=>{const btn=e.target.closest('.remove-row');if(!btn)return;if(body.querySelectorAll('tr').length>1){btn.closest('tr').remove();renumber();}});
})();
</script>
@endpush
