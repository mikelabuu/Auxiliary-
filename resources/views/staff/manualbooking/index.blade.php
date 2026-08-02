@extends('layouts.admin')

@section('title', 'Admin - Manual Booking')
@section('page-title', 'Manual Booking')

@section('content')
{{--
    The form body is shared with the front desk walk-in screen
    (staff/frontdesk/walkin/create) — see <x-staff.booking.form>. Only the
    admin chrome around it lives here.
--}}
<div class="space-y-6 max-w-[1680px] mx-auto">
    @php
        $upcomingReservationCount = $upcomingBookings->sum(fn ($b) => $b->reservations->count());
    @endphp

    <x-admin.ui.ops-header
        subtitle="Create a booking on behalf of a guest: walk-in, phone, or any offline channel. Payment is recorded as paid.">
        Manual Booking
        <x-slot:pills>
            <span class="ops-pill"><span class="ops-pill-dot"></span><span class="ops-pill-num">{{ $totalAvailableRooms }}</span> rooms available now</span>
            <span class="ops-pill"><span class="ops-pill-dot info"></span><span class="ops-pill-num">{{ $upcomingReservationCount }}</span> upcoming reservation{{ $upcomingReservationCount === 1 ? '' : 's' }}</span>
        </x-slot:pills>
        <x-slot:aside>
            <p class="ops-aside-label">Recorded as</p>
            <p class="ops-aside-value">Paid</p>
            <p class="ops-aside-meta">Manual payment channel</p>
            <div class="ops-aside-divider"></div>
            <a href="{{ route('staff.bookings.index') }}" class="btn btn-center !no-underline" style="width:100%;background:#fff;color:var(--color-g-800);box-shadow:0 2px 8px rgba(5,46,28,.18);">
                <x-admin.ui.icon name="receipt" class="w-4 h-4" />
                Booking Hub
            </a>
        </x-slot:aside>
    </x-admin.ui.ops-header>

    <x-staff.booking.upcoming-strip :bookings="$upcomingBookings" />

    {{-- Session success toasts fire from layouts/admin; error lists stay inline --}}
    @if($errors->any())
        <div class="animate-in rounded-2xl border border-ember-200 bg-ember-50 px-5 py-3.5 text-sm text-ember-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li class="flex items-start gap-1.5"><x-admin.ui.icon name="block" class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-staff.booking.form
        :action="route('staff.manualbooking.store')"
        :available-url="route('staff.manualbooking.available')"
        empty-board-message="No rooms found. Add rooms in Room Management first."
        sticky-top="lg:top-24" />
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
{{-- Behaviour: resources/js/pages/staff-booking-form.js (bundled via app.js) --}}
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
@endpush
