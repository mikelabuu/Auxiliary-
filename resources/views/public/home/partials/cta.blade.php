<!-- Final CTA (full-bleed evening band) -->
<section class="mt-8">
    <x-booking.sections.band image="image/hostel1.jpg" alt="Farmers Hostel exterior in the evening" overlay="bg-night/70" image-class="object-top">
        <div class="relative mx-auto flex min-h-[60dvh] max-w-4xl flex-col items-center justify-center px-6 py-28 text-center" data-aos="fade-up">
            <span aria-hidden="true" class="block h-px w-12 bg-gold/70"></span>
            <h2 class="text-balance mt-6 pb-1 font-display text-4xl leading-[1.12] text-bone md:text-6xl">Ready for your <span class="italic text-gold">campus stay?</span></h2>
            <p class="mx-auto mt-4 max-w-md text-base text-bone/70">Pick your dates, choose a room, and confirm. No prepayment, and Senior or PWD guests save 20%.</p>
            <button type="button" onclick="smoothScrollTo(document.getElementById('rooms'))" class="press focus-ring mt-9 inline-flex min-h-12 items-center gap-2 rounded-full bg-bone px-9 py-4 text-[12px] font-semibold uppercase tracking-[0.2em] text-night cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_32%,transparent),0_18px_44px_-18px_rgba(0,0,0,0.85)]">
                <x-booking.ui.icon name="calendar" class="h-4 w-4" />
                Reserve your stay
            </button>
        </div>
    </x-booking.sections.band>
</section>

<!-- Mobile sticky reserve bar (revealed after the hero by home.js) -->
<div id="mobileStickyBar" class="fixed bottom-0 left-0 right-0 z-40 flex translate-y-full items-center justify-between border-t border-white/12 bg-night-2/90 p-4 backdrop-blur-xl transition-transform duration-500 md:hidden shadow-[0_-16px_40px_rgba(0,0,0,0.5)]">
    <div>
        <p class="text-[9px] font-bold uppercase tracking-[0.28em] text-bone/50">Starting from</p>
        <p class="font-display text-lg text-ink">₱{{ number_format($minPrice ?? 1600) }} <span class="text-[10px] uppercase tracking-[0.2em] text-ink/50">/ night</span></p>
    </div>
    <button type="button" onclick="smoothScrollTo(document.getElementById('rooms'))" class="press inline-flex min-h-11 items-center rounded-full bg-bone px-6 py-2.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-night cursor-pointer">
        Reserve
    </button>
</div>
