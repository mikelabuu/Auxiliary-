@props([
    'period',
    'value',
    'label',
])

<div class="stat-card {{ str_contains(strtolower($label), 'pickup') ? 'stat-card-pickup' : '' }}">
    <span class="stat-period">{{ $period }}</span>
    <p class="stat-value stat-value-compact">{{ $value }}</p>
    <p class="stat-label">{{ $label }}</p>
</div>
