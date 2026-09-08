@extends('layouts.public.base')
@section('title', 'Farmers Hostel · Boutique Stay Inside CLSU Campus')
{{-- Light CLSU redesign on light theme-boutique tokens (ivory canvas, warm ink,
     green + palay-gold accents). nav_dark: the hero is now a full-bleed photo
     panel, so the nav rides transparent/white over it and swaps to the solid
     light skin on scroll. --}}
@section('nav_dark', '1')

@section('content')

    {{-- Cinematic intro splash — disabled 2026-08-13.

         To bring it back, uncomment the line below. The partial, its styles
         (public/09-intro-splash.css) and the exit choreography are all intact
         and untouched; this is the only line that switches it on or off.

         Why it is off: it held the page for ~1.15s of dwell plus a ~0.85s
         curtain lift, with `overflow: hidden` on <html> and <body> for the
         whole of it, so nothing could be scrolled or tapped for roughly two
         seconds. On the hosted site the landing is now interactive at ~800ms
         (332ms of that is TTFB), which made the splash comfortably the single
         largest delay on a first visit — it was holding a page that had
         already finished loading. It had also been reported as "heavy" twice.

         It was already skipped below 768px, for reduced-motion, on save-data
         and on repeat visits in the same tab, so it only ever played for
         first-time desktop visitors. --}}
    {{-- @include('public.home.partials.intro') --}}

    <!-- Site-wide film grain: fixed, non-interactive, breaks digital flatness.
         Sits above content but below the nav (z-50) and overlays. -->
    <div class="film-grain pointer-events-none fixed inset-0 z-[45]" aria-hidden="true"></div>

    @include('public.home.partials.hero')
    @include('public.home.partials.stats')
    @include('public.home.partials.story')
    @include('public.home.partials.rooms')
    @include('public.home.partials.testimonials')
    @include('public.home.partials.gallery')
    @include('public.home.partials.cta')
    @include('public.home.partials.room-modal')

    {{-- Preserve dependency order without blocking HTML parsing. The checkout
         engine belongs only on checkout; home uses availability-search.js. --}}
    <script src="{{ \App\Support\PublicScript::url('js/availability-search.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/room-filters.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/home.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/parallax.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/scroll-effects.js') }}" defer></script>
@endsection
