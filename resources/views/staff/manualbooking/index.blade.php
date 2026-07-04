@extends('layouts.admin')

@section('title', 'Admin - Manual Booking')
@section('page-title', 'Manual Booking')

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.page-header subtitle="Create a booking on behalf of a guest — walk-in, phone, or any offline channel.">
        Manual <span class="font-display italic font-medium text-clsu-800">Booking</span>
    </x-admin.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <x-admin.stat-card icon="bed" badge="RIGHT NOW" label="Available Rooms" :delay="40">
            {{ $totalAvailableRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">Ready to assign to a booking</p></x-slot:footnote>
        </x-admin.stat-card>

        <div class="lg:col-span-2 animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card p-6" style="animation-delay:80ms">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center shrink-0">
                    <x-admin.icon name="calendar" class="w-4 h-4" />
                </div>
                <p class="font-semibold text-stone-900 text-sm">Upcoming Reservations</p>
            </div>
            @if($upcomingBookings->isEmpty())
                <p class="text-sm text-stone-400">No upcoming paid or pending-payment bookings.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($upcomingBookings as $booking)
                        @foreach($booking->reservations as $res)
                            <span class="text-xs font-medium text-stone-600 bg-stone-50 border border-stone-200 rounded-full px-3 py-1.5">
                                Room {{ $res->room_number }} · {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}–{{ \Carbon\Carbon::parse($booking->check_out)->format('M d') }}
                            </span>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-admin.section-card icon="calendar-plus" title="Booking Details" :delay="120">
        @if(session('success'))
            <div class="flex items-center gap-2 text-sm font-medium text-clsu-800 bg-clsu-50 border border-clsu-200 rounded-xl px-4 py-2.5 mb-5">
                <x-admin.icon name="check-circle" class="w-4 h-4 shrink-0" />
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="text-sm text-ember-700 bg-ember-50 border border-ember-200 rounded-xl px-4 py-3 mb-5">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="flex items-start gap-1.5"><x-admin.icon name="block" class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.manualbooking.store') }}" id="walkin-form" class="space-y-6">
            @csrf

            <!-- Guest Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Guest Name</label>
                    <input type="text" name="guest_name" value="{{ old('guest_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Guest Phone</label>
                    <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Guest Address</label>
                    <livewire:address-selector />
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Expected Guests</label>
                    <input type="number" name="expected_guests" value="1" min="1" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
                </div>
            </div>

            <!-- Dates & Discount -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Check-In</label>
                    <input type="date" name="check_in" id="check_in" value="{{ old('check_in', date('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Check-Out</label>
                    <input type="date" name="check_out" id="check_out" value="{{ old('check_out', date('Y-m-d', strtotime('+1 day'))) }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="has_senior_pwd" id="has_senior_pwd" class="w-4 h-4 rounded border-stone-300 text-clsu-600 focus:ring-clsu-500">
                    <label for="has_senior_pwd" class="text-sm font-medium text-stone-700">PWD / Senior?</label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Discount (₱)</label>
                    <input type="number" name="discount_amount" value="{{ old('discount_amount', 0) }}" min="0" step="1" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
                </div>
            </div>

            <div id="availability-status" class="flex items-center gap-2 text-xs font-medium text-stone-500 min-h-[1rem]"></div>

            <!-- Room Reservations -->
            <div class="pt-2 border-t border-stone-100">
                <div class="flex items-center justify-between mt-4 mb-1">
                    <p class="text-sm font-semibold text-stone-900">Room Reservations</p>
                    <p class="text-xs text-stone-500">Guests left to assign: <span id="remaining-guests" class="font-bold text-clsu-700 font-data tabnum">{{ old('expected_guests', 1) }}</span></p>
                </div>

                <div id="reservations-container" class="space-y-3 mt-3">
                    <div class="reservation-block rounded-xl border border-stone-200/70 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 text-xs font-bold text-stone-500 tracking-wide uppercase">
                                <span class="room-index-number w-5 h-5 rounded-full bg-clsu-100 text-clsu-700 flex items-center justify-center text-[10px] font-bold normal-case">1</span>
                                Room
                            </span>
                            <div data-delete-slot></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">

                            <!-- Available Rooms -->
                            <div>
                                <label class="block text-[10px] font-bold text-stone-500 tracking-wider uppercase mb-1.5">Available Room</label>
                                <select name="reservations[0][room_id]" class="available-rooms-dropdown w-full px-3 py-2.5 rounded-lg border border-stone-200 bg-white text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors" required>
                                    <option value="" selected disabled>Select a room…</option>
                                </select>
                                <input type="hidden" name="reservations[0][room_type]" class="room-type-input">
                            </div>

                            <!-- Room Number -->
                            <div>
                                <label class="block text-[10px] font-bold text-stone-500 tracking-wider uppercase mb-1.5">Room Number</label>
                                <input type="text" name="reservations[0][room_number]"
                                    class="room-number-input w-full px-3 py-2.5 rounded-lg border border-stone-200 bg-stone-100 text-stone-500 text-sm cursor-not-allowed outline-none"
                                    placeholder="Auto-filled…" readonly required>
                            </div>

                            <!-- Price -->
                            <div>
                                <label class="block text-[10px] font-bold text-stone-500 tracking-wider uppercase mb-1.5">Price / Night (₱)</label>
                                <input type="number" name="reservations[0][price_per_night]"
                                    class="price-per-night w-full px-3 py-2.5 rounded-lg border border-stone-200 bg-stone-100 text-stone-500 text-sm cursor-not-allowed outline-none" readonly required>
                            </div>

                            <!-- Guests -->
                            <div>
                                <label class="block text-[10px] font-bold text-stone-500 tracking-wider uppercase mb-1.5">Guests</label>
                                <input type="number" name="reservations[0][num_guests]"
                                    class="num-guests w-full px-3 py-2.5 rounded-lg border border-stone-200 bg-white text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors disabled:bg-stone-100 disabled:text-stone-400 disabled:cursor-not-allowed" value="1" min="1" disabled required>
                                <p class="text-ember-600 text-[11px] mt-1 guest-error hidden">Exceeds room capacity!</p>
                            </div>

                            <!-- Seniors -->
                            <div>
                                <label class="block text-[10px] font-bold text-stone-500 tracking-wider uppercase mb-1.5">Seniors / PWD</label>
                                <input type="number" name="reservations[0][num_seniors]"
                                    class="num-senior w-full px-3 py-2.5 rounded-lg border border-stone-200 bg-white text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" value="0" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" id="add-room" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 transition-colors cursor-pointer">
                        <x-admin.icon name="plus" class="w-4 h-4" stroke-width="2" />
                        Add Another Room
                    </button>
                </div>
            </div>

            <div class="pt-4 border-t border-stone-100">
                <button type="submit" class="flex items-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-5 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all cursor-pointer">
                    <x-admin.icon name="check-circle" class="w-4 h-4" />
                    Create Booking
                </button>
            </div>
        </form>
    </x-admin.section-card>
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
            ? `<span class="text-clsu-700">${availableCount} room${availableCount === 1 ? '' : 's'} available for these dates.</span>`
            : `<span class="text-ember-600">No rooms available for these dates — try different dates.</span>`;
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
            deleteBtn.className = 'delete-room-block text-xs font-semibold text-ember-600 hover:text-ember-700 cursor-pointer';
            deleteBtn.textContent = 'Remove Room';
            deleteContainer.appendChild(deleteBtn);

            deleteBtn.addEventListener('click', function() {
                block.remove();
                updateDropdowns();
                updateReservationIndexes();
                updateRemainingGuests();
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
        });

        guestsInput.addEventListener('input', function() {

            const selectedOption = dropdown.options[dropdown.selectedIndex];
            if (!selectedOption.value) return;

            const capacity = roomCapacityMap[selectedOption.dataset.roomType.toLowerCase()] || 1;

            guestError.classList.toggle('hidden', parseInt(this.value) <= capacity);

            updateRemainingGuests();
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
    });

    expectedGuestsInput.addEventListener('input', updateRemainingGuests);

    // =====================================================
    //  TRIGGER AJAX WHEN DATES CHANGE
    // =====================================================
    checkInInput.addEventListener('change', fetchAvailableRooms);
    checkOutInput.addEventListener('change', fetchAvailableRooms);

    // Load availability on page load
    fetchAvailableRooms();

});
</script>
@endpush
