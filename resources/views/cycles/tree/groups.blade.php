@forelse($groups as $group)
    <div class="workload-tree-group-shell">
        <details class="workload-tree-group" data-tree-group-id="{{ $group->id }}" data-tree-load="{{ route('workload-tree.courses', $group) }}">
            <summary class="workload-tree-summary workload-group-summary">
                <span class="tree-branch-mark"></span>
                <span class="tree-node-icon tree-group-icon"><x-icon name="users" size="16" /></span>
                <span class="tree-node-copy">
                    <strong>Grupo {{ $group->name }}</strong>
                    <small>{{ $group->assignments_count }} cursos · {{ $group->students_count }} alumnos</small>
                </span>
                <span class="tree-chevron"><x-icon name="chevron-right" size="14" /></span>
            </summary>

            <div class="workload-tree-children workload-group-children">
                <div class="workload-tree-group-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ route('groups.show', $group) }}"><x-icon name="graduation-cap" /> Gestión académica</a>
                </div>
                <div data-tree-content>
                    <div class="tree-loading-state">Despliega el grupo para consultar sus cursos.</div>
                </div>
            </div>
        </details>
        <button class="workload-tree-add-course" type="button"
            data-open-course-dialog
            data-group-id="{{ $group->id }}"
            data-create-url="{{ route('workload-tree.courses.store', $group) }}"
            aria-label="Agregar curso al grupo {{ $group->name }}" title="Agregar curso">
            <x-icon name="plus" size="13" /> <span>Agregar curso</span>
        </button>
    </div>
@empty
    <div class="tree-empty-child">No hay grupos que coincidan con esta búsqueda.</div>
@endforelse
