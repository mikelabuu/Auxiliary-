@props(['value', 'label'])

{{-- Stats-band cell: italic gold Lora numeral over tracked label --}}
<div {{ $attributes->merge(['class' => 'px-6 py-10 text-center']) }}>
    <p class="font-display text-4xl italic text-gold md:text-5xl">{{ $value }}</p>
    <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.3em] text-cream/70">{{ $label }}</p>
</div>
