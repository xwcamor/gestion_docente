@extends('layouts.app')
@section('title', $cycle->academic_year.' · Ciclo '.$cycle->cycle_code)
@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dashboard') }}">Resumen</a>
    <x-icon name="chevron-right" size="12" />
    <a href="{{ route('cycles.index') }}">Ciclos académicos</a>
    <x-icon name="chevron-right" size="12" />
    <span class="current">{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</span>
</div>
<a class="cycle-back" href="{{ route('cycles.index') }}"><x-icon name="arrow-left" size="15" /> Volver a todos los ciclos</a>

<div class="page-head">
    <div>
        <p class="eyebrow"><x-icon name="calendar-days" size="12" /> Ciclo académico</p>
        <h1>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</h1>
        <div class="cycle-item-meta">
            <span><x-icon name="calendar-range" size="12" /> Rango de carga: {{ $cycle->start_date?->format('d/m/Y') }} – {{ $cycle->end_date?->format('d/m/Y') }}</span>
            <span class="badge badge-green">{{ $cycle->status === 'active' ? 'Activo' : 'Registrado' }}</span>
            <button class="inline-edit" type="button" data-modal-open="cycle-edit-dialog"><x-icon name="file-text" size="13" /> Editar ciclo</button>
        </div>
    </div>
    <div class="actions">
        <button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Agregar grupo</button>
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:17px">
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Grupos</span><span class="stat-icon"><x-icon name="users" /></span></div>
        <div class="stat">{{ $groupCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Cursos</span><span class="stat-icon"><x-icon name="book-open" /></span></div>
        <div class="stat">{{ $courseCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span class="stat-label">Modalidades</span><span class="stat-icon"><x-icon name="layers" /></span></div>
        <div class="stat">{{ $modalityCount }}</div>
    </div>
</div>

<section class="card">
    <div class="card-head">
        <div><h2>Grupos</h2></div>
        <span class="badge badge-blue">{{ $groupCount }} {{ $groupCount === 1 ? 'grupo' : 'grupos' }}</span>
    </div>
    @forelse($groups as $group)
        <article class="cycle-item">
            <div class="cycle-item-main">
                <span class="cycle-item-icon"><x-icon name="users" size="18" /></span>
                <div>
                    <h3 class="cycle-item-title">{{ $group->name }}</h3>
                    <div class="cycle-item-meta">
                        <span><x-icon name="clock" size="12" /> {{ $group->assigned_at?->format('d/m/Y') ?? 'Sin fecha de asignación' }}</span>
                        <span>·</span>
                        <span><x-icon name="book-open" size="12" /> {{ $group->assignments_count }} {{ $group->assignments_count === 1 ? 'curso' : 'cursos' }}</span>
                    </div>
                </div>
            </div>
            <div class="cycle-item-actions">
                <button
                    class="btn btn-ghost btn-sm"
                    type="button"
                    data-edit-group
                    data-group-id="{{ $group->id }}"
                    data-update-url="{{ route('groups.update', $group) }}"
                    data-name="{{ $group->name }}"
                    data-assigned-at="{{ $group->assigned_at?->format('Y-m-d') }}"
                ><x-icon name="file-text" /> Editar</button>
                <a class="btn btn-secondary btn-sm" href="{{ route('groups.show', $group) }}">Abrir grupo <x-icon name="arrow-right" /></a>
            </div>
        </article>
    @empty
        <div class="empty">
            <span class="stat-icon" style="margin:0 auto 10px;width:42px;height:42px"><x-icon name="users" size="20" /></span>
            <strong>Este ciclo todavía no tiene grupos.</strong>
            <span>Agrega el primer grupo para registrar después sus cursos.</span>
            <div class="mt-16"><button class="btn" type="button" data-modal-open="group-create-dialog"><x-icon name="plus" /> Crear grupo</button></div>
        </div>
    @endforelse
    @if($groups->hasPages())
        <x-pagination :paginator="$groups" />
    @endif
</section>

<dialog class="ui-dialog sheet-dialog" id="cycle-edit-dialog" aria-labelledby="cycle-edit-title" data-backdrop-close @if(request('dialog') === 'edit-cycle') data-auto-open @endif>
    <div class="dialog-head">
        <div><h2 id="cycle-edit-title">Editar ciclo</h2><p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('cycles.update', $cycle) }}" data-loading-form="1">
        @csrf
        @method('PATCH')
        <div class="dialog-body">
            @if($errors->any() && request('dialog') === 'edit-cycle')
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="form-row">
                <div><label for="edit_academic_year">Año académico</label><input class="field" id="edit_academic_year" type="number" name="academic_year" min="2020" max="2100" value="{{ old('academic_year', $cycle->academic_year) }}" required></div>
                <div><label for="edit_cycle_code">Código del ciclo</label><input class="field" id="edit_cycle_code" name="cycle_code" maxlength="20" value="{{ old('cycle_code', $cycle->cycle_code) }}" required></div>
            </div>
            <div class="form-row">
                <div><label for="edit_cycle_start">Inicio del rango de carga</label><input class="field" id="edit_cycle_start" type="date" name="start_date" value="{{ old('start_date', $cycle->start_date?->format('Y-m-d')) }}" required></div>
                <div><label for="edit_cycle_end">Fin del rango de carga</label><input class="field" id="edit_cycle_end" type="date" name="end_date" value="{{ old('end_date', $cycle->end_date?->format('Y-m-d')) }}" required></div>
            </div>
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="group-create-dialog" aria-labelledby="group-create-title" data-backdrop-close @if(request()->boolean('nuevo_grupo') || ($errors->any() && !request('dialog'))) data-auto-open @endif>
    <div class="dialog-head">
        <div><h2 id="group-create-title">Agregar grupo</h2><p>{{ $cycle->academic_year }} · Ciclo {{ $cycle->cycle_code }}</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="{{ route('groups.store', $cycle) }}" data-loading-form="1">
        @csrf
        <div class="dialog-body">
            @if($errors->any() && !request('dialog'))
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="group-count-control">
                <label for="group-count">¿Cuántos grupos vas a crear?</label>
                <input class="field" id="group-count" type="number" min="1" max="30" value="{{ max(1, count(old('groups', [])) ?: 1) }}">
                <p class="help">Indica la cantidad y escribe el nombre de cada grupo. Puedes crear hasta 30 de una sola vez.</p>
            </div>
            @if($errors->has('groups') || $errors->has('groups.*.name'))
                <div class="dialog-error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            <div class="group-fields" id="group-fields"></div>
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar grupo</button></div>
    </form>
</dialog>

<dialog class="ui-dialog sheet-dialog" id="group-edit-dialog" aria-labelledby="group-edit-title" data-backdrop-close>
    <div class="dialog-head">
        <div><h2 id="group-edit-title">Editar grupo</h2><p>Actualiza el nombre o la fecha de asignación.</p></div>
        <button class="dialog-close" type="button" data-modal-close aria-label="Cerrar"><x-icon name="x" /></button>
    </div>
    <form method="POST" action="#" data-loading-form="1" id="group-edit-form">
        @csrf
        @method('PATCH')
        <div class="dialog-body">
            @if($errors->any() && request('dialog') === 'edit-group')
                <div class="dialog-error" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <label for="edit_group_name">Nombre del grupo</label>
            <input type="hidden" name="page" value="{{ request('page', 1) }}">
            <input class="field" id="edit_group_name" name="name" required maxlength="100">
            <label for="edit_group_assigned">Fecha de asignación <span class="muted">(opcional)</span></label>
            <input class="field" id="edit_group_assigned" type="date" name="assigned_at">
        </div>
        <div class="dialog-footer"><button class="btn btn-secondary" type="button" data-modal-close>Cancelar</button><button class="btn" type="submit"><x-icon name="check" /> Guardar cambios</button></div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.group-count-control{display:grid;grid-template-columns:minmax(0,1fr) 110px;align-items:center;gap:10px}
.group-count-control label{margin:0}
.group-count-control .help{grid-column:1/-1;margin:0}
.group-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:16px}
.group-name-field{padding:11px;border:1px solid #dce7ed;border-radius:10px;background:#f9fcfd}
.group-name-field label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;color:#477085}
@media(max-width:560px){.group-fields{grid-template-columns:1fr}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const countInput = document.getElementById('group-count');
    const groupFields = document.getElementById('group-fields');
    if (countInput && groupFields) {
        const oldGroups = @json(array_values(old('groups', [])));
        const values = oldGroups.map(group => group?.name || '');
        const render = () => {
            const count = Math.max(1, Math.min(30, Number.parseInt(countInput.value, 10) || 1));
            countInput.value = count;
            groupFields.replaceChildren();

            for (let index = 0; index < count; index++) {
                const wrapper = document.createElement('div');
                wrapper.className = 'group-name-field';

                const label = document.createElement('label');
                label.htmlFor = 'group-name-' + index;
                label.textContent = 'Grupo ' + (index + 1);

                const input = document.createElement('input');
                input.className = 'field';
                input.id = 'group-name-' + index;
                input.name = 'groups[' + index + '][name]';
                input.maxLength = 100;
                input.required = true;
                input.placeholder = 'Ej. IA 20' + (index + 1);
                input.value = values[index] || '';
                input.addEventListener('input', () => { values[index] = input.value; });

                wrapper.append(label, input);
                groupFields.append(wrapper);
            }
        };

        countInput.addEventListener('input', render);
        countInput.addEventListener('change', render);
        render();
    }
})();
</script>
<script>
(() => {
    const dialog = document.getElementById('group-edit-dialog');
    const form = document.getElementById('group-edit-form');
    const name = document.getElementById('edit_group_name');
    const assigned = document.getElementById('edit_group_assigned');
    if (!dialog || !form || !name || !assigned) return;

    const prepare = trigger => {
        form.action = trigger.dataset.updateUrl;
        name.value = trigger.dataset.name || '';
        assigned.value = trigger.dataset.assignedAt || '';
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-edit-group]');
        if (!trigger) return;
        prepare(trigger);
        dialog.showModal();
        name.focus();
    });

    @if(request('dialog') === 'edit-group')
        const previousTrigger = document.querySelector('[data-edit-group][data-group-id="{{ (int) request('group_id') }}"]');
        if (previousTrigger) {
            prepare(previousTrigger);
            name.value = @json(old('name', ''));
            assigned.value = @json(old('assigned_at', ''));
            dialog.showModal();
            name.focus();
        }
    @endif
})();
</script>
@endpush
