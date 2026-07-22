<header id="firstsection" class="relative isolate overflow-hidden bg-canvas pt-28 sm:pt-32">
    {{-- Soft ambient CLSU wash behind the split — light, airy, green + palay --}}
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute -top-24 -left-24 h-[28rem] w-[28rem] rounded-full bg-clsu-100/50 blur-3xl"></div>
        <div class="absolute top-32 -right-16 h-96 w-96 rounded-full bg-palay-100/40 blur-3xl"></div>
    </div>

    <div class="mx-auto max-w-7xl px-6">
        <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_1fr] lg:gap-14">
            {{-- LEFT: eyebrow + flip-fade headline + subtitle + CTAs. No
                 prlx-hero-content here: that preset applies a scroll-tied blur +
                 opacity fade (tuned for the old full-height dark hero) that left
                 the headline soft at the top of a short hero on desktop. --}}
            <div class="pb-4 text-left lg:pb-0">
                <p class="reveal-line text-[10px] font-semibold uppercase tracking-[0.32em] text-clsu-600 sm:text-[11px] sm:tracking-[0.45em]">
                    <span style="--rise-delay:.1s">Inside CLSU since 1998</span>
                </p>

                {{-- FlipFadeText (vengenceui.com/components/flip-fade-text) ported to
                     vanilla: every character flips up into place (rotateX 90°→0 + blur
                     8px→0) on the hero's own load clock, staggered via --i. Words wrap in
                     inline-block .split-word. Plain text is the no-JS / reduced-motion
                     fallback. Ink on the light canvas; the rotating word carries CLSU green. --}}
                @php $fi = 0; @endphp
                <h1 id="heroTitle" class="mt-5 font-display leading-[1.05] text-ink" style="font-size:clamp(2.5rem, 5.6vw, 4.75rem)">
                    <span class="block">
                        <span class="split-word">@foreach (str_split('The') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        {{-- Rotating superlative — FlipFadeText cycler in home.js; reuses these
                             .rt-char after their flip-in. --}}
                        <span class="split-word">
                            <span id="heroWordRotate" class="word-rotate italic text-clsu-600" aria-label="quietest" data-words="quietest,greenest,calmest,warmest">
                                <span class="word-rotate-track" aria-hidden="true"><span class="word-rotate-word is-active">@foreach (str_split('quietest') as $ch)<span class="flip-char rt-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span></span>
                            </span>
                        </span>
                    </span>
                    <span class="block">
                        <span class="split-word">@foreach (str_split('address') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">@foreach (str_split('on') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">@foreach (str_split('campus') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                    </span>
                </h1>

                <p class="hero-sub text-pretty mt-6 max-w-lg text-base leading-relaxed text-ink/65 sm:text-lg">
                    @foreach (preg_split('/\s+/', 'A boutique hostel in the heart of CLSU. Two minutes to the labs, the fields, and a proper Filipino breakfast.') as $i => $word)
                        <span class="hero-sub-word" style="--w:{{ $i }}">{{ $word }}</span>
                    @endforeach
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3 animate-[fade-in-up_1s_ease-out_1.1s_both]">
                    <button type="button" onclick="smoothScrollTo(document.getElementById('rooms'))"
                            class="press focus-ring inline-flex min-h-12 items-center gap-2 rounded-full bg-emerald-deep px-7 py-3.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream cursor-pointer transition hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-clsu-500)_22%,transparent)]">
                        Explore rooms <x-booking.ui.icon name="arrow-right" class="h-4 w-4" />
                    </button>
                    <button type="button" onclick="smoothScrollTo(document.getElementById('gallery'))"
                            class="press focus-ring inline-flex min-h-12 items-center gap-2 rounded-full border border-ink/15 px-7 py-3.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-ink cursor-pointer transition hover:border-clsu-500/60 hover:bg-clsu-50">
                        View gallery
                    </button>
                </div>
            </div>

            {{-- RIGHT: campus photo in a rounded card + floating provenance badge --}}
            <div class="relative prlx-float">
                <div class="relative overflow-hidden rounded-[2rem] shadow-[0_40px_90px_-30px_rgba(8,36,20,0.42)] ring-1 ring-ink/5">
                    <img src="{{ asset('image/hostel1.jpg') }}" alt="Farmers Hostel exterior inside the CLSU campus"
                         fetchpriority="high" decoding="async"
                         class="h-[52vh] max-h-[600px] min-h-[380px] w-full animate-ken-burns object-cover object-center">
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-linear-to-t from-ink/35 to-transparent"></div>
                </div>
                {{-- Floating badge (inspo: the little review card) --}}
                <div class="absolute -bottom-5 -left-3 flex items-center gap-3 rounded-2xl border border-ink/5 bg-canvas/95 px-4 py-3 shadow-[0_20px_50px_-24px_rgba(8,36,20,0.4)] backdrop-blur-sm sm:left-5">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-clsu-50 text-clsu-700">
                        <x-booking.ui.icon name="star" class="h-5 w-5" />
                    </span>
                    <div class="leading-tight">
                        <p class="font-display text-lg text-ink">Est. 1998</p>
                        <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-ink/50">Inside CLSU campus</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Booking capsule — light glass, spans the full width beneath the split.
             Functional IDs preserved for booking.js / availability-search.js / home.js. --}}
        <div class="mt-12 pb-16 sm:mt-14 sm:pb-20 animate-[fade-in-up_1.1s_ease-out_1.25s_both]">
            <div id="bookingCapsule" class="glass-light rounded-3xl p-4 md:p-3">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_auto] md:items-center md:gap-2">
                    <div class="px-4 py-3 text-left md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-ink/45">Check in</p>
                        <input type="text" id="widget_check_in" aria-label="Check in date" placeholder="Select date"
                               class="focus-ring w-full min-h-11 cursor-pointer bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink/35">
                    </div>
                    <div class="px-4 py-3 text-left md:border-l md:border-ink/10 md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-ink/45">Check out</p>
                        <input type="text" id="widget_check_out" aria-label="Check out date" placeholder="Select date"
                               class="focus-ring w-full min-h-11 cursor-pointer bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink/35">
                    </div>
                    <div class="px-4 py-3 text-left md:border-l md:border-ink/10 md:px-6 md:py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.28em] text-ink/45">Guests</p>
                        <div class="flex min-h-11 items-center justify-between">
                            <span class="text-sm font-medium text-ink"><span id="guests_display" class="anim-number"><span>1</span></span> guest<span id="guests_plural" class="hidden">s</span></span>
                            <input type="hidden" id="widget_guests" value="1">
                            <div class="flex items-center gap-1">
                                <button type="button" id="btn_minus_guests" aria-label="Decrease guests" class="focus-ring press grid h-9 w-9 place-items-center rounded-full border border-ink/15 bg-ink/5 text-ink transition hover:border-clsu-500/60 hover:bg-clsu-50 cursor-pointer">
                                    <x-booking.ui.icon name="minus" class="h-3 w-3" />
                                </button>
                                <button type="button" id="btn_plus_guests" aria-label="Increase guests" class="focus-ring press grid h-9 w-9 place-items-center rounded-full border border-ink/15 bg-ink/5 text-ink transition hover:border-clsu-500/60 hover:bg-clsu-50 cursor-pointer">
                                    <x-booking.ui.icon name="plus" class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnSearchRooms"
                            class="focus-ring press cta-shine group relative inline-flex min-h-12 items-center justify-center gap-2 overflow-hidden rounded-full bg-emerald-deep px-8 py-4 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream cursor-pointer transition hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-clsu-500)_28%,transparent),0_18px_44px_-18px_rgba(8,36,20,0.5)]">
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
    </div>
</header>
