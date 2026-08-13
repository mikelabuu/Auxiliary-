@php
    // Does this page carry a Livewire component? As of 2026-08-09 no public
    // page does — checkout was the last one, via the address-selector, which is
    // Blade + Alpine now. The gate stays because it is what keeps a future
    // Livewire component from loading a second Alpine. Sections are registered
    // before the layout renders, so this is readable up here in <head>.
    $usesLivewire = trim($__env->yieldContent('livewire')) === '1';
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Farmers Hostel · Boutique Stay Inside CLSU Campus')</title>

    <!-- Gates the scroll-reveal hidden state (see reveal.js) so content is
         never invisible if JS doesn't run. Must execute before first paint. -->
    <script>
        document.documentElement.classList.add('js-reveal');
    </script>

    {{-- `window.pubModalClose` used to be defined here: a bare hide/show with no
         scroll lock, no Escape, no focus trap and no focus restore. The modal
         engine in resources/js/admin-modals.js does all of that and has always
         been bundled into the app.js below — the public site simply wasn't
         using it. It now owns x-booking.ui.modal and keeps `pubModalClose` as
         an alias. --}}

    <!-- Tailwind & Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Early connection for the font origins (the only remaining third party —
         every library below is served from public/vendor on our own origin) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Google Fonts — the three families body.theme-boutique actually points at:
         Playfair Display (--font-display), Manrope (--font-sans) and Oswald
         (--font-label).

         Lora and Nunito Sans used to be requested here too, described as "the
         fallback stack for views still authored against them". They were not:
         a search of every stylesheet found them only inside comments, with zero
         live rules, so both were paid for on every page load and used by
         nothing. --}}
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..600&family=Oswald:wght@300;400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome Free, self-hosted (scripts/sync-vendor.mjs). One icon set
         across the public site and both staff consoles; replaced the Material
         Icons CDN font the booking flow used to pull.

         Pages opt out of the eager path with @section('defer_icons', '1'), or
         out of Font Awesome altogether with @section('no_icons', '1').

         The preload is a high-priority fetch of a 117 KB font, and the sheet
         is 90 KB of render-blocking CSS. On checkout, account and booking that
         is correct: those pages render FA glyphs in their first paint.

         The landing renders none at all now, so it takes `no_icons` and pays
         nothing. It used to take `defer_icons` on the argument that its only
         Font Awesome was the three availability pills, which "in most visits
         never appear" — but availability-search.js runs its search on
         DOMContentLoaded rather than waiting for the guest, so the pills, and
         the 205 KB they dragged in, were on every single load. Deferring the
         sheet only meant the glyphs popped in late. Those three icons are
         inline lucide SVG now (see PILL_ICONS in availability-search.js),
         which is what the rest of the page's icons already were.

         Deferred pages still get the exact same stylesheet, just off the
         critical path: media="print" makes it non-blocking and the onload
         hands it back to all. The noscript copy covers JS-off, where the
         swap would never fire. --}}
    @if (trim($__env->yieldContent('no_icons')) !== '')
        {{-- nothing: this page renders no Font Awesome glyph at all --}}
    @elseif (trim($__env->yieldContent('defer_icons')) !== '')
        <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet"
              media="print" onload="this.media='all'; this.onload=null;">
        <noscript><link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet"></noscript>
    @else
        <link rel="preload" as="font" type="font/woff2" crossorigin
              href="{{ asset('vendor/fontawesome/webfonts/fa-solid-900.woff2') }}">
        <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    @endif

    {{-- Vendor libraries are self-hosted from public/vendor (see
         scripts/sync-vendor.mjs) and are now OPT-IN. Each one is a partial in
         partials/vendor/ guarded by @once, and the view that actually needs it
         pushes it here — so the landing page no longer ships flatpickr to a
         page with no date field, and checkout no longer ships Swiper.

         Push from the partial that owns the dependency, not from the page:

             @push('vendor') @include('partials.vendor.lightbox') @endpush --}}
    @stack('vendor')

    {{-- Alpine, exactly once.

         Livewire's runtime bundles its own Alpine, so a page that has a
         Livewire component must NOT also load the standalone build - two
         Alpines on one page throw on init. No public page carries one today,
         so every page takes the ~46 KB standalone build. A page that adds a
         Livewire component must declare @section('livewire', '1') to flip
         this, or it will end up with two Alpines. --}}
    @if ($usesLivewire)
        @livewireStyles
    @else
        <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
    @endif

    {{-- Shared scroll/frame scheduler. Must execute before any consumer
         (home.js, parallax.js, scroll-effects.js), so it lives here in <head>
         rather than beside them: `defer` scripts run in document order, and
         @yield('content') — where the page-level scripts sit — comes later.

         One rAF loop and one scroll listener for the whole page. The three
         files above each used to run their own; measured at 6x CPU throttle,
         scrolling the landing page cost 27.7ms/frame with all three loops and
         7.1ms with none, while removing any single one only reached ~21ms. --}}
    <script src="{{ asset('js/frame-bus.js') }}?v={{ filemtime(public_path('js/frame-bus.js')) }}" defer></script>

    @stack('styles')
</head>
@php
    $navDark = trim($__env->yieldContent('nav_dark')) !== '';
    $nightTheme = trim($__env->yieldContent('theme_night')) !== '';
@endphp
{{-- overflow-x-clip guards against pre-reveal translateX states (fade-left/right) widening the page --}}
<body class="theme-boutique {{ $nightTheme ? 'theme-night' : '' }} antialiased font-sans bg-canvas text-ink flex flex-col min-h-screen overflow-x-clip selection:bg-gold-soft selection:text-ink">

    {{-- Keyboard users land here first: one Tab jumps past the whole nav to the
         page content. Off-screen until focused (see .skip-link in 01-tokens.css). --}}
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Reading-progress hairline (driven by --scroll-progress from parallax.js) -->
    <div class="scroll-progress" aria-hidden="true"></div>

    <!-- Header: spans the frame edge-to-edge and transparent over the hero,
         then condenses into a dark-glass pill past the fold. Pages with no
         dark hero start in the light `is-static` pill instead. Retreats while
         reading down and returns on the first upward scroll. -->
    <div id="navWrap" class="{{ $navDark ? '' : 'is-static' }}">
        <nav id="siteNav" aria-label="Primary" data-dark="{{ $navDark ? '1' : '0' }}">
            <!-- Brand lockup -->
            <a href="{{ route('home') }}" aria-label="Farmers Hostel home" class="fh-brand focus-ring rounded-full">
                {{-- sizes matches the mark's largest painted size (40px box,
                     scaled to 1.15 in the rest state); it said 30px, which had
                     the pipeline serving a source too small for the lockup. --}}
                <x-img src="image/fh-mark.png" alt="" aria-hidden="true" sizes="46px"
                       width="60" height="60" decoding="async" class="fh-brand-mark" />
                <span class="grid min-w-0">
                    <span class="fh-brand-name truncate">Farmers Hostel</span>
                    <span class="fh-brand-tag">CLSU · Science City of Muñoz</span>
                </span>
            </a>

            <!-- Center Nav Links -->
            <div class="fh-nav hidden md:flex">
                <a href="{{ route('home') }}" class="fh-nav-link focus-ring {{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                <a href="{{ route('home') }}#rooms" class="fh-nav-link focus-ring">Rooms</a>
                <a href="{{ route('home') }}#gallery" class="fh-nav-link focus-ring">Gallery</a>
                @auth
                    <a href="{{ route('settings.bookings') }}" class="fh-nav-link focus-ring">My Bookings</a>
                @endauth
            </div>

            <!-- Right Side Actions & User Menu -->
            <div class="flex items-center gap-3">
                <div class="hidden md:block">
                    @auth
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="fh-hdr-ghost press focus-ring cursor-pointer select-none">
                                <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-deep font-display text-sm italic text-cream">
                                    {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) }}
                                </span>
                                <span class="truncate">{{ $username ?? auth()->user()->username ?? 'Account' }}</span>
                                <x-booking.ui.icon name="chevron-right" class="h-3.5 w-3.5 transition-transform duration-200" ::class="open ? 'rotate-90' : ''" />
                            </button>

                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-out duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-3 w-60 origin-top-right overflow-hidden rounded-2xl border border-ink/10 bg-canvas py-2 text-ink shadow-[0_24px_60px_-20px_rgba(4,14,10,0.5)] z-50"
                                 style="display: none;"
                            >
                                <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 px-5 py-3 text-[12px] font-semibold uppercase tracking-[0.14em] text-ink/70 hover:bg-canvas-deep hover:text-ink transition-colors">
                                    <x-booking.ui.icon name="user" class="h-4 w-4 text-emerald" /> My Profile
                                </a>
                                <a href="{{ route('settings.bookings') }}" class="flex items-center gap-3 px-5 py-3 text-[12px] font-semibold uppercase tracking-[0.14em] text-ink/70 hover:bg-canvas-deep hover:text-ink transition-colors">
                                    <x-booking.ui.icon name="book-open" class="h-4 w-4 text-emerald" /> My Bookings
                                </a>
                                <a href="{{ route('settings.transactions') }}" class="flex items-center gap-3 px-5 py-3 text-[12px] font-semibold uppercase tracking-[0.14em] text-ink/70 hover:bg-canvas-deep hover:text-ink transition-colors">
                                    <x-booking.ui.icon name="credit-card" class="h-4 w-4 text-emerald" /> Transactions
                                </a>
                                <div class="mx-5 my-1 h-px bg-ink/10"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-[0.14em] text-ember-600 hover:bg-ember-50 transition-colors cursor-pointer">
                                        <x-booking.ui.icon name="log-out" class="h-4 w-4" /> Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-4">
                            <a href="{{ route('login') }}" class="fh-nav-link focus-ring">Sign in</a>
                            <a href="{{ route('home') }}#rooms" class="fh-cta focus-ring">
                                Book a stay <span class="fh-cta-disc" aria-hidden="true">↗</span>
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                {{-- aria-expanded starts false and is kept in sync by
                     toggleDrawer(); aria-controls ties it to the panel it
                     opens. --}}
                <button id="mobileMenuBtn" class="focus-ring press grid h-11 w-11 shrink-0 place-items-center rounded-full border border-current/30 bg-current/10 md:hidden cursor-pointer" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
                    <x-booking.ui.icon name="menu" class="h-4 w-4" />
                </button>
            </div>
        </nav>
    </div>

    <!-- Mobile Drawer Navigation -->
    {{-- role/aria-modal + the label make this announce as a dialog; the scroll
         lock, focus trap, focus restore and Escape come from the modal engine
         via openOverlay() below. It keeps its own opacity/visibility exit
         (05-header.css) rather than the engine's `hidden` toggle. --}}
    <div id="mobileDrawer" class="fixed inset-0 z-[60] flex justify-end bg-ink/50 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-label="Site navigation" aria-hidden="true">
        <div class="drawer-panel bg-canvas w-80 h-full shadow-2xl p-7 flex flex-col justify-between border-l border-ink/10">
            <div>
                <div class="flex items-center justify-between pb-6 border-b border-ink/10">
                    <div class="flex items-center gap-2.5">
                        <x-img src="image/fh-mark.png" alt="" aria-hidden="true" sizes="36px"
                               width="60" height="60" decoding="async" class="h-9 w-9 object-contain" />
                        <span class="font-display text-base tracking-tight text-ink">Farmers <span class="italic text-clsu-600">Hostel</span></span>
                    </div>
                    {{-- tap-expand: this drawer is touch-only by definition, and
                         36px is under every platform's target guidance. The ring
                         stays 36px; the tappable region becomes 48px. --}}
                    <button id="mobileDrawerCloseBtn" class="press tap-expand grid h-9 w-9 place-items-center rounded-full text-ink/50 hover:bg-canvas-deep hover:text-ink cursor-pointer" aria-label="Close navigation menu">
                        <x-booking.ui.icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <nav class="mt-7 flex flex-col gap-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                        <x-booking.ui.icon name="home" class="h-4 w-4 text-emerald" /> Home
                    </a>
                    <a href="{{ route('home') }}#rooms" id="mobileRoomsLink" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                        <x-booking.ui.icon name="bed" class="h-4 w-4 text-emerald" /> Rooms
                    </a>
                    <a href="{{ route('home') }}#gallery" id="mobileGalleryLink" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                        <x-booking.ui.icon name="images" class="h-4 w-4 text-emerald" /> Gallery
                    </a>
                    <a href="#Footer" id="mobileContactLink" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                        <x-booking.ui.icon name="mail" class="h-4 w-4 text-emerald" /> Contact
                    </a>
                    @auth
                        <div class="my-2 h-px bg-ink/10"></div>
                        <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                            <x-booking.ui.icon name="user" class="h-4 w-4 text-emerald" /> My Profile
                        </a>
                        <a href="{{ route('settings.bookings') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                            <x-booking.ui.icon name="book-open" class="h-4 w-4 text-emerald" /> My Bookings
                        </a>
                        <a href="{{ route('settings.transactions') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-colors">
                            <x-booking.ui.icon name="credit-card" class="h-4 w-4 text-emerald" /> Transactions
                        </a>
                    @endauth
                </nav>
            </div>

            <div>
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="press flex w-full items-center justify-center gap-2 rounded-full bg-ember-50 py-3 px-4 text-[12px] font-bold uppercase tracking-[0.16em] text-ember-700 hover:bg-ember-100 cursor-pointer">
                            <x-booking.ui.icon name="log-out" class="h-4 w-4" /> Log Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="press flex w-full items-center justify-center gap-2 rounded-full bg-emerald-deep py-3.5 px-4 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream hover:bg-emerald">
                        Sign in / Register
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Main Content wrapper -->
    <main id="main-content" class="flex-grow" tabindex="-1">
        @yield('content')
    </main>

    <!-- Editorial Footer (Night Estate; shared across cream and night pages) -->
    {{-- z-31 puts the footer above .gradual-blur (z-30). The scrim fades to
         --color-canvas, which is cream on the light pages, and the footer is
         dark — without this it would sit as a pale haze over the footer once
         you reach the bottom. The footer is the end of the page, so there is
         nothing there that needs a soft exit anyway. Stays below the mobile
         sticky bar (z-40) and the nav, so their stacking is unchanged. --}}
    <footer class="relative z-[31] overflow-hidden border-t border-white/10 bg-clsu-950 text-bone/85 mt-auto" id="Footer">
        <div class="mx-auto max-w-7xl px-6 pt-20 pb-14 md:pt-24">
            <div class="grid gap-12 md:grid-cols-3 md:gap-16">
                <!-- Brand column -->
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-gold">Est. 1998</p>
                    <h2 class="mt-4 font-display text-4xl leading-tight text-bone md:text-5xl">Farmers <span class="italic text-gold">Hostel</span></h2>
                    <p class="text-pretty mt-6 max-w-sm text-sm leading-relaxed text-bone/60">
                        A quiet boutique residence inside CLSU, built for visiting professors, researchers, families, and student groups who want rest, service, and a two-minute walk to the lab.
                    </p>
                    <p class="mt-8 text-[11px] uppercase tracking-[0.3em] text-bone/50">CLSU, Science City of Muñoz, Nueva Ecija</p>
                </div>

                <!-- Explore + Contact -->
                <div class="grid grid-cols-2 gap-8 min-w-0">
                    <div>
                        <h4 class="mb-6 text-[10px] font-bold uppercase tracking-[0.3em] text-gold">Explore</h4>
                        <ul class="space-y-3 text-sm">
                            <li><a href="{{ route('home') }}" class="gold-underline focus-ring rounded">Home</a></li>
                            <li><a href="{{ route('home') }}#rooms" class="gold-underline focus-ring rounded">Rooms</a></li>
                            <li><a href="{{ route('home') }}#gallery" class="gold-underline focus-ring rounded">Gallery</a></li>
                            @auth
                                <li><a href="{{ route('settings.bookings') }}" class="gold-underline focus-ring rounded">My Bookings</a></li>
                                <li><a href="{{ route('settings.profile') }}" class="gold-underline focus-ring rounded">User Center</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="gold-underline focus-ring rounded">Sign in</a></li>
                            @endauth
                        </ul>
                    </div>
                    <div class="min-w-0">
                        <h4 class="mb-6 text-[10px] font-bold uppercase tracking-[0.3em] text-gold">Contact</h4>
                        <ul class="space-y-3 text-sm text-bone/85 break-words">
                            <li><a href="mailto:farmershostel@clsu.edu.ph" class="gold-underline focus-ring">farmershostel@clsu.edu.ph</a></li>
                            <li>+63 945 123 4567</li>
                            <li class="text-bone/55">Front desk, 24 / 7</li>
                        </ul>
                    </div>
                </div>

                <!-- Field Notes newsletter -->
                <div class="min-w-0">
                    <h4 class="mb-6 text-[10px] font-bold uppercase tracking-[0.3em] text-gold">Field Notes</h4>
                    <p class="text-pretty text-sm leading-relaxed text-bone/60">A seasonal letter from the farmstead: new rooms, harvests, and a standing invitation to breakfast.</p>
                    <form class="mt-5" id="fieldNotesForm" aria-label="Subscribe to Field Notes">
                        <label for="footer-email" class="sr-only">Email address</label>
                        <div class="flex items-center gap-2 border-b border-gold/50 py-2 focus-within:border-gold transition-colors">
                            <input id="footer-email" type="email" required placeholder="you@fieldwork.ph" class="min-w-0 flex-1 bg-transparent text-sm text-bone placeholder:text-bone/40 focus:outline-none" />
                            <button type="submit" aria-label="Subscribe" class="focus-ring press grid h-9 w-9 place-items-center rounded-full bg-gold text-night transition hover:bg-gold-soft cursor-pointer">
                                <x-booking.ui.icon name="arrow-right" class="h-4 w-4" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10 bg-black/20">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-6 py-6 text-[10px] uppercase tracking-[0.3em] text-bone/50 md:flex-row">
                <p>&copy; {{ date('Y') }} Farmers Hostel · CLSU Campus</p>
                <a href="{{ route('staff.login') }}" class="gold-underline focus-ring inline-flex items-center gap-2 rounded">
                    <x-booking.ui.icon name="lock" class="h-3 w-3" /> Staff Portal
                </a>
            </div>
        </div>
    </footer>

    {{-- Bottom-edge soft exit — content fades out as it leaves the viewport.
         Now a single gradient scrim rather than three stacked backdrop-filter
         layers; the element carries the whole effect itself, so the layer divs
         that used to live here are gone. See .gradual-blur in 08-utilities.css
         for why the blur was retired. --}}
    <div class="gradual-blur" aria-hidden="true"></div>

    @if ($usesLivewire)
        @livewireScripts
    @endif
    @stack('scripts')

    <!-- Nav, drawer & footer behaviours -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Header behaviour: condense past the fold, and retreat while
            // reading down. Both used to be their own scroll listener, each
            // reading window.scrollY and writing classes straight out of the
            // event — two handlers firing at the scroll event's rate, on a
            // fixed element, interleaved with the parallax loop's own reads.
            // They are one rAF-coalesced pass now: at most one read and one
            // class write per frame, no matter how fast the events arrive.
            const nav = document.getElementById('siteNav');
            const navWrap = document.getElementById('navWrap');
            if (navWrap) {
                const condenses = nav && nav.dataset.dark === '1';
                let condensed = null;
                let hidden = null;
                let lastY = window.scrollY;
                let travel = 0;          // signed distance since the last reversal
                let queued = false;

                // The retreat used to key off a single frame's delta:
                //   if (Math.abs(delta) < 8 || Math.abs(delta) > 320) return;
                //   toggle('nav-hidden', delta > 0 && y > 420);
                // which had two failure modes. A trackpad or a momentum tail
                // delivers a long run of 1-7px frames, and every one of those
                // hit the `< 8` guard and returned — so easing back up the page
                // slowly never un-hid the nav, and it stayed gone until you
                // flicked hard enough to clear the threshold. Meanwhile a
                // single 9px twitch downward was enough to hide it.
                //
                // Accumulating into `travel` and resetting on reversal fixes
                // both: small movements still count, they just have to add up
                // to something intentional. The reveal threshold is lower than
                // the hide threshold because getting the nav *back* should feel
                // eager, while losing it should take a deliberate read-down.
                const HIDE_AFTER = 72;   // px of continuous downward travel
                const SHOW_AFTER = 40;   // px of continuous upward travel
                const HIDE_BELOW = 420;  // never retreat this near the top

                // The condense used to be one threshold: `y > 36`. A single
                // value means the state can change on a 1px move, and the
                // moment you rest anywhere near it — a trackpad easing off, a
                // momentum tail, the bounce at the top of the page — it flips
                // back and forth. Walking y from 30 to 40 and back flipped it
                // four times, and every flip restarts a 0.42s transition that
                // was already mid-flight, so the pill reverses direction
                // partway and reads as a wobble rather than a change of state.
                //
                // A Schmitt trigger fixes it: condense on the way down at 56,
                // release on the way back up at 16, and inside that band keep
                // whatever state you already have. Crossing has to be
                // deliberate, and once crossed the transition gets to finish.
                // Same shape as the HIDE/SHOW pair above — the asymmetry is the
                // point, not an oversight.
                const CONDENSE_AT = 56;  // px down before the pill forms
                const RELEASE_AT = 16;   // px — must return this close to release

                function update() {
                    queued = false;
                    const y = window.scrollY;
                    const delta = y - lastY;
                    lastY = y;

                    if (condenses) {
                        // null on the first pass (page load / bfcache restore):
                        // pick a state outright rather than inheriting one.
                        const want = condensed === null
                            ? y > CONDENSE_AT
                            : (condensed ? y > RELEASE_AT : y > CONDENSE_AT);
                        if (want !== condensed) {
                            condensed = want;
                            navWrap.classList.toggle('is-condensed', want);
                        }
                    }

                    // Anchor landings and bfcache restores arrive as one huge
                    // jump; treat them as a fresh start rather than as reading.
                    if (Math.abs(delta) > 320) { travel = 0; return; }
                    if (!delta) return;

                    travel = (travel > 0) === (delta > 0) ? travel + delta : delta;

                    let want = hidden;
                    if (y <= HIDE_BELOW) want = false;
                    else if (travel > HIDE_AFTER) want = true;
                    else if (travel < -SHOW_AFTER) want = false;

                    if (want !== hidden) {
                        hidden = want;
                        navWrap.classList.toggle('nav-hidden', !!want);
                    }
                }

                function onScroll() {
                    if (!queued) { queued = true; requestAnimationFrame(update); }
                }

                update();
                window.addEventListener('scroll', onScroll, { passive: true });

                // An open drawer sits above the header, and the scroll lock
                // means no scroll events arrive to bring it back — so a nav
                // that retreated just before opening would still be gone on
                // close. Reset when the drawer takes over.
                // Unconditional rather than guarded on `hidden`: the class is
                // the thing that matters, and gating the removal on our own
                // state means any drift between the two (a stylesheet swap, a
                // restore, anything that touches the class from outside) leaves
                // the nav stuck off-screen with no way back.
                window.fhRevealNav = function () {
                    travel = 0;
                    hidden = false;
                    navWrap.classList.remove('nav-hidden');
                };
            }

            // Mobile drawer
            const menuBtn = document.getElementById('mobileMenuBtn');
            const drawer = document.getElementById('mobileDrawer');
            const closeBtn = document.getElementById('mobileDrawerCloseBtn');

            // Opening also hands the drawer to the modal engine, which locks
            // the page behind it, traps Tab inside the panel, closes it on
            // Escape or a backdrop click, and returns focus to the burger
            // button on the way out. Before this, none of that happened: Tab
            // walked straight into the page underneath and the body kept
            // scrolling behind the open drawer.
            function toggleDrawer(open) {
                if (!drawer) return;
                drawer.classList.toggle('drawer-open', open);
                drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
                // The burger is a disclosure control, so its state has to be
                // announced. Without aria-expanded a screen reader reads the
                // same "Open navigation menu" whether the drawer is open or
                // shut, and the only way to find out is to activate it and
                // listen for what changes. aria-hidden on the drawer above is
                // not a substitute: it hides the panel, it says nothing about
                // the button.
                menuBtn && menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                menuBtn && menuBtn.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
                // Bring the header back before the drawer covers it, so closing
                // never returns you to a page with no visible nav.
                if (open) window.fhRevealNav && window.fhRevealNav();
                if (open) window.openOverlay && window.openOverlay(drawer, () => toggleDrawer(false));
                else window.closeOverlay && window.closeOverlay(drawer);
            }
            menuBtn && menuBtn.addEventListener('click', () => toggleDrawer(true));
            closeBtn && closeBtn.addEventListener('click', () => toggleDrawer(false));
            drawer && drawer.addEventListener('click', (e) => { if (e.target === drawer) toggleDrawer(false); });
            ['mobileRoomsLink', 'mobileGalleryLink', 'mobileContactLink'].forEach(id => {
                const el = document.getElementById(id);
                el && el.addEventListener('click', () => toggleDrawer(false));
            });

            // Field Notes (decorative — friendly acknowledgement, no backend).
            // Uses window.toast from resources/js/app.js rather than SweetAlert:
            // this handler lives in the layout, so depending on swal here would
            // have forced the 40 KB library onto every public page for a form
            // in the footer. toast() ships in the app bundle already.
            const fieldNotes = document.getElementById('fieldNotesForm');
            fieldNotes && fieldNotes.addEventListener('submit', function(e) {
                e.preventDefault();
                window.toast && window.toast('You\'re on the list. Field Notes arrives with the next harvest.');
                this.reset();
            });
        });
    </script>

    <!-- Scroll reveal (local AOS replacement — see public/js/reveal.js) -->
    <script src="{{ asset('js/reveal.js') }}?v={{ filemtime(public_path('js/reveal.js')) }}" defer></script>

    {{-- Lenis inertia scroll used to load here, desktop-only (fine pointer,
         ≥1024px), for a weighted "cinematic" scroll feel.

         It was removed because it was the reason desktop scrolling felt laggy
         while mobile — which never loaded it — felt smooth. Lenis takes scroll
         off the browser's compositor thread and drives the scroll position
         from JS on the main thread every frame, so any main-thread work lands
         directly in the scroll's critical path. Native scrolling is immune to
         that: it keeps moving on the compositor even when the main thread is
         busy. Public testers reported exactly that split, on exactly the
         viewports Lenis was gated to.

         Anchor scrolling is now native: <html> keeps its `scroll-smooth`
         class (Lenis had to strip it), so same-page # links animate on their
         own with no click handler, and the #rooms / #gallery targets already
         carry `scroll-mt-28` to clear the fixed nav — which is what Lenis's
         hardcoded `offset: -96` was doing by hand. --}}
    <script>
        // In-page CTAs (hero + cta partials) call this. Native smooth scroll;
        // the nav offset comes from the target's own scroll-margin, and
        // browsers honour prefers-reduced-motion here automatically.
        window.smoothScrollTo = function (el) {
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
    </script>

</body>
</html>
