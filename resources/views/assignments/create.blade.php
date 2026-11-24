@extends('layouts.app')
@section('title', 'Agregar curso')
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('cycles.index') }}">Ciclos</a><x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</a><x-icon name="chevron-right" size="12" />
    <a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><x-icon name="chevron-right" size="12" />
    <span class="current">Nuevo curso</span>
</div>
<div class="page-head">
    <div><p class="eyebrow">Registro académico · {{ $group->name }}</p><h1>Agregar curso</h1><p class="subhead">Ingresa el código, el nombre y qué componentes se dictan. TEC, TAL y NRC se detectan automáticamente.</p></div>
    <a class="btn btn-secondary" href="{{ route('groups.show', $group) }}"><x-icon name="arrow-left" /> Volver al grupo</a>
</div>
@if($errors->any())
    <div class="alert alert-error" role="alert"><strong>No se pudo registrar el curso.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ route('assignments.store', $group) }}" id="course-register-form" data-loading-form="1">
    @csrf
    <section class="card course-register-card">
        <div class="course-register-fields">
            <div>
                <label for="course_code">1. Código completo del curso</label>
                <input class="field mono" id="course_code" name="course_code" value="{{ old('course_code') }}" maxlength="200" required autocomplete="off" placeholder="202620-PIAD-224-TAL-NRC_11524">
                <p class="help">Pega el código institucional. Para registrar ambos NRC en un solo ingreso, separa los códigos TEC y TAL con punto y coma.</p>
                <div class="course-code-preview" id="course-code-preview" aria-live="polite">El código se interpretará aquí automáticamente.</div>
                @error('course_code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="course_name">2. Nombre del curso</label>
                <input class="field" id="course_name" name="course_name" value="{{ old('course_name') }}" required maxlength="255" placeholder="Ej. Sistemas Operativos y Comunicación de Datos">
                @error('course_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <fieldset class="course-component-field">
                <legend>3. ¿Qué componentes se dictan?</legend>
                <div class="course-component-options">
                    <label class="course-component-option"><input type="radio" name="component" value="TEC" @checked(old('component') === 'TEC')><span class="course-component-icon"><x-icon name="book-open" size="18" /></span><span><strong>Teoría</strong><small>TEC</small></span></label>
                    <label class="course-component-option"><input type="radio" name="component" value="TALL" @checked(old('component') === 'TALL')><span class="course-component-icon"><x-icon name="wrench" size="18" /></span><span><strong>Taller</strong><small>TAL / TALL</small></span></label>
                    <label class="course-component-option"><input type="radio" name="component" value="BOTH" @checked(old('component') === 'BOTH')><span class="course-component-icon"><x-icon name="layers" size="18" /></span><span><strong>Ambos</strong><small>TEC + TALL</small></span></label>
                </div>
                <p class="help">El código propone la selección; puedes corregirla manualmente.</p>
                @error('component')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>
        </div>
        <div class="course-register-note"><x-icon name="info" size="16" /><span>Se crea el curso y sus modalidades. Las fechas, días y horarios se configuran después dentro de cada modalidad. Si el código no incluye un NRC, podrás completarlo allí.</span></div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('groups.show', $group) }}">Cancelar</a><button class="btn" type="submit"><x-icon name="check" /> Guardar curso</button></div>
    </section>
</form>
@endsection

@push('styles')
<style>
.course-register-card{max-width:900px}.course-register-fields{display:grid;gap:20px}
.course-code-preview{margin-top:8px;padding:9px 11px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:11px;line-height:1.5}
.course-code-preview.is-warning{border-color:var(--warning);color:var(--warning)}
.course-component-field{margin:0;padding:0;border:0;min-width:0}
.course-component-field legend{margin-bottom:9px;color:var(--ink);font-size:12px;font-weight:750}
.course-component-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.course-component-option{position:relative;display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface);cursor:pointer}
.course-component-option input{position:absolute;inset:0;width:100%;height:100%;margin:0;opacity:0;cursor:pointer}
.course-component-option:has(input:checked){border-color:var(--accent);background:var(--surface-soft);box-shadow:inset 0 0 0 1px var(--accent)}
.course-component-option:has(input:focus-visible){outline:2px solid var(--accent);outline-offset:3px}
.course-component-icon{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:8px;background:var(--surface-soft);color:var(--accent)}
.course-component-option>span:last-child{display:grid;gap:3px}
.course-component-option strong{color:var(--ink);font-size:12px}.course-component-option small{color:var(--muted);font-size:10px}
.course-register-note{display:flex;align-items:flex-start;gap:9px;margin-top:20px;padding:11px 12px;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:11px;line-height:1.5}
@media(max-width:620px){.course-component-options{grid-template-columns:1fr}.course-register-card .form-actions{align-items:stretch;flex-direction:column-reverse}.course-register-card .form-actions .btn{width:100%}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const codeInput=document.getElementById('course_code');
    const preview=document.getElementById('course-code-preview');
    const radios=[...document.querySelectorAll('input[name="component"]')];
    if(!codeInput||!preview||!radios.length)return;
    let manualChoice=@json(old('component') !== null);

    const parseCode=value=>{
        const pattern=/(?:(20\d{4})-)?([A-Z][A-Z0-9]{1,9})-([A-Z0-9]{1,12})-(TEC|TALL|TAL)(?:[-_\s]*NRC[_\s-]*(\d{3,12}))?/gi;
        const entries=[...value.matchAll(pattern)].map(m=>({
            base:[m[1],m[2],m[3]].filter(Boolean).join('-'),
            period:m[1]||'',
            program:m[2],
            course:m[3],
            type:m[4].toUpperCase()==='TAL'?'TALL':m[4].toUpperCase(),
            nrc:m[5]||null
        }));
        return {entries,bases:[...new Set(entries.map(x=>x.base))],types:[...new Set(entries.map(x=>x.type))]};
    };
    const update=()=>{
        const parsed=parseCode(codeInput.value);
        if(!parsed.entries.length){
            preview.textContent=codeInput.value.trim()?'No se detectó TEC/TAL; selecciona el componente manualmente.':'El código se interpretará aquí automáticamente.';
            preview.classList.remove('is-warning');
            if(!manualChoice)radios.forEach(r=>r.checked=false);
            return;
        }
        const first=parsed.entries[0];
        const parts=[];
        if(first.period)parts.push('Periodo '+first.period);
        parts.push('Programa '+first.program,'Curso '+first.course);
        const detected=[...new Set(parsed.entries.map(x=>x.type+(x.nrc?' · NRC '+x.nrc:'')))];
        preview.textContent=parts.join(' · ')+' · Detectado: '+detected.join(' / ');
        preview.classList.toggle('is-warning',parsed.bases.length>1);
        if(parsed.bases.length>1)preview.textContent+=' · Revisa que sean códigos del mismo curso.';
        if(!manualChoice&&parsed.bases.length===1){
            const suggested=parsed.types.length>1?'BOTH':parsed.types[0];
            radios.forEach(r=>r.checked=r.value===suggested);
        }
    };
    radios.forEach(r=>r.addEventListener('change',()=>manualChoice=true));
    codeInput.addEventListener('input',update);
    update();
})();
</script>
@endpush
