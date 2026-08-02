{{-- Lightbox2. Ships its own bundled jQuery, which is the only jQuery the
     public site loads at all - nothing here calls $ directly.
     lightbox.min.css resolves its controls as url(../images/...), so css/ and
     images/ must stay siblings under public/vendor (see sync-vendor.mjs). --}}
@once
    <link rel="stylesheet" href="{{ asset('vendor/lightbox2/css/lightbox.min.css') }}">
    <script src="{{ asset('vendor/lightbox2/js/lightbox-plus-jquery.min.js') }}" defer></script>
@endonce
