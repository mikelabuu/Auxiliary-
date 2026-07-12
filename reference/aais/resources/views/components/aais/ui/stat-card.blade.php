@props([
    'value',
    'label',
    'icon' => null,
    'bg' => 'stat-icon-green',
    'trend' => null,
    'up' => true,
])

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;">
        @if ($icon)
            <div class="stat-icon {{ $bg }}">{!! $icon !!}</div>
        @endif

        @if ($trend)
            <span class="stat-trend {{ $up ? 'stat-trend-up' : 'stat-trend-down' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="{{ $up ? 'M5 15l7-7 7 7' : 'M5 9l7 7 7-7' }}"/>
                </svg>
                {{ $trend }}
            </span>
        @endif
    </div>

    <p class="stat-value">{{ $value }}</p>
    <p class="stat-label">{{ $label }}</p>
</div>
