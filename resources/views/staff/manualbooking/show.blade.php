@extends('layouts.admin')

@section('title', 'Admin - Manual Booking')
@section('page-title', 'Manual Booking')

@section('content')
@php
    $statusColorMap = [
        'paid' => 'clsu', 'active' => 'clsu', 'completed' => 'clsu',
        'pending_payment' => 'palay', 'pending_discount' => 'palay',
        'cancelled' => 'ember', 'expired' => 'ember', 'no_show' => 'ember',
    ];
    $badgeClassMap = [
        'clsu'  => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'palay' => 'bg-palay-100 text-palay-800 border-palay-200',
        'ember' => 'bg-ember-50 text-ember-700 border-ember-200',
    ];
    $color = $statusColorMap[$booking->status] ?? 'stone';
    $badgeClass = $badgeClassMap[$color] ?? 'bg-stone-100 text-stone-600 border-stone-200';
    $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));
    $payment = $booking->payments;
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header :subtitle="'Created via manual booking · ' . ucfirst($booking->payment_mode)">
        <x-slot:breadcrumb>
            <x-admin.ui.breadcrumb :items="[
                ['label' => 'Bookings', 'href' => route('staff.bookings.index')],
                ['label' => 'Booking #' . $booking->id],
            ]" />
        </x-slot:breadcrumb>
        Booking <span class="text-clsu-700">#{{ $booking->id }}</span>
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('staff.bookings.index')">
                <x-admin.ui.icon name="chevron-left" class="w-4 h-4" stroke-width="2" />
                Back to Bookings
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" type="button" onclick="window.print()">
                <x-admin.ui.icon name="printer" class="w-4 h-4" />
                Print
            </x-admin.ui.button>
            <x-admin.ui.button variant="primary" :href="route('staff.manualbooking')">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New Booking
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    {{-- Session success toasts fire from layouts/admin --}}

    <x-admin.ui.section-card icon="user" title="Guest &amp; Stay Information" :delay="40">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="user" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Guest Name</p>
                        <p class="text-sm font-semibold text-stone-800">{{ $booking->guest_name }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="bell" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Phone</p>
                        <p class="text-sm font-semibold text-stone-800">{{ $booking->guest_phone }}</p>
                        @if($booking->guest_phone_alt)
                            <p class="text-xs text-faint mt-0.5">Also {{ $booking->guest_phone_alt }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="map-pin" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Address</p>
                        <p class="text-sm font-semibold text-stone-800">{{ $booking->guest_address }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="users" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Expected Guests</p>
                        <p class="text-sm font-semibold text-stone-800">{{ $booking->expected_guests }} <span class="text-faint font-normal">· {{ $booking->num_seniors }} senior/PWD</span></p>
                    </div>
                </div>
            </div>
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="calendar" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Check-In &rarr; Check-Out</p>
                        <p class="text-sm font-semibold text-stone-800 font-data tabnum">{{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }} &rarr; {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</p>
                        <p class="text-xs text-faint mt-0.5">{{ $nights }} night{{ $nights === 1 ? '' : 's' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="tag" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Status</p>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-bold border {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $booking->status)) }}</span>
                    </div>
                </div>
                @if($payment)
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-stone-100 text-muted flex items-center justify-center shrink-0"><x-admin.ui.icon name="credit-card" class="w-4 h-4" /></div>
                    <div>
                        <p class="text-2xs font-bold text-faint tracking-widest uppercase">Payment Reference</p>
                        <p class="text-sm font-semibold text-stone-800 font-data tabnum">{{ $payment->reference_no }}</p>
                        <p class="text-xs text-faint mt-0.5">{{ ucfirst($payment->payment_type) }} · {{ ucfirst($payment->status) }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </x-admin.ui.section-card>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="receipt" label="Total Price" :delay="80">
            &#8369;{{ number_format($booking->total_price, 2) }}
        </x-admin.ui.stat-card>
        <x-admin.ui.stat-card icon="tag" label="Discount" :delay="100">
            &#8369;{{ number_format($booking->discount, 2) }}
        </x-admin.ui.stat-card>
        <x-admin.ui.stat-card icon="credit-card" label="Payable Amount" :delay="120">
            &#8369;{{ number_format($booking->payable_amount, 2) }}
        </x-admin.ui.stat-card>
    </div>

    <x-admin.ui.section-card icon="bed" title="Room Reservations" :subtitle="$booking->reservations->count() . ' room(s)'" :delay="160">
        <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Guests</th>
                        <th>Seniors/PWD</th>
                        <th class="text-right">Price/Night</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->reservations as $res)
                        <tr>
                            <td><span class="cell-tag font-data">{{ $res->room_number }}</span></td>
                            <td class="text-faint text-2xs font-bold uppercase tracking-wide">{{ ucfirst($res->room_type) }}</td>
                            <td class="font-data tabnum">{{ $res->num_guests }}</td>
                            <td class="font-data tabnum">{{ $res->num_seniors }}</td>
                            <td class="text-right font-data tabnum font-semibold">&#8369;{{ number_format($res->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.ui.section-card>
</div>
@endsection
