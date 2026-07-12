@props([
    'name',
    'count',
    'percent',
])

<div class="breakdown-row">
    <span class="breakdown-label">{{ $name }}</span>
    <div class="breakdown-track">
        <div class="breakdown-fill" style="width:{{ $percent }}%;"></div>
    </div>
    <span class="breakdown-count">{{ $count }}</span>
</div>
