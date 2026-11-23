@extends('layouts.app')
@section('title', 'Agregar curso')
@section('content')
<div class="breadcrumbs"><a href="{{ route('cycles.index') }}">Ciclos</a><span>›</span><a href="{{ route('cycles.show', $group->cycle) }}">{{ $group->cycle->academic_year }} · {{ $group->cycle->cycle_code }}</a><span>›</span><a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a><span>›</span><span class="current">Nuevo curso</span></div>
<div class="page-head"><div><p class="eyebrow">Paso 3 de 4 · Registro de curso</p><h1>Agregar curso al grupo</h1><p class="subhead">Primero indica qué curso te asignaron. Después configura solo las modalidades que correspondan.</p></div></div>

<div class="stepper" id="assignment-stepper">
    <div class="step active" data-step-indicator="1"><span class="step-number">1</span><span>Datos del curso</span></div><span class="step-line"></span>
    <div class="step" data-step-indicator="2"><span class="step-number">2</span><span>Modalidades y horario</span></div>
</div>

<form method="POST" action="{{ route('assignments.store', $group) }}" id="assignment-form">
    @csrf
    <section class="card wizard-section" id="assignment-step-1">
        <div class="card-head"><div><h2>1. Datos generales</h2><p class="card-description">Estos datos identifican el curso dentro de tu grupo.</p></div><span class="badge badge-blue">Obligatorio</span></div>
        <div class="form-row">
            <div><label for="course_name">Nombre del curso</label><input class="field" id="course_name" name="course_name" value="{{ old('course_name') }}" required maxlength="255" placeholder="Ej. Sistemas Operativos y Comunicación de Datos"></div>
            <div><label for="course_code">Código del curso (opcional)</label><input class="field" id="course_code" name="course_code" value="{{ old('course_code') }}" maxlength="80" placeholder="Ej. PIAD-224"><p class="help">Si no tienes código, el sistema generará uno usando el nombre.</p></div>
        </div>
        <div class="form-row-3">
            <div><label for="career">Carrera (opcional)</label><input class="field" id="career" name="career" value="{{ old('career') }}" placeholder="Ej. Desarrollo de Software"></div>
            <div><label for="module">Módulo / semestre (opcional)</label><input class="field" id="module" name="module" value="{{ old('module') }}" placeholder="Ej. IV"></div>
            <div><label for="assigned_at">Fecha de asignación</label><input class="field" id="assigned_at" type="date" name="assigned_at" value="{{ old('assigned_at') }}"></div>
        </div>
        <label for="notes">Notas internas (opcional)</label><textarea class="field" id="notes" name="notes" rows="2" placeholder="Observaciones sobre la asignación…">{{ old('notes') }}</textarea>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('groups.show', $group) }}">← Volver al grupo</a><button class="btn" type="button" id="next-step">Continuar: modalidades →</button></div>
    </section>

    <section class="card wizard-section" id="assignment-step-2" hidden>
        <div class="card-head"><div><h2>2. Modalidades y horario</h2><p class="card-description">Activa TEC, TALL o ambas. Cada modalidad tendrá su propio NRC, fechas, horario y alumnos.</p></div><span class="badge badge-blue">Selecciona al menos una</span></div>
        @if(count($scheduleTemplates))
            <div class="alert alert-info"><strong>Atajo disponible:</strong> puedes copiar días, horas, ambiente y fechas desde otro curso de este grupo. El NRC no se copia porque cada modalidad debe conservar el suyo.</div>
        @else
            <div class="alert alert-info">Cuando agregues otros cursos, podrás reutilizar sus horarios desde aquí. Por ahora puedes registrar el primer horario manualmente.</div>
        @endif
        <div class="grid grid-2">
            @foreach(['TEC'=>'Conocimientos tecnológicos','TALL'=>'Práctica de taller'] as $kind=>$label)
                @php($prefix = strtolower($kind))
                <div class="choice-card" id="{{ $prefix }}-choice">
                    <label class="choice-toggle"><input type="checkbox" name="has_{{ $prefix }}" value="1" id="has_{{ $prefix }}" {{ old('has_'.$prefix) ? 'checked' : ($kind === 'TEC' && !old('has_tec') && !old('has_tall') ? 'checked' : '') }}> <span>{{ $kind }} · {{ $label }}</span></label>
                    <p class="help">Activa esta opción si tu asignación incluye esta modalidad.</p>
                    <div class="modality-fields" id="{{ $prefix }}-fields" hidden>
                        <div class="form-row">
                            <div><label for="{{ $prefix }}_nrc">NRC {{ $kind }}</label><input class="field" id="{{ $prefix }}_nrc" name="{{ $prefix }}_nrc" value="{{ old($prefix.'_nrc') }}" placeholder="NRC propio de esta modalidad"></div>
                            <div><label for="{{ $prefix }}_total_hours">Horas por semana</label><input class="field" id="{{ $prefix }}_total_hours" type="number" min="0" step=".5" name="{{ $prefix }}_total_hours" value="{{ old($prefix.'_total_hours') }}" placeholder="Ej. 6"></div>
                        </div>
                        <div class="form-row">
                            <div><label for="{{ $prefix }}_start_date">Fecha de inicio</label><input class="field" id="{{ $prefix }}_start_date" type="date" name="{{ $prefix }}_start_date" value="{{ old($prefix.'_start_date') }}"></div>
                            <div><label for="{{ $prefix }}_end_date">Fecha de fin</label><input class="field" id="{{ $prefix }}_end_date" type="date" name="{{ $prefix }}_end_date" value="{{ old($prefix.'_end_date') }}"></div>
                        </div>
                        <label for="{{ $prefix }}_template">Reutilizar horario de otro curso</label>
                        <select class="field schedule-template" id="{{ $prefix }}_template" data-kind="{{ $kind }}">
                            <option value="">No copiar · configurar manualmente</option>
                            @foreach($scheduleTemplates as $template)
                                @if($template['modality'] === $kind)
                                    <option value="{{ $template['id'] }}">{{ $template['label'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="help">Al elegir un horario guardado se completan automáticamente fechas, días, horas y ambiente. El NRC queda vacío para que ingreses el de este curso.</p>
                        <div class="schedule-copy-note" id="{{ $prefix }}-copy-note" hidden></div>
                        <div class="schedule-editor">
                            <label>Días de clase</label>
                            <div class="weekday-list">
                                @foreach([1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'] as $day=>$dayLabel)
                                    <label class="weekday"><input type="checkbox" name="{{ $prefix }}_days[]" value="{{ $day }}" data-weekday="{{ $prefix }}"> {{ $dayLabel }}</label>
                                @endforeach
                            </div>
                            <div class="form-row-3" style="margin-top:10px">
                                <div><label for="{{ $prefix }}_start_time">Hora de inicio</label><input class="field" id="{{ $prefix }}_start_time" type="time" name="{{ $prefix }}_start_time" value="{{ old($prefix.'_start_time') }}"></div>
                                <div><label for="{{ $prefix }}_end_time">Hora de fin</label><input class="field" id="{{ $prefix }}_end_time" type="time" name="{{ $prefix }}_end_time" value="{{ old($prefix.'_end_time') }}"></div>
                                <div><label for="{{ $prefix }}_classroom">Aula / ambiente</label><input class="field" id="{{ $prefix }}_classroom" name="{{ $prefix }}_classroom" value="{{ old($prefix.'_classroom') }}" placeholder="Ej. Lab. 204"></div>
                            </div>
                            <p class="help">Este horario común se aplica a todos los días seleccionados. Al reutilizar un horario se copian exactamente sus bloques guardados.</p>
                        </div>
                        <div class="copied-schedules" id="{{ $prefix }}-copied-schedules"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="form-actions">
            <button class="btn btn-secondary" type="button" id="back-step">← Volver a datos del curso</button>
            <div class="actions"><a class="btn btn-ghost" href="{{ route('groups.show', $group) }}">Cancelar</a><button class="btn" type="submit">Guardar curso y modalidades</button></div>
        </div>
    </section>
</form>
@endsection

@push('styles')
<style>
.modality-fields{margin-top:15px;padding-top:13px;border-top:1px solid #edf0f2}
.schedule-copy-note{margin-top:10px;padding:10px 11px;border-radius:8px;background:#effaf3;border:1px solid #cdebd7;color:#17663b;font-size:11px;line-height:1.5}
.copied-schedules{display:none}
.modality-fields[hidden],.wizard-section[hidden]{display:none!important}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const form = document.getElementById('assignment-form');
    if (!form) return;
    const scheduleTemplates = @json($scheduleTemplates);
    const stepOne = document.getElementById('assignment-step-1');
    const stepTwo = document.getElementById('assignment-step-2');
    const indicators = [...document.querySelectorAll('[data-step-indicator]')];

    const showStep = number => {
        stepOne.hidden = number !== 1;
        stepTwo.hidden = number !== 2;
        indicators.forEach(item => {
            const n = Number(item.dataset.stepIndicator);
            item.classList.toggle('active', n === number);
            item.classList.toggle('done', n < number);
        });
        if (number === 1) document.getElementById('course_name').focus();
    };

    const clearCopied = prefix => {
        const hidden = document.getElementById(prefix + '-copied-schedules');
        const note = document.getElementById(prefix + '-copy-note');
        hidden.innerHTML = '';
        note.hidden = true;
    };

    const addHidden = (container, name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value ?? '';
        container.appendChild(input);
    };

    const fillTemplate = (prefix, templateId) => {
        clearCopied(prefix);
        const template = scheduleTemplates.find(item => String(item.id) === String(templateId));
        if (!template) return;

        const set = (suffix, value) => {
            const input = document.getElementById(prefix + '_' + suffix);
            if (input && value !== null && value !== undefined) input.value = value;
        };

        set('start_date', template.start_date);
        set('end_date', template.end_date);
        set('total_hours', template.total_hours);

        const days = new Set(template.schedules.map(item => String(item.day_of_week)));
        document.querySelectorAll('input[data-weekday="' + prefix + '"]').forEach(input => {
            input.checked = days.has(input.value);
        });

        if (template.schedules.length) {
            set('start_time', template.schedules[0].start_time);
            set('end_time', template.schedules[0].end_time);
            set('classroom', template.schedules[0].classroom || '');

            const hidden = document.getElementById(prefix + '-copied-schedules');
            template.schedules.forEach((schedule, index) => {
                const base = prefix + '_schedules[' + index + ']';
                addHidden(hidden, base + '[day_of_week]', schedule.day_of_week);
                addHidden(hidden, base + '[start_time]', schedule.start_time);
                addHidden(hidden, base + '[end_time]', schedule.end_time);
                addHidden(hidden, base + '[classroom]', schedule.classroom || '');
                addHidden(hidden, base + '[block_type]', schedule.block_type || 'CLASS');
            });

            const daysText = [...days].map(day => ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'][Number(day)]).join(', ');
            const note = document.getElementById(prefix + '-copy-note');
            note.textContent = 'Horario copiado desde "' + template.label + '". Se conservarán ' + template.schedules.length + ' bloques (' + daysText + '), con sus horas y ambientes. Al modificar los campos manuales, pasarás a un horario común.';
            note.hidden = false;
        }
    };

    ['tec','tall'].forEach(prefix => {
        const checkbox = document.getElementById('has_' + prefix);
        const panel = document.getElementById(prefix + '-fields');
        const choice = document.getElementById(prefix + '-choice');
        const sync = () => {
            panel.hidden = !checkbox.checked;
            choice.classList.toggle('selected', checkbox.checked);
        };
        checkbox.addEventListener('change', sync);
        sync();

        const selector = document.getElementById(prefix + '_template');
        selector.addEventListener('change', () => fillTemplate(prefix, selector.value));

        panel.querySelectorAll('.schedule-editor input').forEach(input => {
            input.addEventListener('input', () => clearCopied(prefix));
            input.addEventListener('change', () => clearCopied(prefix));
        });
    });

    document.getElementById('next-step').addEventListener('click', () => {
        const required = document.getElementById('course_name');
        if (!required.reportValidity()) return;
        showStep(2);
    });
    document.getElementById('back-step').addEventListener('click', () => showStep(1));

    form.addEventListener('submit', event => {
        const enabled = ['tec','tall'].filter(prefix => document.getElementById('has_' + prefix).checked);
        if (!enabled.length) {
            event.preventDefault();
            showStep(2);
            alert('Selecciona TEC, TALL o ambas modalidades para continuar.');
            return;
        }

        let scheduleError = false;
        enabled.forEach(prefix => {
            const days = [...document.querySelectorAll('input[data-weekday="' + prefix + '"]:checked')];
            const start = document.getElementById(prefix + '_start_time').value;
            const end = document.getElementById(prefix + '_end_time').value;
            const hidden = document.getElementById(prefix + '-copied-schedules');

            if (hidden.children.length) return;
            if (days.length && (!start || !end || end <= start)) {
                scheduleError = true;
                document.getElementById(prefix + '_start_time').setCustomValidity('Completa una hora de inicio y una hora de fin posterior.');
                document.getElementById(prefix + '_start_time').reportValidity();
                document.getElementById(prefix + '_start_time').setCustomValidity('');
                return;
            }

            if (days.length) {
                hidden.innerHTML = '';
                days.forEach((day, index) => {
                    const base = prefix + '_schedules[' + index + ']';
                    addHidden(hidden, base + '[day_of_week]', day.value);
                    addHidden(hidden, base + '[start_time]', start);
                    addHidden(hidden, base + '[end_time]', end);
                    addHidden(hidden, base + '[classroom]', document.getElementById(prefix + '_classroom').value);
                    addHidden(hidden, base + '[block_type]', 'CLASS');
                });
            }
        });
        if (scheduleError) {
            event.preventDefault();
            showStep(2);
        }
    });

    @if($errors->any())
        showStep({{ collect($errors->keys())->contains(fn($key) => str_starts_with($key, 'tec_') || str_starts_with($key, 'tall_') || $key === 'modalities') ? 2 : 1 }});
    @else
        showStep(1);
    @endif
})();
</script>
@endpush
