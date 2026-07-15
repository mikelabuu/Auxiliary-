@props(['room'])

{{--
    One tile in the dashboard Room Status Map. Extracted because the three room
    groups (dorm/standard/deluxe) each carried an identical copy of this markup
    and its status-colour if-chain, so adding a status meant editing it in three
    places — which is how 'cleaning' came to be missing from the map entirely.
--}}

@php
    $variants = [
        'available'   => 'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300',
        'occupied'    => 'bg-clsu-600 text-white border-clsu-700 hover:bg-clsu-700',
        'reserved'    => 'bg-palay-100 text-palay-800 border-palay-200 hover:bg-palay-200',
        'cleaning'    => 'bg-sky-50 text-sky-800 border-sky-200 hover:bg-sky-100 hover:border-sky-300',
        'maintenance' => 'bg-ember-50 text-ember-800 border-ember-200 hover:bg-ember-100',
    ];

    $status = $room['display_status'];
    $btnClass = $variants[$status] ?? $variants['available'];

    // "101 · Occupied · Ana Cruz · until Apr 18" — the map showed status only,
    // with no way to tell who a room was occupied by.
    $title = $room['room_number'] . ' · ' . ucfirst($status);
    if (!empty($room['occupant'])) {
        $title .= ' · ' . $room['occupant'];
    }
@endphp

<button type="button"
        data-room-btn="{{ $room['id'] }}"
        data-display-status="{{ $status }}"
        @if(!empty($room['occupant'])) data-occupant="{{ $room['occupant'] }}" @endif
        title="{{ $title }}"
        class="room-map-btn w-16 h-12 rounded-xl border text-[13px] font-bold font-data flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40 {{ $btnClass }}">{{ $room['room_number'] }}</button>
