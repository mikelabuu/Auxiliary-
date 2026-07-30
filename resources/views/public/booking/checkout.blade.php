@extends('layouts.public.base')
@section('title', 'Checkout | Farmers Hostel')
{{-- Cream Boutique, not Night Estate: checkout now matches the discount and
     payment pages either side of it, so the booking journey reads as one
     place instead of dipping into a dark room in the middle. The night aura
     and film grain that dressed the dark canvas came out with it. --}}
@section('content')

    <div class="min-h-screen bg-canvas pt-28 pb-24 relative isolate overflow-x-clip">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="co-enter mb-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4" style="--co:0">
                <div>
                    <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-palay-800 mb-3">
                        <span class="h-px w-8 bg-gold/50"></span> Almost there
                    </span>
                    <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Complete your <span class="italic text-gold">booking</span></h1>
                    <p class="text-sm font-medium text-stone-600 mt-3">Fill in your details to secure your reservation. No payment needed yet.</p>
                </div>
                <a href="{{ route('home') }}#rooms" class="gold-underline self-start sm:self-end text-[11px] font-bold uppercase tracking-[0.3em] text-stone-600 hover:text-emerald-deep transition-colors">
                    &larr; Back to Rooms
                </a>
            </div>

            <!-- Live progress rail — booking.js toggles .done per step; each
                 step doubles as a jump-link to its card (same deep-link
                 contract as the summary rows) -->
            <ol id="checkoutProgress" class="co-enter mb-8 grid grid-cols-3 gap-3" style="--co:1">
                @foreach (['dates' => 'Dates', 'details' => 'Your details', 'rooms' => 'Rooms'] as $step => $label)
                    <li data-progress-step="{{ $step }}" class="checkout-step cursor-pointer" role="button" tabindex="0" aria-label="Jump to {{ strtolower($label) }}">
                        <div class="flex items-center gap-2.5">
                            <span class="step-dot grid h-7 w-7 shrink-0 place-items-center rounded-full border border-emerald-deep/20 bg-white/60 text-[11px] font-bold text-stone-500 transition-[color,background-color,border-color,box-shadow] duration-200">
                                <span class="step-num">{{ $loop->iteration }}</span>
                                <svg class="step-check hidden h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span class="hidden text-[11px] font-bold uppercase tracking-[0.18em] text-stone-600 sm:block">{{ $label }}</span>
                        </div>
                        {{-- The un-done track needs to be DARKER than the page,
                             not lighter — white-on-cream is invisible. --}}
                        <span class="step-bar mt-2.5 block h-1 rounded-full bg-emerald-deep/25 transition-colors duration-300"></span>
                    </li>
                @endforeach
            </ol>

            <x-booking.ui.error-list class="mb-6" />
            {{-- animate-pop replays each time booking.js un-hides this (display swap restarts the keyframes) --}}
            <div id="bookingFormAlert" role="alert" class="animate-pop mb-6 p-4 bg-ember-600/15 text-ember-700 border border-ember-600/40 rounded-2xl text-sm font-semibold d-none"></div>

            <form id="bookingForm" method="POST" action="{{ route('booking.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                @csrf

                <!-- Hidden aggregate values needed for backend forms -->
                <input type="hidden" name="room_numbers" id="selected_room_number">
                <input type="hidden" name="num_seniors" id="num_seniors" value="0">
                <input type="hidden" name="check_in" id="check_in_hidden">
                <input type="hidden" name="check_out" id="check_out_hidden">

                <!-- Left Column: Guest Details & Config -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- DATES -->
                    <x-booking.checkout.step-card icon="event" step="Step 1 of 3" title="Stay Dates" id="stepCardDates" class="co-enter scroll-mt-28" style="--co:2">
                        <x-slot:aside>
                            <span id="nights_duration_badge" class="hidden px-3.5 py-1.5 rounded-full bg-gold/15 border border-gold/40 text-stone-700 text-[11px] font-bold uppercase tracking-[0.14em] animate-pop whitespace-nowrap"></span>
                        </x-slot:aside>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Check-in</label>
                                <input type="text" id="check_in" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none font-semibold cursor-pointer transition-[color,background-color,border-color,box-shadow]" placeholder="Select Date" value="{{ $checkIn ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Check-out</label>
                                <input type="text" id="check_out" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none font-semibold cursor-pointer transition-[color,background-color,border-color,box-shadow]" placeholder="Select Date" value="{{ $checkOut ?? '' }}">
                            </div>
                        </div>
                    </x-booking.checkout.step-card>

                    <!-- GUEST INFO -->
                    <x-booking.checkout.step-card icon="person" step="Step 2 of 3" title="Personal Information" id="stepCardDetails" class="co-enter scroll-mt-28" style="--co:3">
                        @include('public.booking.partials.step-guest')
                    </x-booking.checkout.step-card>

                    <!-- ROOM SELECTION -->
                    <x-booking.checkout.step-card icon="meeting_room" step="Step 3 of 3" title="Room Allocation" id="stepCardRooms" class="co-enter scroll-mt-28" style="--co:4">
                        <x-slot:aside>
                            <button type="button" onclick="window.addReservationBlock()" class="press inline-flex items-center gap-1.5 rounded-full border border-emerald-deep/15 bg-white/60 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-ink transition-colors hover:bg-emerald-deep hover:text-cream cursor-pointer">
                                <span class="material-icons text-[15px]">add</span> Add Room Type
                            </button>
                        </x-slot:aside>
                        <p class="text-sm text-stone-600 font-medium mb-4">Configure the rooms you want to book. You must select specific room numbers for each type.</p>

                        <div id="reservationBlocks" class="space-y-4">
                            <!-- JS will inject blocks here -->
                        </div>
                    </x-booking.checkout.step-card>

                </div>

                <!-- Right Column: Sticky Summary -->
                <div class="lg:col-span-4">
                    <div class="co-enter bg-cream-warm rounded-3xl p-6 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sticky top-28" style="--co:3">
                        <h3 class="text-xl text-ink border-b border-emerald-deep/10 pb-4 mb-4 font-display flex items-center gap-2.5">
                            <span class="material-icons text-palay-800 text-[20px]">receipt_long</span>
                            Booking <span class="italic text-palay-800 -ml-1">Summary</span>
                        </h3>

                        <!-- Summary Invoice will be rendered here by JS -->
                        {{-- Initial markup mirrors booking.js's empty state exactly,
                             so the JS takeover on load is invisible --}}
                        <div id="summaryInvoice" class="space-y-4 mb-6 text-sm font-medium text-stone-600">
                            <div class="text-center py-10 text-stone-500">
                                <span class="material-icons text-5xl mb-3 block text-emerald-deep/25">event</span>
                                <p class="font-semibold">Please select your stay dates.</p>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitBooking" class="press focus-ring w-full min-h-12 py-4 rounded-full text-[12px] font-semibold uppercase tracking-[0.2em] bg-emerald-deep text-cream cursor-pointer flex items-center justify-center gap-2 hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)] disabled:opacity-70 disabled:pointer-events-none">
                            <span class="material-icons text-[18px]">check_circle</span>
                            Confirm Booking
                        </button>
                        <div class="mt-4 grid grid-cols-3 gap-1 text-center">
                            <div class="text-[9px] font-bold text-stone-500 uppercase tracking-wider flex flex-col items-center gap-1">
                                <span class="material-icons text-[16px] text-palay-800">lock</span> Secure
                            </div>
                            <div class="text-[9px] font-bold text-stone-500 uppercase tracking-wider flex flex-col items-center gap-1">
                                <span class="material-icons text-[16px] text-palay-800">credit_card_off</span> No prepayment
                            </div>
                            <div class="text-[9px] font-bold text-stone-500 uppercase tracking-wider flex flex-col items-center gap-1">
                                <span class="material-icons text-[16px] text-palay-800">verified</span> Instant hold
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile sticky total bar (summary column is off-screen on phones) -->
    <div class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-cream-warm/95 backdrop-blur-xl border-t border-emerald-deep/15 px-4 py-3 shadow-[0_-16px_40px_-20px_rgba(6,40,30,0.35)] flex items-center justify-between gap-3">
        <div>
            <p class="text-[9px] font-bold text-stone-500 uppercase tracking-[0.28em] leading-none">Total due</p>
            {{-- "—" until a real total exists; ₱0 due would be a false statement --}}
            <p id="mobileTotalAmount" class="font-display text-xl text-ink tabnum mt-1">—</p>
            <p id="mobileMetaLine" class="text-[10px] font-semibold text-stone-500 mt-0.5"></p>
        </div>
        <button type="submit" form="bookingForm" id="btnSubmitBookingMobile" class="press min-h-11 px-6 py-2.5 rounded-full text-cream text-[12px] font-semibold uppercase tracking-[0.18em] cursor-pointer bg-emerald-deep hover:bg-emerald flex items-center gap-1.5 disabled:opacity-70 disabled:pointer-events-none">
            <span class="material-icons text-[16px]">check_circle</span>
            Confirm
        </button>
    </div>

    <!-- Template for Room Blocks -->
    <template id="reservationBlockTemplate">
        @include('public.booking.partials.reservation-block')
    </template>

@endsection

@push('scripts')
{{-- booking.js is vanilla JS — no jQuery dependency --}}
<script>
    // Make PHP variables available to JS
    window.INITIAL_ROOM_TYPE = "{{ $selectedRoomType ? $selectedRoomType['id'] : '' }}";
    window.INITIAL_GUESTS = "{{ $guests ?? 1 }}";
    window.ROOM_TYPES_CONFIG = @json($roomTypes);
</script>
<script src="{{ asset('js/booking.js') }}?v={{ filemtime(public_path('js/booking.js')) }}"></script>

@if ($errors->any() || session('error'))
<script>
    // Server-side rejections pop up too, not just the client-side checks.
    // "The following rooms are already booked: 102" comes back from the
    // double-booking guard in BookingController::store() after a redirect, and
    // used to land in a banner above a very long form that the guest was
    // already scrolled past. DOMContentLoaded because window.toast is defined
    // by app.js, which is a deferred Vite module.
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.toast !== 'function') return;
        @foreach ($errors->all() as $error)
            window.toast(@json($error), 'error');
        @endforeach
        @if (session('error'))
            window.toast(@json(session('error')), 'error');
        @endif
    });
</script>
@endif
@endpush
