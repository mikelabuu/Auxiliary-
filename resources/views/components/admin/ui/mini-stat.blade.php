@props([
    'icon' => 'grid',
    'color' => 'clsu',
    'label' => null,
    'valueId' => null,
])

{{--
    Small horizontal icon+number card for secondary metric strips.

    <x-admin.ui.mini-stat icon="arrival" label="Check-ins this week" value-id="statCheckins">{{ $checkinsThisWeek }}</x-admin.ui.mini-stat>
--}}

@php
    $iconBg = match ($color) {
        'palay' => 'stat-icon-gold',
        'ember' => 'stat-icon-rose',
        'sky'   => 'stat-icon-blue',
        default => 'stat-icon-green',
    };
@endphp

<div {{ $attributes->merge(['class' => 'mini-stat']) }}>
    <div class="stat-icon {{ $iconBg }}">
        <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px]" />
    </div>
    <div class="min-w-0">
        <p @if($valueId) id="{{ $valueId }}" @endif class="mini-stat-value">{{ $slot }}</p>
        <p class="mini-stat-label">{{ $label }}</p>
    </div>
</div>
