@props([
    'room',
    'settableStatuses',      // statusMeta narrowed to Room::SETTABLE_STATUSES
    'currentGuest' => null,  // who is checked in, for the occupied note
])

{{--
    The "Set status" kebab: the one control that writes to the `rooms.status`
    housekeeping column.

    Shared by the admin board (components/admin/rooms/room-card) and the front
    desk board (staff/frontdesk/rooms/index), which both may set housekeeping
    status. Extracted when front desk gained the ability, rather than copied,
    because the checkmark logic and the `.quick-status-btn` / `[data-kebab-panel]`
    hooks are read by two different scripts — a second copy would be two places
    to keep a status list in step, and the boards would drift the first time one
    of them was edited.

    Actions beyond setting status go in the slot, under a divider. Only the
    admin board passes anything (Delete Room); the desk gets status alone.
--}}

<div class="relative">
    <button type="button"
            class="room-kebab-btn w-7 h-7 rounded-lg bg-white/95 border border-stone-200 text-faint hover:text-clsu-700 hover:border-clsu-300 hover:bg-clsu-50 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40"
            data-id="{{ $room->id }}" title="More actions" aria-label="More actions" aria-haspopup="true" aria-expanded="false">
        <x-admin.ui.icon name="kebab" class="w-3.5 h-3.5" stroke-width="2" />
    </button>

    <div data-kebab-panel class="hidden animate-pop absolute right-0 top-full mt-1.5 w-44 bg-white rounded-xl border border-stone-200 shadow-card-lg overflow-hidden z-20 py-1">
        <p class="px-3.5 pt-1.5 pb-1 text-2xs font-bold text-faint tracking-wide uppercase">Set status</p>

        @foreach($settableStatuses as $statusKey => $sm)
            <button type="button" class="quick-status-btn w-full flex items-center gap-2 px-3.5 py-1.5 text-xs text-stone-600 hover:bg-clsu-50 hover:text-clsu-800 transition-colors" data-status-value="{{ $statusKey }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $sm['dot'] }}"></span>
                <span class="flex-1 text-left">{{ $sm['label'] }}</span>
                {{-- Ticks the HOUSEKEEPING column, not the badge: a room can read
                     "Reserved" on the board while housekeeping still calls it
                     available, and this menu is what writes that column. --}}
                <x-admin.ui.icon name="check" class="quick-status-check icon w-3 h-3 text-clsu-600 {{ $room->status === $statusKey ? '' : 'invisible' }}" stroke-width="2.5" />
            </button>
        @endforeach

        @if(in_array($room->status, \App\Models\Room::DERIVED_STATUSES, true))
            <p class="px-3.5 pt-1 pb-1.5 text-2xs text-clsu-700 bg-clsu-50/70 leading-snug">
                Occupied by check-in{{ $currentGuest ? ' · ' . $currentGuest : '' }} — check out to free this room.
            </p>
        @endif

        @if($slot->isNotEmpty())
            <div class="h-px bg-stone-100 my-1"></div>
            {{ $slot }}
        @endif
    </div>
</div>
