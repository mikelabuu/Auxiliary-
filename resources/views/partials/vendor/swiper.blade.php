{{-- Swiper. Only the landing page's testimonial deck uses it.
     @once so two consumers on one page still emit a single copy.

     Loaded on approach rather than up front: 151 KB of JS plus an 18 KB
     stylesheet — which was the last render-blocking vendor sheet on the
     landing, with no media="print" swap — for one carousel roughly ten
     screens down. home.js waits for `fh:swiper-ready` before constructing it.

     The deck's markup renders and reads fine as a plain stack of quotes until
     Swiper arrives, so nothing is missing in the gap. --}}
@once
    @include('partials.vendor.lazy-loader')
    <script>
        window.fhLazyVendor({
            name: 'swiper',
            watch: '.swiper',
            css: ['{{ asset('vendor/swiper/swiper-bundle.min.css') }}'],
            js: ['{{ asset('vendor/swiper/swiper-bundle.min.js') }}'],
            event: 'fh:swiper-ready',
        });
    </script>
@endonce
