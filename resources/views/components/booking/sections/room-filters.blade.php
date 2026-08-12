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
     The .active skin lives in 07-checkout-night.css (per-theme).

     The idle fill was bg-ink/5, which resolves to 1.11:1 against this cream
     canvas: on the page the unselected pills read as ghosts and only the
     selected one looked like a control at all. They now carry the same warm
     white surface as the room cards below, so the whole section is built from
     one material.

     On a cream page no light fill can carry that job by luminance alone
     (white over cream measures 1.06:1), so the outline is what has to define
     the chip. border-ink/50 measures 3.27:1 against the canvas and clears the
     3:1 WCAG floor for the boundary of a user interface component; ink/45 came
     in at 2.85 and missed. That is a heavier outline than the rest of this
     page's hairlines, and it is deliberate: these are the only controls in the
     section that have to look pressable before they are selected. --}}
<div id="roomFilters" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2.5']) }} role="group" aria-label="Filter rooms by capacity">
    @foreach ($filters as $key => $label)
        <button type="button"
                data-filter="{{ $key }}"
                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                class="room-filter-pill press focus-ring rounded-full border border-ink/50 bg-white/55 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.18em] text-ink/75 cursor-pointer hover:border-emerald-deep hover:bg-white hover:text-ink {{ $loop->first ? 'active' : '' }}">
            {{ $label }}
        </button>
    @endforeach
</div>
