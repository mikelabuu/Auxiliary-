{{-- data-fx-card: cinematic card handoff — arrives as a lifted rounded card
     and settles to full bleed as the hero recedes (scroll-effects.js) --}}
<section class="border-b border-white/10 px-6" data-fx-card>
    <div class="mx-auto grid max-w-6xl grid-cols-2 md:grid-cols-4 md:divide-x md:divide-white/10" data-aos="fade-up">
        <x-booking.cards.stat :value="count($roomTypes)" label="Room Types" />
        <x-booking.cards.stat value="24/7" label="Front Desk" />
        <x-booking.cards.stat value="2 min" label="Walk to the Labs" />
        <x-booking.cards.stat value="₱{{ number_format($minPrice ?? 1600) }}" label="From, Per Night" />
    </div>
</section>
