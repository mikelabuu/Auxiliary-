@extends('layouts.frontdesk')
@section('title', 'Front Desk · Rooms')

@section('content')

{{-- Status overview --}}
<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-admin.ui.stat-card icon="bed" label="Total Rooms" delay="0">
        {{ $totalRooms }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="users" color="palay" label="Occupied" delay="40">
        {{ $occupiedRooms }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="wrench" color="ember" label="Under Maintenance" delay="80">
        {{ $maintenanceRooms }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="droplet" color="sky" label="Cleaning" delay="120">
        {{ $cleaningRooms }}
    </x-admin.ui.stat-card>
</div>

{{-- Room types & nightly rates --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <x-admin.ui.icon name="tag" />
            Room types &amp; rates
        </h3>
        <span class="section-label">Per night</span>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($prices as $type => $price)
                <div class="group overflow-hidden rounded-xl border border-stone-200 bg-white shadow-subtle transition-shadow duration-300 hover:shadow-card-lg">
                    <div class="aspect-[4/3] overflow-hidden bg-stone-100">
                        {{-- image/roomtypes/*.jpg are byte-identical copies of the
                             image/*.jpg that config/room_types.php points at.
                             Using the config's path keeps one canonical source. --}}
                        <x-img src="image/{{ $type }}.jpg" alt="{{ ucfirst($type) }} room"
                               loading="lazy" decoding="async" sizes="(max-width: 640px) 50vw, 220px"
                               class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    </div>
                    <div class="flex items-baseline justify-between gap-2 px-3 py-2.5">
                        <p class="truncate text-sm font-semibold text-ink">{{ ucfirst($type) }}</p>
                        <p class="shrink-0 font-data text-sm font-bold text-g-700 tabnum">₱{{ number_format($price) }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Rooms board --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <x-admin.ui.icon name="bed" />
            Rooms
        </h3>
        <span class="section-label">{{ $totalRooms }} rooms · {{ $availableRooms }} available</span>
    </div>
    <div class="card-body">
        <div class="filter-toolbar mb-5">
            <div class="filter-search">
                <x-admin.ui.icon name="search" stroke-width="2" />
                <input type="text" id="roomSearch" placeholder="Search room number, type or wing" autocomplete="off">
            </div>
            <select id="roomStatusFilter" class="filter-select" aria-label="Filter by status">
                <option value="all">All statuses</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Under Maintenance</option>
                <option value="cleaning">Cleaning</option>
            </select>
            <button type="button" id="roomFilterClear" class="filter-clear">
                <x-admin.ui.icon name="x" stroke-width="2.5" />
                Clear
            </button>
        </div>

        @php
            $statusMeta = [
                'available'   => ['bar' => 'bg-g-500'],
                'occupied'    => ['bar' => 'bg-au-500'],
                'maintenance' => ['bar' => 'bg-ember-500'],
                'cleaning'    => ['bar' => 'bg-sky-500'],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            @foreach($rooms as $room)
                <div class="room-card group/card relative cursor-pointer overflow-hidden rounded-xl border border-stone-200 bg-white shadow-subtle hover:border-clsu-200 hover:shadow-card-lg"
                     data-room-id="{{ $room->id }}"
                     data-room-number="{{ strtolower($room->room_number) }}"
                     data-type="{{ $room->room_type }}"
                     data-wing="{{ $room->wing }}">
                    <div class="status-bar h-1 {{ ($statusMeta[$room->status] ?? $statusMeta['available'])['bar'] }}"></div>
                    <div class="flex flex-col items-center gap-2 p-4 pb-3 text-center">
                        <div>
                            <p class="font-data text-base font-extrabold text-stone-900 tabnum">Room {{ $room->room_number }}</p>
                            <p class="mt-0.5 text-2xs font-bold uppercase tracking-wide text-faint">{{ ucfirst($room->room_type) }} · {{ ucfirst($room->wing) }} wing</p>
                        </div>
                        <span class="room-status status status-{{ $room->status }}">{{ ucfirst($room->status) }}</span>
                        <p class="text-2xs italic text-faint">Updated {{ $room->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center justify-center gap-1.5 border-t border-stone-100 px-4 py-2 text-2xs font-semibold text-faint transition-colors group-hover/card:bg-clsu-50/60 group-hover/card:text-clsu-600">
                        <x-admin.ui.icon name="eye" class="h-3 w-3" />
                        View occupancy
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Inline display:none — .empty-state is unlayered and would beat a
             Tailwind `hidden` utility; jQuery .toggle() drives it instead. --}}
        <x-admin.ui.empty-state id="roomsEmpty" style="display:none" icon="search" title="No rooms match the current filters." />
    </div>
</div>

{{-- Occupancy modal (admin modal system; opened by the jQuery below) --}}
<x-admin.ui.modal id="occupancyModal" icon="user" title="Current occupant">
    <div class="modal-body" id="occupancyModalBody">
        <p class="py-2 text-center text-sm text-muted">Loading…</p>
    </div>
</x-admin.ui.modal>

@endsection

@push('scripts')
<script>
$(function() {
    const base = "{{ url('staff/rooms') }}";

    // ------------------- ROOM CARD CLICK (Show Booking Info) -------------------
    $(document).on('click', '.room-card', function(e) {
        const roomId = $(this).data('room-id');

        $.get(`${base}/${roomId}/occupancy`)
            .done(function(res) {
                if (!res.success) return alert('Could not fetch bookings');
                let modalBody = $('#occupancyModalBody');
                modalBody.empty();

                if (res.bookings.length === 0) {
                    modalBody.append('<p class="py-4 text-center text-sm text-muted">No active bookings for this room.</p>');
                } else {
                    res.bookings.forEach(b => {
                        modalBody.append(`
                            <div class="booking-entry record-detail-panel mb-3">
                                <div class="record-detail-row"><span class="record-detail-label">Booking</span><span class="record-detail-value ref-code">BK-${String(b.id).padStart(4, '0')}</span></div>
                                <div class="record-detail-row"><span class="record-detail-label">Guest</span><span class="record-detail-value">${b.guest_name}</span></div>
                                <div class="record-detail-row"><span class="record-detail-label">Check-in</span><span class="record-detail-value">${b.check_in_formatted}</span></div>
                                <div class="record-detail-row"><span class="record-detail-label">Check-out</span><span class="record-detail-value">${b.check_out_formatted}</span></div>
                                <div class="record-detail-row"><span class="record-detail-label">Status</span><span class="status status-${String(b.status).toLowerCase()}">${b.status}</span></div>
                            </div>
                        `);
                    });
                }

                openModal('occupancyModal');
            })
            .fail(() => alert('Error fetching booking info'));
    });

    // ------------------- ROOM FILTERING (status + search combined) -------------------
    function applyRoomFilters() {
        const status = $('#roomStatusFilter').val();
        const q = ($('#roomSearch').val() || '').trim().toLowerCase();

        $('.room-card').each(function() {
            const cardStatus = $(this).find('.room-status').text().trim().toLowerCase();
            const hay = [$(this).data('room-number'), $(this).data('type'), $(this).data('wing')].join(' ').toLowerCase();

            const okStatus = status === 'all' || cardStatus === status;
            const okSearch = !q || hay.includes(q);
            $(this).toggle(okStatus && okSearch);
        });

        $('#roomsEmpty').toggle($('.room-card:visible').length === 0);
    }

    $('#roomStatusFilter').on('change', applyRoomFilters);
    $('#roomSearch').on('input', applyRoomFilters);
    $('#roomFilterClear').on('click', function() {
        $('#roomSearch').val('');
        $('#roomStatusFilter').val('all');
        applyRoomFilters();
    });
});

// Real-time push: the board reflects check-ins, check-outs and housekeeping
// flips made anywhere else (admin console, another desk) without staff having
// to refresh. Deferred while a modal or filter is in use — see live-refresh.js.
window.liveRefresh([
    { channel: 'rooms',    event: 'RoomStatusChanged' },
    { channel: 'bookings', event: 'BookingChanged' },
]);
</script>
@endpush
