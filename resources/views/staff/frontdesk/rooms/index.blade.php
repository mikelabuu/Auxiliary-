@extends('layouts.frontdesk')
@section('title', 'Front Desk · Rooms')

@section('content')

{{-- Status overview --}}
<div class="grid grid-cols-2 gap-4 xl:grid-cols-5">
    <x-admin.ui.stat-card icon="bed" label="Total Rooms" delay="0">
        {{ $totalRooms }}
    </x-admin.ui.stat-card>
    {{-- value-id: the kebab recomputes these in place after a status flip, so
         the counters cannot disagree with the board underneath them. --}}
    <x-admin.ui.stat-card icon="users" color="palay" label="Occupied" delay="40" value-id="fdStatOccupied">
        {{ $occupiedRooms }}
    </x-admin.ui.stat-card>
    {{-- Booked for tonight, guest not checked in yet. These are not rooms the
         desk can give a walk-in, so they are counted apart from Available. --}}
    <x-admin.ui.stat-card icon="arrival" color="palay" label="Reserved" delay="80" value-id="fdStatReserved">
        {{ $reservedRooms }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="wrench" color="ember" label="Under Maintenance" delay="120" value-id="fdStatMaintenance">
        {{ $maintenanceRooms }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="droplet" color="sky" label="Cleaning" delay="160" value-id="fdStatCleaning">
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
        <span class="section-label">{{ $totalRooms }} rooms · <span id="fdStatAvailable">{{ $availableRooms }}</span> available</span>
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
                <option value="reserved">Reserved</option>
                <option value="pending">Reserved · unpaid</option>
                <option value="maintenance">Under Maintenance</option>
                <option value="cleaning">Cleaning</option>
            </select>
            <button type="button" id="roomFilterClear" class="filter-clear">
                <x-admin.ui.icon name="x" stroke-width="2.5" />
                Clear
            </button>
        </div>

        @php
            // Keyed on the DERIVED status (App\Support\RoomHold::displayStatus),
            // not the raw housekeeping column — 'reserved' and 'pending' come
            // from a booking holding the room tonight.
            $statusMeta = [
                'available'   => ['bar' => 'bg-g-500',     'dot' => 'bg-g-500',     'label' => 'Available'],
                'occupied'    => ['bar' => 'bg-au-500',    'dot' => 'bg-au-500',    'label' => 'Occupied'],
                'reserved'    => ['bar' => 'bg-palay-500', 'dot' => 'bg-palay-500', 'label' => 'Reserved'],
                'pending'     => ['bar' => 'bg-palay-300', 'dot' => 'bg-palay-300', 'label' => 'Reserved · unpaid'],
                'maintenance' => ['bar' => 'bg-ember-500', 'dot' => 'bg-ember-500', 'label' => 'Maintenance'],
                'cleaning'    => ['bar' => 'bg-sky-500',   'dot' => 'bg-sky-500',   'label' => 'Cleaning'],
            ];

            // What the desk's kebab may set — the same three the admin board
            // offers, from the same constant. 'occupied', 'reserved' and
            // 'pending' are derived from the booking lifecycle and are nobody's
            // to hand-pick, here or there.
            $settableStatuses = collect($statusMeta)->only(\App\Models\Room::SETTABLE_STATUSES)->all();
        @endphp
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            @foreach($rooms as $room)
                @php
                    $display = $displayStatuses[$room->id] ?? $room->status;
                    $meta = $statusMeta[$display] ?? $statusMeta['available'];
                @endphp
                {{-- overflow-hidden is gone: it clipped the kebab panel, which
                     hangs below the card. The status bar keeps its rounded top
                     from an explicit rounded-t-xl instead. --}}
                <div class="room-card group/card relative cursor-pointer rounded-xl border border-stone-200 bg-white shadow-subtle hover:border-clsu-200 hover:shadow-card-lg"
                     data-room-id="{{ $room->id }}"
                     data-room-number="{{ strtolower($room->room_number) }}"
                     data-status="{{ $display }}"
                     data-housekeeping="{{ $room->status }}"
                     data-type="{{ $room->room_type }}"
                     data-wing="{{ $room->wing }}">
                    <div class="status-bar h-1 rounded-t-xl {{ $meta['bar'] }}"></div>

                    <div class="absolute right-2 top-2.5 z-10">
                        <x-admin.rooms.status-kebab :room="$room" :settable-statuses="$settableStatuses" />
                    </div>

                    <div class="flex flex-col items-center gap-2 p-4 pb-3 text-center">
                        <div>
                            <p class="font-data text-base font-extrabold text-stone-900 tabnum">Room {{ $room->room_number }}</p>
                            <p class="mt-0.5 text-2xs font-bold uppercase tracking-wide text-faint">{{ ucfirst($room->room_type) }} · {{ ucfirst($room->wing) }} wing</p>
                        </div>
                        <span class="room-status status status-{{ $display }}">{{ $meta['label'] }}</span>
                        <p class="text-2xs italic text-faint">Updated {{ $room->updated_at->diffForHumans() }}</p>
                    </div>
                    {{-- rounded-b-xl replaces the clipping the card's dropped
                         overflow-hidden used to give this hover fill. --}}
                    <div class="flex items-center justify-center gap-1.5 rounded-b-xl border-t border-stone-100 px-4 py-2 text-2xs font-semibold text-faint transition-colors group-hover/card:bg-clsu-50/60 group-hover/card:text-clsu-600">
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
    const STATUS_META = {!! json_encode($statusMeta) !!};

    // The status flip is a PATCH, so unlike the GETs below it needs the token.
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ------------------- ROOM CARD CLICK (Show Booking Info) -------------------
    $(document).on('click', '.room-card', function(e) {
        // The kebab lives inside the card, and the card opens the occupancy
        // modal. Without this, setting a status also popped the modal over it.
        if ($(e.target).closest('.room-kebab-btn, [data-kebab-panel]').length) return;

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
            // Read from the attribute, not the pill's text: the label a status
            // shows and the value it filters on are no longer the same string
            // ("Reserved · unpaid" is `pending`).
            const cardStatus = $(this).attr('data-status');
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

    // ------------------- HOUSEKEEPING STATUS (kebab) -------------------
    // The desk sets available/cleaning/maintenance here. Everything else about
    // a room — adding, editing, repricing, deleting — stays on the admin board.
    $(document).on('click', '.room-kebab-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const panel = $(this).siblings('[data-kebab-panel]');
        const isOpen = !panel.hasClass('hidden');
        $('[data-kebab-panel]').addClass('hidden');
        if (!isOpen) panel.removeClass('hidden');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.room-kebab-btn, [data-kebab-panel]').length) {
            $('[data-kebab-panel]').addClass('hidden');
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') $('[data-kebab-panel]').addClass('hidden');
    });

    $(document).on('click', '.quick-status-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const card = $(this).closest('.room-card');
        const roomId = card.data('room-id');
        const newStatus = $(this).data('status-value').toString();
        const panel = $(this).closest('[data-kebab-panel]');

        $.ajax({ url: `${base}/${roomId}/status`, method: 'PATCH', data: { status: newStatus } })
            .done(function(res) {
                if (!res.success) { window.toast('Could not update status.', 'error'); return; }
                // Marking a room available does not always make the badge read
                // Available — a booking may still hold it tonight — so the card
                // follows the status the server derived, not the one clicked.
                applyStatusToCard(card, res.display_status || newStatus, newStatus);
                panel.addClass('hidden');
                recomputeCounts();
                applyRoomFilters();
                window.toast('Room ' + res.room.room_number + ' marked ' + STATUS_META[newStatus].label.toLowerCase() + '.');
            })
            .fail(function(xhr) {
                window.toast(xhr.responseJSON?.message || 'Could not update status.', 'error');
            });
    });

    /**
     * `display` is what the board shows and what every count and filter reads;
     * `housekeeping` is the rooms.status column the kebab writes and ticks. The
     * two differ whenever a booking holds a room housekeeping calls available.
     */
    function applyStatusToCard(card, display, housekeeping) {
        const meta = STATUS_META[display] || STATUS_META.available;
        card.attr('data-status', display);
        if (housekeeping) card.attr('data-housekeeping', housekeeping);
        card.find('.status-bar').attr('class', 'status-bar h-1 rounded-t-xl ' + meta.bar);
        card.find('.room-status').attr('class', 'room-status status status-' + display).text(meta.label);

        const owned = housekeeping || card.attr('data-housekeeping');
        card.find('.quick-status-btn').each(function() {
            $(this).find('.quick-status-check').toggleClass('invisible', $(this).data('status-value').toString() !== owned);
        });
    }

    /** Recount from the cards themselves, so the tiles cannot drift from the board. */
    function recomputeCounts() {
        const by = {};
        $('.room-card').each(function() {
            const s = $(this).attr('data-status');
            by[s] = (by[s] || 0) + 1;
        });
        const n = (k) => by[k] || 0;

        $('#fdStatOccupied').text(n('occupied'));
        // Reserved and unpaid-hold are one figure here: neither is a room the
        // desk can hand a walk-in. Matches FrontDeskRoomController::index.
        $('#fdStatReserved').text(n('reserved') + n('pending'));
        $('#fdStatMaintenance').text(n('maintenance'));
        $('#fdStatCleaning').text(n('cleaning'));
        $('#fdStatAvailable').text(n('available'));
    }
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
