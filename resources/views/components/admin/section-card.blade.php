@props([
    'icon' => null,
    'color' => 'clsu',
    'title' => null,
    'subtitle' => null,
    'subtitleId' => null,
    'delay' => 0,
])

{{--
    White rounded "panel" shell used for every content block below the stat
    row (Room Types & Pricing, All Rooms, Bookings Insights, Room Status Map...).

    <x-admin.section-card icon="grid" title="All Rooms" subtitle="{{ $totalRooms }} rooms" delay="280">
        ...body...
        <x-slot:actions>...legend / button...</x-slot:actions>
    </x-admin.section-card>

    Omit `title` to get a bare panel shell with no header row.
--}}

@php
    $colorMap = config('adminui.chip_colors');
    $chipColor = $colorMap[$color] ?? $colorMap['clsu'];
@endphp

<div {{ $attributes->merge(['class' => 'animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-6']) }} @if($delay) style="animation-delay:{{ $delay }}ms" @endif>
    @if($title)
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div class="flex items-center gap-2.5">
                @if($icon)
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $chipColor }}">
                        <x-admin.icon :name="$icon" class="w-4 h-4" />
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-stone-900 text-sm">{{ $title }}</p>
                    @if($subtitle)
                        <p @if($subtitleId) id="{{ $subtitleId }}" @endif class="text-xs text-stone-400">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1.5">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
