@forelse($assignments as $assignment)
    @php($curriculum = $assignment->curriculum)
    @php($components = $assignment->modalities->keyBy('modality'))
    <details class="workload-tree-course" data-tree-load="{{ route('workload-tree.modalities', $assignment) }}">
        <summary class="workload-tree-summary workload-course-summary">
            <span class="tree-branch-mark"></span>
            <span class="tree-node-icon tree-course-icon"><x-icon name="book-open" size="15" /></span>
            <span class="tree-node-copy">
                <strong>{{ $assignment->course->name }}</strong>
                <small>{{ $assignment->course->code ?: 'Sin código' }}</small>
            </span>
            <span class="tree-course-components" aria-label="Componentes del curso">
                <span class="tree-component-chip {{ $components->has('TEC') ? 'is-present' : 'is-missing' }}" title="{{ $components->has('TEC') ? 'Teoría registrada' : 'Teoría pendiente' }}">TEC</span>
                <span class="tree-component-chip {{ $components->has('TALL') ? 'is-present' : 'is-missing' }}" title="{{ $components->has('TALL') ? 'Taller registrado' : 'Taller pendiente' }}">TAL</span>
            </span>
            <span class="tree-course-status {{ $curriculum && $curriculum->is_active ? 'is-ready' : 'is-pending' }}">
                <x-icon name="{{ $curriculum && $curriculum->is_active ? 'book-check' : 'book-x' }}" size="12" />
                {{ $curriculum && $curriculum->is_active ? 'Malla activa' : 'Malla pendiente' }}
            </span>
            <span class="tree-chevron"><x-icon name="chevron-right" size="13" /></span>
        </summary>

        <div class="workload-tree-children workload-course-children">
            <p class="tree-course-hint">Doble clic en NRC o fechas para editarlos. La malla y el padrón se administran en Gestión académica.</p>

            @if($curriculum && $curriculum->is_active)
                <div class="tree-curriculum-note">
                    <x-icon name="book-check" size="13" />
                    <strong>{{ $curriculum->course_name ?: $assignment->course->name }}</strong>
                    <span>{{ $curriculum->weeks->count() }} semanas · Versión {{ $curriculum->version }}</span>
                </div>
            @endif

            <div data-tree-content>
                <div class="tree-loading-state">Despliega el curso para consultar sus modalidades y NRC.</div>
            </div>
        </div>
    </details>
@empty
    <div class="tree-empty-child">Este grupo todavía no tiene cursos. Usa «Agregar curso» en la cabecera del grupo para comenzar.</div>
@endforelse
