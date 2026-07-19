{{-- Fresh Meadow pagination — used by the admin console tables.

     Livewire components (default):  $items->links('vendor.pagination.admin')
     Plain Blade pages (URL links):  $items->links('vendor.pagination.admin', ['mode' => 'links'])
--}}
@php $useLinks = ($mode ?? 'wire') === 'links'; @endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="pager">
        <p class="pager-meta">
            Showing <b>{{ $paginator->firstItem() ?? 0 }}</b>-<b>{{ $paginator->lastItem() ?? 0 }}</b>
            of <b>{{ $paginator->total() }}</b>
        </p>

        <div class="pager-pages">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="pager-btn" aria-disabled="true" aria-label="Previous page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @elseif ($useLinks)
                <a href="{{ $paginator->previousPageUrl() }}" class="pager-btn" rel="prev" aria-label="Previous page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @else
                <button type="button" wire:click="previousPage" class="pager-btn" rel="prev" aria-label="Previous page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-gap">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-btn" aria-current="page">{{ $page }}</span>
                        @elseif ($useLinks)
                            <a href="{{ $url }}" class="pager-btn" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }})" class="pager-btn" aria-label="Go to page {{ $page }}">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if (! $paginator->hasMorePages())
                <span class="pager-btn" aria-disabled="true" aria-label="Next page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @elseif ($useLinks)
                <a href="{{ $paginator->nextPageUrl() }}" class="pager-btn" rel="next" aria-label="Next page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <button type="button" wire:click="nextPage" class="pager-btn" rel="next" aria-label="Next page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            @endif
        </div>
    </nav>
@endif
