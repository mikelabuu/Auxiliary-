@props([
    'status' => 'neutral',
    'label' => null,
    'size' => 'md',
    'center' => false,
])

{{--
    AAIS status pill with a leading dot. `status` maps onto the pill palette
    in admin.css (pending/confirmed/checked_in/checked_out/completed/cancelled,
    paid/unpaid, available/occupied/cleaning/maintenance, success/danger/neutral…).

    <x-admin.ui.status-badge :status="$booking->status" />
    <x-admin.ui.status-badge status="paid" label="Payment received" size="sm" />
--}}

@php
    $computedLabel = $label ?? ucfirst(str_replace(['-', '_'], ' ', $status));

    $sizeStyles = [
        'xs' => 'font-size:8px;padding:2px 6px;',
        'sm' => 'font-size:9px;padding:3px 8px;',
        'md' => '',
        'lg' => 'font-size:13px;padding:6px 18px;',
    ];

    $style = trim(($attributes->get('style') ?? '') . ';' . ($sizeStyles[$size] ?? ''), ';');

    if ($center) {
        $style .= ($style ? ';' : '') . 'min-width:95px;justify-content:center;';
    }
@endphp

<span {{ $attributes->except('style')->merge(['class' => 'status status-' . $status]) }} @if($style) style="{{ $style }}" @endif>
    {{ $computedLabel }}
</span>
