@extends('layouts.public.base')
@section('title', 'Farmers Hostel · Boutique Stay Inside CLSU Campus')
@section('nav_dark', '1')
@section('theme_night', '1')
@section('content')

    <!-- Site-wide film grain: fixed, non-interactive, breaks digital flatness.
         Sits above content but below the nav (z-50) and overlays. -->
    <div class="film-grain pointer-events-none fixed inset-0 z-[45]" aria-hidden="true"></div>

    <!-- ====== HERO ====== -->
    <header id="firstsection" class="vignette-emerald relative isolate flex min-h-[100dvh] flex-col justify-end overflow-hidden bg-night">
        <!-- Ken-burns backdrop, graded down so the bone type carries the frame -->
        <div class="absolute inset-0 z-0 will-change-transform prlx-hero-bg">
            <img src="{{ asset('image/hostel1.jpg') }}" alt="Farmers Hostel exterior at dusk, nestled inside the CLSU campus" fetchpriority="high" decoding="async" class="img-night-grade h-full w-full animate-ken-burns object-cover">
            <div class="absolute inset-0 bg-linear-to-b from-night/70 via-night/25 to-night/70"></div>
            <!-- The hero melts into the page instead of ending at an edge -->
            <div class="absolute inset-x-0 bottom-0 h-72 bg-linear-to-b from-transparent to-night"></div>
        </div>

        <!-- Headline (left-aligned, lines rise out of clipped masks) -->
        <div class="relative z-10 mx-auto w-full max-w-6xl px-6 pt-24 pb-10 text-left text-bone prlx-hero-content">
            <p class="reveal-line text-[10px] font-semibold uppercase tracking-[0.32em] text-gold sm:text-[11px] sm:tracking-[0.5em]"><span style="--rise-delay:.1s">A premium stay on campus · Est. 1998</span></p>
            <h1 class="hero-text-glow mt-5 font-display leading-[1.06] text-bone" style="font-size:clamp(2.5rem, 5.5vw, 4.75rem)">
                <span class="reveal-line"><span style="--rise-delay:.28s">Welcome to</span></span>
                <span class="reveal-line"><span style="--rise-delay:.46s"><x-booking.ui.flip-fade-text text="Farmers" :duration="4.5" class="italic text-gold" /> Hostel</span></span>
            </h1>
            <p class="text-pretty mt-5 max-w-xl text-base leading-relaxed text-bone/75 sm:text-lg animate-[fade-in-up_1s_ease-out_0.85s_both]">
                Quiet rooms in the heart of CLSU. Two minutes to the labs, the fields, and a proper Filipino breakfast.
            </p>
        </div>

        <!-- Booking capsule: dark glass with a refractive hairline edge -->
        <div class="relative z-10 mx-auto w-full max-w-5xl px-4 pb-14 sm:px-6 animate-[fade-in-up_1.1s_ease-out_1.05s_both]">
            <div id="bookingCapsule" class="glass-night rounded-3xl p-4 md:p-3"
                 data-prlx-y="-0.22" data-prlx-mouse="7" data-prlx-origin="top" data-prlx-ease="0.06">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_auto] md:items-center md:gap-2">
                    <div class="px-4 py-3 text-left md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Check in</p>
                        <input type="text" id="widget_check_in" aria-label="Check in date" placeholder="Select date"
                               class="focus-ring w-full min-h-11 cursor-pointer bg-transparent text-sm font-medium text-bone outline-none placeholder:text-bone/35">
                    </div>
                    <div class="px-4 py-3 text-left md:border-l md:border-white/10 md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Check out</p>
                        <input type="text" id="widget_check_out" aria-label="Check out date" placeholder="Select date"
                               class="focus-ring w-full min-h-11 cursor-pointer bg-transparent text-sm font-medium text-bone outline-none placeholder:text-bone/35">
                    </div>
                    <div class="px-4 py-3 text-left md:border-l md:border-white/10 md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Guests</p>
                        <div class="flex min-h-11 items-center justify-between">
                            <span class="text-sm font-medium text-bone"><span id="guests_display" class="anim-number"><span>1</span></span> guest<span id="guests_plural" class="hidden">s</span></span>
                            <input type="hidden" id="widget_guests" value="1">
                            <div class="flex items-center gap-1">
                                <button type="button" id="btn_minus_guests" aria-label="Decrease guests" class="focus-ring press grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-white/5 text-bone transition hover:border-gold/60 cursor-pointer">
                                    <x-booking.ui.icon name="minus" class="h-3 w-3" />
                                </button>
                                <button type="button" id="btn_plus_guests" aria-label="Increase guests" class="focus-ring press grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-white/5 text-bone transition hover:border-gold/60 cursor-pointer">
                                    <x-booking.ui.icon name="plus" class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnSearchRooms"
                            class="focus-ring press cta-shine group relative inline-flex min-h-12 items-center justify-center gap-2 overflow-hidden rounded-full bg-bone px-8 py-4 text-[12px] font-semibold uppercase tracking-[0.2em] text-night transition-all duration-500 cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_32%,transparent),0_18px_44px_-18px_rgba(0,0,0,0.85)]"
                            data-prlx-y="0.04" data-prlx-mouse="5" data-prlx-scale="0.02" data-prlx-ease="0.08">
                        <span id="btnSearchRoomsLabel" class="inline-flex items-center gap-2">Search rooms <x-booking.ui.icon name="arrow-right" class="h-4 w-4" /></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ====== STATS STRIP ====== -->
    <section class="border-b border-white/10 px-6">
        <div class="mx-auto grid max-w-6xl grid-cols-2 md:grid-cols-4 md:divide-x md:divide-white/10" data-aos="fade-up">
            <x-booking.cards.stat :value="count($roomTypes)" label="Room Types" />
            <x-booking.cards.stat value="24/7" label="Front Desk" />
            <x-booking.cards.stat value="2 min" label="Walk to the Labs" />
            <x-booking.cards.stat value="₱{{ number_format($minPrice ?? 1600) }}" label="From, Per Night" />
        </div>
    </section>

    <!-- ====== MANIFESTO + FEATURES ====== -->
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

    <!-- ====== THREE CHAPTERS ====== -->
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
        <div class="relative mt-24 overflow-hidden md:mt-32">
            <img src="{{ asset('image/4.jpg') }}" alt="Filipino silog breakfast served at the hostel" loading="lazy" decoding="async"
                 class="img-night-grade absolute inset-0 h-full w-full scale-110 object-cover" data-prlx-y="-0.08" data-prlx-ease="0.06">
            <div class="absolute inset-0 bg-night/60"></div>
            <div class="absolute inset-x-0 top-0 h-40 bg-linear-to-b from-night to-transparent"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-linear-to-t from-night to-transparent"></div>
            <div class="relative mx-auto flex min-h-[70dvh] max-w-7xl items-center px-6">
                <div class="max-w-xl py-28" data-aos="fade-up">
                    <h3 class="text-balance pb-1 font-display text-3xl leading-[1.15] text-bone md:text-5xl">Breakfast, on the house, every morning</h3>
                    <p class="text-pretty mt-6 text-base leading-relaxed text-bone/75">
                        Garlic rice, tapa, mango, and a cup of brewed barako, set at your table before you leave for the field. Simple, generous, unmistakably Filipino.
                    </p>
                    <div class="mt-8 h-px w-16 bg-gold/70"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== LIVING QUARTERS (ROOMS) ====== -->
    <section id="rooms" class="mx-auto max-w-7xl scroll-mt-28 px-6 pt-24 pb-28 md:pt-32">
        <x-booking.sections.heading
            eyebrow="Accommodations"
            description="{{ count($roomTypes) }} fully-serviced room types for short stays, transient guests, and university researchers. Filter by capacity or open a room for the full picture. Booking takes one click."
            class="mb-10" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
            Reserve a <span class="italic text-gold">room</span>
        </x-booking.sections.heading>

        <!-- Capacity filter pills -->
        <x-booking.sections.room-filters class="mb-12" data-aos="fade-up" data-aos-delay="100" />

        <!-- Error List -->
        @if ($errors->any())
            <div class="mx-auto mb-8 max-w-3xl">
                <x-booking.ui.alert type="danger">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-booking.ui.alert>
            </div>
        @endif

        <!-- Live Availability Results Banner (filled by availability-search.js) -->
        <div id="availabilityBanner" class="hidden mb-10">
            <div class="animate-pop glass-night mx-auto flex max-w-3xl flex-col items-center justify-between gap-3 rounded-full px-6 py-4 sm:flex-row">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gold opacity-60"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-gold"></span>
                    </span>
                    <p class="text-sm font-semibold text-ink" id="availabilityBannerText">Live availability</p>
                </div>
                <button type="button" id="btnClearAvailability" class="gold-underline inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-[0.2em] text-ink/60 hover:text-ink transition-colors cursor-pointer">
                    Clear dates
                </button>
            </div>
        </div>

        <!-- Room Grid (cards reveal individually with a stagger — see x-booking.cards.room) -->
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3" data-prlx-y="0.06" data-prlx-scale="0.02">
            @foreach ($roomTypes as $type)
                <div data-room-item
                     data-beds="{{ $type['beds'] }}"
                     data-premium="{{ ($type['badge'] ?? '') === 'Premium' ? 1 : 0 }}"
                     class="transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]">
                    <x-booking.cards.room
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

        <!-- Filter empty state -->
        <div id="roomFilterEmpty" class="hidden mt-4 rounded-3xl border border-dashed border-white/15 bg-white/[0.03] px-8 py-16 text-center">
            <x-booking.ui.icon name="bed" class="mx-auto h-8 w-8 text-bone/30" />
            <p class="mt-4 font-display text-2xl text-ink">No rooms in this range</p>
            <p class="mt-2 text-sm text-ink/55">Try a different capacity, or the dormitories for larger groups.</p>
        </div>
    </section>

    <!-- ====== TESTIMONIALS ====== -->
    <section class="relative overflow-hidden border-t border-white/10 py-24 md:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
                <h2 class="text-balance max-w-xl pb-1 font-display text-4xl leading-[1.12] text-ink md:text-5xl">
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

            <div class="swiper testimonials-swiper mt-8" data-aos="fade-up" data-aos-delay="150">
                <div class="swiper-wrapper">
                    @foreach ([
                        ['quote' => 'Perfect location for my research week at CLSU. The rooms are spotless, the Wi-Fi is reliable, and being right on campus saved me hours of commute.', 'name' => 'Dr. Reyes', 'role' => 'Visiting Professor', 'initials' => 'DR'],
                        ['quote' => 'The staff is exceptionally accommodating. We booked the dormitory for our student organization retreat and the facilities exceeded expectations.', 'name' => 'Maria C.', 'role' => 'Student Leader', 'initials' => 'MC'],
                        ['quote' => 'A peaceful place surrounded by nature, and a proper rest after a long day of meetings. The breakfast alone is worth the stay.', 'name' => 'Juan P.', 'role' => 'Government Official', 'initials' => 'JP'],
                        ['quote' => 'The Deluxe Room felt genuinely premium, and the hot shower was perfect. I will be booking again next harvest season.', 'name' => 'Alumni Sy', 'role' => 'CLSU Alumni', 'initials' => 'AS'],
                    ] as $t)
                        <div class="swiper-slide h-auto">
                            <x-booking.cards.testimonial
                                :quote="$t['quote']"
                                :name="$t['name']"
                                :role="$t['role']"
                                :initials="$t['initials']"
                            />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- ====== GALLERY (BENTO) ====== -->
    <section id="gallery" class="mx-auto max-w-7xl scroll-mt-28 px-6 py-24">
        <x-booking.sections.heading
            description="A quiet walk through our rooms, common spaces, dining hall, and campus greenery."
            align="center" class="mb-12" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
            A walk through the <span class="italic text-gold">grounds</span>
        </x-booking.sections.heading>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:h-[560px] lg:grid-cols-4 lg:grid-rows-6" data-aos="fade-up" data-aos-delay="100">
            @php
                $bento = [
                    ['img' => 'image/gallery/1.jpg', 'span' => 'lg:col-span-2 lg:row-span-4 aspect-[4/3] sm:aspect-square lg:aspect-auto', 'prlx' => '0.06'],
                    ['img' => 'image/gallery/2.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.1'],
                    ['img' => 'image/gallery/3.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.14'],
                    ['img' => 'image/gallery/4.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.08'],
                    ['img' => 'image/gallery/5.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.12'],
                    ['img' => 'image/gallery/6.jpg', 'span' => 'sm:col-span-2 lg:col-span-2 lg:row-span-2 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.05'],
                ];
            @endphp
            @foreach ($bento as $tile)
                <a href="{{ asset($tile['img']) }}" data-lightbox="visual-tour" class="group relative block overflow-hidden rounded-2xl ring-1 ring-white/10 {{ $tile['span'] }}" data-prlx-y="{{ $tile['prlx'] }}" data-prlx-scale="0.03">
                    <img src="{{ asset($tile['img']) }}" alt="Farmers Hostel visual tour" loading="lazy" decoding="async" class="h-full w-full object-cover brightness-[0.82] saturate-[0.85] transition duration-700 group-hover:scale-105 group-hover:brightness-100 group-hover:saturate-100">
                </a>
            @endforeach
            {{-- Remaining shots stay in the same lightbox chain --}}
            @for ($i = 7; $i <= 12; $i++)
                <a href="{{ asset('image/gallery/' . $i . '.jpg') }}" data-lightbox="visual-tour" class="hidden" aria-hidden="true" tabindex="-1"></a>
            @endfor
        </div>

        <div class="mt-10 flex justify-center" data-aos="fade-up">
            <button type="button" onclick="document.querySelector('#gallery a[data-lightbox]')?.click()" class="press focus-ring inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/5 px-6 py-3 text-[11px] font-bold uppercase tracking-[0.3em] text-ink transition hover:border-gold/60 cursor-pointer">
                <x-booking.ui.icon name="sparkles" class="h-4 w-4 text-gold" />
                Explore the full gallery
            </button>
        </div>
    </section>

    <!-- ====== FINAL CTA (full-bleed evening band) ====== -->
    <section class="relative mt-8 overflow-hidden">
        <img src="{{ asset('image/hostel1.jpg') }}" alt="Farmers Hostel exterior in the evening" loading="lazy" decoding="async"
             class="img-night-grade absolute inset-0 h-full w-full scale-110 object-cover object-top" data-prlx-y="-0.08" data-prlx-ease="0.06">
        <div class="absolute inset-0 bg-night/70"></div>
        <div class="absolute inset-x-0 top-0 h-40 bg-linear-to-b from-night to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 h-40 bg-linear-to-t from-night to-transparent"></div>
        <div class="relative mx-auto flex min-h-[60dvh] max-w-4xl flex-col items-center justify-center px-6 py-28 text-center" data-aos="fade-up">
            <span aria-hidden="true" class="block h-px w-12 bg-gold/70"></span>
            <h2 class="text-balance mt-6 pb-1 font-display text-4xl leading-[1.12] text-bone md:text-6xl">Ready for your <span class="italic text-gold">campus stay?</span></h2>
            <p class="mx-auto mt-4 max-w-md text-base text-bone/70">Pick your dates, choose a room, and confirm. No prepayment, and Senior or PWD guests save 20%.</p>
            <button type="button" onclick="smoothScrollTo(document.getElementById('rooms'))" class="press focus-ring mt-9 inline-flex min-h-12 items-center gap-2 rounded-full bg-bone px-9 py-4 text-[12px] font-semibold uppercase tracking-[0.2em] text-night transition-all duration-500 cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_32%,transparent),0_18px_44px_-18px_rgba(0,0,0,0.85)]">
                <x-booking.ui.icon name="calendar" class="h-4 w-4" />
                Reserve your stay
            </button>
        </div>
    </section>

    <!-- ====== MOBILE STICKY RESERVE BAR ====== -->
    <div id="mobileStickyBar" class="fixed bottom-0 left-0 right-0 z-40 flex translate-y-full items-center justify-between border-t border-white/12 bg-night-2/90 p-4 backdrop-blur-xl transition-transform duration-500 md:hidden shadow-[0_-16px_40px_rgba(0,0,0,0.5)]">
        <div>
            <p class="text-[9px] font-bold uppercase tracking-[0.28em] text-bone/50">Starting from</p>
            <p class="font-display text-lg text-ink">₱{{ number_format($minPrice ?? 1600) }} <span class="text-[10px] uppercase tracking-[0.2em] text-ink/50">/ night</span></p>
        </div>
        <button type="button" onclick="smoothScrollTo(document.getElementById('rooms'))" class="press inline-flex min-h-11 items-center rounded-full bg-bone px-6 py-2.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-night cursor-pointer">
            Reserve
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
                isFullyBooked() {
                    if (!this.room) return false;
                    const data = window.LAST_AVAILABILITY;
                    if (!data || !data.summary) return false;
                    const typeSummary = data.summary.find(s => s.room_type === this.room.id);
                    return typeSummary ? typeSummary.available <= 0 : false;
                },
                bookThis() {
                    const roomId = this.room ? this.room.id : null;
                    if (!roomId) return;
                    if (this.isFullyBooked()) {
                        alert('This room type is fully booked for the selected dates.');
                        return;
                    }
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
            class="fixed inset-0 z-[998] bg-black/70 backdrop-blur-sm"
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
                class="relative flex max-h-screen w-full flex-col overflow-hidden border border-white/10 bg-night-2 sm:max-h-[90vh] sm:max-w-3xl sm:rounded-[2rem]"
                style="box-shadow: var(--shadow-night-float)"
            >
                <!-- Hero Image -->
                <div class="relative h-64 flex-shrink-0 overflow-hidden bg-night sm:h-72">
                    <img :src="room ? '{{ asset('/') }}' + room.image : ''" class="h-full w-full object-cover brightness-[0.9]" :alt="room ? room.title : ''">
                    <div class="absolute inset-0 bg-linear-to-t from-night-2 via-night/20 to-transparent"></div>

                    <template x-if="room && room.badge">
                        <span class="absolute top-4 left-4 rounded-full bg-gold px-4 py-1.5 text-[10px] font-bold uppercase tracking-[0.15em] text-night shadow-lg" x-text="room.badge"></span>
                    </template>

                    <button type="button" @click="close()" aria-label="Close room details" class="absolute top-4 right-4 z-10 grid h-9 w-9 place-items-center rounded-full bg-black/40 text-bone backdrop-blur-md transition-colors hover:bg-black/60 cursor-pointer">
                        <x-booking.ui.icon name="x" class="h-4 w-4" />
                    </button>

                    <div class="absolute bottom-0 left-0 right-0 px-6 pb-5 pt-10">
                        <p class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">
                            <x-booking.ui.icon name="map-pin" class="h-3.5 w-3.5" />
                            <span x-text="room ? room.floor : ''"></span>
                        </p>
                        <h2 class="font-display text-3xl leading-tight text-bone" x-text="room ? room.title : ''"></h2>
                    </div>
                </div>

                <!-- Scrollable Body -->
                <div class="custom-scrollbar flex-1 overflow-y-auto">
                    <div class="space-y-6 px-6 py-6">
                        <!-- Price + Capacity -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Room rate</p>
                                <p class="mt-1 font-display text-3xl leading-none text-ink">₱<span x-text="room ? Number(room.price).toLocaleString() : ''"></span></p>
                                <p class="mt-1 text-[10px] uppercase tracking-[0.22em] text-ink/45">per night</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Capacity</p>
                                <p class="mt-1 text-sm font-semibold text-ink" x-text="room ? room.capacity : ''"></p>
                                <p class="mt-1 flex items-center justify-end gap-1 text-[10px] uppercase tracking-[0.22em] text-ink/45">
                                    <x-booking.ui.icon name="users" class="h-3 w-3" />
                                    <span x-text="room ? room.beds + ' pax max' : ''"></span>
                                </p>
                            </div>
                        </div>

                        <span aria-hidden="true" class="block h-px w-12 bg-gold"></span>

                        <!-- Description -->
                        <p x-show="room && room.description" class="text-pretty text-sm leading-relaxed text-ink/60" x-text="room ? room.description : ''"></p>

                        <!-- Amenities -->
                        <div x-show="room && room.amenities && room.amenities.length > 0">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Room features</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="amenity in (room ? room.amenities : [])" :key="amenity.label">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/5 px-3 py-1.5 text-[11px] font-medium text-ink/85 ring-1 ring-white/10">
                                        <span class="h-1 w-1 rounded-full bg-gold"></span>
                                        <span x-text="amenity.label"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- What's Included -->
                        <div x-show="room && room.includes && room.includes.length > 0">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">What's included</p>
                            <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <template x-for="item in (room ? room.includes : [])" :key="item">
                                    <li class="flex items-center gap-2 text-sm font-medium text-ink/80">
                                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-gold/15 text-gold">
                                            <x-booking.ui.icon name="check" class="h-3 w-3" />
                                        </span>
                                        <span x-text="item"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Policies -->
                        <div class="rounded-2xl border border-gold/25 bg-gold/10 px-5 py-4">
                            <p class="mb-2.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.28em] text-ink/75">
                                <x-booking.ui.icon name="clock" class="h-3.5 w-3.5 text-gold" />
                                Stay policies
                            </p>
                            <div class="grid grid-cols-1 gap-y-1.5 text-xs font-medium text-ink/75 sm:grid-cols-2">
                                <span>Check-in · 2:00 PM</span>
                                <span>Check-out · 12:00 NN</span>
                                <span class="sm:col-span-2">Outside food is not allowed in the rooms</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky footer CTAs -->
                <div class="sticky bottom-0 z-20 flex gap-3 border-t border-white/10 bg-night-2/95 px-6 py-5 backdrop-blur-xl">
                    <button type="button" @click="close()" class="press focus-ring flex-1 rounded-full border border-white/15 bg-white/5 px-6 py-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-ink/70 transition-all hover:bg-white/10 cursor-pointer">Close</button>
                    <button type="button" @click="bookThis()" :disabled="isFullyBooked()" :class="isFullyBooked() ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''" class="press focus-ring flex-[2] inline-flex items-center justify-center gap-2 rounded-full bg-bone px-6 py-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-night transition-all duration-500 cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)]">
                        <x-booking.ui.icon name="calendar" class="h-4 w-4" />
                        <span x-text="isFullyBooked() ? 'Fully Booked' : 'Book this room'">Book this room</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- No jQuery needed: booking.js / availability-search.js / room-filters.js
         are vanilla, and lightbox2 ships with its own bundled copy. --}}
    <script src="{{ asset('js/booking.js') }}?v={{ filemtime(public_path('js/booking.js')) }}"></script>
    <script src="{{ asset('js/availability-search.js') }}?v={{ filemtime(public_path('js/availability-search.js')) }}"></script>
    <script src="{{ asset('js/room-filters.js') }}?v={{ filemtime(public_path('js/room-filters.js')) }}"></script>
    <script src="{{ asset('js/parallax.js') }}?v={{ filemtime(public_path('js/parallax.js')) }}" defer></script>

    <!-- Widget, Swiper & Sticky-bar Logic -->
    <script>
        function bookRoomDirect(roomId) {
            if (!roomId) return;
            if (window.LAST_AVAILABILITY && window.LAST_AVAILABILITY.summary) {
                const row = window.LAST_AVAILABILITY.summary.find(s => s.room_type === roomId);
                if (row && row.available <= 0) {
                    alert('This room type is fully booked for the selected dates.');
                    return;
                }
            }
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

            // Odometer roll (vengence-ui animated-number): outgoing value slides
            // out, incoming slides in from the opposite edge based on direction.
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            function setGuests(val) {
                val = Math.min(40, Math.max(1, val));
                const prev = parseInt(hiddenInput.value) || 1;
                hiddenInput.value = val;
                plural && plural.classList.toggle('hidden', val === 1);
                // If results are already on screen, re-filter room types live.
                if (window.LAST_AVAILABILITY && window.__applyGuestFilter) window.__applyGuestFilter(val);
                if (val === prev) return;

                const current = display.querySelector('span:not(.is-leaving)');
                if (reduceMotion || !current || !current.animate) {
                    display.textContent = '';
                    const s = document.createElement('span');
                    s.textContent = val;
                    display.appendChild(s);
                    return;
                }

                const dir = val > prev ? 1 : -1;
                const next = document.createElement('span');
                next.textContent = val;
                display.appendChild(next);

                const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
                current.classList.add('is-leaving');
                current.animate([
                    { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
                    { transform: `translateY(${dir * -100}%)`, opacity: 0, filter: 'blur(2px)' },
                ], { duration: 260, easing, fill: 'forwards' }).onfinish = () => current.remove();
                next.animate([
                    { transform: `translateY(${dir * 100}%)`, opacity: 0, filter: 'blur(2px)' },
                    { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
                ], { duration: 260, easing });
            }
            if (minusBtn && plusBtn && display && hiddenInput) {
                minusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) - 1); });
                plusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) + 1); });
            }

            // Testimonials Swiper — one editorial quote at a time, slow rotation
            new Swiper('.testimonials-swiper', {
                slidesPerView: 1,
                spaceBetween: 32,
                autoHeight: true,
                loop: true,
                speed: 750,
                autoplay: reduceMotion ? false : { delay: 7000, pauseOnMouseEnter: true, disableOnInteraction: false },
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
