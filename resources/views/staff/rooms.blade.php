@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/roomManagement.css') }}">
@section('title', 'Admin - Room Management')
@section('page-title', 'Room Management')

@section('content')

<!-- ==================== Top: Global Overview Section ==================== -->
<section class="mb-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-card hoverable="true" title="Total Rooms" icon="hotel">
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $totalRooms }}</p>
        </x-card>
        <x-card hoverable="true" title="Occupied Rooms" icon="book_online">
            <p class="text-3xl font-extrabold text-blue-600 mt-1">{{ $occupiedRooms }}</p>
        </x-card>
        <x-card hoverable="true" title="Under Maintenance" icon="construction">
            <p class="text-3xl font-extrabold text-red-600 mt-1">{{ $maintenanceRooms }}</p>
        </x-card>
        <x-card hoverable="true" title="Cleaning" icon="cleaning_services">
            <p class="text-3xl font-extrabold text-amber-600 mt-1">{{ $cleaningRooms }}</p>
        </x-card>
    </div>

    <!-- Room Type Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
        @foreach($prices as $type => $price)
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                <div class="h-28 overflow-hidden bg-gray-50">
                    <img src="{{ asset('image/roomtypes/'.$type.'.jpg') }}" alt="{{ ucfirst($type) }}" class="w-full h-full object-cover transition duration-300 hover:scale-105">
                </div>
                <div class="p-4 flex flex-col justify-between">
                    <strong class="text-gray-800 text-sm tracking-wide">{{ ucfirst($type) }}</strong>
                    <p class="text-emerald-700 font-bold text-base mt-1">₱{{ number_format($price) }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>


<!-- ==================== Middle: Rooms Display Section ==================== -->
<section class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rooms</h1>
            <p class="text-gray-500 text-xs font-medium mt-0.5">Manage room availability, wing status, and booking properties.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Filter by status:</span>
            <select id="roomStatusFilter" class="w-48 px-3 py-2 rounded-xl border border-gray-200 bg-white text-gray-800 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-600 cursor-pointer">
                <option value="all">All Rooms</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Under Maintenance</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-12">
        @foreach($rooms as $room)
            @php
                $statusColors = [
                    'available' => 'bg-green-50 text-green-700 border-green-200',
                    'occupied' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'maintenance' => 'bg-red-50 text-red-700 border-red-200',
                    'cleaning' => 'bg-amber-50 text-amber-700 border-amber-200',
                ];
                $colorClass = $statusColors[$room->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
            @endphp
            <div class="room-card bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer overflow-hidden relative group"
                data-room-id="{{ $room->id }}">

                <div class="p-4 text-center flex flex-col items-center h-full justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800 mb-0.5">Room {{ $room->room_number }}</h2>
                        <p class="text-gray-500 text-[10px] font-bold tracking-wide uppercase">{{ ucfirst($room->room_type) }}</p>
                        <p class="text-gray-400 text-[9px] mt-0.5 tracking-wider uppercase font-semibold">Wing: {{ ucfirst($room->wing) }}</p>
                    </div>
                    
                    <div class="mt-2.5">
                        <span class="room-status inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $colorClass }}">
                            {{ ucfirst($room->status) }}
                        </span>
                        
                        <p class="text-[9px] text-gray-400 mt-2.5 italic">
                            Updated {{ $room->updated_at->diffForHumans() }}
                        </p>
                    </div>
                    
                    <div class="mt-3.5 w-full">
                        <x-button variant="primary" class="btn-update w-full text-[11px] py-1.5 rounded-xl font-bold" data-id="{{ $room->id }}">
                            Edit
                        </x-button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>


<!-- ==================== Bottom: Add New Room Section ==================== -->
<section class="max-w-xl mb-12">
    <x-card title="Add New Room" icon="add_box">
        <form id="add-room-form" action="{{ route('staff.rooms.store') }}" method="POST" class="space-y-4">
            @csrf
            
            <x-input label="Room Number" name="room_number" id="room-number" placeholder="Enter Room Number" required />
            
            <x-select label="Room Type" name="room_type" id="room-type" required>
                <option value="" disabled selected hidden>-- Select Room Type --</option>
                @foreach($prices as $type => $price)
                    <option value="{{ $type }}" data-price="{{ $price }}">{{ ucfirst($type) }} - ₱{{ number_format($price) }}</option>
                @endforeach
            </x-select>
            
            <x-select label="Wing" name="wing" id="wing" required>
                <option value="" disabled selected hidden>-- Select Wing --</option>
                <option value="rooster">Rooster</option>
                <option value="tumana">Tumana</option>
                <option value="chev_re">Chev Re</option>
                <option value="torii">Torii</option>
            </x-select>
            
            <x-input label="Price" name="price" id="price" type="number" readonly step="0.01" />
            
            <div class="pt-2">
                <x-button variant="primary" class="w-full rounded-xl">Add Room</x-button>
            </div>
        </form>
    </x-card>
</section>


<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- ==================== Update Room Modal ==================== -->
<div class="modal fade" id="roomEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content rounded-2xl border-0 overflow-hidden shadow-2xl">
      <form id="roomEditForm">
        <div class="modal-header border-b border-gray-100 bg-gray-50/50 px-6 py-4">
          <h5 class="modal-title font-bold text-gray-800 text-base flex items-center gap-2">
            <span class="material-icons text-emerald-600 text-lg">edit</span> Edit Room
          </h5>
          <button type="button" class="btn-close focus:outline-none" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body px-6 py-4 space-y-4">
          <input type="hidden" name="room_id" id="editRoomId">
          
          <div>
            <label class="block text-xs font-bold text-gray-700 tracking-wider uppercase mb-1.5">Room Number</label>
            <input type="text" name="room_number" id="editRoomNumber" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-800 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-600" required>
          </div>
          
          <div>
            <label class="block text-xs font-bold text-gray-700 tracking-wider uppercase mb-1.5">Room Type</label>
            <select name="room_type" id="editRoomType" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-800 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-600 cursor-pointer" required>
              @foreach($prices as $type => $price)
                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
              @endforeach
            </select>
          </div>
          
          <div>
            <label class="block text-xs font-bold text-gray-700 tracking-wider uppercase mb-1.5">Wing</label>
            <select id="editWing" name="wing" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-800 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-600 cursor-pointer" required>
                <option value="" disabled selected hidden>-- Select Wing --</option>
                <option value="rooster">Rooster</option>
                <option value="tumana">Tumana</option>
                <option value="chev_re">Chev Re</option>
                <option value="torii">Torii</option>
            </select>
          </div>
          
          <div>
            <label class="block text-xs font-bold text-gray-700 tracking-wider uppercase mb-1.5">Price (₱)</label>
            <input type="number" step="0.01" name="price" id="editPrice" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-100 text-gray-500 text-sm cursor-not-allowed outline-none" readonly required>
          </div>
        </div>
        <div class="modal-footer border-t border-gray-100 px-6 py-4 flex gap-2 justify-end">
          <x-button type="button" variant="neutral" class="rounded-xl px-4 py-2" data-bs-dismiss="modal">Cancel</x-button>
          <x-button type="submit" variant="primary" class="rounded-xl px-4 py-2">Save changes</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- ==================== Room Bookings Modal ==================== -->
<div class="modal fade" id="occupancyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 overflow-hidden shadow-2xl">
            <div class="modal-header border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                <h5 class="modal-title font-bold text-gray-800 text-base flex items-center gap-2">
                    <span class="material-icons text-emerald-600 text-lg">info</span> Current Occupant
                </h5>
                <button type="button" class="btn-close focus:outline-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-6 py-5" id="occupancyModalBody">
                <p class="text-center text-gray-500">Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (optional if you use it for AJAX) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(function() {
    const base = "{{ url('staff/rooms') }}";
    const priceMap = @json($prices);

    // ------------------- ROOM CARD CLICK (Show Booking Info) -------------------
    $(document).on('click', '.room-card', function(e) {
        const roomId = $(this).data('room-id');
        if ($(e.target).closest('.btn-update, .btn-delete').length) return;

        $.get(`${base}/${roomId}/occupancy`)
            .done(function(res) {
                if (!res.success) return alert('Could not fetch bookings');
                let modalBody = $('#occupancyModalBody');
                modalBody.empty();

                if (res.bookings.length === 0) {
                    modalBody.append('<p class="text-center text-gray-500">No active bookings for this room.</p>');
                } else {
                    res.bookings.forEach(b => {
                        modalBody.append(`
                            <div class="booking-entry mb-3">
                                <p><strong>Guest:</strong> ${b.guest_name}</p>
                                <p><strong>Check-in:</strong> ${b.check_in_formatted}</p>
                                <p><strong>Check-out:</strong> ${b.check_out_formatted}</p>
                                <p><strong>Status:</strong> ${b.status}</p>
                                <hr>
                            </div>
                        `);
                    });
                }

                const modal = new bootstrap.Modal(document.getElementById('occupancyModal'));
                modal.show();
            })
            .fail(() => alert('Error fetching booking info'));
    });

    // ------------------- ROOM UPDATE -------------------
    $(document).on('click', '.btn-update', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const roomId = $(this).data('id');

        $.get(`${base}/${roomId}/edit`)
            .done(function(res) {
                if (!res.success) return alert('Could not load room');
                const room = res.room;

                $('#editRoomId').val(room.id);
                $('#editRoomNumber').val(room.room_number);
                $('#editRoomType').val(room.room_type);
                $('#editWing').val(room.wing);
                $('#editPrice').val(room.price ?? priceMap[room.room_type] ?? '');

                const modal = new bootstrap.Modal(document.getElementById('roomEditModal'));
                modal.show();
            });
    });

    $('#editRoomType').on('change', function() {
        const type = $(this).val();
        if (priceMap[type] !== undefined) $('#editPrice').val(priceMap[type]);
    });

    $('#roomEditForm').on('submit', function(e) {
        e.preventDefault();
        const roomId = $('#editRoomId').val();
        const payload = {
            room_number: $('#editRoomNumber').val(),
            room_type: $('#editRoomType').val(),
            wing: $('#editWing').val(),
            price: $('#editPrice').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: `${base}/${roomId}`,
            method: 'PUT',
            data: payload,
            success: function(res) {
                if (!res.success) return alert('Update failed');
                location.reload();
            },
            error: function() { alert('Update failed'); }
        });
    });

    // ------------------- AUTO-FILL PRICE -------------------
    $('#room-type').on('change', function(){
        const price = $(this).find(':selected').data('price');
        $('#price').val(price);
    });

    // ------------------- ROOM FILTERING -------------------
    $('#roomStatusFilter').on('change', function() {
        const selected = $(this).val();

        $('.room-card').each(function() {
            const status = $(this).find('.room-status').text().trim().toLowerCase();

            if (selected === 'all' || status === selected) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });


});
</script>

@endsection
