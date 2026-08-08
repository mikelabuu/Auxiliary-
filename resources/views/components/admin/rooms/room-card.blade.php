@props([
    'room',
    'statusMeta',
    'settableStatuses' => null, // statusMeta minus the booking-owned ones; falls back to all
    'stay' => null, // ['current' => ['guest','until'], 'next' => ['guest','from']] from RoomController::index
])

{{--
    Single room tile in the "All Rooms" grid: status bar, edit button, kebab
    menu (quick status + delete), status badge, notes, and a footer that opens
    the occupancy modal. Pulled out of rooms/index.blade.php so that page's
    layout stays readable — all the JS hooks (classes/data-* attributes) are
    unchanged, so resources/views/staff/rooms/index.blade.php's <script> block
    needs no changes to keep working with this component.
--}}

@php
    $meta = $statusMeta[$room->status] ?? $statusMeta['available'];
    $settable = $settableStatuses ?? $statusMeta;
    $current = $stay['current'] ?? null;
    $next = $stay['next'] ?? null;

    // One stay line, five variants; classes/data-kind/text are JS hooks kept
    // in sync by the rooms page's live status poller. The wording lives in
    // App\Support\RoomHold so the poller and this card cannot drift.
    $line = \App\Support\RoomHold::stayLine($current, $next);
    $stayKind  = $line['kind'];
    $stayIcon  = $line['icon'];
    $stayClass = $line['class'];
    $stayLabel = $line['label'];
    $stayGuest = $line['guest'];
    $stayTitle = $line['title'];

    // A hold with no money behind it yet. Worth calling out on the card:
    // the room is correctly unavailable, but the booking can still lapse.
    $holdPending = \App\Support\RoomHold::isPending($current['status'] ?? $next['status'] ?? null);
@endphp

<div {{ $attributes->merge(['class' => 'room-card group/card relative bg-white rounded-xl border border-stone-200 shadow-subtle hover:shadow-card-lg hover:border-clsu-200 cursor-pointer']) }}
     data-room-id="{{ $room->id }}" data-status="{{ $room->status }}" data-type="{{ $room->room_type }}" data-wing="{{ $room->wing }}" data-room-number="{{ strtolower($room->room_number) }}" @if($current) data-held="1" @endif @if($holdPending) data-hold-pending="1" @endif>
    <div class="status-bar h-1 rounded-t-xl {{ $meta['bar'] }}"></div>

    <div class="absolute top-3 right-2.5 flex items-center gap-1 z-10">
        <button type="button" class="room-edit-btn w-7 h-7 rounded-lg bg-white/95 border border-stone-200 text-faint hover:text-clsu-700 hover:border-clsu-300 hover:bg-clsu-50 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40" data-id="{{ $room->id }}" title="Edit room" aria-label="Edit room">
            <x-admin.ui.icon name="edit" class="w-3.5 h-3.5" />
        </button>
        <div class="relative">
            <button type="button" class="room-kebab-btn w-7 h-7 rounded-lg bg-white/95 border border-stone-200 text-faint hover:text-clsu-700 hover:border-clsu-300 hover:bg-clsu-50 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40" data-id="{{ $room->id }}" title="More actions" aria-label="More actions" aria-haspopup="true" aria-expanded="false">
                <x-admin.ui.icon name="kebab" class="w-3.5 h-3.5" stroke-width="2" />
            </button>
            <div data-kebab-panel class="hidden animate-pop absolute right-0 top-full mt-1.5 w-44 bg-white rounded-xl border border-stone-200 shadow-card-lg overflow-hidden z-20 py-1">
                <p class="px-3.5 pt-1.5 pb-1 text-2xs font-bold text-faint tracking-wide uppercase">Set status</p>
                @foreach($settable as $statusKey => $sm)
                    <button type="button" class="quick-status-btn w-full flex items-center gap-2 px-3.5 py-1.5 text-xs text-stone-600 hover:bg-clsu-50 hover:text-clsu-800 transition-colors" data-status-value="{{ $statusKey }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $sm['dot'] }}"></span>
                        <span class="flex-1 text-left">{{ $sm['label'] }}</span>
                        <x-admin.ui.icon name="check" class="quick-status-check icon w-3 h-3 text-clsu-600 {{ $room->status === $statusKey ? '' : 'invisible' }}" stroke-width="2.5" />
                    </button>
                @endforeach
                @if(in_array($room->status, \App\Models\Room::DERIVED_STATUSES, true))
                    <p class="px-3.5 pt-1 pb-1.5 text-2xs text-clsu-700 bg-clsu-50/70 leading-snug">
                        Occupied by check-in{{ $current ? ' · ' . $current['guest'] : '' }} — check out to free this room.
                    </p>
                @endif
                <div class="h-px bg-stone-100 my-1"></div>
                <button type="button" class="room-delete-btn w-full flex items-center gap-2 px-3.5 py-1.5 text-xs text-ember-600 hover:bg-ember-50 transition-colors">
                    <x-admin.ui.icon name="trash" class="w-3.5 h-3.5" stroke-width="2" />
                    <span class="delete-label">Delete Room</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Left-aligned, not centred: a board is scanned down a column of room
         numbers, and centred text makes every row start in a different place. --}}
    <div class="room-card-body">
        <div class="room-card-head">
            <p class="room-card-number font-data tabnum">{{ $room->room_number }}</p>
            <p class="room-type-label">{{ ucfirst($room->room_type) }}</p>
        </div>

        <div class="room-card-chips">
            <span class="room-status-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border {{ $meta['badge'] }}">
                <span class="room-status-dot w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                <span class="room-status-text">{{ $meta['label'] }}</span>
            </span>
            {{-- Housekeeping says the room is free; a booking says otherwise and
                 has not been paid for. Both facts matter, so both show.

                 Visibility is driven by [data-hold-pending] on the card, NOT by
                 a `hidden` class. `hidden` and `inline-flex` are both display
                 utilities, and Tailwind emits `inline-flex` after `hidden`, so
                 the two together left this badge permanently visible — every
                 room read "Unpaid hold" regardless of its bookings. --}}
            <span class="room-hold-badge items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border border-palay-200 bg-palay-50 text-palay-800">
                <span class="w-1.5 h-1.5 rounded-full bg-palay-500"></span>
                Unpaid hold
            </span>
        </div>

        <div class="room-stay-block">
            <p class="room-stay-line flex items-center gap-1 text-2xs {{ $stayClass }}" data-kind="{{ $stayKind }}" @if($stayTitle) title="{{ $stayTitle }}" @endif>
                <x-admin.ui.icon :name="$stayIcon" class="w-3 h-3 shrink-0" stroke-width="2" />
                <span class="room-stay-text">{{ $stayLabel }}</span>
            </p>
            {{-- Who is holding it. Previously only in the title attribute above,
                 which a phone cannot hover and a screen reader skips. --}}
            <p class="room-guest-line" @if(! $stayGuest) hidden @endif>
                <x-admin.ui.icon name="user" class="w-3 h-3 shrink-0" stroke-width="2" />
                <span class="room-guest-text">{{ $stayGuest }}</span>
            </p>
        </div>

        @if($room->notes)
            <p class="room-note-line" title="{{ $room->notes }}">
                <x-admin.ui.icon name="note" class="w-3 h-3 shrink-0" />
                <span class="truncate">{{ \Illuminate\Support\Str::limit($room->notes, 28) }}</span>
            </p>
        @endif
        <p class="room-updated">Updated {{ $room->updated_at->diffForHumans() }}</p>
    </div>

    <div class="room-card-foot">
        <x-admin.ui.icon name="eye" class="w-3 h-3" />
        View occupancy
    </div>
</div>
