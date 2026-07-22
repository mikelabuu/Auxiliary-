@props(['eyebrow' => null, 'description' => null, 'align' => 'left'])

{{-- Editorial section heading. Eyebrow is optional and rationed: most
     sections should let the headline speak alone. Slot allows italic
     gold spans; leading + pb reserve keeps italic descenders unclipped. --}}
<div {{ $attributes->merge(['class' => 'max-w-2xl ' . ($align === 'center' ? 'mx-auto text-center' : '')]) }}>
    @if ($eyebrow)
        <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-gold">
            <span class="h-px w-8 bg-gold/50"></span>{{ $eyebrow }}
        </span>
    @endif
    {{-- data-split-text: reveal.js splits the headline into per-character spans
         that rise + fade in as the block reveals (reactbits SplitText port) --}}
    <h2 data-split-text class="text-balance {{ $eyebrow ? 'mt-4' : '' }} pb-1 font-display text-4xl leading-[1.12] text-ink sm:text-5xl md:text-6xl">{{ $slot }}</h2>
    @if ($description)
        <p class="mt-5 max-w-xl text-base leading-relaxed text-ink/60 {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $description }}</p>
    @endif
</div>
