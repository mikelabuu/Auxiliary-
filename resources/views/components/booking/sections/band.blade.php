{{-- Full-bleed image band: night-graded parallax backdrop with top/bottom
     fades so the section melts into the page. Put the content container
     (a `relative` element) in the slot. data-fx-band gives it the Siena
     mask reveal — inset rounded frame expanding to full bleed on scroll
     (scroll-effects.js); without JS it simply renders full-bleed. --}}
@props([
    'image',
    'alt' => '',
    'overlay' => 'bg-night/60',
    'imageClass' => '',
])
<div data-fx-band {{ $attributes->merge(['class' => 'relative overflow-hidden']) }}>
    <img src="{{ asset($image) }}" alt="{{ $alt }}" loading="lazy" decoding="async"
         class="img-night-grade absolute inset-0 h-full w-full scale-110 object-cover {{ $imageClass }}" data-prlx-y="-0.08" data-prlx-ease="0.06">
    <div class="absolute inset-0 {{ $overlay }}"></div>
    <div class="absolute inset-x-0 top-0 h-40 bg-linear-to-b from-night to-transparent"></div>
    <div class="absolute inset-x-0 bottom-0 h-40 bg-linear-to-t from-night to-transparent"></div>
    {{ $slot }}
</div>
