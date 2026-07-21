@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-3">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-1 px-4 py-2 text-sm font-bold text-stone-300 bg-white border border-stone-200 cursor-default rounded-full">
                <span class="material-icons text-[16px]">chevron_left</span> {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 px-4 py-2 text-sm font-bold text-clsu-700 bg-white border border-clsu-200 rounded-full hover:bg-clsu-50 hover:border-clsu-300 transition-[color,background-color,border-color,box-shadow] cursor-pointer">
                <span class="material-icons text-[16px]">chevron_left</span> {!! __('pagination.previous') !!}
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 px-4 py-2 text-sm font-bold text-clsu-700 bg-white border border-clsu-200 rounded-full hover:bg-clsu-50 hover:border-clsu-300 transition-[color,background-color,border-color,box-shadow] cursor-pointer">
                {!! __('pagination.next') !!} <span class="material-icons text-[16px]">chevron_right</span>
            </a>
        @else
            <span class="inline-flex items-center gap-1 px-4 py-2 text-sm font-bold text-stone-300 bg-white border border-stone-200 cursor-default rounded-full">
                {!! __('pagination.next') !!} <span class="material-icons text-[16px]">chevron_right</span>
            </span>
        @endif
    </nav>
@endif
