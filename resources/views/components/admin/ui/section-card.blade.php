@props([
    'icon' => null,
    'color' => 'clsu',
    'title' => null,
    'subtitle' => null,
    'subtitleId' => null,
    'delay' => 0,
    'flush' => false,
])

{{--
    Content panel (AAIS .card): optional .card-header with icon/title/subtitle
    and an `actions` slot, body wrapped in .card-body.

    <x-admin.ui.section-card icon="grid" title="All Rooms" subtitle="{{ $totalRooms }} rooms" delay="280">
        ...body...
        <x-slot:actions>...legend / button...</x-slot:actions>
    </x-admin.ui.section-card>

    Omit `title` for a bare panel. Pass `flush` to drop the body padding
    (e.g. full-bleed tables).
--}}

<div {{ $attributes->merge(['class' => 'card animate-in']) }} @if($delay) style="animation-delay:{{ $delay }}ms" @endif>
    @if($title)
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    @if($icon)
                        <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px] shrink-0" style="color:var(--color-g-600);" />
                    @endif
                    {{ $title }}
                </h3>
                @if($subtitle)
                    <p @if($subtitleId) id="{{ $subtitleId }}" @endif class="card-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="card-header-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class(['card-body' => ! $flush])>
        {{ $slot }}
    </div>
</div>
