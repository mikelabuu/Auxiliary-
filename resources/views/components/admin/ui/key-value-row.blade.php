@props([
    'label',
    'value' => null,
    'border' => true,
    'labelClass' => 'section-label',
    'labelStyle' => null,
    'valueClass' => 'kv-value',
    'valueStyle' => 'font-size:13px;',
    'valueXText' => null,
])

{{--
    Label/value line for summary panels and detail asides (AAIS key-value-row).

    <x-admin.ui.key-value-row label="Room" :value="$booking->room->room_number" />
--}}

<div style="display:flex;justify-content:space-between;padding:12px 0;{{ $border ? 'border-bottom:1px solid var(--color-border);' : '' }}">
    <span class="{{ $labelClass }}" @if($labelStyle) style="{{ $labelStyle }}" @endif>{{ $label }}</span>
    <span class="{{ $valueClass }}" @if($valueStyle) style="{{ $valueStyle }}" @endif @if($valueXText) x-text="{{ $valueXText }}" @endif>
        @if (!$valueXText)
            {{ $value ?? $slot }}
        @endif
    </span>
</div>
