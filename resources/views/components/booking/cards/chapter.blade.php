@props(['index' => null, 'label' => null, 'title', 'image', 'alt' => '', 'flip' => false])

{{-- Alternating editorial image/text block. The image eases in with a soft
     zoom, the copy slides in from its own side; body copy renders with a
     gold drop cap. ($index / $label kept for backwards compatibility.) --}}
<div {{ $attributes->merge(['class' => 'grid items-center gap-10 md:grid-cols-2 md:gap-16']) }}>
    <div class="relative {{ $flip ? 'md:order-2' : '' }}" data-aos="zoom-soft">
        {{-- Depth window: the image is oversized and drifts against the scroll
             inside its clipped frame. Hover zoom transitions `scale` only, so
             it composes with (never fights) parallax.js's inline transform. --}}
        <div class="overflow-hidden rounded-3xl ring-1 ring-white/10 shadow-night-card">
            <x-img :src="$image" :alt="$alt ?: $title" loading="lazy" decoding="async"
                   data-prlx-y="-0.09" data-prlx-ease="0.06"
                   sizes="(max-width: 768px) 100vw, 50vw"
                   class="aspect-[4/5] w-full scale-[1.12] object-cover transition-[scale] duration-[1200ms] ease-[cubic-bezier(0.22,1,0.36,1)] hover:scale-[1.16] md:aspect-[5/6]" />
        </div>
        <div class="pointer-events-none absolute -bottom-4 -right-4 h-24 w-24 rounded-full bg-gold/20 blur-2xl" data-prlx-y="0.22" data-prlx-ease="0.05"></div>
    </div>
    <div class="max-w-lg {{ $flip ? 'md:order-1' : '' }}" data-aos="{{ $flip ? 'fade-right' : 'fade-left' }}" data-aos-delay="120">
        <h3 class="text-balance pb-1 font-display text-3xl leading-[1.15] text-ink md:text-4xl">{{ $title }}</h3>
        <p class="drop-cap text-pretty mt-5 text-base leading-relaxed text-ink/60" style="max-width:62ch">{{ $slot }}</p>
        <div class="mt-8 h-px w-16 bg-gold/70"></div>
    </div>
</div>
