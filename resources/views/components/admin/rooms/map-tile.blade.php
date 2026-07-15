@props(['room'])

{{--
    Room Status Map tile — original flat-button design (restored).
    Tooltip includes room type for richer hover info without any
    space cost; the button itself stays compact (w-16 h-12).
--}}

@php
    $variants = [
        'available'   => 'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300',
        'occupied'    => 'bg-clsu-600 text-white border-clsu-700 hover:bg-clsu-700',
        'reserved'    => 'bg-palay-100 text-palay-800 border-palay-200 hover:bg-palay-200',
        'cleaning'    => 'bg-sky-50 text-sky-800 border-sky-200 hover:bg-sky-100 hover:border-sky-300',
        'maintenance' => 'bg-ember-50 text-ember-800 border-ember-200 hover:bg-ember-100',
    ];

    $typeLabels = [
        'dormitory1' => 'Dorm',
        'dormitory2' => 'Dorm',
        'double'     => '2-bed',
        'triple'     => '3-bed',
        'quadruple'  => '4-bed',
        'deluxe'     => 'Deluxe',
    ];

    $status    = $room['display_status'];
    $btnClass  = $variants[$status] ?? $variants['available'];
    $typeLabel = $typeLabels[$room['room_type']] ?? 'Room';

    // Tooltip: number · type · status [ · occupant]
    $title = $room['room_number'] . ' · ' . $typeLabel . ' · ' . ucfirst($status);
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
