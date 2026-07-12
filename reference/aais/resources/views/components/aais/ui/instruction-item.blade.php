@props([
    'number',
    'title',
    'description',
])

<div class="instruction-item">
    <span class="instruction-num">{{ $number }}</span>
    <div>
        <p class="instruction-title">{{ $title }}</p>
        <p class="instruction-desc">{{ $description }}</p>
    </div>
</div>
