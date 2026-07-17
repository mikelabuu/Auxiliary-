@props(['value', 'label'])

{{-- Stats-strip cell: italic gold Lora numeral over a tracked, muted label --}}
<div {{ $attributes->merge(['class' => 'px-6 py-10 text-center md:py-12']) }}>
    <p class="stat-value tabnum font-display text-4xl italic leading-[1.15] text-gold md:text-5xl">{{ $value }}</p>
    <p class="mt-3 text-[10px] font-bold uppercase tracking-[0.3em] text-ink/50">{{ $label }}</p>
</div>
