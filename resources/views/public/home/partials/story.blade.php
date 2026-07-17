<!-- Manifesto + feature trio -->
<section class="mx-auto max-w-7xl px-6 py-24 md:py-32">
    <p class="text-balance max-w-3xl pb-1 font-display text-3xl leading-[1.3] text-ink sm:text-4xl md:text-[2.75rem]" data-aos="fade-up">
        A working farmstead by day, a quiet <span class="italic text-gold">residence</span> by night. Six kinds of rooms, one long table, and the fields outside your window.
    </p>
    <div class="mt-16 grid gap-10 md:grid-cols-3 md:gap-8">
        <x-booking.cards.feature title="Heart of campus"
            description="Directly inside CLSU, minutes from the laboratories, lecture halls, and research fields."
            data-aos="fade-up" data-aos-delay="100" />
        <x-booking.cards.feature title="Rest, guarded"
            description="Round-the-clock campus security, with hostel staff on duty from dawn to well past dusk."
            data-aos="fade-up" data-aos-delay="200" />
        <x-booking.cards.feature title="Modern comforts"
            description="High-speed Wi-Fi, air-conditioned rooms, and inclusive guest kits in every stay."
            data-aos="fade-up" data-aos-delay="300" />
    </div>
</section>

<!-- Three chapters -->
<section class="relative py-8 md:py-12">
    <div class="mx-auto max-w-7xl px-6">
        <x-booking.sections.heading class="mb-20" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
            A stay written in <span class="italic text-gold">three chapters</span>
        </x-booking.sections.heading>

        <div class="space-y-24 md:space-y-32">
            <x-booking.cards.chapter title="A quiet farmstead inside a working campus"
                image="image/2.jpg" alt="The CLSU campus surrounding Farmers Hostel">
                Wake to mist over the CLSU rice paddies. Walk two minutes to the research fields, the laboratories, or a proper Filipino breakfast. Every stay is grounded in place.
            </x-booking.cards.chapter>

            <x-booking.cards.chapter title="Considered spaces, made for rest"
                image="image/deluxe.jpg" alt="Deluxe room interior at Farmers Hostel" :flip="true">
                Rattan, walnut, brass, and crisp linen. Every room is finished with the same restraint: a boutique lodge in the middle of the fields, never a dormitory.
            </x-booking.cards.chapter>
        </div>
    </div>

    <!-- Chapter three breaks the split rhythm: full-bleed dining band -->
    <x-booking.sections.band image="image/4.jpg" alt="Filipino silog breakfast served at the hostel" class="mt-24 md:mt-32">
        <div class="relative mx-auto flex min-h-[70dvh] max-w-7xl items-center px-6">
            <div class="max-w-xl py-28" data-aos="fade-up">
                <h3 class="text-balance pb-1 font-display text-3xl leading-[1.15] text-bone md:text-5xl">Breakfast, on the house, every morning</h3>
                <p class="text-pretty mt-6 text-base leading-relaxed text-bone/75">
                    Garlic rice, tapa, mango, and a cup of brewed barako, set at your table before you leave for the field. Simple, generous, unmistakably Filipino.
                </p>
                <div class="mt-8 h-px w-16 bg-gold/70"></div>
            </div>
        </div>
    </x-booking.sections.band>
</section>
