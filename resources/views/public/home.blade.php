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

    {{-- No jQuery needed: these are vanilla, and lightbox2 ships with its own
         bundled copy. home.js must come after booking.js/availability-search.js
         (it calls into their window hooks).

         All six are `defer`. The first four used to be plain <script> tags,
         which are parser-blocking: the browser stopped building the DOM at this
         point in <main> and downloaded + executed ~105 KB before it would even
         look at the footer. Every one of them wraps its whole body in a
         DOMContentLoaded handler, so none of them needed to run during parse —
         they were blocking the parser to register a callback.

         `defer` keeps them in document order (that is part of the spec, not an
         accident) and runs them after parsing but before DOMContentLoaded
         fires, so the handlers are still registered in time and home.js still
         sees the window hooks the two before it install. --}}
    <script src="{{ \App\Support\PublicScript::url('js/booking.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/availability-search.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/room-filters.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/home.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/parallax.js') }}" defer></script>
    <script src="{{ \App\Support\PublicScript::url('js/scroll-effects.js') }}" defer></script>
@endsection
