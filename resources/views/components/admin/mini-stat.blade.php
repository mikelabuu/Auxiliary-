@props([
    'icon' => 'grid',
    'color' => 'clsu',
    'label' => null,
    'valueId' => null,
])

{{--
    Small inline icon+number card used in "secondary metrics" strips
    (dashboard's check-ins/check-outs/maintenance row, rooms' cleaning/wings/types row).
    Default slot = the value.

    <x-admin.mini-stat icon="arrival" label="Check-ins this week" value-id="statCheckins">{{ $checkinsThisWeek }}</x-admin.mini-stat>
--}}

@php
    $colorMap = [
        'clsu'  => 'bg-clsu-50 text-clsu-700',
        'palay' => 'bg-palay-100 text-palay-700',
        'ember' => 'bg-ember-50 text-ember-600',
    ];
    $chip = $colorMap[$color] ?? $colorMap['clsu'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-stone-200/70 shadow-card p-4 flex items-center gap-3.5']) }}>
    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $chip }}">
        <x-admin.icon :name="$icon" class="w-[18px] h-[18px]" />
    </div>
    <div>
        <p @if($valueId) id="{{ $valueId }}" @endif class="text-lg font-bold font-data text-stone-900 leading-none">{{ $slot }}</p>
        <p class="text-[11px] text-stone-500 mt-1">{{ $label }}</p>
    </div>
</div>
