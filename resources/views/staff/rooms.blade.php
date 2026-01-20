@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/roomManagement.css') }}">
@section('title', 'Admin - Room Management')
@section('page-title', 'Room Management')

@section('content')

<!-- ==================== Top: Global Overview Section ==================== -->
<section class="overview-section">
    <div class="analytics-grid">
        <!-- Analytics Cards -->
        <div class="analytics-card total">
            <h3>Total Rooms</h3>
            <p class="count">{{ $totalRooms }}</p>
        </div>
        <div class="analytics-card active-bookings">
            <h3>Occupied Rooms</h3>
            <p class="count">{{ $occupiedRooms }}</p>
        </div>
        <div class="analytics-card maintenance">
            <h3>Under Maintenance</h3>
            <p class="count">{{ $maintenanceRooms }}</p>
        </div>
        <div class="analytics-card cleaning">
            <h3>Cleaning</h3>
            <p class="count">{{ $cleaningRooms }}</p>
        </div>
    </div>

    <!-- Room Type Cards -->
    <div class="room-type-grid">
        @foreach($prices as $type => $price)
            <div class="room-type-card">
                <div class="room-type-image">
                    <img src="{{ asset('image/roomtypes/'.$type.'.jpg') }}" alt="{{ ucfirst($type) }}">
                </div>
                <div class="room-type-info">
                    <strong>{{ ucfirst($type) }}</strong>
                    <p>₱{{ number_format($price) }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>


<!-- ==================== Middle: Rooms Display Section ==================== -->
<section class="rooms-display">
    <div class="filter-bar mb-4 flex gap-3 items-center">
        <label class="font-medium">Filter by status:</label>
        <select id="roomStatusFilter" class="border rounded px-2 py-1">
            <option value="all">All Rooms</option>
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="maintenance">Under Maintenance</option>
        </select>
    </div>
    <h1>Rooms</h1><br>
    <div class="room-card-grid">
        @foreach($rooms as $room)
            <div class="room-card bg-white rounded-lg shadow hover:shadow-lg transition cursor-pointer relative"
                data-room-id="{{ $room->id }}">

                <div class="p-4 text-center">
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Room {{ $room->room_number }}</h2>
                    <p class="text-gray-500">{{ ucfirst($room->room_type) }} - {{ ucfirst($room->wing) }}</p>
                    <p class="room-status {{ $room->status }} mt-2">{{ ucfirst($room->status) }}</p>
                    <p class="text-sm text-gray-400 mt-1">
                        Updated {{ $room->updated_at->diffForHumans() }}
                    </p>
                </div>
                <button class="btn-update bg-blue-500 text-white px-1 py-1 rounded" data-id="{{ $room->id }}">Edit</button>
            </div>
        @endforeach
    </div>
</section>


<!-- ==================== Bottom: Add New Room Section ==================== -->
<section class="form-section">
    <h2 class="form-title">Add Room</h2>
    <form id="add-room-form" class="room-form" action="{{ route('staff.rooms.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="room-number">Room Number</label>
            <input type="text" id="room-number" name="room_number" required>
        </div>
        <div class="form-group">
            <label for="room-type">Room Type</label>
            <select id="room-type" name="room_type" required>
                <option value="" disabled selected hidden>-- Select Room Type --</option>
                @foreach($prices as $type => $price)
                    <option value="{{ $type }}" data-price="{{ $price }}">{{ ucfirst($type) }} - ₱{{ number_format($price) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="wing">Wing</label>
            <select id="wing" name="wing" required>
                <option value="" disabled selected hidden>-- Select Wing --</option>
                <option value="rooster">Rooster</option>
                <option value="tumana">Tumana</option>
                <option value="chev_re">Chev Re</option>
                <option value="torii">Torii</option>
            </select>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" id="price" name="price" readonly step="0.01">
        </div>
        <button type="submit" class="btn-submit">Add Room</button>
    </form>
</section>


<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- ==================== Update Room Modal ==================== -->
<div class="modal fade" id="roomEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form id="roomEditForm">
        <div class="modal-header">
          <h5 class="modal-title">Edit Room</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="room_id" id="editRoomId">
          <div class="mb-3">
            <label class="form-label">Room Number</label>
            <input type="text" name="room_number" id="editRoomNumber" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Room Type</label>
            <select name="room_type" id="editRoomType" class="form-select" required>
              @foreach($prices as $type => $price)
                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Wing</label>
            <select id="editWing" name="wing" class="form-control" required>
                <option value="" disabled selected hidden>-- Select Wing --</option>
                <option value="rooster">Rooster</option>
                <option value="tumana">Tumana</option>
                <option value="chev_re">Chev Re</option>
                <option value="torii">Torii</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Price (₱)</label>
            <input type="number" step="0.01" name="price" id="editPrice" class="form-control" readonly required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- ==================== Room Bookings Modal ==================== -->
<div class="modal fade" id="occupancyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Current Occupant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="occupancyModalBody">
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
