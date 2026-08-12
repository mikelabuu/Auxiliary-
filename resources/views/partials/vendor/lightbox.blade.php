{{-- Lightbox2. Ships its own bundled jQuery, which is the only jQuery the
     public site loads at all - nothing here calls $ directly.
     lightbox.min.css resolves its controls as url(../images/...), so css/ and
     images/ must stay siblings under public/vendor (see sync-vendor.mjs). --}}
{{-- Non-blocking for the same reason as flatpickr: every rule in this sheet
     targets #lightbox / #lightboxOverlay, which lightbox2 only appends once a
     gallery tile is clicked. Blocking first paint on it bought nothing. --}}
@once
    <link rel="stylesheet" href="{{ asset('vendor/lightbox2/css/lightbox.min.css') }}"
          media="print" onload="this.media='all'; this.onload=null;">
    <noscript><link rel="stylesheet" href="{{ asset('vendor/lightbox2/css/lightbox.min.css') }}"></noscript>
    <script src="{{ asset('vendor/lightbox2/js/lightbox-plus-jquery.min.js') }}" defer></script>
@endonce
