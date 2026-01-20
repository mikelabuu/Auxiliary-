@extends('layouts.frontdesk')
@section('title', 'Front Desk - Walk-in Booking')
@section('content')

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
                <input type="number" name="expected_guests" value="{{ old('expected_guests', 1) }}" min="1" class="w-full border rounded p-2" required>
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

        <hr class="my-4">

        <!-- Room Reservations -->
        <h2 class="text-xl font-bold mb-2">Room Reservations</h2>
        <div id="reservations-container" class="space-y-4">
            <div class="reservation-block p-4 border rounded space-y-2">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block font-medium">Room Type</label>
                        <select name="reservations[0][room_type]" class="w-full border rounded p-2 room-type" required>
                            <option value="double">Double</option>
                            <option value="triple">Triple</option>
                            <option value="quadruple">Quadruple</option>
                            <option value="deluxe">Deluxe</option>
                            <option value="dormitory1">Dormitory 1</option>
                            <option value="dormitory2">Dormitory 2</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium">Room Number(s) (comma separated)</label>
                        <input type="text" name="reservations[0][room_number]" class="w-full border rounded p-2 room-number" required>
                        <button type="button" class="mt-1 px-2 py-1 bg-blue-500 text-white rounded text-sm auto-fill">Auto Assign</button>
                    </div>
                    <div>
                        <label class="block font-medium">Price per Night (₱)</label>
                        <input type="number" name="reservations[0][price_per_night]" class="w-full border rounded p-2 price-per-night" required>
                    </div>
                    <div>
                        <label class="block font-medium">Number of Guests</label>
                        <input type="number" name="reservations[0][num_guests]" value="1" min="1" class="w-full border rounded p-2" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex space-x-2">
            <button type="button" id="add-reservation-block" class="px-4 py-2 bg-green-500 text-white rounded">Add Room Block</button>
        </div>

        <hr class="my-4">

        <!-- Simulate Payment -->
        <div class="flex items-center space-x-4">
            <label class="font-medium">Simulate Payment:</label>
            <select name="simulate_payment_success" class="border rounded p-2">
                <option value="1">Success</option>
                <option value="0">Fail</option>
            </select>
        </div>

        <div class="mt-6">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded hover:bg-blue-700">Create Walk-In Booking</button>
        </div>

    </form>

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Add Room Block
    document.getElementById('add-reservation-block').addEventListener('click', function() {
        const container = document.getElementById('reservations-container');
        const index = container.children.length;
        const newBlock = container.children[0].cloneNode(true);

        newBlock.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if(name) input.setAttribute('name', name.replace(/\d+/, index));
            if(input.type !== 'select-one') input.value = '';
        });

        container.appendChild(newBlock);
    });

    // Auto Assign Button (event delegation)
    document.getElementById('reservations-container').addEventListener('click', function(e) {
        if(e.target.classList.contains('auto-fill')) {
            const block = e.target.closest('.reservation-block');
            const input = block.querySelector('.room-number');
            // Example: auto-fill with mock values
            input.value = '101,102';
        }
    });

    // Optional: Fetch available rooms (update your HTML with #available_rooms if needed)
    const roomTypeSelect = document.querySelector('.room-type');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');

    async function fetchAvailableRooms() {
        const roomType = roomTypeSelect.value;
        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;
        const container = document.getElementById('available_rooms');
        if(!container) return;

        if (!roomType || !checkIn || !checkOut) return;
        try {
            const res = await fetch(`/staff/front-desk/available-rooms?room_type=${roomType}&check_in=${checkIn}&check_out=${checkOut}`);
            const data = await res.json();
            container.innerHTML = '';
            if(data.rooms.length === 0){
                container.innerHTML = '<p>No available rooms for this type/date.</p>';
                return;
            }
            data.rooms.forEach(room => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'room-btn p-2 m-1 border rounded';
                btn.textContent = `${room.room_number} (${room.status})`;
                btn.disabled = room.status !== 'available';
                btn.addEventListener('click', () => {
                    const input = document.querySelector('.room-number');
                    let current = input.value ? input.value.split(',') : [];
                    if(!current.includes(room.room_number)) current.push(room.room_number);
                    input.value = current.join(',');
                });
                container.appendChild(btn);
            });
        } catch(err){
            console.error(err);
            container.innerHTML = '<p>Error fetching rooms.</p>';
        }
    }

    // Uncomment if you want live fetch on change
    // roomTypeSelect.addEventListener('change', fetchAvailableRooms);
    // checkInInput.addEventListener('change', fetchAvailableRooms);
    // checkOutInput.addEventListener('change', fetchAvailableRooms);

});
</script>
@endsection
