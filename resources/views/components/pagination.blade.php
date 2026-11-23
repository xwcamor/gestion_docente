@props(['paginator'])

@if($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
    @endphp
    <nav class="pagination" role="navigation" aria-label="Paginación">
        <span class="pagination-status">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </span>
        <div class="pagination-links">
            @if($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @if($startPage > 1)
                <a href="{{ $paginator->url(1) }}">1</a>
                @if($startPage > 2)<span class="pagination-ellipsis">…</span>@endif
            @endif

            @foreach($paginator->getUrlRange($startPage, $endPage) as $page => $url)
                @if($page === $currentPage)
                    <span class="is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($endPage < $lastPage)
                @if($endPage < $lastPage - 1)<span class="pagination-ellipsis">…</span>@endif
                <a href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
                <span class="is-disabled" aria-disabled="true">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
