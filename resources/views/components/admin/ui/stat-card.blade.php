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
    KPI stat card (AAIS .stat-card). Default slot = the big value.
    Named `footnote` slot = the small line underneath.

    <x-admin.ui.stat-card icon="bed" color="clsu" badge="ALL WINGS" label="Total Rooms" delay="40" value-id="statTotalNum">
        {{ $totalRooms }}
        <x-slot:footnote><p class="text-xs text-faint">Across {{ $wings }} wings</p></x-slot:footnote>
    </x-admin.ui.stat-card>

    `dark` renders the hero accent variant (vivid emerald gradient).
--}}

@php
    $iconBg = match ($color) {
        'palay' => 'stat-icon-gold',
        'ember' => 'stat-icon-rose',
        'sky'   => 'stat-icon-blue',
        default => 'stat-icon-green',
    };
@endphp

<div {{ $attributes->merge(['class' => 'stat-card animate-in' . ($dark ? ' stat-card-hero' : '')]) }} @if($delay) style="animation-delay:{{ $delay }}ms" @endif>
    <div class="flex items-start justify-between">
        <div class="stat-icon {{ $iconBg }}">
            <x-admin.ui.icon :name="$icon" class="w-5 h-5" />
        </div>
        @if($badge)
            <span class="stat-period">{{ $badge }}</span>
        @endif
    </div>

    <p @if($valueId) id="{{ $valueId }}" @endif class="stat-value tabnum">{{ $slot }}</p>
    <p class="stat-label">{{ $label }}</p>

    @isset($footnote)
        <div @if($footnoteId) id="{{ $footnoteId }}" @endif>{{ $footnote }}</div>
    @endisset
</div>
