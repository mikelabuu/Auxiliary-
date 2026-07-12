@extends('layouts.admin')

@section('title', 'Admin - Bookings Hub')
@section('page-title', 'Bookings Hub')

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
    <x-admin.ui.page-header subtitle="Arrivals, departures, and every active or pending booking in one place.">
        Bookings <span class="text-clsu-700">Hub</span>
        <x-slot:actions>
            <x-admin.ui.button variant="primary" :href="route('staff.manualbooking')">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New Booking
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

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
        }
    });
});
</script>
@endpush
