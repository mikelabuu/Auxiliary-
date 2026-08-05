@props([
    'eyebrow' => null,
    'subtitle' => null,
])

{{--
    Operations hero banner (emerald gradient) — an eyebrow label, big display
    title (default slot, wrap emphasis in <span class="accent">), a subtitle,
    a `pills` slot of live stat chips, and an optional `aside` glass box on the
    right (active-filter / live status). Recolored from the AAIS reference to
    the Fresh Meadow palette.

    <x-admin.ui.ops-header eyebrow="Operations Center" subtitle="…">
        Document <span class="accent">Transactions</span>
        <x-slot:pills>
            <span class="ops-pill"><span class="ops-pill-num">10</span> visible now</span>
        </x-slot:pills>
        <x-slot:aside>…</x-slot:aside>
    </x-admin.ui.ops-header>
--}}

<div {{ $attributes->merge(['class' => 'ops-header animate-in']) }}>
    <div class="ops-header-main">
        @if($eyebrow)
            <p class="ops-header-eyebrow">{{ $eyebrow }}</p>
        @endif
        {{-- h1 for the same reason as page-header: this is the page title on
             the two screens that use this variant (Booking Operations, Manual
             Booking), and both had no h1 at all. Styling hangs off the class,
             not the element. --}}
        <h1 class="ops-header-title">{{ $slot }}</h1>
        @if($subtitle)
            <p class="ops-header-sub">{{ $subtitle }}</p>
        @endif
        @isset($pills)
            <div class="ops-pills">{{ $pills }}</div>
        @endisset
    </div>

    @isset($aside)
        <div class="ops-aside">{{ $aside }}</div>
    @endisset
</div>
