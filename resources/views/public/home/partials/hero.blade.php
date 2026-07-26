{{-- Farmers Hostel hero: daylight sky gradient, a defocused wash of the façade
     for atmosphere, and the cut-out building rising behind a giant two-line
     wordmark. The headline keeps the existing FlipFadeText + rotating-word
     treatment (home.js drives #heroWordRotate), and the capsule keeps every
     functional id used by booking.js / availability-search.js. --}}
<header id="firstsection" class="fh-hero">
    {{-- Atmosphere: an out-of-focus copy of the building tints the whole sky. --}}
    <img src="{{ asset('image/farmers-hostel-wide.png') }}" alt="" aria-hidden="true"
         decoding="async" class="fh-hero-wash">

    {{-- Stacked radial suns + corner falloff. --}}
    <div class="fh-hero-lights" aria-hidden="true"></div>

    {{-- Soft horizon band so the cut-out never floats on a bare gradient. --}}
    <div class="fh-hero-horizon" aria-hidden="true">
        <img src="{{ asset('image/farmers-hostel-wide.png') }}" alt="" decoding="async">
    </div>

    {{-- Dusk wash — opacity driven by scroll in home.js as the hero leaves. --}}
    <div class="fh-hero-deepen" data-hero-deepen aria-hidden="true"></div>

    <div class="fh-hero-stage">
        {{-- Giant wordmark. Parallaxes up and fades out on scroll, and each
             letter lifts/warms as the cursor passes it (home.js). Split into
             per-character spans for both effects; the sr-only copy keeps it
             from being announced letter by letter. --}}
        <div class="fh-wordmark" data-hero-word>
            <p class="fh-wordmark-line">
                <span class="sr-only">Farmers</span>
                <span aria-hidden="true">@foreach (str_split('FARMERS') as $i => $ch)<span class="fh-wm-char" style="--i:{{ $i }}">{{ $ch }}</span>@endforeach</span>
            </p>
            <p class="fh-wordmark-line fh-wordmark-line--end">
                <span class="sr-only">Hostel</span>
                <span aria-hidden="true">@foreach (str_split('HOSTEL') as $i => $ch)<span class="fh-wm-char" style="--i:{{ $i }}">{{ $ch }}</span>@endforeach</span>
            </p>
        </div>

        {{-- Contact shadow pooling under the building's base. --}}
        <div class="fh-hero-pool" aria-hidden="true"></div>

        <img src="{{ asset('image/hostel-front.png') }}"
             alt="Farmers Hostel building inside the CLSU campus"
             fetchpriority="high" decoding="async" class="fh-hero-build">

        {{-- Raked legibility scrim — darkens the copy column, not the picture. --}}
        <div class="fh-hero-scrim" aria-hidden="true"></div>

        <div class="fh-hero-copy">
            <div class="fh-hero-pitch">
                <span class="fh-rule" aria-hidden="true"></span>

                <p class="reveal-line mb-5 font-label text-[12px] uppercase tracking-[0.26em] text-gold-soft [text-shadow:0_1px_14px_rgba(3,16,32,0.7)]">
                    <span style="--rise-delay:.1s">Inside CLSU since 1998</span>
                </p>

                {{-- FlipFadeText headline: each character flips up into place on
                     load, and the superlative keeps cycling through data-words. --}}
                @php $fi = 0; @endphp
                <h1 id="heroTitle" class="fh-hero-title font-display font-normal leading-[1.1] tracking-[-0.012em] text-white [text-shadow:0_2px_22px_rgba(3,16,32,0.6)]">
                    <span class="block">
                        <span class="split-word">@foreach (str_split('The') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">
                            <span id="heroWordRotate" class="word-rotate italic text-gold-soft" aria-label="quietest" data-words="quietest,greenest,calmest">
                                <span class="word-rotate-track" aria-hidden="true"><span class="word-rotate-word is-active">@foreach (str_split('quietest') as $ch)<span class="flip-char rt-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span></span>
                            </span>
                        </span>
                        <span class="split-word">@foreach (str_split('address') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">@foreach (str_split('on') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                        <span class="split-word">@foreach (str_split('campus') as $ch)<span class="flip-char" style="--i:{{ $fi++ }}">{{ $ch }}</span>@endforeach</span>
                    </span>
                </h1>

                <p class="hero-sub text-pretty mt-5 mb-7 max-w-[330px] text-[15.5px] leading-[1.64] text-white/90 [text-shadow:0_1px_16px_rgba(3,16,32,0.7)]">
                    @foreach (preg_split('/\s+/', 'A boutique hostel in the heart of CLSU. Two minutes to the labs, the fields, and a proper Filipino breakfast.') as $i => $word)
                        <span class="hero-sub-word" style="--w:{{ $i }}">{{ $word }}</span>
                    @endforeach
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-magnetic
                            onclick="smoothScrollTo(document.getElementById('rooms'))"
                            class="fh-btn fh-btn--light focus-ring">
                        Check availability <span class="fh-btn-disc" aria-hidden="true">↗</span>
                    </button>
                    <button type="button" data-magnetic
                            onclick="smoothScrollTo(document.getElementById('gallery'))"
                            class="fh-btn fh-btn--ghost focus-ring">
                        Take a tour <span class="fh-btn-disc" aria-hidden="true">↗</span>
                    </button>
                </div>
            </div>

            {{-- Social-proof badge. Hidden below 900px (see app.css). --}}
            <div class="fh-badge">
                <p class="mb-3 text-[12px] tracking-[0.28em] text-[#c9a24a]" aria-label="Rated 4.8 out of 5">★★★★★</p>
                <div class="mb-3.5 flex items-center">
                    <span class="grid h-[38px] w-[38px] place-items-center rounded-full border-2 border-white bg-[#d8c39c] text-[13px] font-semibold text-[#4a3a20]">JR</span>
                    <span class="-ml-3 grid h-[38px] w-[38px] place-items-center rounded-full border-2 border-white bg-[#a9bfa2] text-[13px] font-semibold text-[#2f4029]">MA</span>
                    <span class="-ml-3 grid h-[38px] w-[38px] place-items-center rounded-full border-2 border-white bg-[#b8654a] text-[13px] font-semibold text-[#fff4ec]">LC</span>
                    <span class="-ml-3 grid h-[38px] w-[38px] place-items-center rounded-full border-2 border-white bg-[#10161c] text-[15px] text-white">+</span>
                </div>
                <p class="font-display text-[40px] leading-none tracking-[-0.02em] text-[#10161c]">1,200<span class="text-[#c9a24a]">+</span></p>
                <p class="mt-1.5 font-label text-[11.5px] font-light uppercase tracking-[0.2em] text-[#7c8288]">Guest reviews · 4.8 / 5</p>
            </div>
        </div>
    </div>

    <p class="fh-hero-note">
        <span class="fh-hero-note-dot" aria-hidden="true"></span>
        Best rate when you book direct · no service fees
        <span class="fh-hero-note-dot" aria-hidden="true"></span>
    </p>

    {{-- Booking capsule — functional ids preserved for booking.js /
         availability-search.js / home.js. --}}
    <div id="bookingCapsule" class="fh-capsule">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_1fr_1.15fr_auto] md:items-center">
            <label class="fh-field block cursor-pointer">
                <span class="fh-field-label"><span class="fh-field-dot" aria-hidden="true"></span>Check in</span>
                <input type="text" id="widget_check_in" aria-label="Check in date" placeholder="Select date"
                       class="focus-ring mt-1 w-full cursor-pointer border-0 bg-transparent text-[16px] text-[#10161c] outline-none placeholder:text-[#9aa1aa]">
            </label>

            <label class="fh-field block cursor-pointer">
                <span class="fh-field-label"><span class="fh-field-dot" aria-hidden="true"></span>Check out</span>
                <input type="text" id="widget_check_out" aria-label="Check out date" placeholder="Select date"
                       class="focus-ring mt-1 w-full cursor-pointer border-0 bg-transparent text-[16px] text-[#10161c] outline-none placeholder:text-[#9aa1aa]">
            </label>

            <div class="fh-field">
                <span class="fh-field-label"><span class="fh-field-dot" aria-hidden="true"></span>Guests</span>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <span class="text-[16px] text-[#10161c]">
                        <span id="guests_display" class="anim-number"><span>1</span></span> guest<span id="guests_plural" class="hidden">s</span>
                    </span>
                    <input type="hidden" id="widget_guests" value="1">
                    <div class="flex items-center gap-1">
                        <button type="button" id="btn_minus_guests" aria-label="Decrease guests"
                                class="focus-ring press grid h-7 w-7 cursor-pointer place-items-center rounded-full border border-[#10161c]/15 bg-[#10161c]/5 text-[#10161c] transition hover:border-[#b8654a]/60 hover:bg-[#b8654a]/10">
                            <x-booking.ui.icon name="minus" class="h-3 w-3" />
                        </button>
                        <button type="button" id="btn_plus_guests" aria-label="Increase guests"
                                class="focus-ring press grid h-7 w-7 cursor-pointer place-items-center rounded-full border border-[#10161c]/15 bg-[#10161c]/5 text-[#10161c] transition hover:border-[#b8654a]/60 hover:bg-[#b8654a]/10">
                            <x-booking.ui.icon name="plus" class="h-3 w-3" />
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" id="btnSearchRooms" data-magnetic class="fh-search focus-ring">
                <span id="btnSearchRoomsLabel" class="inline-flex items-center gap-3">Search <span class="fh-search-disc" aria-hidden="true">↗</span></span>
            </button>
        </div>

        <!-- Nights summary — filled by availability-search.js once both dates are set -->
        <div id="capsuleNights" class="capsule-nights" hidden>
            <span class="capsule-nights-dot" aria-hidden="true"></span>
            <span id="capsuleNightsText"></span>
        </div>
    </div>
</header>

{{-- Feature marquee — two identical runs translated by -50% for a seamless loop. --}}
<div class="fh-marquee" aria-hidden="true">
    <div class="fh-marquee-track">
        @foreach ([1, 2] as $pass)
            <div class="fh-marquee-run">
                <span>Air-conditioned rooms</span><span class="fh-marquee-star">✳</span>
                <span>Farm-fresh breakfast</span><span class="fh-marquee-star">✳</span>
                <span>Function &amp; training halls</span><span class="fh-marquee-star">✳</span>
                <span>24/7 front desk</span><span class="fh-marquee-star">✳</span>
                <span>Free parking</span><span class="fh-marquee-star">✳</span>
                <span>Fibre Wi-Fi</span><span class="fh-marquee-star">✳</span>
            </div>
        @endforeach
    </div>
</div>
