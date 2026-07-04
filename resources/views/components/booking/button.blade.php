@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null
])

@php
    $baseStyles = "inline-flex items-center justify-center px-5 py-2.5 rounded-full text-sm font-bold tracking-wide transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 select-none cursor-pointer";
    
    $variants = [
        'primary' => 'bg-brand hover:bg-brand-light text-white shadow-[0_4px_14px_0_rgba(10,66,27,0.39)] hover:shadow-[0_6px_20px_rgba(10,66,27,0.23)] hover:-translate-y-0.5 focus:ring-brand-light',
        'secondary' => 'bg-accent-dark hover:bg-accent text-slate-900 shadow-[0_4px_14px_0_rgba(212,175,55,0.39)] hover:shadow-[0_6px_20px_rgba(212,175,55,0.23)] hover:-translate-y-0.5 focus:ring-accent',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-md hover:shadow-lg focus:ring-red-500',
        'success' => 'bg-green-600 hover:bg-green-700 text-white shadow-md hover:shadow-lg focus:ring-green-500',
        'neutral' => 'bg-gray-100 hover:bg-gray-200 text-gray-800 focus:ring-gray-300 hover:-translate-y-0.5 transition-transform'
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
