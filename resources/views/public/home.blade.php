@extends('layouts.public.base')
@section('title', 'Farmers Hostel · Boutique Stay Inside CLSU Campus')
@section('nav_dark', '1')
@section('theme_night', '1')

@section('content')

    @include('public.home.partials.intro')

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
         (it calls into their window hooks). --}}
    <script src="{{ asset('js/booking.js') }}?v={{ filemtime(public_path('js/booking.js')) }}"></script>
    <script src="{{ asset('js/availability-search.js') }}?v={{ filemtime(public_path('js/availability-search.js')) }}"></script>
    <script src="{{ asset('js/room-filters.js') }}?v={{ filemtime(public_path('js/room-filters.js')) }}"></script>
    <script src="{{ asset('js/home.js') }}?v={{ filemtime(public_path('js/home.js')) }}"></script>
    <script src="{{ asset('js/parallax.js') }}?v={{ filemtime(public_path('js/parallax.js')) }}" defer></script>
@endsection
