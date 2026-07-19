@extends('layouts.admin')

@section('title', 'Admin - Booking Operations')
@section('page-title', 'Booking Operations')

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
    <x-admin.ui.ops-header
        subtitle="Manage, monitor, and verify every booking from reservation to check-out. Arrivals, departures, and active stays in one live view.">
        Booking Operations
        <x-slot:pills>
            <livewire:dashboard.booking-ops-stats />
        </x-slot:pills>
        <x-slot:aside>
            <p class="ops-aside-label">Live View</p>
            <p class="ops-aside-value">Real-time</p>
            <p class="ops-aside-meta">All bookings included</p>
            <div class="ops-aside-divider"></div>
            <p class="ops-aside-foot"><span class="live-dot"></span> Auto-refresh · 15s</p>
            <a href="{{ route('staff.manualbooking') }}" class="btn btn-center !no-underline mt-3" style="width:100%;background:#fff;color:var(--color-g-800);box-shadow:0 2px 8px rgba(5,46,28,.18);">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New Booking
            </a>
        </x-slot:aside>
    </x-admin.ui.ops-header>

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
