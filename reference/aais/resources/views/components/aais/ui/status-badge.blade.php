@props([
    'status' => 'logged',
    'label' => null,
    'size' => 'md',
    'center' => false,
])

@php
    $computedLabel = $label ?? ucfirst(str_replace(['-', '_'], ' ', $status));

    $sizeStyles = [
        'xs' => 'font-size:8px;padding:2px 6px;',
        'sm' => 'font-size:8px;padding:3px 8px;',
        'md' => '',
        'lg' => 'font-size:13px;padding:6px 18px;',
    ];

    $style = trim(($attributes->get('style') ?? '') . ';' . ($sizeStyles[$size] ?? ''));

    if ($center) {
        $style .= 'min-width:95px;justify-content:center;';
    }
@endphp

<span {{ $attributes->except('style')->merge(['class' => 'status status-' . $status]) }} @if($style) style="{{ $style }}" @endif>
    {{ $computedLabel }}
</span>
