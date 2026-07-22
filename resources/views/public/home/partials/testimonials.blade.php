@php
    $testimonials = [
        ['quote' => 'Perfect location for my research week at CLSU. The rooms are spotless, the Wi-Fi is reliable, and being right on campus saved me hours of commute.', 'name' => 'Dr. Reyes', 'role' => 'Visiting Professor', 'initials' => 'DR'],
        ['quote' => 'The staff is exceptionally accommodating. We booked the dormitory for our student organization retreat and the facilities exceeded expectations.', 'name' => 'Maria C.', 'role' => 'Student Leader', 'initials' => 'MC'],
        ['quote' => 'A peaceful place surrounded by nature, and a proper rest after a long day of meetings. The breakfast alone is worth the stay.', 'name' => 'Juan P.', 'role' => 'Government Official', 'initials' => 'JP'],
        ['quote' => 'The Deluxe Room felt genuinely premium, and the hot shower was perfect. I will be booking again next harvest season.', 'name' => 'Alumni Sy', 'role' => 'CLSU Alumni', 'initials' => 'AS'],
    ];
@endphp

<section class="relative overflow-hidden border-t border-white/10 py-24 md:py-28">
    <div class="mx-auto max-w-4xl px-6">
        <div class="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
            {{-- data-split-text: reveal.js splits this into per-character spans
                 that rise + fade as the block reveals (reactbits SplitText port) --}}
            <h2 data-split-text class="text-balance max-w-xl pb-1 font-display text-4xl leading-[1.12] text-ink md:text-5xl">
                Loved by <span class="italic text-gold">academics</span> and travelers alike.
            </h2>
            <div class="flex items-center gap-3">
                <button class="swiper-button-prev-custom focus-ring press grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-white/5 text-ink transition hover:bg-bone hover:text-night cursor-pointer" aria-label="Previous testimonial">
                    <x-booking.ui.icon name="chevron-left" class="h-4 w-4" />
                </button>
                <button class="swiper-button-next-custom focus-ring press grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-white/5 text-ink transition hover:bg-bone hover:text-night cursor-pointer" aria-label="Next testimonial">
                    <x-booking.ui.icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
        </div>

        {{-- Card deck (Swiper `cards` effect, Skiper-48-style): quotes stack
             like a held hand of cards — the next one peeks from behind, and
             the deck is draggable as well as button-driven. Deck sizing and
             overflow overrides live in app.css (.testimonials-swiper). --}}
        <div class="swiper testimonials-swiper mt-10" data-aos="fade-up" data-aos-delay="150">
            <div class="swiper-wrapper">
                @foreach ($testimonials as $t)
                    <div class="swiper-slide h-auto">
                        <div class="glass-night h-full rounded-3xl px-7 py-2 md:px-9">
                            <x-booking.cards.testimonial
                                :quote="$t['quote']"
                                :name="$t['name']"
                                :role="$t['role']"
                                :initials="$t['initials']"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
