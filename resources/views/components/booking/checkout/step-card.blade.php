{{-- Checkout section card: numbered step header with icon, optional right-side
     `aside` slot (badge, action button), body in the default slot. --}}
@props(['icon', 'step', 'title'])
{{-- Cream Boutique surface, matching the discount and payment pages. Only
     the checkout uses this component, so it moved off the night palette
     with the page rather than needing a variant. --}}
{{-- Padding steps up with the viewport instead of holding 24px everywhere.
     These cards nest — page gutter, then this card, then a reservation block
     inside it — and three levels of desktop padding took 128px out of a 375px
     screen before any content was laid out. What was left could not hold a
     room-style card, so the price line broke mid-phrase. --}}
<div {{ $attributes->merge(['class' => 'bg-cream-warm rounded-3xl p-4 sm:p-6 lg:p-8 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]']) }}>
    {{-- Stacked below sm, side by side above it. Kept on one row, the aside
         wins the width fight: step 3's "Add room for 1 more" button held 171
         of the 295px available and crushed "Room Allocation" onto two lines
         while wrapping itself onto three. Neither element is optional, so on a
         phone they get a row each rather than half a row each. --}}
    <div class="mb-5 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0"><x-booking.ui.icon-solid :name="$icon" class="text-[18px]" /></span>
            {{-- min-w-0 so a long title shrinks within the row rather than
                 forcing the flex item past its parent. --}}
            <div class="min-w-0">
                <span class="block text-[10px] font-black text-stone-500 uppercase tracking-[0.18em] leading-none">{{ $step }}</span>
                <h4 class="text-sm font-bold text-ink tracking-tight mt-1">{{ $title }}</h4>
            </div>
        </div>
        @isset($aside){{ $aside }}@endisset
    </div>
    {{ $slot }}
</div>
