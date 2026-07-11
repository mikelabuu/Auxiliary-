@extends('layouts.public.base')
@section('title', 'Checkout | Farmers Hostel')
@section('content')

    <div class="min-h-screen bg-canvas pt-28 pb-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald mb-3">
                        <span class="h-px w-8 bg-emerald/50"></span> Almost there
                    </span>
                    <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Complete your <span class="italic text-gold">booking</span></h1>
                    <p class="text-sm font-medium text-ink/55 mt-3">Fill in your details to secure your reservation — no payment needed yet.</p>
                </div>
                <a href="{{ route('home') }}#rooms" class="gold-underline self-start sm:self-end text-[11px] font-bold uppercase tracking-[0.3em] text-ink/70 hover:text-ink transition-colors">
                    &larr; Back to Rooms
                </a>
            </div>

            <!-- Live progress rail — booking.js toggles .done per step -->
            <ol id="checkoutProgress" class="mb-8 grid grid-cols-3 gap-3">
                <li data-progress-step="dates" class="checkout-step">
                    <div class="flex items-center gap-2.5">
                        <span class="step-dot grid h-7 w-7 shrink-0 place-items-center rounded-full border border-stone-300 bg-cream-warm text-[11px] font-bold text-stone-500 transition-all duration-200">
                            <span class="step-num">1</span>
                            <svg class="step-check hidden h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span class="hidden text-[11px] font-bold uppercase tracking-[0.18em] text-ink/70 sm:block">Dates</span>
                    </div>
                    <span class="step-bar mt-2.5 block h-1 rounded-full bg-emerald-deep/10 transition-colors duration-300"></span>
                </li>
                <li data-progress-step="details" class="checkout-step">
                    <div class="flex items-center gap-2.5">
                        <span class="step-dot grid h-7 w-7 shrink-0 place-items-center rounded-full border border-stone-300 bg-cream-warm text-[11px] font-bold text-stone-500 transition-all duration-200">
                            <span class="step-num">2</span>
                            <svg class="step-check hidden h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span class="hidden text-[11px] font-bold uppercase tracking-[0.18em] text-ink/70 sm:block">Your details</span>
                    </div>
                    <span class="step-bar mt-2.5 block h-1 rounded-full bg-emerald-deep/10 transition-colors duration-300"></span>
                </li>
                <li data-progress-step="rooms" class="checkout-step">
                    <div class="flex items-center gap-2.5">
                        <span class="step-dot grid h-7 w-7 shrink-0 place-items-center rounded-full border border-stone-300 bg-cream-warm text-[11px] font-bold text-stone-500 transition-all duration-200">
                            <span class="step-num">3</span>
                            <svg class="step-check hidden h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span class="hidden text-[11px] font-bold uppercase tracking-[0.18em] text-ink/70 sm:block">Rooms</span>
                    </div>
                    <span class="step-bar mt-2.5 block h-1 rounded-full bg-emerald-deep/10 transition-colors duration-300"></span>
                </li>
            </ol>

            <!-- Error feedback display -->
            @if ($errors->any())
                <div class="mb-6">
                    <x-booking.ui.alert type="danger">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-booking.ui.alert>
                </div>
            @endif
            <div id="bookingFormAlert" class="mb-6 p-4 bg-ember-50 text-ember-800 border border-ember-200 rounded-2xl text-sm font-semibold d-none"></div>

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
                    <div class="bg-cream-warm rounded-3xl p-6 sm:p-8 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                        <div class="flex items-center justify-between gap-3 mb-5">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">event</span></span>
                                <div>
                                    <span class="block text-[9px] font-black text-stone-400 uppercase tracking-[0.18em] leading-none">Step 1 of 3</span>
                                    <h4 class="text-sm font-bold text-ink tracking-tight mt-1">Stay Dates</h4>
                                </div>
                            </div>
                            <span id="nights_duration_badge" class="hidden px-3.5 py-1.5 rounded-full bg-gold-soft/40 border border-gold/40 text-ink/80 text-[11px] font-bold uppercase tracking-[0.14em] animate-pop whitespace-nowrap"></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Check-in</label>
                                <input type="text" id="check_in" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none font-semibold cursor-pointer transition-all" placeholder="Select Date" value="{{ $checkIn ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Check-out</label>
                                <input type="text" id="check_out" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none font-semibold cursor-pointer transition-all" placeholder="Select Date" value="{{ $checkOut ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- GUEST INFO (Imported from Partial) -->
                    <div class="bg-cream-warm rounded-3xl p-6 sm:p-8 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                        @include('booking.partials.step-guest')
                    </div>

                    <!-- ROOM SELECTION -->
                    <div class="bg-cream-warm rounded-3xl p-6 sm:p-8 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">meeting_room</span></span>
                                <div>
                                    <span class="block text-[9px] font-black text-stone-400 uppercase tracking-[0.18em] leading-none">Step 3 of 3</span>
                                    <h4 class="text-sm font-bold text-ink tracking-tight mt-1">Room Allocation</h4>
                                </div>
                            </div>
                            <button type="button" onclick="window.addReservationBlock()" class="press inline-flex items-center gap-1.5 rounded-full border border-emerald-deep/20 bg-cream px-4 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-deep transition-colors hover:bg-emerald-deep hover:text-cream cursor-pointer">
                                <span class="material-icons text-[15px]">add</span> Add Room Type
                            </button>
                        </div>
                        <p class="text-sm text-stone-500 font-medium mb-4">Configure the rooms you want to book. You must select specific room numbers for each type.</p>

                        <div id="reservationBlocks" class="space-y-4">
                            <!-- JS will inject blocks here -->
                        </div>
                    </div>

                </div>

                <!-- Right Column: Sticky Summary -->
                <div class="lg:col-span-4">
                    <div class="bg-cream-warm rounded-3xl p-6 ring-1 ring-emerald-deep/5 shadow-boutique-card sticky top-28">
                        <h3 class="text-xl text-ink border-b border-emerald-deep/10 pb-4 mb-4 font-display flex items-center gap-2.5">
                            <span class="material-icons text-emerald-deep text-[20px]">receipt_long</span>
                            Booking <span class="italic text-gold -ml-1">Summary</span>
                        </h3>

                        <!-- Summary Invoice will be rendered here by JS -->
                        <div id="summaryInvoice" class="space-y-4 mb-6 text-sm font-medium text-stone-600">
                            <div class="text-center py-8">
                                <span class="material-icons text-stone-300 text-4xl mb-2 block">receipt_long</span>
                                <p>Select dates and rooms to view summary.</p>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitBooking" class="press focus-ring w-full min-h-12 py-4 rounded-full text-[12px] font-semibold uppercase tracking-[0.2em] bg-emerald-deep text-cream transition-all cursor-pointer flex items-center justify-center gap-2 hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_25%,transparent)] disabled:opacity-70 disabled:pointer-events-none">
                            <span class="material-icons text-[18px]">check_circle</span>
                            Confirm Booking
                        </button>
                        <div class="mt-4 grid grid-cols-3 gap-1 text-center">
                            <div class="text-[9px] font-bold text-stone-400 uppercase tracking-wider flex flex-col items-center gap-1">
                                <span class="material-icons text-[16px] text-clsu-600">lock</span> Secure
                            </div>
                            <div class="text-[9px] font-bold text-stone-400 uppercase tracking-wider flex flex-col items-center gap-1">
                                <span class="material-icons text-[16px] text-clsu-600">credit_card_off</span> No prepayment
                            </div>
                            <div class="text-[9px] font-bold text-stone-400 uppercase tracking-wider flex flex-col items-center gap-1">
                                <span class="material-icons text-[16px] text-clsu-600">verified</span> Instant hold
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile sticky total bar (summary column is off-screen on phones) -->
    <div class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-cream-warm/95 backdrop-blur-md border-t border-gold/30 px-4 py-3 shadow-[0_-12px_30px_rgba(6,40,30,0.12)] flex items-center justify-between gap-3">
        <div>
            <p class="text-[9px] font-bold text-emerald/70 uppercase tracking-[0.28em] leading-none">Total due</p>
            <p id="mobileTotalAmount" class="font-display text-xl text-ink tabnum mt-1">₱0</p>
            <p id="mobileMetaLine" class="text-[10px] font-semibold text-stone-400 mt-0.5"></p>
        </div>
        <button type="submit" form="bookingForm" id="btnSubmitBookingMobile" class="press min-h-11 px-6 py-2.5 rounded-full text-cream text-[12px] font-semibold uppercase tracking-[0.18em] cursor-pointer bg-emerald-deep hover:bg-emerald flex items-center gap-1.5 disabled:opacity-70 disabled:pointer-events-none">
            <span class="material-icons text-[16px]">check_circle</span>
            Confirm
        </button>
    </div>

    <!-- Template for Room Blocks -->
    <template id="reservationBlockTemplate">
        @include('booking.partials.reservation-block-template')
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
@endpush
