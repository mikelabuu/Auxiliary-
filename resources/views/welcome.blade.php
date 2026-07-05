@extends('layouts.guest')
@section('title', 'Farmers Hostel · Boutique Stay Inside CLSU Campus')
@section('nav_dark', '1')
@section('content')

    <!-- ====== HERO ====== -->
    <header id="firstsection" class="film-grain vignette-emerald relative isolate flex min-h-[92dvh] flex-col justify-end overflow-hidden bg-emerald-deep">
        <!-- Ken-burns backdrop -->
        <div class="absolute inset-0 z-0 will-change-transform">
            <img src="{{ asset('image/hostel1.jpg') }}" alt="Farmers Hostel exterior, nestled inside the CLSU campus" fetchpriority="high" class="h-full w-full animate-ken-burns object-cover">
            <div class="absolute inset-0 bg-linear-to-b from-ink/60 via-ink/25 to-ink/80"></div>
        </div>

        <!-- Headline -->
        <div class="relative z-10 mx-auto w-full max-w-6xl px-6 pt-36 pb-14 text-center text-cream sm:pt-44">
            <p class="text-[11px] font-semibold uppercase tracking-[0.5em] text-gold animate-[fade-in-up_0.8s_ease-out_both]">A Premium Stay on Campus · Est. 1998</p>
            <div aria-hidden="true" class="mx-auto mt-6 hairline-gold w-24"></div>
            <h1 class="text-balance mt-6 font-display leading-[1.05] animate-[fade-in-up_1s_ease-out_0.2s_both]" style="font-size:clamp(2.5rem, 8vw, 6.5rem)">
                Welcome to<br><span class="italic text-gold">Farmers</span> Hostel
            </h1>
            <p class="text-pretty mx-auto mt-6 max-w-2xl text-base leading-relaxed text-cream/85 sm:text-lg animate-[fade-in-up_1s_ease-out_0.4s_both]">
                Unparalleled comfort and convenience inside the Central Luzon State University agricultural research campus — a two-minute walk to the lab, the field, and a proper Filipino breakfast.
            </p>
            <div class="pointer-events-none mt-12 flex flex-col items-center gap-2 text-cream/70 animate-[fade-in-up_1s_ease-out_0.7s_both]">
                <span class="text-[10px] font-semibold uppercase tracking-[0.4em] text-gold/80">Scroll</span>
                <span aria-hidden="true" class="scroll-cue block h-8 w-px bg-gold/60"></span>
            </div>
        </div>

        <!-- Booking capsule -->
        <div class="relative z-10 mx-auto w-full max-w-5xl px-4 pb-14 sm:px-6 animate-[fade-in-up_1s_ease-out_0.5s_both]">
            <div id="bookingCapsule" class="rounded-3xl border border-gold/25 bg-cream-warm/85 p-4 backdrop-blur-xl shadow-capsule md:p-3">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_auto] md:items-center md:gap-2">
                    <div class="px-4 py-3 text-left md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-emerald">Check in</p>
                        <input type="text" id="widget_check_in" aria-label="Check in date" placeholder="Select date"
                               class="focus-ring w-full min-h-11 cursor-pointer bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink/40">
                    </div>
                    <div class="px-4 py-3 text-left md:border-l md:border-emerald-deep/10 md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-emerald">Check out</p>
                        <input type="text" id="widget_check_out" aria-label="Check out date" placeholder="Select date"
                               class="focus-ring w-full min-h-11 cursor-pointer bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink/40">
                    </div>
                    <div class="px-4 py-3 text-left md:border-l md:border-emerald-deep/10 md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-emerald">Guests</p>
                        <div class="flex min-h-11 items-center justify-between">
                            <span class="text-sm font-medium text-ink"><span id="guests_display">1</span> guest<span id="guests_plural" class="hidden">s</span></span>
                            <input type="hidden" id="widget_guests" value="1">
                            <div class="flex items-center gap-1">
                                <button type="button" id="btn_minus_guests" aria-label="Decrease guests" class="focus-ring press grid h-9 w-9 place-items-center rounded-full bg-cream text-ink ring-1 ring-emerald-deep/10 transition hover:ring-gold/60 cursor-pointer">
                                    <x-booking.icon name="minus" class="h-3 w-3" />
                                </button>
                                <button type="button" id="btn_plus_guests" aria-label="Increase guests" class="focus-ring press grid h-9 w-9 place-items-center rounded-full bg-cream text-ink ring-1 ring-emerald-deep/10 transition hover:ring-gold/60 cursor-pointer">
                                    <x-booking.icon name="plus" class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnSearchRooms"
                            class="focus-ring press group relative inline-flex min-h-11 items-center justify-center gap-2 overflow-hidden rounded-full bg-linear-to-r from-emerald-deep to-emerald px-8 py-4 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream shadow-[0_10px_30px_-14px_rgba(6,78,59,0.6)] transition-all cursor-pointer hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_28%,transparent),0_14px_36px_-14px_rgba(6,78,59,0.65)]">
                        <span aria-hidden="true" class="pointer-events-none absolute inset-0 rounded-full opacity-0 transition-opacity duration-500 group-hover:opacity-100" style="box-shadow:inset 0 0 24px color-mix(in oklch, var(--color-gold) 35%, transparent)"></span>
                        <span id="btnSearchRoomsLabel" class="inline-flex items-center gap-2">Search rooms <x-booking.icon name="arrow-right" class="h-4 w-4" /></span>
                    </button>
                </div>
                <div class="mt-3 flex items-center justify-center gap-2 border-t border-emerald-deep/10 pt-3 text-[10px] font-semibold uppercase tracking-[0.28em] text-emerald">
                    <span class="h-1 w-1 rounded-full bg-gold"></span>
                    Best rate guarantee · Direct booking only
                    <span class="h-1 w-1 rounded-full bg-gold"></span>
                </div>
            </div>

            <!-- Trust chips -->
            <div class="mt-8 flex flex-wrap items-center justify-center gap-y-3 divide-x divide-cream/20 text-[11px] font-medium uppercase tracking-[0.22em] text-cream/90">
                <span class="inline-flex items-center gap-2 px-5"><x-booking.icon name="map-pin" class="h-4 w-4 text-gold" /> CLSU Campus</span>
                <span class="inline-flex items-center gap-2 px-5"><x-booking.icon name="badge-check" class="h-4 w-4 text-gold" /> No prepayment</span>
                <span class="inline-flex items-center gap-2 px-5"><x-booking.icon name="clock" class="h-4 w-4 text-gold" /> 24/7 front desk</span>
                <span class="hidden items-center gap-2 px-5 sm:inline-flex"><x-booking.icon name="wifi" class="h-4 w-4 text-gold" /> Free Wi-Fi</span>
                <span class="hidden items-center gap-2 px-5 sm:inline-flex"><x-booking.icon name="tag" class="h-4 w-4 text-gold" /> Senior / PWD 20%</span>
            </div>
        </div>
    </header>

    <!-- ====== STATS BAND (overlapping hero) ====== -->
    <section class="relative -mt-6 px-4 sm:px-6">
        <div class="mx-auto max-w-6xl overflow-hidden rounded-3xl bg-emerald-deep text-cream shadow-boutique-card" data-aos="fade-up">
            <div class="grid grid-cols-2 divide-x divide-cream/10 md:grid-cols-4">
                <div class="px-6 py-10 text-center">
                    <p class="font-display text-4xl italic text-gold md:text-5xl">{{ count($roomTypes) }}</p>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.3em] text-cream/70">Room Types</p>
                </div>
                <div class="px-6 py-10 text-center">
                    <p class="font-display text-4xl italic text-gold md:text-5xl">24/7</p>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.3em] text-cream/70">Campus Security</p>
                </div>
                <div class="px-6 py-10 text-center">
                    <p class="font-display text-4xl italic text-gold md:text-5xl">100%</p>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.3em] text-cream/70">On Campus</p>
                </div>
                <div class="px-6 py-10 text-center">
                    <p class="font-display text-4xl italic text-gold md:text-5xl">₱{{ number_format($minPrice ?? 1600) }}</p>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.3em] text-cream/70">From / Night</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== FEATURES ====== -->
    <section class="mx-auto max-w-7xl px-6 py-24 md:py-28">
        <div class="grid gap-8 md:grid-cols-3">
            <div class="rounded-2xl bg-cream-warm p-8 ring-1 ring-emerald-deep/5" data-aos="fade-up" data-aos-delay="100">
                <div class="mb-6 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 ring-1 ring-emerald-deep/10">
                    <x-booking.icon name="leaf" class="h-5 w-5 text-emerald-deep" />
                </div>
                <h3 class="font-display text-2xl text-ink">Heart of Campus</h3>
                <p class="mt-3 text-sm leading-relaxed text-ink/60">Located directly inside CLSU, offering unparalleled convenience for visiting researchers, alumni, and university guests.</p>
            </div>
            <div class="rounded-2xl bg-cream-warm p-8 ring-1 ring-emerald-deep/5" data-aos="fade-up" data-aos-delay="200">
                <div class="mb-6 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 ring-1 ring-emerald-deep/10">
                    <x-booking.icon name="shield" class="h-5 w-5 text-emerald-deep" />
                </div>
                <h3 class="font-display text-2xl text-ink">24/7 Security</h3>
                <p class="mt-3 text-sm leading-relaxed text-ink/60">Peace of mind with round-the-clock campus security and dedicated hostel staff on duty from dawn to well past dusk.</p>
            </div>
            <div class="rounded-2xl bg-cream-warm p-8 ring-1 ring-emerald-deep/5" data-aos="fade-up" data-aos-delay="300">
                <div class="mb-6 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 ring-1 ring-emerald-deep/10">
                    <x-booking.icon name="wifi" class="h-5 w-5 text-emerald-deep" />
                </div>
                <h3 class="font-display text-2xl text-ink">Modern Amenities</h3>
                <p class="mt-3 text-sm leading-relaxed text-ink/60">Stay connected and comfortable — high-speed Wi-Fi, air-conditioned rooms, and inclusive guest kits in every stay.</p>
            </div>
        </div>
    </section>

    <!-- Ornament I -->
    <div role="presentation" class="mx-auto flex w-full max-w-xs flex-col items-center gap-3 py-6 text-center">
        <span aria-hidden="true" class="h-px w-12 bg-gold/70"></span>
        <span class="font-display text-xl italic text-gold/90">I</span>
        <span class="text-[10px] font-bold uppercase tracking-[0.4em] text-emerald/70">The Experience</span>
    </div>

    <!-- ====== THREE CHAPTERS ====== -->
    <section class="relative bg-cream-warm py-24 md:py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mb-16 max-w-2xl" data-aos="fade-up">
                <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald">
                    <span class="h-px w-8 bg-emerald/50"></span>The experience
                </span>
                <h2 class="text-balance mt-4 font-display text-5xl leading-[1.05] text-ink md:text-6xl">A stay written in <span class="italic text-gold">three chapters</span></h2>
            </div>

            <div class="space-y-24 md:space-y-32">
                <!-- Chapter 01 -->
                <div class="grid items-center gap-10 md:grid-cols-2 md:gap-16" data-aos="fade-up">
                    <div class="relative">
                        <div class="overflow-hidden rounded-3xl shadow-boutique-card">
                            <img src="{{ asset('image/2.jpg') }}" alt="The CLSU campus surrounding Farmers Hostel" loading="lazy" class="aspect-[4/5] w-full object-cover transition duration-[1200ms] hover:scale-[1.04] md:aspect-[5/6]">
                        </div>
                        <div class="pointer-events-none absolute -bottom-4 -right-4 h-24 w-24 rounded-full bg-gold/25 blur-2xl"></div>
                    </div>
                    <div class="max-w-lg">
                        <p class="text-[10px] font-bold uppercase tracking-[0.32em] text-emerald">Chapter 01 · The Setting</p>
                        <h3 class="text-balance mt-4 font-display text-3xl leading-tight text-ink md:text-4xl">A quiet farmstead inside a working campus</h3>
                        <p class="drop-cap text-pretty mt-5 text-base leading-relaxed text-ink/60" style="max-width:62ch">Wake to mist over the CLSU rice paddies. Walk two minutes to the research fields, the laboratories, or a proper Filipino breakfast. Every stay is grounded in place.</p>
                        <div class="mt-8 h-px w-16 bg-gold"></div>
                    </div>
                </div>

                <!-- Chapter 02 (flipped) -->
                <div class="grid items-center gap-10 md:grid-cols-2 md:gap-16" data-aos="fade-up">
                    <div class="relative md:order-2">
                        <div class="overflow-hidden rounded-3xl shadow-boutique-card">
                            <img src="{{ asset('image/deluxe.jpg') }}" alt="Deluxe room interior at Farmers Hostel" loading="lazy" class="aspect-[4/5] w-full object-cover transition duration-[1200ms] hover:scale-[1.04] md:aspect-[5/6]">
                        </div>
                        <div class="pointer-events-none absolute -bottom-4 -right-4 h-24 w-24 rounded-full bg-gold/25 blur-2xl"></div>
                    </div>
                    <div class="max-w-lg md:order-1">
                        <p class="text-[10px] font-bold uppercase tracking-[0.32em] text-emerald">Chapter 02 · The Rooms</p>
                        <h3 class="text-balance mt-4 font-display text-3xl leading-tight text-ink md:text-4xl">Considered spaces, made for rest</h3>
                        <p class="drop-cap text-pretty mt-5 text-base leading-relaxed text-ink/60" style="max-width:62ch">Rattan, walnut, brass, and crisp linen. Each of the six rooms is finished with the same restraint — a boutique lodge in the middle of the fields, never a dormitory pretending otherwise.</p>
                        <div class="mt-8 h-px w-16 bg-gold"></div>
                    </div>
                </div>

                <!-- Chapter 03 -->
                <div class="grid items-center gap-10 md:grid-cols-2 md:gap-16" data-aos="fade-up">
                    <div class="relative">
                        <div class="overflow-hidden rounded-3xl shadow-boutique-card">
                            <img src="{{ asset('image/4.jpg') }}" alt="Filipino silog breakfast served at the hostel" loading="lazy" class="aspect-[4/5] w-full object-cover transition duration-[1200ms] hover:scale-[1.04] md:aspect-[5/6]">
                        </div>
                        <div class="pointer-events-none absolute -bottom-4 -right-4 h-24 w-24 rounded-full bg-gold/25 blur-2xl"></div>
                    </div>
                    <div class="max-w-lg">
                        <p class="text-[10px] font-bold uppercase tracking-[0.32em] text-emerald">Chapter 03 · The Table</p>
                        <h3 class="text-balance mt-4 font-display text-3xl leading-tight text-ink md:text-4xl">Breakfast, on the house, every morning</h3>
                        <p class="drop-cap text-pretty mt-5 text-base leading-relaxed text-ink/60" style="max-width:62ch">Garlic rice, tapa, mango, and a cup of brewed barako — set at your table before you leave for the field. Simple, generous, unmistakably Filipino.</p>
                        <div class="mt-8 h-px w-16 bg-gold"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ornament II -->
    <div role="presentation" class="mx-auto flex w-full max-w-xs flex-col items-center gap-3 py-6 text-center">
        <span aria-hidden="true" class="h-px w-12 bg-gold/70"></span>
        <span class="font-display text-xl italic text-gold/90">II</span>
        <span class="text-[10px] font-bold uppercase tracking-[0.4em] text-emerald/70">The Living Quarters</span>
    </div>

    <!-- ====== LIVING QUARTERS (ROOMS) ====== -->
    <section id="rooms" class="mx-auto max-w-7xl scroll-mt-28 px-6 pt-8 pb-24">
        <div class="mb-12 grid grid-cols-1 gap-8 md:grid-cols-[minmax(0,1fr)_auto] md:items-end" data-aos="fade-up">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald">
                    <span class="h-px w-8 bg-emerald/50"></span>Choose your stay
                </span>
                <h2 class="text-balance mt-4 font-display text-5xl leading-[1.05] text-ink md:text-6xl">The <span class="italic text-gold">Living Quarters</span></h2>
                <p class="mt-4 max-w-xl text-base text-ink/60">Six thoughtfully appointed rooms — from an intimate double to a six-bunk dormitory. Rates start at ₱{{ number_format($minPrice ?? 1600) }} and every stay includes a proper Filipino breakfast.</p>
            </div>
        </div>

        <!-- Error List -->
        @if ($errors->any())
            <div class="mx-auto mb-8 max-w-3xl">
                <x-booking.alert type="danger">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-booking.alert>
            </div>
        @endif

        <!-- Live Availability Results Banner (filled by availability-search.js) -->
        <div id="availabilityBanner" class="hidden mb-10">
            <div class="animate-pop mx-auto flex max-w-3xl flex-col items-center justify-between gap-3 rounded-full border border-gold/30 bg-cream-warm px-6 py-4 shadow-boutique-card sm:flex-row">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald opacity-60"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-deep"></span>
                    </span>
                    <p class="text-sm font-semibold text-ink" id="availabilityBannerText">Live availability</p>
                </div>
                <button type="button" id="btnClearAvailability" class="gold-underline inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-[0.2em] text-ink/60 hover:text-ember-600 transition-colors cursor-pointer">
                    Clear dates
                </button>
            </div>
        </div>

        <!-- Room Grid -->
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($roomTypes as $type)
                <div data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3 + 1) * 100 }}">
                    <x-booking.room-card
                        :title="$type['title']"
                        :beds="$type['beds']"
                        :price="$type['price']"
                        :typeId="$type['id']"
                        :image="$type['image']"
                        :capacity="$type['capacity']"
                        :badge="$type['badge'] ?? null"
                        :amenities="$type['amenities'] ?? []"
                        :floor="$type['floor'] ?? ''"
                        :description="\Illuminate\Support\Str::limit($type['description'] ?? '', 90)"
                        :index="$loop->iteration"
                    />
                </div>
            @endforeach
        </div>
    </section>

    <!-- Ornament III -->
    <div role="presentation" class="mx-auto flex w-full max-w-xs flex-col items-center gap-3 py-6 text-center">
        <span aria-hidden="true" class="h-px w-12 bg-gold/70"></span>
        <span class="font-display text-xl italic text-gold/90">III</span>
        <span class="text-[10px] font-bold uppercase tracking-[0.4em] text-emerald/70">In Their Words</span>
    </div>

    <!-- ====== TESTIMONIALS ====== -->
    <section class="relative overflow-hidden bg-cream-warm py-24 md:py-28">
        <div class="mx-auto max-w-5xl px-6">
            <div class="grid grid-cols-1 items-end gap-8 md:grid-cols-[minmax(0,1fr)_auto]" data-aos="fade-up">
                <div class="max-w-xl">
                    <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald">
                        <span class="h-px w-8 bg-emerald/50"></span>Guest experiences
                    </span>
                    <h2 class="text-balance mt-4 font-display text-4xl leading-tight text-ink md:text-5xl">Loved by <span class="italic text-gold">academics</span> and travelers alike.</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button class="swiper-button-prev-custom focus-ring press grid h-11 w-11 place-items-center rounded-full bg-cream ring-1 ring-emerald-deep/10 transition hover:bg-emerald-deep hover:text-cream cursor-pointer" aria-label="Previous testimonial">
                        <x-booking.icon name="chevron-left" class="h-4 w-4" />
                    </button>
                    <button class="swiper-button-next-custom focus-ring press grid h-11 w-11 place-items-center rounded-full bg-cream ring-1 ring-emerald-deep/10 transition hover:bg-emerald-deep hover:text-cream cursor-pointer" aria-label="Next testimonial">
                        <x-booking.icon name="chevron-right" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div class="swiper testimonials-swiper mt-12" data-aos="fade-up" data-aos-delay="150">
                <div class="swiper-wrapper">
                    @foreach ([
                        ['quote' => 'Perfect location for my research week at CLSU. The rooms are spotless, the Wi-Fi is reliable, and being right on campus saved me hours of commute.', 'name' => 'Dr. Reyes', 'role' => 'Visiting Professor', 'initials' => 'DR'],
                        ['quote' => 'The staff is exceptionally accommodating. We booked the dormitory for our student organization retreat and the facilities exceeded expectations.', 'name' => 'Maria C.', 'role' => 'Student Leader', 'initials' => 'MC'],
                        ['quote' => 'A peaceful place surrounded by nature — a proper rest after a long day of meetings. The breakfast alone is worth the stay.', 'name' => 'Juan P.', 'role' => 'Government Official', 'initials' => 'JP'],
                        ['quote' => 'The Deluxe Room felt genuinely premium, and the hot shower was perfect. I will be booking again next harvest season.', 'name' => 'Alumni Sy', 'role' => 'CLSU Alumni', 'initials' => 'AS'],
                    ] as $t)
                        <div class="swiper-slide h-auto">
                            <figure class="rounded-3xl bg-cream p-10 shadow-boutique-card md:p-14">
                                <div class="flex gap-1 text-gold">
                                    @for ($i = 0; $i < 5; $i++)
                                        <x-booking.icon name="star" class="h-4 w-4 fill-gold text-gold" />
                                    @endfor
                                </div>
                                <x-booking.icon name="quote" class="mt-8 h-6 w-6 text-gold" />
                                <blockquote class="mt-4 font-display text-2xl italic leading-relaxed text-ink md:text-3xl">&ldquo;{{ $t['quote'] }}&rdquo;</blockquote>
                                <figcaption class="mt-8 flex items-center gap-4 border-t border-emerald-deep/10 pt-6">
                                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-deep font-display text-sm text-cream">{{ $t['initials'] }}</div>
                                    <div>
                                        <p class="font-semibold text-ink">{{ $t['name'] }}</p>
                                        <p class="text-xs uppercase tracking-[0.2em] text-ink/50">{{ $t['role'] }}</p>
                                    </div>
                                </figcaption>
                            </figure>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Ornament IV -->
    <div role="presentation" class="mx-auto flex w-full max-w-xs flex-col items-center gap-3 py-6 text-center">
        <span aria-hidden="true" class="h-px w-12 bg-gold/70"></span>
        <span class="font-display text-xl italic text-gold/90">IV</span>
        <span class="text-[10px] font-bold uppercase tracking-[0.4em] text-emerald/70">Visual Tour</span>
    </div>

    <!-- ====== GALLERY (BENTO) ====== -->
    <section id="gallery" class="mx-auto max-w-7xl scroll-mt-28 px-6 py-20">
        <div class="mb-12 text-center" data-aos="fade-up">
            <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald">
                <span class="h-px w-8 bg-emerald/50"></span>Visual tour
            </span>
            <h2 class="text-balance mt-4 font-display text-5xl leading-tight text-ink md:text-6xl">Our <span class="italic text-gold">gallery</span></h2>
            <p class="mx-auto mt-4 max-w-xl text-base text-ink/60">A quiet walk through our rooms, common spaces, dining hall, and campus greenery.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:h-[560px] lg:grid-cols-4 lg:grid-rows-6" data-aos="fade-up" data-aos-delay="100">
            @php
                $bento = [
                    ['img' => 'image/gallery/1.jpg', 'span' => 'lg:col-span-2 lg:row-span-4 aspect-[4/3] sm:aspect-square lg:aspect-auto'],
                    ['img' => 'image/gallery/2.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto'],
                    ['img' => 'image/gallery/3.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto'],
                    ['img' => 'image/gallery/4.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto'],
                    ['img' => 'image/gallery/5.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto'],
                    ['img' => 'image/gallery/6.jpg', 'span' => 'sm:col-span-2 lg:col-span-2 lg:row-span-2 aspect-[4/3] lg:aspect-auto'],
                ];
            @endphp
            @foreach ($bento as $tile)
                <a href="{{ asset($tile['img']) }}" data-lightbox="visual-tour" class="group relative block overflow-hidden rounded-2xl {{ $tile['span'] }}">
                    <img src="{{ asset($tile['img']) }}" alt="Farmers Hostel — visual tour" loading="lazy" class="h-full w-full object-cover grayscale-[35%] transition duration-700 group-hover:scale-105 group-hover:grayscale-0">
                </a>
            @endforeach
            {{-- Remaining shots stay in the same lightbox chain --}}
            @for ($i = 7; $i <= 12; $i++)
                <a href="{{ asset('image/gallery/' . $i . '.jpg') }}" data-lightbox="visual-tour" class="hidden" aria-hidden="true" tabindex="-1"></a>
            @endfor
        </div>

        <div class="mt-10 flex justify-center" data-aos="fade-up">
            <button type="button" onclick="document.querySelector('#gallery a[data-lightbox]')?.click()" class="press inline-flex items-center gap-3 rounded-full border border-emerald-deep/20 bg-cream-warm px-6 py-3 text-[11px] font-bold uppercase tracking-[0.3em] text-ink transition hover:bg-cream cursor-pointer">
                <x-booking.icon name="sparkles" class="h-4 w-4 text-gold" />
                Explore the full gallery
            </button>
        </div>
    </section>

    <!-- ====== FINAL CTA ====== -->
    <section class="px-6 pb-24">
        <div class="mx-auto max-w-4xl text-center" data-aos="fade-up">
            <span aria-hidden="true" class="mx-auto block h-px w-12 bg-gold/70"></span>
            <h2 class="text-balance mt-6 font-display text-4xl leading-tight text-ink md:text-5xl">Ready to book your <span class="italic text-gold">campus stay?</span></h2>
            <p class="mx-auto mt-4 max-w-md text-base text-ink/60">Pick your dates, choose a room, and confirm. No prepayment required.</p>
            <div class="mt-8">
                <button type="button" onclick="document.getElementById('rooms').scrollIntoView({ behavior: 'smooth' });" class="press focus-ring inline-flex min-h-11 items-center gap-2 rounded-full bg-emerald-deep px-8 py-3.5 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream transition-all cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_25%,transparent)]">
                    <x-booking.icon name="calendar" class="h-4 w-4" />
                    Reserve your stay
                </button>
            </div>
        </div>
    </section>

    <!-- ====== MOBILE STICKY BOOK NOW BAR ====== -->
    <div id="mobileStickyBar" class="fixed bottom-0 left-0 right-0 z-40 flex translate-y-full items-center justify-between border-t border-gold/30 bg-cream-warm/95 p-4 backdrop-blur-md transition-transform duration-300 md:hidden shadow-[0_-12px_30px_rgba(6,40,30,0.12)]">
        <div>
            <p class="text-[9px] font-bold uppercase tracking-[0.28em] text-emerald/70">Starting from</p>
            <p class="font-display text-lg text-ink">₱{{ number_format($minPrice ?? 1600) }} <span class="text-[10px] uppercase tracking-[0.2em] text-ink/50">/ night</span></p>
        </div>
        <button type="button" onclick="document.getElementById('rooms').scrollIntoView({ behavior: 'smooth' });" class="press inline-flex min-h-11 items-center rounded-full bg-emerald-deep px-6 py-2.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream cursor-pointer">
            Book Now
        </button>
    </div>

    <!-- ====== ROOM DETAIL MODAL ====== -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('roomModal', () => ({
                isOpen: false,
                room: null,
                rooms: @json($roomTypes),
                openRoom(roomId) {
                    this.room = this.rooms[roomId] ?? null;
                    if (this.room) {
                        this.isOpen = true;
                        document.body.style.overflow = 'hidden';
                    }
                },
                close() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                },
                bookThis() {
                    const roomId = this.room ? this.room.id : null;
                    if (!roomId) return;
                    const checkIn = document.getElementById('widget_check_in').value;
                    const checkOut = document.getElementById('widget_check_out').value;
                    const guests = document.getElementById('widget_guests').value;
                    let url = `/checkout?room_type=${roomId}`;
                    if (checkIn) url += `&check_in=${checkIn}`;
                    if (checkOut) url += `&check_out=${checkOut}`;
                    if (guests) url += `&guests=${guests}`;
                    window.location.href = url;
                }
            }));
        });
    </script>
    <div
        x-data="roomModal"
        @open-room-detail.window="openRoom($event.detail.roomId)"
        @keydown.escape.window="if(isOpen) close()"
    >
        <!-- Backdrop -->
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[998] bg-ink/70 backdrop-blur-sm"
            @click="close()"
            style="display:none;"
        ></div>

        <!-- Panel -->
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="fixed inset-0 z-[999] flex items-end justify-center overflow-y-auto p-0 sm:items-center sm:p-6"
            style="display:none;"
        >
            <div
                @click.stop
                x-show="room"
                class="relative flex max-h-screen w-full flex-col overflow-hidden border border-gold/20 bg-cream sm:max-h-[90vh] sm:max-w-3xl sm:rounded-[2rem]"
                style="box-shadow: var(--shadow-boutique-modal)"
            >
                <!-- Hero Image -->
                <div class="relative h-64 flex-shrink-0 overflow-hidden bg-canvas-deep sm:h-72">
                    <img :src="room ? '{{ asset('/') }}' + room.image : ''" class="h-full w-full object-cover" :alt="room ? room.title : ''">
                    <div class="absolute inset-0 bg-linear-to-t from-ink/85 via-ink/10 to-transparent"></div>

                    <template x-if="room && room.badge">
                        <span class="absolute top-4 left-4 rounded-full bg-gold px-4 py-1.5 text-[10px] font-bold uppercase tracking-[0.15em] text-ink shadow-lg" x-text="room.badge"></span>
                    </template>

                    <button type="button" @click="close()" aria-label="Close room details" class="absolute top-4 right-4 z-10 grid h-9 w-9 place-items-center rounded-full bg-ink/40 text-cream backdrop-blur-md transition-colors hover:bg-ink/60 cursor-pointer">
                        <x-booking.icon name="x" class="h-4 w-4" />
                    </button>

                    <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-ink/70 to-transparent px-6 pb-5 pt-10">
                        <p class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">
                            <x-booking.icon name="map-pin" class="h-3.5 w-3.5" />
                            <span x-text="room ? room.floor : ''"></span>
                        </p>
                        <h2 class="font-display text-3xl leading-tight text-cream" x-text="room ? room.title : ''"></h2>
                    </div>
                </div>

                <!-- Scrollable Body -->
                <div class="custom-scrollbar flex-1 overflow-y-auto">
                    <div class="space-y-6 px-6 py-6">
                        <!-- Price + Capacity -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-emerald/70">Room rate</p>
                                <p class="mt-1 font-display text-3xl leading-none text-ink">₱<span x-text="room ? Number(room.price).toLocaleString() : ''"></span></p>
                                <p class="mt-1 text-[10px] uppercase tracking-[0.22em] text-ink/50">per night</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-emerald/70">Capacity</p>
                                <p class="mt-1 text-sm font-semibold text-ink" x-text="room ? room.capacity : ''"></p>
                                <p class="mt-1 flex items-center justify-end gap-1 text-[10px] uppercase tracking-[0.22em] text-ink/50">
                                    <x-booking.icon name="users" class="h-3 w-3" />
                                    <span x-text="room ? room.beds + ' pax max' : ''"></span>
                                </p>
                            </div>
                        </div>

                        <span aria-hidden="true" class="block h-px w-12 bg-gold"></span>

                        <!-- Description -->
                        <p x-show="room && room.description" class="text-pretty text-sm leading-relaxed text-ink/60" x-text="room ? room.description : ''"></p>

                        <!-- Amenities -->
                        <div x-show="room && room.amenities && room.amenities.length > 0">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-emerald/70">Room features</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="amenity in (room ? room.amenities : [])" :key="amenity.label">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-cream-warm px-3 py-1.5 text-[11px] font-medium text-ink ring-1 ring-emerald-deep/10">
                                        <span class="h-1 w-1 rounded-full bg-gold"></span>
                                        <span x-text="amenity.label"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- What's Included -->
                        <div x-show="room && room.includes && room.includes.length > 0">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-emerald/70">What's included</p>
                            <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <template x-for="item in (room ? room.includes : [])" :key="item">
                                    <li class="flex items-center gap-2 text-sm font-medium text-ink/75">
                                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-deep/8 text-emerald-deep">
                                            <x-booking.icon name="check" class="h-3 w-3" />
                                        </span>
                                        <span x-text="item"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Policies -->
                        <div class="rounded-2xl border border-gold/30 bg-gold-soft/30 px-5 py-4">
                            <p class="mb-2.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.28em] text-ink/70">
                                <x-booking.icon name="clock" class="h-3.5 w-3.5 text-gold" />
                                Stay policies
                            </p>
                            <div class="grid grid-cols-1 gap-y-1.5 text-xs font-medium text-ink/70 sm:grid-cols-2">
                                <span>Check-in · 2:00 PM</span>
                                <span>Check-out · 12:00 NN</span>
                                <span class="sm:col-span-2">Outside food is not allowed in the rooms</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky footer CTAs -->
                <div class="sticky bottom-0 z-20 flex gap-3 border-t border-emerald-deep/10 bg-cream-warm/95 px-6 py-5 backdrop-blur-xl">
                    <button type="button" @click="close()" class="press focus-ring flex-1 rounded-full border border-emerald-deep/15 bg-cream px-6 py-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-ink/70 transition-all hover:bg-cream-warm cursor-pointer">Close</button>
                    <button type="button" @click="bookThis()" class="press focus-ring flex-[2] inline-flex items-center justify-center gap-2 rounded-full bg-emerald-deep px-6 py-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream transition-all cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_25%,transparent)]">
                        <x-booking.icon name="calendar" class="h-4 w-4" />
                        Book this room
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery for booking script fallback and script inclusion -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/booking.js') }}"></script>
    <script src="{{ asset('js/availability-search.js') }}"></script>

    <!-- Widget, Swiper & Sticky-bar Logic -->
    <script>
        function bookRoomDirect(roomId) {
            if (!roomId) return;
            const checkIn = document.getElementById('widget_check_in').value;
            const checkOut = document.getElementById('widget_check_out').value;
            const guests = document.getElementById('widget_guests').value;
            let url = `/checkout?room_type=${roomId}`;
            if (checkIn) url += `&check_in=${checkIn}`;
            if (checkOut) url += `&check_out=${checkOut}`;
            if (guests) url += `&guests=${guests}`;
            window.location.href = url;
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Guests stepper
            const minusBtn = document.getElementById('btn_minus_guests');
            const plusBtn = document.getElementById('btn_plus_guests');
            const display = document.getElementById('guests_display');
            const plural = document.getElementById('guests_plural');
            const hiddenInput = document.getElementById('widget_guests');

            function setGuests(val) {
                val = Math.min(40, Math.max(1, val));
                hiddenInput.value = val;
                display.textContent = val;
                plural && plural.classList.toggle('hidden', val === 1);
            }
            if (minusBtn && plusBtn && display && hiddenInput) {
                minusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) - 1); });
                plusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) + 1); });
            }

            // Testimonials Swiper — one editorial card at a time
            new Swiper('.testimonials-swiper', {
                slidesPerView: 1,
                spaceBetween: 32,
                autoHeight: true,
                loop: true,
                speed: 650,
                navigation: {
                    nextEl: '.swiper-button-next-custom',
                    prevEl: '.swiper-button-prev-custom',
                },
            });

            // Mobile sticky bar appears after the hero
            const stickyBar = document.getElementById('mobileStickyBar');
            const heroSection = document.getElementById('firstsection');
            if (stickyBar && heroSection) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        stickyBar.classList.toggle('translate-y-full', entry.isIntersecting);
                    });
                }, { threshold: 0.1 });
                observer.observe(heroSection);
            }
        });
    </script>
@endsection
