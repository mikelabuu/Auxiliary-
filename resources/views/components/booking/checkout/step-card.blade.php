{{-- Checkout section card: numbered step header with icon, optional right-side
     `aside` slot (badge, action button), body in the default slot. --}}
@props(['icon', 'step', 'title'])
<div {{ $attributes->merge(['class' => 'bg-gradient-to-b from-white/[0.06] to-white/[0.03] rounded-3xl p-6 sm:p-8 ring-1 ring-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.07),0_24px_50px_-30px_rgba(0,0,0,0.8)]']) }}>
    <div class="flex items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-gold/10 text-gold ring-1 ring-gold/25 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">{{ $icon }}</span></span>
            <div>
                <span class="block text-[9px] font-black text-ink/45 uppercase tracking-[0.18em] leading-none">{{ $step }}</span>
                <h4 class="text-sm font-bold text-ink tracking-tight mt-1">{{ $title }}</h4>
            </div>
        </div>
        @isset($aside){{ $aside }}@endisset
    </div>
    {{ $slot }}
</div>
