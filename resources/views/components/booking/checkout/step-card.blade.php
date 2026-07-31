{{-- Checkout section card: numbered step header with icon, optional right-side
     `aside` slot (badge, action button), body in the default slot. --}}
@props(['icon', 'step', 'title'])
{{-- Cream Boutique surface, matching the discount and payment pages. Only
     the checkout uses this component, so it moved off the night palette
     with the page rather than needing a variant. --}}
<div {{ $attributes->merge(['class' => 'bg-cream-warm rounded-3xl p-6 sm:p-8 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]']) }}>
    <div class="flex items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0"><i class="fa-solid fa-{{ $icon }} text-[18px]"></i></span>
            <div>
                <span class="block text-[9px] font-black text-stone-500 uppercase tracking-[0.18em] leading-none">{{ $step }}</span>
                <h4 class="text-sm font-bold text-ink tracking-tight mt-1">{{ $title }}</h4>
            </div>
        </div>
        @isset($aside){{ $aside }}@endisset
    </div>
    {{ $slot }}
</div>
