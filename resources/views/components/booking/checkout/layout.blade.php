@props([
    'title' => 'Checkout',
    'subtitle' => 'Complete your booking details to secure your reservation.',
    'backUrl' => null,
    'backLabel' => 'Back',
    'roomTypes' => [],
    'checkIn' => null,
    'checkOut' => null,
    'selectedRoomType' => null,
    'guests' => 1,
])

@php
    $backUrl = $backUrl ?? route('home');
    $selectedRoomTypeId = $selectedRoomType ? $selectedRoomType['id'] : '';
@endphp

<style>
    :root {
        --color-clsu-green: #0a4f2d;
        --color-clsu-green-light: #12663c;
        --color-clsu-gold: #d4af37;
    }
    .d-none { display: none !important; }
    .room-tiles {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 8px;
        margin-top: 12px;
    }
    .room-tile {
        padding: 10px 6px;
        text-align: center;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        user-select: none;
    }
    .room-tile:hover {
        border-color: #cbd5e1;
        background-color: #f1f5f9;
    }
    .room-tile.available {
        border-color: #bbf7d0;
        background-color: #f0fdf4;
        color: #15803d;
    }
    .room-tile.available:hover {
        background-color: #dcfce7;
        border-color: #86efac;
    }
    .room-tile.selected {
        background-color: var(--color-clsu-green) !important;
        color: #ffffff !important;
        border-color: var(--color-clsu-green) !important;
        box-shadow: 0 4px 12px rgba(10, 79, 45, 0.3);
    }
    .room-tile.booked {
        background-color: #fee2e2;
        color: #b91c1c;
        border-color: #fecaca;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .room-tile.cleaning {
        background-color: #fef3c7;
        color: #b45309;
        border-color: #fde68a;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .room-tile.maintenance {
        background-color: #f1f5f9;
        color: #64748b;
        border-color: #e2e8f0;
        cursor: not-allowed;
        opacity: 0.6;
        text-decoration: line-through;
    }
    .flatpickr-calendar {
        box-shadow: var(--shadow-md) !important;
        border: 1px solid var(--color-slate-100) !important;
        border-radius: 1rem !important;
    }
</style>

<div class="min-h-screen bg-slate-50 pt-28 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-booking.checkout.header :title="$title" :subtitle="$subtitle" :back-url="$backUrl" :back-label="$backLabel" />

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200/60 rounded-xl text-sm font-semibold">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div id="bookingFormAlert" class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200/60 rounded-xl text-sm font-semibold d-none"></div>

        <form
            id="bookingForm"
            method="POST"
            action="{{ route('booking.store') }}"
            class="grid grid-cols-1 lg:grid-cols-12 gap-8"
            data-initial-room-type="{{ $selectedRoomTypeId }}"
            data-initial-guests="{{ $guests }}"
        >
            @csrf

            <input type="hidden" name="room_numbers" id="selected_room_number">
            <input type="hidden" name="num_seniors" id="num_seniors" value="0">
            <input type="hidden" name="check_in" id="check_in_hidden">
            <input type="hidden" name="check_out" id="check_out_hidden">

            <div class="lg:col-span-8 space-y-6">
                <x-booking.checkout.dates :check-in="$checkIn" :check-out="$checkOut" />
                <x-booking.checkout.guest-details />
                <x-booking.checkout.room-allocation :room-types="$roomTypes" />
            </div>

            <div class="lg:col-span-4">
                <x-booking.checkout.summary />
            </div>
        </form>
    </div>
</div>

<template id="reservationBlockTemplate">
    <x-booking.checkout.reservation-block :room-types="$roomTypes" />
</template>

