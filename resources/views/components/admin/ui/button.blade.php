@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null
])

@php
    $variants = [
        'primary'   => 'btn btn-primary',
        'secondary' => 'btn btn-outline',
        'danger'    => 'btn btn-danger',
        'success'   => 'btn btn-primary',
        'gold'      => 'btn btn-gold',
        'neutral'   => 'btn btn-ghost',
        'ghost'     => 'btn btn-ghost',
        'outline'   => 'btn btn-outline',
    ];

    $classes = $variants[$variant] ?? $variants['primary'];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes . ' !no-underline']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
