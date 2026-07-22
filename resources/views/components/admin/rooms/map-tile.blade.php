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
        'occupied'    => 'bg-clsu-600 text-white      border-clsu-700  hover:bg-clsu-700                         border-solid',
        'reserved'    => 'bg-palay-100 text-palay-800 border-palay-300 hover:bg-palay-200                        border-dashed',
        'cleaning'    => 'bg-sky-50   text-sky-800   border-sky-300   hover:bg-sky-100   hover:border-sky-400   border-dotted',
        'maintenance' => 'bg-ember-50 text-ember-800 border-ember-300 hover:bg-ember-100                         border-double border-[3px]',
    ];

    $typeLabels = [
        'dormitory1' => 'Dormitory',
        'dormitory2' => 'Dormitory',
        'double'     => '2-bed',
        'triple'     => '3-bed',
        'quadruple'  => '4-bed',
        'deluxe'     => 'Deluxe',
    ];

    $statusLabels = [
        'available'   => 'Available',
        'occupied'    => 'Occupied',
        'reserved'    => 'Reserved',
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
        class="room-map-btn relative w-16 h-12 rounded-xl border text-[13px] font-bold font-data flex items-center justify-center cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40 {{ $btnClass }}">
    <span data-bento-summary>{{ $room['room_number'] }}</span>
    <span data-status-dot class="absolute top-1.5 right-1.5 {{
        $status === 'available' ? 'w-1.5 h-1.5 rounded-full border border-clsu-400' : (
        $status === 'occupied' ? 'w-1.5 h-1.5 rounded-full bg-white' : (
        $status === 'reserved' ? 'w-1.5 h-1.5 rounded-full border border-dashed border-palay-500' : (
        $status === 'cleaning' ? 'w-1.5 h-1.5 rounded-full border border-dotted border-sky-500' : (
        $status === 'maintenance' ? 'w-1.5 h-1.5 bg-ember-500 rotate-45' : ''
        ))))
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
        occupied: 'bg-clsu-600 text-white border-clsu-700',
        reserved: 'bg-palay-100 text-palay-800 border-palay-300',
        cleaning: 'bg-sky-50 text-sky-800 border-sky-300',
        maintenance: 'bg-ember-50 text-ember-800 border-ember-300',
    };
    const LABELS = { available: 'Available', occupied: 'Occupied', reserved: 'Reserved', cleaning: 'Cleaning', maintenance: 'Maintenance' };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const cap = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);

    function bookingRow(b) {
        const chip = b.timeline === 'current'
            ? '<span class="text-[9px] font-bold uppercase tracking-wide text-clsu-700 bg-clsu-50 border border-clsu-200 rounded-full px-1.5 py-0.5">Now</span>'
            : '<span class="text-[9px] font-bold uppercase tracking-wide text-palay-800 bg-palay-100 border border-palay-200 rounded-full px-1.5 py-0.5">Upcoming</span>';
        return '<div class="rounded-lg border border-stone-200 bg-stone-50/70 px-3 py-2 mb-2">'
            + '<div class="flex items-center justify-between gap-2">'
            + '<p class="guest-history-link cursor-pointer hover:underline text-sm font-semibold text-stone-800 truncate" data-booking-id="' + esc(b.id) + '" data-bento-dismiss title="View guest history">' + esc(b.guest_name) + '</p>'
            + chip + '</div>'
            + '<p class="text-[11px] text-stone-500 mt-0.5 font-data tabnum">' + esc(b.check_in_formatted) + ' – ' + esc(b.check_out_formatted)
            + ' · ' + esc(b.nights) + (b.nights == 1 ? ' night' : ' nights') + ' · ' + esc(b.status) + '</p>'
            + '</div>';
    }

    window.BentoDetail.renderers.roomOccupancy = function (data, item) {
        const status = item.dataset.displayStatus || 'available';
        const number = esc(item.dataset.roomNumber || '');
        const occupant = item.dataset.occupant ? '<p class="text-xs text-stone-500 mt-1">' + esc(item.dataset.occupant) + '</p>' : '';
        let body;
        if (data && data.success && data.bookings && data.bookings.length) {
            body = data.bookings.map(bookingRow).join('');
        } else {
            body = '<p class="text-[13px] text-stone-400 py-1">No current or upcoming stays.</p>';
        }
        return '<div class="flex items-center justify-between gap-3">'
            + '<p class="text-lg font-bold text-stone-900 font-data">Room ' + number + '</p>'
            + '<span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full border ' + (CHIP[status] || CHIP.available) + '">' + esc(LABELS[status] || cap(status)) + '</span>'
            + '</div>' + occupant
            + '<div class="mt-4 mb-2 text-[11px] font-bold uppercase tracking-wider text-stone-400">Current &amp; upcoming stays</div>'
            + body
            + '<div class="mt-3 pt-3 border-t border-stone-100"><a href="{{ route('staff.rooms') }}" class="text-[12px] font-bold text-clsu-700 hover:underline">Manage rooms →</a></div>';
    };
})();
</script>
@endpush
@endonce
