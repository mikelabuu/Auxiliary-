@props([
    'icon' => 'grid',
    'color' => 'clsu',
    'badge' => null,
    'label' => null,
    'dark' => false,
    'delay' => 0,
    'valueId' => null,
    'footnoteId' => null,
])

{{--
    KPI stat card used across dashboard/rooms/etc.
    Default slot = the big value. Named `footnote` slot = the small line underneath —
    it's a bare slot (not a styled prop) so callers can bring their own <p> with
    whatever color/icon a metric needs (e.g. a red/green trend line):

    <x-admin.stat-card icon="bed" color="clsu" badge="ALL WINGS" label="Total Rooms" delay="40" value-id="statTotalNum">
        {{ $totalRooms }}
        <x-slot:footnote><p class="text-xs text-stone-400">Across {{ $wings }} wings</p></x-slot:footnote>
    </x-admin.stat-card>

    Pass `dark` for the single "hero" accent card per row (dark green gradient, white text).

    Color chip/badge classes are written out literally per color (not built with
    string interpolation) so Tailwind's build-time scanner can actually see them —
    an interpolated "from-{{ $color }}-50" would never get generated.
--}}

@php
    $colorMap = [
        'clsu' => [
            'chip'  => 'bg-gradient-to-br from-clsu-50 to-clsu-100 text-clsu-700 ring-1 ring-clsu-100',
            'badge' => 'text-clsu-700 bg-clsu-50',
        ],
        'palay' => [
            'chip'  => 'bg-gradient-to-br from-palay-50 to-palay-100 text-palay-700 ring-1 ring-palay-100',
            'badge' => 'text-palay-700 bg-palay-50',
        ],
        'ember' => [
            'chip'  => 'bg-gradient-to-br from-ember-50 to-ember-50 text-ember-600 ring-1 ring-ember-50',
            'badge' => 'text-ember-700 bg-ember-50',
        ],
        'sky' => [
            'chip'  => 'bg-gradient-to-br from-sky-50 to-sky-100 text-sky-700 ring-1 ring-sky-100',
            'badge' => 'text-sky-700 bg-sky-50',
        ],
    ];
    $colors = $colorMap[$color] ?? $colorMap['clsu'];

    $cardClasses = $dark
        ? 'relative overflow-hidden bg-gradient-to-br from-clsu-800 via-clsu-900 to-clsu-950 rounded-2xl shadow-glow hover:-translate-y-0.5 transition-all duration-200 p-6 text-white'
        : 'bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:-translate-y-0.5 transition-all duration-200 p-6';

    $chipClasses = $dark
        ? 'w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center ring-1 ring-white/10'
        : 'w-11 h-11 rounded-xl flex items-center justify-center ' . $colors['chip'];

    $badgeClasses = $dark
        ? 'text-[10px] font-bold bg-palay-400/20 text-palay-100 rounded-full px-2.5 py-1 tracking-wide'
        : 'text-[10px] font-bold rounded-full px-2.5 py-1 tracking-wide ' . $colors['badge'];
@endphp

<div {{ $attributes->merge(['class' => 'animate-in ' . $cardClasses]) }} @if($delay) style="animation-delay:{{ $delay }}ms" @endif>
    @if($dark)
        <div aria-hidden="true" class="absolute -right-8 -bottom-10 w-40 h-40 rounded-full bg-palay-400/10 blur-2xl"></div>
    @endif
    <div class="flex items-start justify-between {{ $dark ? 'relative z-10' : '' }}">
        <div class="{{ $chipClasses }}">
            <x-admin.icon :name="$icon" class="w-5 h-5" />
        </div>
        @if($badge)
            <span class="{{ $badgeClasses }}">{{ $badge }}</span>
        @endif
    </div>
    <p class="text-sm {{ $dark ? 'text-clsu-200' : 'text-stone-500' }} mt-4 {{ $dark ? 'relative z-10' : '' }}">{{ $label }}</p>
    <p @if($valueId) id="{{ $valueId }}" @endif class="text-3xl font-bold font-data tabnum mt-1 {{ $dark ? 'relative z-10' : 'text-stone-900' }}">{{ $slot }}</p>
    @isset($footnote)
        <div @if($footnoteId) id="{{ $footnoteId }}" @endif class="mt-2 {{ $dark ? 'relative z-10' : '' }}">{{ $footnote }}</div>
    @endisset
</div>
