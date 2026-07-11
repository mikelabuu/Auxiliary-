@props(['index', 'label', 'title', 'image', 'alt' => '', 'flip' => false])

{{-- Alternating editorial image/text block ("A stay written in three chapters").
     Body copy goes in the slot and renders with a gold drop cap. --}}
<div {{ $attributes->merge(['class' => 'grid items-center gap-10 md:grid-cols-2 md:gap-16']) }} data-aos="fade-up">
    <div class="relative {{ $flip ? 'md:order-2' : '' }}">
        {{-- Depth window: the image is oversized and drifts against the scroll
             inside its clipped frame. Hover zoom transitions `scale` only, so
             it composes with (never fights) parallax.js's inline transform. --}}
        <div class="overflow-hidden rounded-3xl shadow-boutique-card">
            <img src="{{ asset($image) }}" alt="{{ $alt ?: $title }}" loading="lazy" decoding="async"
                 data-prlx-y="-0.09" data-prlx-ease="0.06"
                 class="aspect-[4/5] w-full scale-[1.12] object-cover transition-[scale] duration-[1200ms] ease-[cubic-bezier(0.22,1,0.36,1)] hover:scale-[1.16] md:aspect-[5/6]">
        </div>
        <div class="pointer-events-none absolute -bottom-4 -right-4 h-24 w-24 rounded-full bg-gold/25 blur-2xl" data-prlx-y="0.22" data-prlx-ease="0.05"></div>
    </div>
    <div class="max-w-lg {{ $flip ? 'md:order-1' : '' }}">
        <p class="text-[10px] font-bold uppercase tracking-[0.32em] text-emerald">Chapter {{ $index }} · {{ $label }}</p>
        <h3 class="text-balance mt-4 font-display text-3xl leading-tight text-ink md:text-4xl">{{ $title }}</h3>
        <p class="drop-cap text-pretty mt-5 text-base leading-relaxed text-ink/60" style="max-width:62ch">{{ $slot }}</p>
        <div class="mt-8 h-px w-16 bg-gold"></div>
    </div>
</div>
