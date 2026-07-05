@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'center', // center | left
    'light' => false,    // true when placed on a dark background
])

@php
    $alignClasses = $align === 'left' ? 'text-left mx-0' : 'text-center mx-auto';
    $eyebrowColor = $light ? 'text-palay-300' : 'text-clsu-700';
    $titleColor   = $light ? 'text-white' : 'text-ink';
    $descColor    = $light ? 'text-clsu-100/80' : 'text-stone-500';
@endphp

<div {{ $attributes->merge(['class' => "max-w-2xl mb-14 space-y-4 $alignClasses"]) }}>
    @if ($eyebrow)
        <span class="{{ $eyebrowColor }} font-bold tracking-[0.2em] uppercase text-[11px] inline-flex items-center gap-2 {{ $align === 'center' ? 'justify-center' : '' }}">
            <span class="w-6 h-px {{ $light ? 'bg-palay-300/60' : 'bg-clsu-300' }}"></span>
            {{ $eyebrow }}
        </span>
    @endif

    <h2 class="text-3xl sm:text-[42px] font-medium tracking-tight leading-[1.1] font-display {{ $titleColor }}">{{ $title }}</h2>

    @if ($description)
        <p class="text-[15px] font-medium leading-relaxed {{ $descColor }}">{{ $description }}</p>
    @endif
</div>
