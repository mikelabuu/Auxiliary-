{{-- Circular sunflower mark, cropped from the full FHLogo2 lockup (flower sits top-center above the wordmark) --}}
<span {{ $attributes->merge(['class' => 'relative block shrink-0 overflow-hidden rounded-full bg-cream ring-1 ring-gold/40']) }}>
    {{-- Blown up to 320% of a small mark, so it still wants a mid-tier
         derivative rather than the smallest one. --}}
    <x-img src="image/FHLogo2.png" alt="" sizes="160px"
           class="absolute max-w-none"
           style="width: 320%; left: 50%; top: 50%; transform: translate(-50%, -50%) translateY(16%);" />
</span>
