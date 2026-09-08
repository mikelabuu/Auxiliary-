@extends('layouts.admin')

@section('title', 'Admin - Booking Operations')
@section('page-title', 'Booking Operations')
@section('body-class', 'admin-refined')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>

<div class="space-y-6 max-w-[1680px] mx-auto">
    <header class="booking-page-header">
        <div class="booking-page-header__top">
            <div>
                <h1 class="booking-page-title">Booking Operations</h1>
                <p class="booking-page-description">Arrivals, departures and reservations, in one place.</p>
            </div>
            <a href="{{ route('staff.manualbooking') }}" class="btn btn-primary">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New booking
            </a>
        </div>
        <div class="booking-page-overview" aria-label="Booking overview">
            <livewire:dashboard.booking-ops-stats />
        </div>
    </header>

    <x-admin.ui.section-nav :items="[
        ['id' => 'arrivals', 'label' => 'Arrivals & Departures', 'icon' => 'arrival'],
        ['id' => 'active-stays', 'label' => 'Active Stays', 'icon' => 'log-in'],
        ['id' => 'all-bookings', 'label' => 'All Bookings', 'icon' => 'clipboard'],
    ]" />

    <div id="arrivals" class="scroll-mt-32">
        <livewire:dashboard.arrivals-departures />
    </div>

    <div id="active-stays" class="scroll-mt-32">
        <livewire:active-bookings />
    </div>

    <div id="all-bookings" class="scroll-mt-32">
        <livewire:bookings-table />
    </div>
</div>
@endsection

@push('scripts')
<script>
// Real-time push: refresh the booking panels the instant a booking changes
// anywhere (check-in/out, no-show, cancellation, new booking). The panels also
// wire:poll as a fallback if the socket is unavailable.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Echo) return;
    window.Echo.channel('bookings').listen('.BookingChanged', function () {
        if (window.Livewire) {
            Livewire.dispatch('refreshBookingsTable');
            Livewire.dispatch('refreshActiveBookings');
            Livewire.dispatch('refreshArrivalsDepartures');
            Livewire.dispatch('refreshBookingOpsStats');
        }
    });
});
</script>
@endpush
