{{-- Lightbox2. Ships its own bundled jQuery, which is the only jQuery the
     public site loads at all - nothing here calls $ directly.
     lightbox.min.css resolves its controls as url(../images/...), so css/ and
     images/ must stay siblings under public/vendor (see sync-vendor.mjs). --}}
{{-- Loaded on approach. Every rule in the sheet targets #lightbox /
     #lightboxOverlay, and lightbox2 only appends those once a tile is clicked,
     so neither the sheet nor the 98 KB script has anything to do until the
     guest reaches the gallery — which on the landing is ~14 screens down.

     Late loading is safe because lightbox2 binds a *delegated* click handler
     on <body> rather than binding each anchor, so anchors that already exist
     are picked up the moment it initialises. The observer's 600px margin fires
     well before the tiles are tappable; until then a tap just opens the image
     directly, which is the correct no-JS fallback anyway. --}}
@once
    @include('partials.vendor.lazy-loader')
    <script>
        window.fhLazyVendor({
            name: 'lightbox',
            watch: '[data-lightbox]',
            css: ['{{ asset('vendor/lightbox2/css/lightbox.min.css') }}'],
            js: ['{{ asset('vendor/lightbox2/js/lightbox-plus-jquery.min.js') }}'],
            event: 'fh:lightbox-ready',
        });
    </script>
@endonce
