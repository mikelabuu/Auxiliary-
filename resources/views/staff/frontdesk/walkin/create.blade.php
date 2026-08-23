@extends('layouts.frontdesk')
@section('title', 'Front Desk · Manual Booking')

@section('content')
{{--
    The form body is shared with the admin Manual Booking screen
    (staff/manualbooking/index) — see <x-staff.booking.form>. Only the front
    desk chrome around it lives here.
--}}
<x-staff.booking.upcoming-strip :bookings="$upcomingBookings" />

<x-frontdesk.flash />

<x-staff.booking.form
    :action="route('frontdesk.walkin.store')"
    :available-url="route('frontdesk.available')"
    empty-board-message="No rooms found. Ask an admin to add rooms first."
    sticky-offset="1.5rem" />
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
{{-- Behaviour: resources/js/pages/staff-booking-form.js (bundled via app.js) --}}
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
@endpush
