@props([
    'variant' => 'primary',
    'size' => null,
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

    // .btn-sm / .btn-lg from 04-components.css. `sm` is what a card header
    // takes: an action sitting beside a card title has to be quieter than the
    // title, and a full-size button there outweighs the heading it belongs to.
    $sizes = ['sm' => ' btn-sm', 'lg' => ' btn-lg'];

    $classes = ($variants[$variant] ?? $variants['primary']) . ($sizes[$size] ?? '');
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
