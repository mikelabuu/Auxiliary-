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

    // One stay line with three variants; classes/data-kind/text are JS hooks
    // kept in sync by the rooms page's live status poller.
    if ($current) {
        $stayKind  = 'current';
        $stayIcon  = 'clock';
        $stayClass = 'font-semibold text-clsu-700';
        $stayLabel = 'In use · until ' . $current['until'];
        $stayTitle = $current['guest'] . ' · until ' . $current['until'];
    } elseif ($next) {
        $stayKind  = 'next';
        $stayIcon  = 'arrival';
        $stayClass = 'font-semibold text-palay-700';
        $stayLabel = 'Next stay · ' . $next['from'];
        $stayTitle = $next['guest'] . ' arrives ' . $next['from'];
    } else {
        $stayKind  = 'none';
        $stayIcon  = 'check';
        $stayClass = 'font-medium text-stone-400';
        $stayLabel = 'No upcoming stays';
        $stayTitle = '';
    }
@endphp

<div {{ $attributes->merge(['class' => 'room-card group/card relative bg-white rounded-xl border border-stone-200 shadow-subtle hover:shadow-card-lg hover:border-clsu-200 cursor-pointer']) }}
     data-room-id="{{ $room->id }}" data-status="{{ $room->status }}" data-type="{{ $room->room_type }}" data-wing="{{ $room->wing }}" data-room-number="{{ strtolower($room->room_number) }}" @if($current) data-held="1" @endif>
    <div class="status-bar h-1 rounded-t-xl {{ $meta['bar'] }}"></div>

    <div class="absolute top-3 right-2.5 flex items-center gap-1 z-10">
        <button type="button" class="room-edit-btn w-7 h-7 rounded-lg bg-white/95 border border-stone-200 text-stone-400 hover:text-clsu-700 hover:border-clsu-300 hover:bg-clsu-50 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40" data-id="{{ $room->id }}" title="Edit room" aria-label="Edit room">
            <x-admin.ui.icon name="edit" class="w-3.5 h-3.5" />
        </button>
        <div class="relative">
            <button type="button" class="room-kebab-btn w-7 h-7 rounded-lg bg-white/95 border border-stone-200 text-stone-400 hover:text-clsu-700 hover:border-clsu-300 hover:bg-clsu-50 flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40" data-id="{{ $room->id }}" title="More actions" aria-label="More actions" aria-haspopup="true" aria-expanded="false">
                <x-admin.ui.icon name="kebab" class="w-3.5 h-3.5" stroke-width="2" />
            </button>
            <div data-kebab-panel class="hidden animate-pop absolute right-0 top-full mt-1.5 w-44 bg-white rounded-xl border border-stone-200 shadow-card-lg overflow-hidden z-20 py-1">
                <p class="px-3.5 pt-1.5 pb-1 text-[10px] font-bold text-stone-400 tracking-wide uppercase">Set status</p>
                @foreach($settable as $statusKey => $sm)
                    <button type="button" class="quick-status-btn w-full flex items-center gap-2 px-3.5 py-1.5 text-xs text-stone-600 hover:bg-clsu-50 hover:text-clsu-800 transition-colors" data-status-value="{{ $statusKey }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $sm['dot'] }}"></span>
                        <span class="flex-1 text-left">{{ $sm['label'] }}</span>
                        <x-admin.ui.icon name="check" class="quick-status-check icon w-3 h-3 text-clsu-600 {{ $room->status === $statusKey ? '' : 'invisible' }}" stroke-width="2.5" />
                    </button>
                @endforeach
                @if(in_array($room->status, \App\Models\Room::DERIVED_STATUSES, true))
                    <p class="px-3.5 pt-1 pb-1.5 text-[10px] text-clsu-700 bg-clsu-50/70 leading-snug">
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

    <div class="p-4 pb-3 flex flex-col items-center text-center gap-2">
        <div>
            <p class="text-base font-extrabold text-stone-900 font-data tabnum">{{ $room->room_number }}</p>
            <p class="room-type-label text-stone-400 text-[10px] font-bold tracking-wide uppercase mt-0.5">{{ ucfirst($room->room_type) }}</p>
        </div>
        <span class="room-status-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $meta['badge'] }}">
            <span class="room-status-dot w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
            <span class="room-status-text">{{ $meta['label'] }}</span>
        </span>
        <p class="room-stay-line flex items-center gap-1 text-[10px] {{ $stayClass }}" data-kind="{{ $stayKind }}" @if($stayTitle) title="{{ $stayTitle }}" @endif>
            <x-admin.ui.icon :name="$stayIcon" class="w-3 h-3 shrink-0" stroke-width="2" />
            <span class="room-stay-text">{{ $stayLabel }}</span>
        </p>
        @if($room->notes)
            <p class="flex items-center gap-1 text-[10px] text-stone-400 italic mt-0.5 max-w-full" title="{{ $room->notes }}">
                <x-admin.ui.icon name="note" class="w-3 h-3 shrink-0" />
                <span class="truncate">{{ \Illuminate\Support\Str::limit($room->notes, 28) }}</span>
            </p>
        @endif
        <p class="room-updated text-[11px] text-stone-400 italic">Updated {{ $room->updated_at->diffForHumans() }}</p>
    </div>

    <div class="border-t border-stone-100 px-4 py-2 flex items-center justify-center gap-1.5 text-[10px] font-semibold text-stone-400 group-hover/card:text-clsu-600 group-hover/card:bg-clsu-50/60 transition-colors">
        <x-admin.ui.icon name="eye" class="w-3 h-3" />
        View occupancy
    </div>
</div>
