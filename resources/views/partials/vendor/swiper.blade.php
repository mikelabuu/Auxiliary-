{{-- Swiper. Only the landing page's testimonial deck uses it.
     @once so two consumers on one page still emit a single copy. --}}
@once
    <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
    <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}" defer></script>
@endonce
