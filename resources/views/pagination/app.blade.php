@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="pager-link is-disabled" aria-disabled="true">&laquo; Prev</span>
        @else
            <a class="pager-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Prev</a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pager-link is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pager-link is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pager-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a class="pager-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &raquo;</a>
        @else
            <span class="pager-link is-disabled" aria-disabled="true">Next &raquo;</span>
        @endif
    </nav>

    <p class="pager-summary">
        Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}
        of {{ $paginator->total() }}
    </p>
@endif
