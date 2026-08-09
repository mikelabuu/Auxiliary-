@props([
    // wave | bounce | double-bounce | circle
    'variant' => 'wave',
    // sm | md | (default) | lg
    'size' => 'md',
    // Announced to assistive tech and, when `block` is set, shown as text.
    'label' => 'Loading',
    // Renders the spinner centred in a padded box with the label under it.
    'block' => false,
    // Inherit the surrounding text colour instead of brand green.
    'current' => false,
])

@php
    // Each variant needs a different number of children; generating them here
    // keeps twelve <span>s out of every call site.
    $children = match ($variant) {
        'bounce'        => 3,
        'double-bounce' => 2,
        'chase'         => 6,
        'circle'        => 12,
        default         => 5,
    };

    $classes = trim(implode(' ', [
        'sk',
        'sk-' . ($variant === 'circle' ? 'fading-circle' : $variant),
        $size ? 'sk-' . $size : '',
        $current ? 'sk-current' : '',
    ]));
@endphp

@if($block)
    <div class="sk-block" role="status">
        <span class="{{ $classes }}" aria-hidden="true">
            @for($i = 0; $i < $children; $i++)<span></span>@endfor
        </span>
        <span class="sk-block__label">{{ $label }}</span>
    </div>
@else
    {{-- role=status makes the label announce when it appears, without stealing
         focus. aria-hidden on the shape itself keeps the dots out of the
         accessibility tree — they carry no meaning, the label does. --}}
    <span {{ $attributes->merge(['class' => $classes]) }} role="status" aria-label="{{ $label }}">
        @for($i = 0; $i < $children; $i++)<span aria-hidden="true"></span>@endfor
    </span>
@endif
