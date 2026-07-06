@extends('layouts.admin')

@section('title', 'Admin - Manual Booking')
@section('page-title', 'Manual Booking')

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.page-header subtitle="Create a booking on behalf of a guest — walk-in, phone, or any offline channel.">
        Manual <span class="font-display italic font-medium text-clsu-800">Booking</span>
    </x-admin.page-header>

    <!-- Boutique stats band — mirrors the landing page's emerald band -->
    <div class="animate-in relative overflow-hidden rounded-3xl bg-emerald-deep text-cream shadow-boutique-card" style="animation-delay:40ms">
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gold/40"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-16 h-64 w-64 rounded-full bg-gold/10 blur-3xl"></div>
        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr] md:divide-x md:divide-cream/10">
            <div class="px-7 py-6 md:pr-12">
                <p class="text-[10px] font-bold uppercase tracking-[0.32em] text-gold">Right now</p>
                <p class="mt-2 font-display text-5xl leading-none tabnum">{{ $totalAvailableRooms }}</p>
                <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-cream/60">Available rooms</p>
            </div>
            <div class="px-7 py-6 border-t border-cream/10 md:border-t-0">
                <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.32em] text-gold">Upcoming reservations</p>
                @if($upcomingBookings->isEmpty())
                    <p class="text-sm text-cream/60">No upcoming paid or pending-payment bookings.</p>
                @else
                    <div class="custom-scrollbar flex max-h-24 flex-wrap gap-2 overflow-y-auto pr-1">
                        @foreach($upcomingBookings as $booking)
                            @foreach($booking->reservations as $res)
                                <span class="inline-flex items-center gap-2 rounded-full border border-cream/15 bg-cream/10 px-3 py-1.5 text-xs font-medium text-cream/85">
                                    <span class="h-1 w-1 rounded-full bg-gold"></span>
                                    Room {{ $res->room_number }} · {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}–{{ \Carbon\Carbon::parse($booking->check_out)->format('M d') }}
                                </span>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="animate-in flex items-center gap-2.5 rounded-2xl border border-clsu-200 bg-clsu-50 px-5 py-3 text-sm font-medium text-clsu-800">
            <x-admin.icon name="check-circle" class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="animate-in rounded-2xl border border-ember-200 bg-ember-50 px-5 py-3.5 text-sm text-ember-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li class="flex items-start gap-1.5"><x-admin.icon name="block" class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('staff.manualbooking.store') }}" id="walkin-form" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        @csrf

        <!-- ══════════ Left column — the three steps ══════════ -->
        <div class="space-y-6 lg:col-span-8">

            <!-- STEP 1 · STAY DATES -->
            <div class="animate-in rounded-3xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:80ms">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                            <x-admin.icon name="calendar" class="w-4 h-4" />
                        </span>
                        <div>
                            <span class="block text-[9px] font-black uppercase tracking-[0.2em] leading-none text-stone-400">Step 1 of 3</span>
                            <h4 class="mt-1 font-display text-lg leading-none text-ink">Stay Dates</h4>
                        </div>
                    </div>
                    <span id="nights-badge" class="hidden animate-pop whitespace-nowrap rounded-full border border-gold/40 bg-gold-soft/40 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/80"></span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Check-In</label>
                        <input type="date" name="check_in" id="check_in" value="{{ old('check_in', date('Y-m-d')) }}"
                               class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none cursor-pointer" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Check-Out</label>
                        <input type="date" name="check_out" id="check_out" value="{{ old('check_out', date('Y-m-d', strtotime('+1 day'))) }}"
                               class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none cursor-pointer" required>
                    </div>
                </div>

                <div id="availability-status" class="mt-4 flex min-h-[1.25rem] items-center gap-2 text-xs font-medium text-stone-500"></div>
            </div>

            <!-- STEP 2 · GUEST DETAILS -->
            <div class="animate-in rounded-3xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:120ms">
                <div class="mb-5 flex items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                        <x-admin.icon name="user" class="w-4 h-4" />
                    </span>
                    <div>
                        <span class="block text-[9px] font-black uppercase tracking-[0.2em] leading-none text-stone-400">Step 2 of 3</span>
                        <h4 class="mt-1 font-display text-lg leading-none text-ink">Guest Details</h4>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Guest Name</label>
                        <input type="text" name="guest_name" value="{{ old('guest_name') }}" placeholder="Full name"
                               class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-stone-400 transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Guest Phone</label>
                        <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="09xx xxx xxxx"
                               class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-stone-400 transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Guest Address</label>
                        <livewire:address-selector />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Expected Guests</label>
                        <input type="number" name="expected_guests" value="1" min="1"
                               class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm text-ink transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none" required>
                    </div>
                </div>
            </div>

            <!-- STEP 3 · ROOM ALLOCATION -->
            <div class="animate-in rounded-3xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:160ms">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                            <x-admin.icon name="bed" class="w-4 h-4" />
                        </span>
                        <div>
                            <span class="block text-[9px] font-black uppercase tracking-[0.2em] leading-none text-stone-400">Step 3 of 3</span>
                            <h4 class="mt-1 font-display text-lg leading-none text-ink">Room Allocation</h4>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold-soft/40 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/80">
                        <x-admin.icon name="users" class="w-3.5 h-3.5" />
                        Left to assign: <span id="remaining-guests" class="font-data tabnum">{{ old('expected_guests', 1) }}</span>
                    </span>
                </div>

                <div id="reservations-container" class="space-y-4">
                    <div class="reservation-block space-y-4 rounded-2xl border border-emerald-deep/10 bg-white/70 p-5">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2.5 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-deep">
                                <span class="room-index-number grid h-6 w-6 place-items-center rounded-full bg-emerald-deep font-data text-[10px] font-bold normal-case tracking-normal text-cream">1</span>
                                Room
                            </span>
                            <div data-delete-slot></div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                            <!-- Available Rooms -->
                            <div>
                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Available Room</label>
                                <select name="reservations[0][room_id]" class="available-rooms-dropdown w-full cursor-pointer rounded-xl border border-emerald-deep/15 bg-white px-3 py-2.5 text-sm text-ink transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none" required>
                                    <option value="" selected disabled>Select a room…</option>
                                </select>
                                <input type="hidden" name="reservations[0][room_type]" class="room-type-input">
                            </div>

                            <!-- Room Number -->
                            <div>
                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Room Number</label>
                                <input type="text" name="reservations[0][room_number]"
                                    class="room-number-input w-full cursor-not-allowed rounded-xl border border-emerald-deep/10 bg-cream px-3 py-2.5 text-sm text-stone-500 outline-none"
                                    placeholder="Auto-filled…" readonly required>
                            </div>

                            <!-- Price -->
                            <div>
                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Price / Night (₱)</label>
                                <input type="number" name="reservations[0][price_per_night]"
                                    class="price-per-night w-full cursor-not-allowed rounded-xl border border-emerald-deep/10 bg-cream px-3 py-2.5 text-sm text-stone-500 outline-none tabnum" readonly required>
                            </div>

                            <!-- Guests -->
                            <div>
                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Guests</label>
                                <input type="number" name="reservations[0][num_guests]"
                                    class="num-guests w-full rounded-xl border border-emerald-deep/15 bg-white px-3 py-2.5 text-sm text-ink transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none disabled:cursor-not-allowed disabled:bg-cream disabled:text-stone-400" value="1" min="1" disabled required>
                                <p class="guest-error mt-1 hidden text-[11px] text-ember-600">Exceeds room capacity!</p>
                            </div>

                            <!-- Seniors -->
                            <div>
                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Seniors / PWD</label>
                                <input type="number" name="reservations[0][num_seniors]"
                                    class="num-senior w-full rounded-xl border border-emerald-deep/15 bg-white px-3 py-2.5 text-sm text-ink transition-colors focus:border-gold focus:ring-2 focus:ring-gold/25 focus:outline-none" value="0" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="button" id="add-room" class="press inline-flex cursor-pointer items-center gap-2 rounded-full border border-emerald-deep/20 bg-white px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-deep transition-colors hover:bg-emerald-deep hover:text-cream">
                        <x-admin.icon name="plus" class="w-3.5 h-3.5" stroke-width="2" />
                        Add Another Room
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════ Right column — live booking summary ══════════ -->
        <div class="animate-in lg:sticky lg:top-24 lg:col-span-4" style="animation-delay:200ms">
            <div class="relative overflow-hidden rounded-3xl bg-emerald-deep p-6 text-cream shadow-boutique-card sm:p-7">
                <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gold/40"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 -left-16 h-56 w-56 rounded-full bg-gold/10 blur-3xl"></div>

                <h3 class="flex items-center gap-2.5 border-b border-cream/10 pb-4 font-display text-xl">
                    <x-admin.icon name="receipt" class="w-5 h-5 text-gold" />
                    Booking <span class="-ml-1 italic text-gold">Summary</span>
                </h3>

                <!-- Dates -->
                <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                    <span id="summary-dates" class="font-medium text-cream/85">—</span>
                    <span id="summary-nights" class="hidden whitespace-nowrap rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-gold"></span>
                </div>

                <!-- Room lines -->
                <div id="summary-rooms" class="mt-4 space-y-2.5 border-t border-cream/10 pt-4 text-sm">
                    <p class="text-cream/50">Select dates and rooms to build the summary.</p>
                </div>

                <!-- Subtotal -->
                <div class="mt-4 flex items-center justify-between border-t border-cream/10 pt-4 text-sm">
                    <span class="text-cream/60">Subtotal</span>
                    <span id="summary-subtotal" class="font-semibold tabnum">₱0</span>
                </div>

                <!-- Senior / PWD flag -->
                <label for="has_senior_pwd" class="mt-4 flex cursor-pointer items-center gap-2.5 text-sm text-cream/85">
                    <input type="checkbox" name="has_senior_pwd" id="has_senior_pwd" class="h-4 w-4 rounded border-cream/40 bg-transparent text-gold focus:ring-gold/50">
                    Senior / PWD guest present
                </label>

                <!-- Discount -->
                <div class="mt-3">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-gold">Discount (₱)</label>
                    <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', 0) }}" min="0" step="1"
                           class="w-full rounded-xl border border-cream/15 bg-cream/10 px-4 py-2.5 text-sm text-cream placeholder:text-cream/40 transition-colors focus:border-gold focus:ring-2 focus:ring-gold/30 focus:outline-none tabnum">
                </div>

                <!-- Total -->
                <div aria-hidden="true" class="mt-5 h-px w-full bg-gold/40"></div>
                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-cream/60">Total payable</p>
                        <p class="mt-1 font-display text-3xl leading-none">
                            <span id="summary-total" class="anim-number tabnum"><span>₱0</span></span>
                        </p>
                    </div>
                </div>

                <button type="submit" class="press focus-ring mt-6 flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-gold px-6 py-3.5 text-[12px] font-bold uppercase tracking-[0.2em] text-ink transition-all hover:bg-gold-soft hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)]">
                    <x-admin.icon name="check-circle" class="w-4 h-4" />
                    Create Booking
                </button>
                <p class="mt-3 text-center text-[10px] font-semibold uppercase tracking-[0.2em] text-cream/50">Recorded as paid · Manual payment</p>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    const roomCapacityMap = {
        deluxe: 2,
        double: 2,
        triple: 3,
        quadruple: 4,
        dormitory1: 5,
        dormitory2: 6
    };

    const reservationsContainer = document.getElementById('reservations-container');
    const addRoomBtn = document.getElementById('add-room');
    const expectedGuestsInput = document.querySelector('input[name="expected_guests"]');
    const remainingGuestsEl = document.getElementById('remaining-guests');

    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const discountInput = document.getElementById('discount_amount');

    let latestAvailableRooms = [];

    const availabilityStatusEl = document.getElementById('availability-status');

    // =====================================================
    // AVAILABILITY STATUS MESSAGE
    // =====================================================
    function setAvailabilityStatus(state) {
        if (state === 'loading') {
            availabilityStatusEl.innerHTML = `
                <svg class="w-3.5 h-3.5 animate-spin text-stone-400" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
                <span class="text-stone-400">Checking room availability…</span>`;
            return;
        }

        if (state === 'error') {
            availabilityStatusEl.innerHTML = `<span class="text-ember-600">Couldn't check room availability. Please try again.</span>`;
            return;
        }

        const availableCount = latestAvailableRooms.filter(r => r.status === 'available').length;

        availabilityStatusEl.innerHTML = availableCount > 0
            ? `<span class="relative flex h-2 w-2 shrink-0">
                   <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-clsu-500 opacity-60"></span>
                   <span class="relative inline-flex h-2 w-2 rounded-full bg-clsu-600"></span>
               </span>
               <span class="font-semibold text-clsu-700">${availableCount} room${availableCount === 1 ? '' : 's'} available for these dates.</span>`
            : `<span class="h-2 w-2 shrink-0 rounded-full bg-ember-500"></span>
               <span class="font-semibold text-ember-600">No rooms available for these dates — try different dates.</span>`;
    }

    // =====================================================
    // 🟢 AJAX: FETCH AVAILABLE ROOMS
    // =====================================================
    function fetchAvailableRooms() {

        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;

        if (!checkIn || !checkOut) return;

        setAvailabilityStatus('loading');

        fetch("{{ route('staff.manualbooking.available') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                check_in: checkIn,
                check_out: checkOut
            })
        })
        .then(response => response.json())
        .then(data => {
            latestAvailableRooms = data.rooms || [];
            updateAllDropdownOptions();
            setAvailabilityStatus('ready');
            updateSummary();
        })
        .catch(error => {
            console.error("Room availability fetch error:", error);
            setAvailabilityStatus('error');
        });
    }

    // =====================================================
    // 🟢 UPDATE ALL DROPDOWNS FROM AJAX DATA
    // =====================================================
    function updateAllDropdownOptions() {

        document.querySelectorAll('.available-rooms-dropdown').forEach(dropdown => {

            const currentValue = dropdown.value;
            dropdown.innerHTML = '<option value="" disabled selected>Select a room…</option>';

            latestAvailableRooms.forEach(room => {

                if (room.status === 'available') {

                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `Room ${room.room_number} (${room.room_type})`;
                    option.dataset.roomNumber = room.room_number;
                    option.dataset.roomType = room.room_type;
                    option.dataset.price = room.price;

                    dropdown.appendChild(option);
                }
            });

            dropdown.value = currentValue;
        });

        updateDropdowns(); // prevent duplicates after refresh
    }

    // =====================================================
    // UPDATE REMAINING GUESTS
    // =====================================================
    function updateRemainingGuests() {
        const expectedGuests = parseInt(expectedGuestsInput.value) || 0;
        const assignedGuests = Array.from(document.querySelectorAll('.num-guests'))
            .reduce((sum, input) => sum + parseInt(input.value || 0), 0);

        remainingGuestsEl.textContent = Math.max(expectedGuests - assignedGuests, 0);
    }

    // =====================================================
    // LIVE BOOKING SUMMARY (boutique sidebar card)
    // =====================================================
    const summaryDatesEl = document.getElementById('summary-dates');
    const summaryNightsEl = document.getElementById('summary-nights');
    const summaryRoomsEl = document.getElementById('summary-rooms');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryTotalEl = document.getElementById('summary-total');
    const nightsBadgeEl = document.getElementById('nights-badge');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let lastTotal = 0;

    function peso(n) {
        return '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    }

    function calcNights() {
        if (!checkInInput.value || !checkOutInput.value) return 0;
        const diff = (new Date(checkOutInput.value) - new Date(checkInInput.value)) / 86400000;
        return diff > 0 ? Math.max(1, Math.round(diff)) : 0;
    }

    // Same odometer roll as the landing-page guests stepper.
    function rollTotal(total) {
        if (total === lastTotal) return;
        const dir = total > lastTotal ? 1 : -1;
        lastTotal = total;

        const current = summaryTotalEl.querySelector('span:not(.is-leaving)');
        if (reduceMotion || !current || !current.animate) {
            summaryTotalEl.textContent = '';
            const s = document.createElement('span');
            s.textContent = peso(total);
            summaryTotalEl.appendChild(s);
            return;
        }

        const next = document.createElement('span');
        next.textContent = peso(total);
        summaryTotalEl.appendChild(next);

        const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
        current.classList.add('is-leaving');
        current.animate([
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
            { transform: `translateY(${dir * -100}%)`, opacity: 0, filter: 'blur(2px)' },
        ], { duration: 260, easing, fill: 'forwards' }).onfinish = () => current.remove();
        next.animate([
            { transform: `translateY(${dir * 100}%)`, opacity: 0, filter: 'blur(2px)' },
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
        ], { duration: 260, easing });
    }

    function updateSummary() {
        const nights = calcNights();
        const fmt = { month: 'short', day: 'numeric' };

        if (nights > 0) {
            const ci = new Date(checkInInput.value).toLocaleDateString('en-US', fmt);
            const co = new Date(checkOutInput.value).toLocaleDateString('en-US', fmt);
            const label = `${nights} night${nights === 1 ? '' : 's'}`;
            summaryDatesEl.textContent = `${ci} → ${co}`;
            summaryNightsEl.textContent = label;
            summaryNightsEl.classList.remove('hidden');
            nightsBadgeEl.textContent = label;
            nightsBadgeEl.classList.remove('hidden');
        } else {
            summaryDatesEl.textContent = 'Select valid dates';
            summaryNightsEl.classList.add('hidden');
            nightsBadgeEl.classList.add('hidden');
        }

        let subtotal = 0;
        let lines = '';

        document.querySelectorAll('.reservation-block').forEach(block => {
            const dropdown = block.querySelector('.available-rooms-dropdown');
            const option = dropdown.options[dropdown.selectedIndex];
            if (!option || !option.value) return;

            const price = parseFloat(block.querySelector('.price-per-night').value) || 0;
            const guests = parseInt(block.querySelector('.num-guests').value) || 1;
            const lineTotal = price * nights;
            subtotal += lineTotal;

            lines += `
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-cream">Room ${option.dataset.roomNumber} <span class="font-normal capitalize text-cream/60">· ${option.dataset.roomType}</span></p>
                        <p class="text-xs text-cream/50">${peso(price)} × ${nights} night${nights === 1 ? '' : 's'} · ${guests} guest${guests === 1 ? '' : 's'}</p>
                    </div>
                    <span class="whitespace-nowrap font-semibold tabnum">${peso(lineTotal)}</span>
                </div>`;
        });

        summaryRoomsEl.innerHTML = lines || '<p class="text-cream/50">Select dates and rooms to build the summary.</p>';
        summarySubtotalEl.textContent = peso(subtotal);

        const discount = Math.max(0, parseFloat(discountInput.value) || 0);
        rollTotal(Math.max(0, subtotal - discount));
    }

    // =====================================================
    // PREVENT DUPLICATE ROOM SELECTION
    // =====================================================
    function updateDropdowns() {

        const selectedRooms = Array.from(document.querySelectorAll('.available-rooms-dropdown'))
            .map(d => d.value)
            .filter(Boolean);

        document.querySelectorAll('.available-rooms-dropdown option').forEach(option => {

            const parentDropdown = option.closest('.available-rooms-dropdown');

            option.disabled =
                selectedRooms.includes(option.value) &&
                parentDropdown.value !== option.value;
        });
    }

    // =====================================================
    // UPDATE INPUT NAMES FOR LARAVEL
    // =====================================================
    function updateReservationIndexes() {
        document.querySelectorAll('.reservation-block').forEach((block, index) => {
            block.querySelectorAll('input, select').forEach(input => {
                if (input.name.includes('reservations[')) {
                    input.name = input.name.replace(/reservations\[\d+\]/, `reservations[${index}]`);
                }
            });

            const badge = block.querySelector('.room-index-number');
            if (badge) badge.textContent = index + 1;
        });
    }

    // =====================================================
    // ATTACH LISTENERS TO BLOCK
    // =====================================================
    function attachBlockListeners(block, isFirstBlock = false) {

        const dropdown = block.querySelector('.available-rooms-dropdown');
        const roomInput = block.querySelector('.room-number-input');
        const priceInput = block.querySelector('.price-per-night');
        const guestsInput = block.querySelector('.num-guests');
        const guestError = block.querySelector('.guest-error');
        const deleteContainer = block.querySelector('[data-delete-slot]');

        deleteContainer.innerHTML = '';

        if (!isFirstBlock) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'delete-room-block press inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-ember-200 bg-ember-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ember-600 transition-colors hover:bg-ember-100';
            deleteBtn.textContent = 'Remove';
            deleteContainer.appendChild(deleteBtn);

            deleteBtn.addEventListener('click', function() {
                block.remove();
                updateDropdowns();
                updateReservationIndexes();
                updateRemainingGuests();
                updateSummary();
            });
        }

        dropdown.addEventListener('change', function() {

            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) return;

            const roomTypeInput = block.querySelector('.room-type-input');
            roomTypeInput.value = selectedOption.dataset.roomType;

            roomInput.value = selectedOption.dataset.roomNumber;
            priceInput.value = selectedOption.dataset.price;

            const capacity = roomCapacityMap[selectedOption.dataset.roomType.toLowerCase()] || 1;

            guestsInput.disabled = false;
            guestsInput.max = capacity;

            if (parseInt(guestsInput.value) > capacity) guestsInput.value = capacity;
            if (parseInt(guestsInput.value) < 1) guestsInput.value = 1;

            guestError.classList.add('hidden');

            updateDropdowns();
            updateRemainingGuests();
            updateSummary();
        });

        guestsInput.addEventListener('input', function() {

            const selectedOption = dropdown.options[dropdown.selectedIndex];
            if (!selectedOption.value) return;

            const capacity = roomCapacityMap[selectedOption.dataset.roomType.toLowerCase()] || 1;

            guestError.classList.toggle('hidden', parseInt(this.value) <= capacity);

            updateRemainingGuests();
            updateSummary();
        });
    }

    // =====================================================
    // INITIALIZE EXISTING BLOCKS
    // =====================================================
    document.querySelectorAll('.reservation-block').forEach((block, index) => {
        attachBlockListeners(block, index === 0);
    });

    updateReservationIndexes();
    updateDropdowns();
    updateRemainingGuests();
    updateSummary();

    // =====================================================
    // ADD ROOM BLOCK
    // =====================================================
    addRoomBtn.addEventListener('click', function() {

        const expectedGuests = parseInt(expectedGuestsInput.value) || 0;
        const assignedGuests = Array.from(document.querySelectorAll('.num-guests'))
            .reduce((sum, input) => sum + parseInt(input.value || 0), 0);

        if (assignedGuests >= expectedGuests) {
            Swal.fire({
                icon: 'info',
                title: 'All guests already assigned!',
                text: 'You cannot add more rooms because guests are already accommodated.'
            });
            return;
        }

        const firstBlock = document.querySelector('.reservation-block');
        const newBlock = firstBlock.cloneNode(true);

        newBlock.querySelector('.available-rooms-dropdown').value = '';
        newBlock.querySelector('.room-number-input').value = '';
        newBlock.querySelector('.price-per-night').value = '';
        newBlock.querySelector('.room-type-input').value = '';

        const guestsInput = newBlock.querySelector('.num-guests');
        guestsInput.value = 1;
        guestsInput.disabled = true;
        guestsInput.max = 1;

        newBlock.querySelector('.guest-error').classList.add('hidden');

        reservationsContainer.appendChild(newBlock);

        updateReservationIndexes();
        attachBlockListeners(newBlock, false);

        updateDropdowns();
        updateRemainingGuests();
        updateSummary();
    });

    expectedGuestsInput.addEventListener('input', updateRemainingGuests);
    discountInput.addEventListener('input', updateSummary);

    // =====================================================
    //  TRIGGER AJAX WHEN DATES CHANGE
    // =====================================================
    checkInInput.addEventListener('change', function() { updateSummary(); fetchAvailableRooms(); });
    checkOutInput.addEventListener('change', function() { updateSummary(); fetchAvailableRooms(); });

    // Load availability on page load
    fetchAvailableRooms();

});
</script>
@endpush
