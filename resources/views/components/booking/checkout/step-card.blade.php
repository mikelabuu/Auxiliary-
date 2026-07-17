{{-- Checkout section card: numbered step header with icon, optional right-side
     `aside` slot (badge, action button), body in the default slot. --}}
@props(['icon', 'step', 'title'])
<div {{ $attributes->merge(['class' => 'bg-white/[0.04] rounded-3xl p-6 sm:p-8 ring-1 ring-white/10']) }}>
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
