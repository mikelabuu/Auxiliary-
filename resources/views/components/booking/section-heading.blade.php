@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'center', // center | left
    'light' => false, // true when placed on a dark background
])

@php
    $alignClasses = $align === 'left' ? 'text-left mx-0' : 'text-center mx-auto';
    $eyebrowColor = $light ? 'text-accent' : 'text-brand';
    $titleColor = $light ? 'text-white' : 'text-slate-950';
    $descColor = $light ? 'text-slate-300' : 'text-slate-500';
@endphp

<div {{ $attributes->merge(['class' => "max-w-3xl mb-16 space-y-4 $alignClasses"]) }}>
    @if ($eyebrow)
        <span class="{{ $eyebrowColor }} font-bold tracking-widest uppercase text-xs block">{{ $eyebrow }}</span>
    @endif

    <h2 class="text-3xl sm:text-4xl font-black tracking-tight {{ $titleColor }}">{{ $title }}</h2>

    @if ($description)
        <p class="text-sm font-semibold leading-relaxed {{ $descColor }}">{{ $description }}</p>
    @endif
</div>
