@forelse($groups as $group)
    <details class="workload-tree-group" data-tree-load="{{ route('workload-tree.courses', $group) }}">
        <summary class="workload-tree-summary workload-group-summary">
            <span class="tree-branch-mark"></span>
            <span class="tree-node-icon tree-group-icon"><x-icon name="users" size="15" /></span>
            <span class="tree-node-copy">
                <strong>{{ $group->name }}</strong>
                <small>{{ $group->assignments_count }} cursos · {{ $group->students_count }} alumnos</small>
            </span>
            <span class="tree-chevron"><x-icon name="chevron-right" size="14" /></span>
        </summary>

        <div class="workload-tree-children workload-group-children">
            <div class="workload-tree-actions">
                <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}"><x-icon name="folder-open" /> Abrir grupo</a>
                <a class="btn btn-ghost btn-sm" href="{{ route('assignments.quick', $group) }}"><x-icon name="plus" /> Agregar cursos</a>
                <a class="btn btn-ghost btn-sm" href="{{ route('groups.calendar', $group) }}"><x-icon name="calendar-days" /> Calendario</a>
                <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}#group-roster"><x-icon name="users" /> Alumnos</a>
                @if($group->students_count > 0)
                    <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}?talks=1#group-talks"><x-icon name="message-circle" /> Charla 5 min</a>
                @endif
            </div>
            <div data-tree-content>
                <div class="tree-loading-state">Despliega el grupo para consultar sus cursos.</div>
            </div>
        </div>
    </details>
@empty
    <div class="tree-empty-child">No hay grupos que coincidan con esta búsqueda.</div>
@endforelse
