{{-- Flatpickr. Needed by the landing page's availability capsule
     (availability-search.js) and the checkout date fields (booking.js).
     Both initialise on DOMContentLoaded, so defer is safe. --}}
{{-- The sheet styles .flatpickr-calendar, which does not exist in the document
     until the guest focuses a date field — yet it was blocking first paint on
     every page that carries one. Same non-blocking swap the icon sheet uses in
     layouts/public/base: media="print" keeps it off the critical path and the
     onload hands it back. The calendar cannot open before onload has fired. --}}
@once
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}"
          media="print" onload="this.media='all'; this.onload=null;">
    <noscript><link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}"></noscript>
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}" defer></script>
@endonce
