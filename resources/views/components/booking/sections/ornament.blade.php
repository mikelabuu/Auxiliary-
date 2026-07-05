@props(['numeral', 'label'])

{{-- Roman-numeral chapter divider between landing sections --}}
<div role="presentation" {{ $attributes->merge(['class' => 'mx-auto flex w-full max-w-xs flex-col items-center gap-3 py-6 text-center']) }}>
    <span aria-hidden="true" class="h-px w-12 bg-gold/70"></span>
    <span class="font-display text-xl italic text-gold/90">{{ $numeral }}</span>
    <span class="text-[10px] font-bold uppercase tracking-[0.4em] text-emerald/70">{{ $label }}</span>
</div>
