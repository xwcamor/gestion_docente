@forelse($assignments as $assignment)
    @php($components = $assignment->modalities->keyBy('modality'))
    @php($tecModality = $components->get('TEC'))
    @php($tallModality = $components->get('TALL'))
    <article class="workload-tree-course" data-tree-course-item data-assignment-id="{{ $assignment->id }}">
        <div class="tree-course-head">
            <span class="tree-branch-mark"></span>
            <span class="tree-node-icon tree-course-icon"><x-icon name="book-open" size="15" /></span>
            <div class="tree-node-copy">
                <strong class="tree-inline-name"
                        data-inline-course-name
                        data-course-name="{{ $assignment->course->name }}"
                        data-course-name-url="{{ route('workload-tree.courses.name', $assignment) }}"
                        title="Doble clic para cambiar el nombre del curso">{{ $assignment->course->name }}</strong>
                <small>{{ $assignment->course->code ?: 'Sin código' }}</small>
            </div>
            <div class="tree-course-components" aria-label="Estado de las modalidades">
                <span class="tree-component-chip {{ !$tecModality ? 'is-missing' : ($tecModality->active ? 'is-present' : 'is-disabled') }}" data-component-chip="TEC">TEC</span>
                <span class="tree-component-chip {{ !$tallModality ? 'is-missing' : ($tallModality->active ? 'is-present' : 'is-disabled') }}" data-component-chip="TALL">TAL</span>
            </div>
            <button class="tree-course-delete-trigger" type="button" data-toggle-course-delete title="Retirar curso del grupo">
                <x-icon name="x" size="14" /><span>Eliminar</span>
            </button>
        </div>

        <div class="tree-delete-confirm tree-course-delete-confirm" data-course-delete-confirm hidden>
            <p><strong>¿Retirar {{ $assignment->course->name }} de este grupo?</strong> Se eliminarán sus modalidades y horarios de este grupo. La malla histórica se conserva; el curso compartido permanece en otros grupos.</p>
            <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-tree-delete-course>
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> Confirmar retiro</button>
                <button class="btn btn-ghost btn-sm" type="button" data-inline-cancel>Cancelar</button>
            </form>
        </div>

        <div class="tree-course-modalities">
            @include('cycles.tree.modalities', ['modalities' => $assignment->modalities, 'assignment' => $assignment])
        </div>
    </article>
@empty
    <div class="tree-empty-child">Este grupo todavía no tiene cursos. Usa «Agregar curso» para comenzar.</div>
@endforelse
