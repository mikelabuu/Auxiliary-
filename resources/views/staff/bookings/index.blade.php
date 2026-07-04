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
    <x-admin.page-header subtitle="Arrivals, departures, and every active or pending booking in one place.">
        Bookings <span class="font-display italic font-medium text-clsu-800">Hub</span>
        <x-slot:actions>
            <a href="{{ route('staff.manualbooking') }}" class="flex items-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-4 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all !no-underline">
                <x-admin.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New Booking
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:dashboard.arrivals-departures />

    <livewire:active-bookings />

    <livewire:bookings-table />
</div>
@endsection
