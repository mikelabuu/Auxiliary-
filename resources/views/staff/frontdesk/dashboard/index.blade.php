@extends('layouts.frontdesk')

@section('title', 'Front Desk · Dashboard')
@section('content')

{{-- Header actions: open the insights + calendar modals --}}
<div class="flex items-center justify-end gap-2">
    <x-admin.ui.button variant="secondary" type="button" id="openInsightsBtn">
        <x-admin.ui.icon name="chart-bar" class="w-4 h-4" />
        Booking insights
    </x-admin.ui.button>
    <x-admin.ui.button variant="secondary" type="button" id="openCalendarBtn">
        <x-admin.ui.icon name="calendar" class="w-4 h-4" />
        Calendar
    </x-admin.ui.button>
</div>


{{-- KPI row: the desk's day at a glance --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
    <x-admin.ui.stat-card icon="arrival" label="Arrivals Today" delay="0">
        {{ $arrivalsToday }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="departure" color="palay" label="Departures Today" delay="40">
        {{ $departuresToday }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="users" label="In-House Stays" delay="80">
        {{ $inHouse }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="bed" label="Available Tonight" delay="120">
        {{ $availableTonight }}
        <x-slot:footnote><p class="mt-1 text-xs text-faint">of {{ $totalRooms }} rooms</p></x-slot:footnote>
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="credit-card" dark badge="TODAY" label="Collected" delay="160" class="col-span-2 lg:col-span-1">
        ₱{{ number_format($collectedToday, 2) }}
    </x-admin.ui.stat-card>
</div>

{{-- Quick actions --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-admin.ui.quick-action icon="calendar-plus" title="New walk-in booking" subtitle="Create a manual booking" :href="route('frontdesk.walkin.create')" />
    <x-admin.ui.quick-action icon="bed" title="Room board" subtitle="Status, rates and occupancy" :href="route('frontdesk.room.index')" />
    <x-admin.ui.quick-action icon="clipboard" title="Find a booking" subtitle="Search, view and check out" :href="route('frontdesk.booking')" />
</div>

{{-- Arrivals & departures + occupancy (shared Livewire components) --}}
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <livewire:dashboard.arrivals-departures />
    </div>
    <div class="lg:col-span-1">
        <livewire:dashboard.occupancy-snapshot />
    </div>
</div>

{{-- Booking insights + calendar are header-button modals (shared partials) --}}
@include('partials.dashboard.insights-modal')
@include('partials.dashboard.calendar-modal')

@endsection

@push('scripts')
<script>
// Real-time push: this dashboard is built from Livewire panels, so a broadcast
// refreshes those in place rather than reloading the page (the panels keep
// their own wire:poll as the fallback when Reverb is down). Mirrors the
// listener on the admin dashboard.
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Echo) return;

    let timer = null;
    function scheduleRefresh() {
        clearTimeout(timer);
        // One desk action emits several events; coalesce them into one refresh.
        timer = setTimeout(function () {
            if (!window.Livewire) return;
            Livewire.dispatch('refreshArrivalsDepartures');
            Livewire.dispatch('refreshOccupancy');
        }, 400);
    }

    window.Echo.channel('rooms').listen('.RoomStatusChanged', scheduleRefresh);
    window.Echo.channel('bookings').listen('.BookingChanged', scheduleRefresh);
});
</script>
@endpush
