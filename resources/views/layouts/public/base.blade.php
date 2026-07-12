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
    <script>document.documentElement.classList.add('js-reveal');</script>

    <!-- Tailwind & Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Early connections for every CDN origin used below -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <!-- Google Fonts — Lora (editorial serif) + Nunito Sans (body, upright only) -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Nunito+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Material Icons (used by booking flow internals) -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- SweetAlert (deferred — only called from user-event handlers) -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js" defer></script>

    <!-- LightBox for Gallery (deferred — activates on gallery clicks) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox-plus-jquery.min.js" defer></script>

    <!-- Flatpickr Datepicker (deferred — initialised on DOMContentLoaded) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

    <!-- Swiper.js (deferred — initialised on DOMContentLoaded) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    @livewireStyles
    @stack('styles')
</head>
@php
    $navDark = trim($__env->yieldContent('nav_dark')) !== '';
    $nightTheme = trim($__env->yieldContent('theme_night')) !== '';
@endphp
{{-- overflow-x-clip guards against pre-reveal translateX states (fade-left/right) widening the page --}}
<body class="theme-boutique {{ $nightTheme ? 'theme-night' : '' }} antialiased font-sans bg-canvas text-ink flex flex-col min-h-screen overflow-x-clip selection:bg-gold-soft selection:text-ink">

    <!-- Reading-progress hairline (driven by --scroll-progress from parallax.js) -->
    <div class="scroll-progress" aria-hidden="true"></div>

    <!-- Floating Pill Nav (transparent over hero, glass after scroll; retreats
         while reading down and returns on the first upward scroll) -->
    <div id="navWrap" class="fixed inset-x-0 top-4 z-50 px-4 sm:top-6 sm:px-6">
        <nav id="siteNav" aria-label="Primary" data-dark="{{ $navDark ? '1' : '0' }}"
             class="mx-auto flex max-w-6xl items-center justify-between rounded-full border px-4 py-3 sm:px-6 transition-[background-color,border-color,box-shadow,color,backdrop-filter] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] {{ $navDark ? 'nav-glass-dark' : 'nav-glass-solid' }}">
            <!-- Logo -->
            <a href="{{ route('home') }}" aria-label="Farmers Hostel home" class="focus-ring flex min-w-0 items-center gap-3 rounded-full">
                <x-booking.ui.logo-mark class="h-9 w-9" />
                <span class="hidden truncate font-display text-lg tracking-tight sm:block">Farmers <span class="italic text-gold">Hostel</span></span>
            </a>

            <!-- Center Nav Links -->
            <div class="hidden items-center gap-8 md:flex">
                <a href="{{ route('home') }}" class="gold-underline focus-ring rounded text-[13px] font-medium uppercase tracking-[0.18em] {{ request()->routeIs('home') ? 'text-gold active' : '' }}">Home</a>
                <a href="{{ route('home') }}#rooms" class="gold-underline focus-ring rounded text-[13px] font-medium uppercase tracking-[0.18em]">Rooms</a>
                <a href="{{ route('home') }}#gallery" class="gold-underline focus-ring rounded text-[13px] font-medium uppercase tracking-[0.18em]">Gallery</a>
                @auth
                    <a href="{{ route('settings.bookings') }}" class="gold-underline focus-ring rounded text-[13px] font-medium uppercase tracking-[0.18em]">My Bookings</a>
                @endauth
            </div>

            <!-- Right Side Actions & User Menu -->
            <div class="flex items-center gap-3">
                <div class="hidden md:block">
                    @auth
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="press focus-ring flex items-center gap-2.5 rounded-full border border-current/15 py-1.5 pl-2 pr-3 cursor-pointer select-none transition-colors hover:border-gold/60">
                                <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-deep font-display text-sm italic text-cream">
                                    {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) }}
                                </span>
                                <span class="text-[12px] font-semibold uppercase tracking-[0.14em]">{{ $username ?? auth()->user()->username ?? 'Account' }}</span>
                                <x-booking.ui.icon name="chevron-right" class="h-3.5 w-3.5 transition-transform duration-200" ::class="open ? 'rotate-90' : ''" />
                            </button>

                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-3 w-60 overflow-hidden rounded-2xl border border-ink/10 bg-canvas py-2 text-ink shadow-[0_24px_60px_-20px_rgba(4,14,10,0.5)] z-50"
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
                        <a href="{{ route('login') }}" class="press focus-ring inline-flex items-center gap-2 rounded-full bg-emerald-deep px-5 py-2 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream transition-all hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_20%,transparent)]">
                            Sign in
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="focus-ring press grid h-11 w-11 shrink-0 place-items-center rounded-full border border-current/20 md:hidden cursor-pointer" aria-label="Open navigation menu">
                    <x-booking.ui.icon name="menu" class="h-4 w-4" />
                </button>
            </div>
        </nav>
    </div>

    <!-- Mobile Drawer Navigation -->
    <div id="mobileDrawer" class="fixed inset-0 z-[60] flex justify-end bg-ink/50 backdrop-blur-sm transition-opacity duration-300 hidden">
        <div class="bg-canvas w-80 h-full shadow-2xl p-7 flex flex-col justify-between border-l border-ink/10">
            <div>
                <div class="flex items-center justify-between pb-6 border-b border-ink/10">
                    <div class="flex items-center gap-2.5">
                        <x-booking.ui.logo-mark class="h-9 w-9" />
                        <span class="font-display text-base tracking-tight text-ink">Farmers <span class="italic text-gold">Hostel</span></span>
                    </div>
                    <button id="mobileDrawerCloseBtn" class="press grid h-9 w-9 place-items-center rounded-full text-ink/50 hover:bg-canvas-deep hover:text-ink transition-all cursor-pointer" aria-label="Close navigation menu">
                        <x-booking.ui.icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <nav class="mt-7 flex flex-col gap-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                        <x-booking.ui.icon name="home" class="h-4 w-4 text-emerald" /> Home
                    </a>
                    <a href="{{ route('home') }}#rooms" id="mobileRoomsLink" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                        <x-booking.ui.icon name="bed" class="h-4 w-4 text-emerald" /> Rooms
                    </a>
                    <a href="{{ route('home') }}#gallery" id="mobileGalleryLink" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                        <x-booking.ui.icon name="images" class="h-4 w-4 text-emerald" /> Gallery
                    </a>
                    <a href="#Footer" id="mobileContactLink" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                        <x-booking.ui.icon name="mail" class="h-4 w-4 text-emerald" /> Contact
                    </a>
                    @auth
                        <div class="my-2 h-px bg-ink/10"></div>
                        <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                            <x-booking.ui.icon name="user" class="h-4 w-4 text-emerald" /> My Profile
                        </a>
                        <a href="{{ route('settings.bookings') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                            <x-booking.ui.icon name="book-open" class="h-4 w-4 text-emerald" /> My Bookings
                        </a>
                        <a href="{{ route('settings.transactions') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold uppercase tracking-[0.16em] text-ink/80 hover:bg-canvas-deep hover:text-ink transition-all">
                            <x-booking.ui.icon name="credit-card" class="h-4 w-4 text-emerald" /> Transactions
                        </a>
                    @endauth
                </nav>
            </div>

            <div>
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="press flex w-full items-center justify-center gap-2 rounded-full bg-ember-50 py-3 px-4 text-[12px] font-bold uppercase tracking-[0.16em] text-ember-700 hover:bg-ember-100 transition-all cursor-pointer">
                            <x-booking.ui.icon name="log-out" class="h-4 w-4" /> Log Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="press flex w-full items-center justify-center gap-2 rounded-full bg-emerald-deep py-3.5 px-4 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream hover:bg-emerald transition-all">
                        Sign in / Register
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Main Content wrapper -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Editorial Footer (Night Estate; shared across cream and night pages) -->
    <footer class="relative overflow-hidden border-t border-white/10 bg-night text-bone/85 mt-auto" id="Footer">
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

    <!-- Gradual blur — content softly blurs as it exits the bottom of the viewport -->
    <div class="gradual-blur" aria-hidden="true">
        <div></div><div></div><div></div><div></div><div></div><div></div>
    </div>

    @livewireScripts
    @stack('scripts')

    <!-- Nav, drawer & footer behaviours -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll-aware nav skin: transparent over the hero, glass once
            // scrolled (what "glass" looks like is decided per-theme in CSS)
            const nav = document.getElementById('siteNav');
            if (nav && nav.dataset.dark === '1') {
                let overHero = true;
                const applyNavState = () => {
                    const wantOver = window.scrollY < 80;
                    if (wantOver === overHero) return;
                    overHero = wantOver;
                    nav.classList.toggle('nav-glass-dark', wantOver);
                    nav.classList.toggle('nav-glass-solid', !wantOver);
                };
                applyNavState();
                window.addEventListener('scroll', applyNavState, { passive: true });
            }

            // Nav retreats while reading down, returns on the first upward
            // scroll. Large single-frame jumps (anchor landings, page
            // restores) are ignored so arriving at /#rooms keeps the nav.
            const navWrap = document.getElementById('navWrap');
            if (navWrap) {
                let lastY = window.scrollY;
                window.addEventListener('scroll', () => {
                    const y = window.scrollY;
                    const delta = y - lastY;
                    lastY = y;
                    if (Math.abs(delta) < 8 || Math.abs(delta) > 320) return;
                    navWrap.classList.toggle('nav-hidden', delta > 0 && y > 420);
                }, { passive: true });
            }

            // Mobile drawer
            const menuBtn = document.getElementById('mobileMenuBtn');
            const drawer = document.getElementById('mobileDrawer');
            const closeBtn = document.getElementById('mobileDrawerCloseBtn');

            function toggleDrawer(open) {
                drawer && drawer.classList.toggle('hidden', !open);
            }
            menuBtn && menuBtn.addEventListener('click', () => toggleDrawer(true));
            closeBtn && closeBtn.addEventListener('click', () => toggleDrawer(false));
            drawer && drawer.addEventListener('click', (e) => { if (e.target === drawer) toggleDrawer(false); });
            ['mobileRoomsLink', 'mobileGalleryLink', 'mobileContactLink'].forEach(id => {
                const el = document.getElementById(id);
                el && el.addEventListener('click', () => toggleDrawer(false));
            });

            // Field Notes (decorative — friendly acknowledgement, no backend)
            const fieldNotes = document.getElementById('fieldNotesForm');
            fieldNotes && fieldNotes.addEventListener('submit', function(e) {
                e.preventDefault();
                if (typeof swal !== 'undefined') {
                    swal('You\'re on the list', 'Field Notes will arrive with the next harvest.', 'success');
                }
                this.reset();
            });
        });
    </script>

    <!-- Scroll reveal (local AOS replacement — see public/js/reveal.js) -->
    <script src="{{ asset('js/reveal.js') }}?v={{ filemtime(public_path('js/reveal.js')) }}" defer></script>

    <!-- Lenis inertia scroll — the weighted, cinematic scroll feel. Desktop
         fine-pointer only; reduced-motion users keep native scrolling. -->
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js" defer></script>
    <script>
        // Scrolls to an element through Lenis when it's running, otherwise
        // falls back to native smooth scroll. Used by in-page CTAs.
        window.smoothScrollTo = function (el) {
            if (!el) return;
            if (window.__lenis) window.__lenis.scrollTo(el, { offset: -96 });
            else el.scrollIntoView({ behavior: 'smooth' });
        };

        window.addEventListener('load', function () {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const fine = window.matchMedia('(pointer: fine)').matches;
            if (reduce || !fine || window.innerWidth < 1024 || typeof Lenis === 'undefined') return;

            // Lenis requires native scroll-behavior to stay auto
            document.documentElement.classList.remove('scroll-smooth');

            const lenis = new Lenis({
                duration: 1.15,
                easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            });
            window.__lenis = lenis;

            function raf(time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);

            // Same-page anchor links ride the inertia scroll
            document.addEventListener('click', function (e) {
                const a = e.target.closest('a[href*="#"]');
                if (!a || !a.hash) return;
                const url = new URL(a.href, location.href);
                if (url.origin !== location.origin || url.pathname !== location.pathname) return;
                const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
                if (!target) return;
                e.preventDefault();
                lenis.scrollTo(target, { offset: -96 });
                history.pushState(null, '', url.hash);
            });
        });
    </script>
</body>
</html>
