@props([
    // filter key => label; keys are interpreted by public/js/room-filters.js
    'filters' => [
        'all'     => 'All Rooms',
        '1-2'     => '1-2 Pax',
        '3-4'     => '3-4 Pax',
        '5plus'   => '5+ Pax',
        'premium' => 'Premium',
    ],
])

{{-- Capacity filter pills for the room grid. Cards opt in via
     data-room-item + data-beds / data-premium attributes.
     The .active skin lives in app.css (per-theme). --}}
<div id="roomFilters" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }} role="group" aria-label="Filter rooms by capacity">
    @foreach ($filters as $key => $label)
        <button type="button"
                data-filter="{{ $key }}"
                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                class="room-filter-pill press focus-ring rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.18em] text-ink/70 cursor-pointer hover:border-gold/60 hover:text-ink {{ $loop->first ? 'active' : '' }}">
            {{ $label }}
        </button>
    @endforeach
</div>
