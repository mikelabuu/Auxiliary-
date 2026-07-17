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

<button type="button"
        data-room-btn="{{ $room['id'] }}"
        data-display-status="{{ $status }}"
        @if(!empty($room['occupant'])) data-occupant="{{ $room['occupant'] }}" @endif
        title="{{ $title }}"
        class="room-map-btn relative w-16 h-12 rounded-xl border text-[13px] font-bold font-data flex items-center justify-center cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40 {{ $btnClass }}">
    {{ $room['room_number'] }}
    <span data-status-dot class="absolute top-1.5 right-1.5 {{
        $status === 'available' ? 'w-1.5 h-1.5 rounded-full border border-clsu-400' : (
        $status === 'occupied' ? 'w-1.5 h-1.5 rounded-full bg-white' : (
        $status === 'reserved' ? 'w-1.5 h-1.5 rounded-full border border-dashed border-palay-500' : (
        $status === 'cleaning' ? 'w-1.5 h-1.5 rounded-full border border-dotted border-sky-500' : (
        $status === 'maintenance' ? 'w-1.5 h-1.5 bg-ember-500 rotate-45' : ''
        ))))
    }}" aria-hidden="true"></span>
</button>
