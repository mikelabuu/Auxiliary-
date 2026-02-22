@extends('layouts.frontdesk')
<link rel="stylesheet" href="{{ asset('css/roomManagement.css') }}">
@section('title', 'Front Desk - Walk-in Booking')
@section('content')


<div class="walkin-container p-6 bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        
        <div class="flex items-center space-x-4">
            <div class="p-3 bg-green-100 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Available Rooms</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalAvailableRooms }}</h3>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <div class="p-3 bg-green-100 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <div>
                <h2>Upcoming Reservations:</h2>
                <ul>
                    @foreach($upcomingBookings as $booking)
                        @foreach($booking->reservations as $res)
                            <li>Room {{ $res->room_number }}: {{ $booking->check_in }} - {{ $booking->check_out }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>


<div class="walkin-container p-6 bg-white rounded shadow space-y-6">

    <h1 class="text-2xl font-bold mb-4">Walk-In Booking</h1>

    @if(session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('frontdesk.walkin.store') }}" id="walkin-form" class="space-y-4">
        @csrf

        <!-- Guest Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium">Guest Name</label>
                <input type="text" name="guest_name" value="{{ old('guest_name') }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block font-medium">Guest Phone</label>
                <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block font-medium">Guest Address</label>
                <input type="text" name="guest_address" value="{{ old('guest_address') }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block font-medium">Expected Guests</label>
                <input type="number" name="expected_guests" value="1" min="1" class="w-full border rounded p-2" required>
            </div>
        </div>

        <!-- Dates & Discount -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block font-medium">Check-In</label>
                <input type="date" name="check_in" id="check_in" value="{{ old('check_in', date('Y-m-d')) }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block font-medium">Check-Out</label>
                <input type="date" name="check_out" id="check_out" value="{{ old('check_out', date('Y-m-d', strtotime('+1 day'))) }}" class="w-full border rounded p-2" required>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="has_senior_pwd" id="has_senior_pwd" class="h-5 w-5">
                <label for="has_senior_pwd" class="font-medium">PWD / Senior?</label>
            </div>
            <div>
                <label class="block font-medium">Discount Amount (₱)</label>
                <input type="number" name="discount_amount" value="{{ old('discount_amount', 0) }}" min="0" step="1" class="w-full border rounded p-2">
            </div>
        </div>

        <!-- Room Reservations -->
        <h2 class="text-xl font-bold mb-2">Room Reservation</h2>
        <p class="text-gray-600 mb-2">
            Remaining Guests to Assign: <span id="remaining-guests">{{ old('expected_guests', 1) }}</span>
        </p>

        <div id="reservations-container" class="space-y-4">
            <div class="reservation-block p-4 border rounded space-y-2">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                    <!-- Available Rooms -->
                    <div>
                        <label class="block font-medium">Available Rooms</label>
                        <select name="reservations[0][room_id]" class="available-rooms-dropdown w-full border rounded p-2 focus:ring-2 focus:ring-green-500" required>
                            <option value="" selected disabled>Select a room ---</option>
                        </select>
                        <input type="hidden" name="reservations[0][room_type]" class="room-type-input">
                    </div>

                    <!-- Room Number -->
                    <div>
                        <label class="block font-medium">Room Number</label>
                        <input type="text" name="reservations[0][room_number]" 
                            class="room-number-input w-full border rounded p-2 bg-gray-50" 
                            placeholder="Room number auto-filled..." readonly required>
                    </div>

                    <!-- Price -->
                    <div>
                        <label class="block font-medium">Price per Night (₱)</label>
                        <input type="number" name="reservations[0][price_per_night]" 
                            class="price-per-night w-full border rounded p-2" readonly required>
                    </div>

                    <!-- Guests -->
                    <div>
                        <label class="block font-medium">Number of Guests</label>
                        <input type="number" name="reservations[0][num_guests]" 
                            class="num-guests w-full border rounded p-2" value="1" min="1" disabled required>
                        <p class="text-red-500 text-xs mt-1 guest-error hidden">Exceeds room capacity!</p>
                    </div>

                    <!-- Seniors -->
                    <div>
                        <label class="block font-medium">Number of Seniors</label>
                        <input type="number" name="reservations[0][num_seniors]" 
                            class="num-senior w-full border rounded p-2" value="0" min="0">
                    </div>

                </div>


                <!-- Delete Room Block -->
                <div class="mt-2 text-right">
                    <button type="button" class="delete-room-block text-xs text-red-500 hover:underline">Remove Room</button>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <button type="button" id="add-room" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Add Another Room</button>
        </div>

        <hr class="my-4">

        <div class="mt-6">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded hover:bg-blue-700">Create Walk-In Booking</button>
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

    let latestAvailableRooms = [];

    // =====================================================
    // 🟢 AJAX: FETCH AVAILABLE ROOMS
    // =====================================================
    function fetchAvailableRooms() {

        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;

        if (!checkIn || !checkOut) return;

        fetch("{{ route('frontdesk.available') }}", {
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
        })
        .catch(error => {
            console.error("Room availability fetch error:", error);
        });
    }

    // =====================================================
    // 🟢 UPDATE ALL DROPDOWNS FROM AJAX DATA
    // =====================================================
    function updateAllDropdownOptions() {

        document.querySelectorAll('.available-rooms-dropdown').forEach(dropdown => {

            const currentValue = dropdown.value;
            dropdown.innerHTML = '<option value="" disabled selected>Select a room ---</option>';

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
        const deleteContainer = block.querySelector('.mt-2.text-right');

        deleteContainer.innerHTML = '';

        if (!isFirstBlock) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'delete-room-block text-xs text-red-500 hover:underline';
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
    // 🔥 TRIGGER AJAX WHEN DATES CHANGE
    // =====================================================
    checkInInput.addEventListener('change', fetchAvailableRooms);
    checkOutInput.addEventListener('change', fetchAvailableRooms);

    // Load availability on page load
    fetchAvailableRooms();

});
</script>
@endpush
