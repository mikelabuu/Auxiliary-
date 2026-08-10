@props(['room'])

{{--
    Room Status Map tile — original flat-button design.
    Accessibility: border-style patterns differentiate statuses
    beyond color alone (solid / dashed / dotted / double border).
    Tooltip: number · type · status [ · occupant].
--}}

@php
    // Each variant includes a border-style Tailwind class so
    // the pattern — not just the color — signals the status.
    // solid   = available / occupied (already visually distinct)
    // dashed  = reserved   (booking placed, not yet arrived)
    // dotted  = cleaning   (transitional state)
    // border-2 double style = maintenance (alert/attention state)
    $variants = [
        'available'   => 'bg-clsu-50  text-clsu-800  border-clsu-200  hover:bg-clsu-100  hover:border-clsu-300  border-solid',
        'occupied'    => 'bg-clsu-700 text-white      border-clsu-700  hover:bg-clsu-800                         border-solid',
        'reserved'    => 'bg-palay-100 text-palay-800 border-palay-300 hover:bg-palay-200                        border-dashed',
        // Held but unpaid — amber like reserved, dashed and lighter, because
        // this one can still lapse and the desk may want to chase it.
        'pending'     => 'bg-palay-50 text-palay-700  border-palay-400 hover:bg-palay-100                        border-dashed',
        'cleaning'    => 'bg-sky-50   text-sky-800   border-sky-300   hover:bg-sky-100   hover:border-sky-400   border-dotted',
        'maintenance' => 'bg-ember-50 text-ember-800 border-ember-300 hover:bg-ember-100                         border-double border-[3px]',
    ];

    // Short forms, because these are read off a 80px tile at the console's
    // 13px floor — 'Dormitory' was the one label that had to ellipsise, and
    // widening every tile in the building to fit four dorm rooms is the wrong
    // trade. 'Dorm' is what the desk says out loud anyway.
    $typeLabels = [
        'dormitory1' => 'Dorm',
        'dormitory2' => 'Dorm',
        'double'     => '2-bed',
        'triple'     => '3-bed',
        'quadruple'  => '4-bed',
        'deluxe'     => 'Deluxe',
    ];

    $statusLabels = [
        'available'   => 'Available',
        'occupied'    => 'Occupied',
        'reserved'    => 'Reserved',
        'pending'     => 'Reserved · awaiting payment',
        'cleaning'    => 'Cleaning',
        'maintenance' => 'Maintenance',
    ];

    $status      = $room['display_status'];
    $btnClass    = $variants[$status]    ?? $variants['available'];
    $typeLabel   = $typeLabels[$room['room_type']]  ?? 'Room';
    $statusLabel = $statusLabels[$status] ?? ucfirst($status);

    // Tooltip: number · room-type · status [ · occupant name] [ · Last Updated]
    $title = $room['room_number'] . ' · ' . $typeLabel . ' · ' . $statusLabel;
    if (!empty($room['occupant'])) {
        $title .= ' · ' . $room['occupant'];
    }
    if (!empty($room['updated_at'])) {
        $title .= ' · Updated ' . $room['updated_at'];
    }
@endphp

{{-- Element is a div (role=button), not a <button>, so the expanded bento card
     can legally contain links (guest history, "Manage rooms"). data-bento-item
     + data-bento-detail-src makes the whole tile morph into an occupancy card
     (expandable-bento.js), rendered by the roomOccupancy renderer registered
     once below. The real-time feed keeps patching .room-map-btn live. --}}
<div role="button" tabindex="0"
        data-room-btn="{{ $room['id'] }}"
        data-display-status="{{ $status }}"
        data-room-number="{{ $room['room_number'] }}"
        data-bento-item data-bento-flush data-bento-narrow data-bento-detail-fresh
        data-bento-detail-src="{{ url('staff/rooms/'.$room['id'].'/occupancy') }}"
        data-bento-render="roomOccupancy"
        aria-expanded="false"
        aria-label="Room {{ $room['room_number'] }} — {{ $statusLabel }}. Show occupancy."
        @if(!empty($room['occupant'])) data-occupant="{{ $room['occupant'] }}" @endif
        title="{{ $title }}"
        class="room-map-btn relative w-20 h-14 px-1.5 rounded-xl border font-data flex flex-col items-center justify-center cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40 {{ $btnClass }}">
    {{-- Number and type together. The type used to live only in `title`, which
         is unreachable on the tablets the desk carries and skipped by screen
         readers — so "what kind of room is 210?" needed a trip to another page.
         Now the rows are wings, a tile's type is no longer implied by which
         row it sits in, which makes this load-bearing rather than decorative. --}}
    <span data-bento-summary class="room-map-btn__num">{{ $room['room_number'] }}</span>
    <span class="room-map-btn__type">{{ $typeLabel }}</span>
    <span data-status-dot class="absolute top-1.5 right-1.5 {{
        $status === 'available' ? 'w-1.5 h-1.5 rounded-full border border-clsu-400' : (
        $status === 'occupied' ? 'w-1.5 h-1.5 rounded-full bg-white' : (
        $status === 'reserved' ? 'w-1.5 h-1.5 rounded-full border border-dashed border-palay-500' : (
        $status === 'pending' ? 'w-1.5 h-1.5 rounded-full border border-dashed border-palay-400' : (
        $status === 'cleaning' ? 'w-1.5 h-1.5 rounded-full border border-dotted border-sky-500' : (
        $status === 'maintenance' ? 'w-1.5 h-1.5 bg-ember-500 rotate-45' : ''
        )))))
    }}" aria-hidden="true"></span>
    <div data-bento-detail></div>
</div>

@once
@push('scripts')
<script>
// Room occupancy → the expanded bento card's body. Registered once (the tile
// renders many times). Consumes the staff/rooms/{id}/occupancy JSON the engine
// fetches per tile; the guest names reuse the layout's .guest-history-link modal.
(function () {
    window.BentoDetail = window.BentoDetail || { renderers: {} };
    const CHIP = {
        available: 'bg-clsu-50 text-clsu-800 border-clsu-200',
        occupied: 'bg-clsu-700 text-white border-clsu-700',
        reserved: 'bg-palay-100 text-palay-800 border-palay-300',
        pending: 'bg-palay-50 text-palay-700 border-palay-400',
        cleaning: 'bg-sky-50 text-sky-800 border-sky-300',
        maintenance: 'bg-ember-50 text-ember-800 border-ember-300',
    };
    const LABELS = { available: 'Available', occupied: 'Occupied', reserved: 'Reserved', pending: 'Reserved · unpaid', cleaning: 'Cleaning', maintenance: 'Maintenance' };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const cap = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);

    function bookingRow(b) {
        const chip = b.timeline === 'current'
            ? '<span class="text-2xs font-bold uppercase tracking-wide text-clsu-700 bg-clsu-50 border border-clsu-200 rounded-full px-1.5 py-0.5">Now</span>'
            : '<span class="text-2xs font-bold uppercase tracking-wide text-palay-800 bg-palay-100 border border-palay-200 rounded-full px-1.5 py-0.5">Upcoming</span>';
        return '<div class="rounded-lg border border-stone-200 bg-stone-50/70 px-3 py-2 mb-2">'
            + '<div class="flex items-center justify-between gap-2">'
            + '<p class="guest-history-link cursor-pointer hover:underline text-sm font-semibold text-stone-800 truncate" data-booking-id="' + esc(b.id) + '" data-bento-dismiss title="View guest history">' + esc(b.guest_name) + '</p>'
            + chip + '</div>'
            + '<p class="text-2xs text-muted mt-0.5 font-data tabnum">' + esc(b.check_in_formatted) + ' – ' + esc(b.check_out_formatted)
            + ' · ' + esc(b.nights) + (b.nights == 1 ? ' night' : ' nights') + ' · ' + esc(b.status) + '</p>'
            + '</div>';
    }

    window.BentoDetail.renderers.roomOccupancy = function (data, item) {
        const status = item.dataset.displayStatus || 'available';
        const number = esc(item.dataset.roomNumber || '');
        const occupant = item.dataset.occupant ? '<p class="text-xs text-muted mt-1">' + esc(item.dataset.occupant) + '</p>' : '';
        let body;
        if (data && data.success && data.bookings && data.bookings.length) {
            body = data.bookings.map(bookingRow).join('');
        } else {
            body = '<p class="text-2xs text-faint py-1">No current or upcoming stays.</p>';
        }
        return '<div class="flex items-center justify-between gap-3">'
            + '<p class="text-lg font-bold text-stone-900 font-data">Room ' + number + '</p>'
            + '<span class="text-2xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-full border ' + (CHIP[status] || CHIP.available) + '">' + esc(LABELS[status] || cap(status)) + '</span>'
            + '</div>' + occupant
            + '<div class="mt-4 mb-2 text-2xs font-bold uppercase tracking-wider text-faint">Current &amp; upcoming stays</div>'
            + body
            + '<div class="mt-3 pt-3 border-t border-stone-100"><a href="{{ route('staff.rooms') }}" class="text-2xs font-bold text-clsu-700 hover:underline">Manage rooms →</a></div>';
    };
})();
</script>
@endpush
@endonce
