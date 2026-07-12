@props([
    'icon',
    'value',
    'label',
])

<div class="hero-stat">
    <div class="hero-stat-icon">{!! $icon !!}</div>
    <p class="hero-stat-value">{{ $value }}</p>
    <p class="hero-stat-label">{{ $label }}</p>
</div>
