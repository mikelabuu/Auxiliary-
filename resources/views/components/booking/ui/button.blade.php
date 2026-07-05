@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
])

@php
    $baseStyles = "inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-full text-sm font-bold tracking-wide transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 select-none cursor-pointer";

    $variants = [
        // Forest-green gradient — the primary CTA
        'primary'   => 'text-white bg-gradient-to-b from-clsu-600 to-clsu-800 shadow-[0_6px_16px_-4px_rgba(17,78,40,0.5)] hover:shadow-[0_10px_24px_-6px_rgba(17,78,40,0.6)] hover:-translate-y-0.5 focus:ring-clsu-400',
        // Palay gold — secondary emphasis
        'secondary' => 'text-clsu-900 bg-gradient-to-b from-palay-300 to-palay-400 shadow-[0_6px_16px_-4px_rgba(240,164,0,0.5)] hover:shadow-[0_10px_24px_-6px_rgba(240,164,0,0.6)] hover:-translate-y-0.5 focus:ring-palay-300',
        // Outline / ghost on the ivory canvas
        'outline'   => 'text-clsu-800 bg-white border border-clsu-200 hover:bg-clsu-50 hover:border-clsu-300 focus:ring-clsu-300',
        'danger'    => 'text-white bg-ember-600 hover:bg-ember-700 shadow-md hover:shadow-lg focus:ring-ember-500',
        'success'   => 'text-white bg-clsu-600 hover:bg-clsu-700 shadow-md hover:shadow-lg focus:ring-clsu-500',
        'neutral'   => 'text-stone-700 bg-stone-100 hover:bg-stone-200 focus:ring-stone-300 hover:-translate-y-0.5',
    ];

    $classes = $baseStyles . ' ' . ($variants[$variant] ?? $variants['primary']);
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
