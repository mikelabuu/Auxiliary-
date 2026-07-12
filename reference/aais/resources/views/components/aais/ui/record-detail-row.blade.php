@props([
    'label',
    'value' => null,
    'valueXText' => null,
])

<div class="record-detail-row">
    <span class="record-detail-label">{{ $label }}</span>
    <span class="record-detail-value" @if($valueXText) x-text="{{ $valueXText }}" @endif>
        @if (!$valueXText)
            {{ $value ?? $slot }}
        @endif
    </span>
</div>
