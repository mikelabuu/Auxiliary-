<header id="firstsection" class="relative isolate bg-canvas px-3 pt-20 pb-10 sm:px-5 sm:pt-24">
    {{-- Blurred campus backdrop behind the panel (Kordex "city behind the card"). --}}
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <img src="{{ asset('image/hostel1.jpg') }}" alt="" class="h-full w-full scale-125 object-cover object-center opacity-30 blur-2xl saturate-[.85]">
        <div class="absolute inset-0 bg-canvas/55"></div>
    </div>

    {{-- ── Hero panel: full-bleed campus photo, white type over legibility scrims ── --}}
    <div class="relative mx-auto flex min-h-[86vh] max-w-7xl flex-col overflow-hidden rounded-[2.25rem] shadow-[0_50px_120px_-45px_rgba(8,36,20,0.55)] sm:rounded-[2.75rem]">
        {{-- Photo bg (kept fairly bright; scrims below carry the text legibility). --}}
        <img src="{{ asset('image/hostel1.jpg') }}" alt="Farmers Hostel exterior inside the CLSU campus"
             fetchpriority="high" decoding="async"
             class="absolute inset-0 -z-10 h-full w-full animate-ken-burns object-cover object-center brightness-[.82] saturate-[.96]">
        {{-- Scrims: darker at the bottom-left where the copy sits, a touch at the top
             for the wordmark; the centre stays bright so the building reads. --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink/70 via-ink/15 to-ink/35"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink/45 via-transparent to-transparent"></div>

        {{-- Content column: giant wordmark up top, copy pinned to the bottom. --}}
        <div class="relative flex flex-1 flex-col p-6 sm:p-9 lg:p-11">
            {{-- Giant brand wordmark — prominent cream, the photo sits behind it. --}}
            <div class="pt-4 sm:pt-6">
                <span class="block font-display font-semibold leading-[0.9] tracking-tight text-cream drop-shadow-[0_4px_28px_rgba(8,36,20,0.5)]" style="font-size:clamp(3rem, 10.5vw, 8.5rem)">
                    <span class="block">Farmers</span>
                    <span class="block italic text-gold-soft">Hostel</span>
                </span>
            </div>

            <div class="flex-1"></div>

            {{-- Copy: eyebrow + flip-fade headline + subtitle + CTAs, in white. --}}
            <div class="max-w-xl">
                <p class="reveal-line text-[10px] font-semibold uppercase tracking-[0.32em] text-gold-soft sm:text-[11px] sm:tracking-[0.42em]">
                    <span style="--rise-delay:.1s">Inside CLSU since 1998</span>
                </p>

                {{-- FlipFadeText headline (crisp flip + fade, no blur); cream over the
                     photo, the rotating superlative in warm palay-gold. --}}
                @php $fi = 0; @endphp
                <h1 id="heroTitle" class="mt-4 font-display leading-[1.05] text-cream drop-shadow-[0_2px_16px_rgba(8,36,20,0.45)]" style="font-size:clamp(1.9rem, 3.6vw, 3rem)">
                    <span class="block">
                        <span class="split-word">@foreach (str_split('The') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">
                            <span id="heroWordRotate" class="word-rotate italic text-gold-soft" aria-label="quietest" data-words="quietest,greenest,calmest,warmest">
                                <span class="word-rotate-track" aria-hidden="true"><span class="word-rotate-word is-active">@foreach (str_split('quietest') as $ch)<span class="flip-char rt-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span></span>
                            </span>
                        </span>
                        <span class="split-word">@foreach (str_split('address') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">@foreach (str_split('on') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">@foreach (str_split('campus') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                    </span>
                </h1>

                <p class="hero-sub text-pretty mt-5 max-w-md text-base leading-relaxed text-cream/80">
                    @foreach (preg_split('/\s+/', 'A boutique hostel in the heart of CLSU. Two minutes to the labs, the fields, and a proper Filipino breakfast.') as $i => $word)
                        <span class="hero-sub-word" style="--w:{{ $i }}">{{ $word }}</span>
                    @endforeach
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3 animate-[fade-in-up_1s_ease-out_1.1s_both]">
                    <button type="button" onclick="smoothScrollTo(document.getElementById('rooms'))"
                            class="press focus-ring inline-flex min-h-12 items-center gap-2 rounded-full bg-cream px-7 py-3.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-ink cursor-pointer transition hover:bg-white hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-cream)_28%,transparent)]">
                        Start exploring <x-booking.ui.icon name="arrow-right" class="h-4 w-4" />
                    </button>
                    <button type="button" onclick="smoothScrollTo(document.getElementById('gallery'))"
                            class="press focus-ring inline-flex min-h-12 items-center gap-2 rounded-full border border-cream/40 bg-cream/10 px-7 py-3.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream backdrop-blur-sm cursor-pointer transition hover:bg-cream/20">
                        View gallery
                    </button>
                </div>
            </div>
        </div>

        {{-- Floating review card (inspo's "1200+ customer review"). --}}
        <div class="absolute right-6 bottom-32 z-20 hidden items-center gap-3 rounded-2xl border border-ink/5 bg-white/95 px-4 py-3 shadow-[0_22px_50px_-24px_rgba(8,36,20,0.5)] backdrop-blur-sm sm:flex">
            <div class="flex -space-x-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-clsu-100 text-[11px] font-bold text-clsu-700 ring-2 ring-white">JS</span>
                <span class="grid h-8 w-8 place-items-center rounded-full bg-palay-100 text-[11px] font-bold text-palay-700 ring-2 ring-white">MR</span>
                <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-deep text-[11px] font-bold text-cream ring-2 ring-white">+</span>
            </div>
            <div class="leading-tight">
                <p class="flex items-center gap-1 font-display text-lg text-ink">4.9
                    <x-booking.ui.icon name="star" class="h-4 w-4 fill-palay-400 text-palay-400" />
                </p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink/50">Loved by guests</p>
            </div>
        </div>

        {{-- Booking capsule — frosted white, spans the bottom of the panel.
             Functional IDs preserved for booking.js / availability-search.js / home.js. --}}
        <div class="relative z-20 px-4 pb-4 sm:px-9 sm:pb-8 lg:px-11">
            <div id="bookingCapsule" class="glass-light rounded-3xl p-3 md:p-2.5">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_1fr_1fr_auto] md:items-center">
                    <div class="px-4 py-2.5 text-left md:px-5 md:py-3">
                        <p class="mb-0.5 text-[10px] font-bold uppercase tracking-[0.26em] text-ink/45">Check in</p>
                        <input type="text" id="widget_check_in" aria-label="Check in date" placeholder="Select date"
                               class="focus-ring w-full min-h-10 cursor-pointer bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink/35">
                    </div>
                    <div class="px-4 py-2.5 text-left md:border-l md:border-ink/10 md:px-5 md:py-3">
                        <p class="mb-0.5 text-[10px] font-bold uppercase tracking-[0.26em] text-ink/45">Check out</p>
                        <input type="text" id="widget_check_out" aria-label="Check out date" placeholder="Select date"
                               class="focus-ring w-full min-h-10 cursor-pointer bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink/35">
                    </div>
                    <div class="px-4 py-2.5 text-left md:border-l md:border-ink/10 md:px-5 md:py-3">
                        <p class="mb-0.5 text-[10px] font-bold uppercase tracking-[0.26em] text-ink/45">Guests</p>
                        <div class="flex min-h-10 items-center justify-between">
                            <span class="text-sm font-medium text-ink"><span id="guests_display" class="anim-number"><span>1</span></span> guest<span id="guests_plural" class="hidden">s</span></span>
                            <input type="hidden" id="widget_guests" value="1">
                            <div class="flex items-center gap-1">
                                <button type="button" id="btn_minus_guests" aria-label="Decrease guests" class="focus-ring press grid h-8 w-8 place-items-center rounded-full border border-ink/15 bg-ink/5 text-ink transition hover:border-clsu-500/60 hover:bg-clsu-50 cursor-pointer">
                                    <x-booking.ui.icon name="minus" class="h-3 w-3" />
                                </button>
                                <button type="button" id="btn_plus_guests" aria-label="Increase guests" class="focus-ring press grid h-8 w-8 place-items-center rounded-full border border-ink/15 bg-ink/5 text-ink transition hover:border-clsu-500/60 hover:bg-clsu-50 cursor-pointer">
                                    <x-booking.ui.icon name="plus" class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnSearchRooms"
                            class="focus-ring press cta-shine group relative inline-flex min-h-12 items-center justify-center gap-2 overflow-hidden rounded-full bg-emerald-deep px-8 py-3.5 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream cursor-pointer transition hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-clsu-500)_28%,transparent),0_18px_44px_-18px_rgba(8,36,20,0.5)]">
                        <span id="btnSearchRoomsLabel" class="inline-flex items-center gap-2">Search <x-booking.ui.icon name="arrow-right" class="h-4 w-4" /></span>
                    </button>
                </div>

                <!-- Nights summary — filled by availability-search.js once both dates are set -->
                <div id="capsuleNights" class="capsule-nights" hidden>
                    <span class="capsule-nights-dot" aria-hidden="true"></span>
                    <span id="capsuleNightsText"></span>
                </div>
            </div>
        </div>
    </div>
</header>
