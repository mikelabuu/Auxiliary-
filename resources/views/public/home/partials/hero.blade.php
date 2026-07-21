<header id="firstsection" class="vignette-emerald relative isolate flex min-h-[100dvh] flex-col overflow-hidden bg-night">
    <!-- Ken-burns backdrop. The directional scrim below carries type
         legibility: solid night on the left where the copy sits, thinning
         rightward so the building (and its real signage) stays visible
         instead of being graded into murk. -->
    <div class="absolute -inset-8 z-0 will-change-transform prlx-hero-bg">
        {{-- The photo's aspect ratio ≈ the viewport's, so object-position has
             no crop slack: the scaled origin-left wrapper creates it, pushing
             the facade signage right, out from behind the headline (desktop
             only; the portrait crop already clears it on mobile). Overlays
             stay siblings so the scrims never scale with the photo. --}}
        <div class="absolute inset-0 md:origin-left md:scale-[1.28]">
            <img src="{{ asset('image/hostel1.jpg') }}" alt="Farmers Hostel exterior inside the CLSU campus" fetchpriority="high" decoding="async" class="img-night-grade h-full w-full animate-ken-burns object-cover object-[25%_center]">
        </div>
        <div class="absolute inset-0 bg-linear-to-r from-night/95 via-night/70 via-50% to-night/15"></div>
        <div class="absolute inset-x-0 top-0 h-44 bg-linear-to-b from-night/70 to-transparent"></div>
        <!-- The hero melts into the page instead of ending at an edge -->
        <div class="absolute inset-x-0 bottom-0 h-72 bg-linear-to-b from-transparent to-night"></div>
    </div>

    <!-- Headline: vertically centered, left-anchored on the scrim; lines
         rise out of clipped masks (reveal-line reserves italic descenders) -->
    <div class="relative z-10 mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-6 pt-28 pb-8 text-left text-bone prlx-hero-content">
        <p class="reveal-line text-[10px] font-semibold uppercase tracking-[0.32em] text-gold sm:text-[11px] sm:tracking-[0.5em]"><span style="--rise-delay:.1s">Inside CLSU since 1998</span></p>
        <h1 id="heroTitle" class="mt-5 font-display leading-[1.1] text-bone" style="font-size:clamp(2.75rem, 6.2vw, 5.5rem)">
            <span class="reveal-line"><span style="--rise-delay:.28s">The <span class="italic text-gold">quietest</span></span></span>
            <span class="reveal-line"><span style="--rise-delay:.46s">address on campus</span></span>
        </h1>
        <p class="text-pretty mt-6 max-w-xl text-base leading-relaxed text-bone/75 sm:text-lg animate-[fade-in-up_1s_ease-out_0.85s_both]">
            A boutique hostel in the heart of CLSU. Two minutes to the labs, the fields, and a proper Filipino breakfast.
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
                        class="focus-ring press cta-shine group relative inline-flex min-h-12 items-center justify-center gap-2 overflow-hidden rounded-full bg-bone px-8 py-4 text-[12px] font-semibold uppercase tracking-[0.2em] text-night cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_32%,transparent),0_18px_44px_-18px_rgba(0,0,0,0.85)]"
                        data-prlx-y="0.04" data-prlx-mouse="5" data-prlx-scale="0.02" data-prlx-ease="0.08">
                    <span id="btnSearchRoomsLabel" class="inline-flex items-center gap-2">Search rooms <x-booking.ui.icon name="arrow-right" class="h-4 w-4" /></span>
                </button>
            </div>

            <!-- Nights summary — filled by availability-search.js once both dates are set -->
            <div id="capsuleNights" class="capsule-nights" hidden>
                <span class="capsule-nights-dot" aria-hidden="true"></span>
                <span id="capsuleNightsText"></span>
            </div>
        </div>
    </div>
</header>
