{{-- Flatpickr. Needed by the landing page's availability capsule
     (availability-search.js) and the checkout date fields (booking.js).
     Both initialise on DOMContentLoaded, so defer is safe. --}}
@once
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}" defer></script>
@endonce
