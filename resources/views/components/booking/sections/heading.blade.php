@props(['eyebrow', 'description' => null, 'align' => 'left'])

{{-- Editorial section heading: gold-hairline eyebrow + Lora display title (slot allows italic gold spans) --}}
<div {{ $attributes->merge(['class' => 'max-w-2xl ' . ($align === 'center' ? 'mx-auto text-center' : '')]) }}>
    <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald">
        <span class="h-px w-8 bg-emerald/50"></span>{{ $eyebrow }}
    </span>
    <h2 class="text-balance mt-4 font-display text-5xl leading-[1.05] text-ink md:text-6xl">{{ $slot }}</h2>
    @if ($description)
        <p class="mt-4 max-w-xl text-base text-ink/60 {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $description }}</p>
    @endif
</div>
