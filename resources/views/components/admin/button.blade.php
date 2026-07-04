@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null
])

@php
    $baseStyles = "inline-flex items-center justify-center px-4 py-2.5 rounded-md text-xs font-bold tracking-wider uppercase transition-all duration-200 focus:outline-none focus:ring-1 select-none cursor-pointer border";
    
    $variants = [
        'primary' => 'bg-sage-tertiary border-sage-tertiary text-white hover:bg-sage-tertiary/90 shadow-sm focus:ring-sage-tertiary',
        'secondary' => 'bg-white border-sage-secondary/20 text-sage-primary hover:bg-sage-neutral focus:ring-sage-secondary',
        'danger' => 'bg-red-650 border-red-650 text-white hover:bg-red-700 focus:ring-red-500',
        'success' => 'bg-sage-primary border-sage-primary text-white hover:bg-sage-primary/95 focus:ring-sage-primary',
        'neutral' => 'bg-sage-neutral border-sage-secondary/10 text-sage-secondary hover:text-sage-primary hover:bg-sage-secondary/10 focus:ring-sage-secondary'
    ];
    
    $classes = $baseStyles . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
