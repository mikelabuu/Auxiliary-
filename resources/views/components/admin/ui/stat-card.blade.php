@props([
    'icon' => 'grid',
    'color' => 'clsu',
    'badge' => null,
    'label' => null,
    'dark' => false,
    'delay' => 0,
    'valueId' => null,
    'footnoteId' => null,
    'progress' => null,
    'progressLabel' => null,
    'href' => null,
    'spark' => null,
    'sparkLabel' => null,
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

    // Renders as an <a> only when a destination is given, so existing
    // callers keep getting a plain <div>.
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'stat-card animate-in' . ($dark ? ' stat-card-hero' : '') . ($href ? ' stat-card-link' : '')]) }}
    @if($delay) style="animation-delay:{{ $delay }}ms" @endif>
    <div class="flex items-start justify-between">
        <div class="stat-icon {{ $iconBg }}">
            <x-admin.ui.icon :name="$icon" class="w-5 h-5" />
        </div>
        @if($badge)
            <span class="stat-period">{{ $badge }}</span>
        @endif
    </div>

    {{-- Value, with an optional trend glyph beside it. A share-meter answers
         "how much of the total?"; a sparkline answers "which way is it going?".
         Cards take whichever question actually applies to their metric. --}}
    <div class="stat-value-row">
        <p @if($valueId) id="{{ $valueId }}" @endif class="stat-value tabnum">{{ $slot }}</p>

        @if(!empty($spark))
            @php $sparkMax = max(max($spark), 1); @endphp
            <span class="stat-spark" role="img" aria-label="{{ $sparkLabel ?? 'Recent trend' }}">
                @foreach($spark as $v)
                    <span class="stat-spark__bar stat-spark__bar--{{ $color }}"
                          style="--h:{{ max(6, round(($v / $sparkMax) * 100)) }}%"></span>
                @endforeach
            </span>
        @endif
    </div>

    <p class="stat-label">
        {{ $label }}
        @if($href)
            <x-admin.ui.icon name="chevron-right" class="stat-card-go w-3.5 h-3.5" />
        @endif
    </p>

    {{-- Optional share-of-total bar. Gives the number a denominator at a
         glance — "15 available" means little until you see it's 68% of stock. --}}
    @if(!is_null($progress))
        @php $pct = max(0, min(100, (float) $progress)); @endphp
        <div class="stat-meter" role="img"
             aria-label="{{ $progressLabel ?? round($pct) . '%' }}">
            <span class="stat-meter__fill stat-meter__fill--{{ $color }}"
                  style="--meter:{{ round($pct / 100, 4) }}"></span>
        </div>
    @endif

    @isset($footnote)
        <div @if($footnoteId) id="{{ $footnoteId }}" @endif>{{ $footnote }}</div>
    @endisset
</{{ $tag }}>
