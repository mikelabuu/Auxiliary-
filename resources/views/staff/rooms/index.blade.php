@extends('layouts.admin')

@section('title', 'Admin - Room Management')
@section('page-title', 'Room Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    use App\Models\Room;

    // Wing labelling and ordering come from the model, so this page and the
    // dashboard's Room Status Map lay the building out identically.
    $wingLabel = fn ($w) => Room::wingLabel($w);

    // Cards show the DERIVED state (App\Support\RoomHold::displayStatus), not
    // the raw housekeeping column: 'reserved' and 'pending' come from a booking
    // holding the room tonight and never appear in `rooms.status`.
    $statusMeta = [
        'available'   => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200', 'dot' => 'bg-clsu-400', 'bar' => 'bg-clsu-400', 'label' => 'Available'],
        'occupied'    => ['badge' => 'bg-clsu-700 text-white border-clsu-700', 'dot' => 'bg-white/70', 'bar' => 'bg-clsu-700', 'label' => 'Occupied'],
        'reserved'    => ['badge' => 'bg-palay-100 text-palay-800 border-palay-300', 'dot' => 'bg-palay-600', 'bar' => 'bg-palay-500', 'label' => 'Reserved'],
        'pending'     => ['badge' => 'bg-palay-50 text-palay-700 border-palay-300', 'dot' => 'bg-palay-400', 'bar' => 'bg-palay-300', 'label' => 'Reserved · unpaid'],
        'maintenance' => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200', 'dot' => 'bg-ember-500', 'bar' => 'bg-ember-500', 'label' => 'Maintenance'],
        'cleaning'    => ['badge' => 'bg-sky-50 text-sky-800 border-sky-200', 'dot' => 'bg-sky-500', 'bar' => 'bg-sky-400', 'label' => 'Cleaning'],
    ];

    // What the kebab menu may set. "Occupied" is owned by the booking lifecycle
    // — check-in sets it, check-out clears it — so it is shown and filterable
    // but never hand-picked; setting it by hand produced a room with no guest,
    // no end date and nothing to clear it. 'reserved'/'pending' are derived the
    // same way and are likewise not staff's to assign.
    $settableStatuses = collect($statusMeta)->only(Room::SETTABLE_STATUSES)->all();
    $roomsByWing = Room::groupByWing($rooms);
    $wingOrder = Room::WING_ORDER;
    $orderedWings = $roomsByWing->keys();

    // Everything resources/js/pages/admin-rooms.js needs, as one payload.
    // Built here (not via @json in a script) because Blade's @json directive
    // naively explode(',')s its raw argument text, which mangles any expression
    // containing commas inside nested arrays/closures.
    $pageData = json_encode([
        'base'      => url('staff/rooms'),
        'typesBase' => url('staff/room-types'),
        'statusMeta' => $statusMeta,
        'derivedStatuses' => Room::DERIVED_STATUSES,
        'roomTypes' => $roomTypes->map(fn ($t) => [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->name,
            'base_price' => (float) $t->base_price,
            'capacity' => (int) $t->capacity,
            'room_count' => $t->rooms_count,
            'available_now' => $availableNowByType[$t->slug] ?? 0,
        ])->values(),
        // Reopen whichever modal the staff member was in when validation failed.
        'openModal' => $errors->any()
            ? (($errors->has('name') || $errors->has('base_price') || $errors->has('capacity')) ? 'typeModal' : 'addRoomModal')
            : null,
    ]);
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Manage availability, wings, and pricing across all rooms.">
        Room Management
        {{-- Status Map stays here: it is navigation to another screen, not an
             action on the list below. Add Room moved down onto the All Rooms
             card, next to the rooms it adds to. --}}
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('staff.dashboard') . '#room-map'">
                <x-admin.ui.icon name="grid" class="w-4 h-4" />
                Status Map
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    <x-admin.ui.section-nav :items="[
        ['id' => 'rooms-overview', 'label' => 'Overview', 'icon' => 'grid'],
        ['id' => 'room-types', 'label' => 'Types & Pricing', 'icon' => 'tag'],
        ['id' => 'all-rooms', 'label' => 'All Rooms', 'icon' => 'bed'],
    ]" />

    <!-- Primary stat cards -->
    <div id="rooms-overview" class="scroll-mt-32 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="bed" badge="ALL WINGS" label="Total Rooms" :delay="40" value-id="statTotalNum">
            {{ $totalRooms }}
            <x-slot:footnote><p id="statTotalFoot" class="text-xs text-faint">Across {{ $roomsByWing->count() }} wings</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check-circle" badge="READY" label="Available" :delay="80" value-id="statAvailableNum">
            {{ $availableRooms }}
            <x-slot:footnote><p class="text-xs text-faint">Free to give out tonight</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="users" badge="IN USE" label="Occupied" :delay="120" value-id="statOccupiedNum" dark>
            {{ $occupiedRooms }}
            <x-slot:footnote><p class="text-xs text-clsu-300">Currently hosting guests</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="wrench" color="ember" badge="NEEDS ATTENTION" label="Maintenance" :delay="160" value-id="statMaintenanceNum">
            {{ $maintenanceRooms }}
            <x-slot:footnote><p class="text-xs text-faint">Out of rotation</p></x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    <!-- Secondary metrics strip -->
    <div class="animate-in grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6" style="animation-delay:200ms">
        {{-- Booked for tonight but nobody checked in yet. Without this the
             rooms simply vanished from Available with nothing to explain it. --}}
        <x-admin.ui.mini-stat icon="arrival" color="palay" label="Reserved tonight" value-id="statReservedNum">{{ $reservedRooms + $pendingRooms }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="droplet" color="sky" label="Rooms being cleaned" value-id="statCleaningNum">{{ $cleaningRooms }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="grid" label="Wings in use" value-id="statWingsNum">{{ $roomsByWing->count() }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="tag" label="Room types offered" value-id="statTypesNum">{{ $roomTypes->count() }}</x-admin.ui.mini-stat>
    </div>

    <!-- Room Types & Pricing -->
    <x-admin.ui.section-card id="room-types" class="scroll-mt-32" icon="tag" title="Room Types & Pricing" subtitle="Base nightly rates by category. Click a type to filter rooms below" :delay="240">
        <x-slot:actions>
            <button type="button" id="clearTypeFilterBtn" class="hidden shrink-0 items-center gap-1.5 text-xs font-semibold text-clsu-700 bg-clsu-50 hover:bg-clsu-100 rounded-full px-3 py-1.5 transition-colors cursor-pointer">
                <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" />
                <span id="clearTypeFilterLabel">Showing: All</span>
            </button>
        </x-slot:actions>
        <div id="typesGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
    </x-admin.ui.section-card>

    <!-- All Rooms -->
    <x-admin.ui.section-card id="all-rooms" class="scroll-mt-32" icon="grid" title="All Rooms" :subtitle="$totalRooms . ' rooms across ' . $roomsByWing->count() . ' wings'" subtitle-id="allRoomsSubtitle" :delay="280">
        <x-slot:actions>
            <span class="flex items-center gap-1.5 text-2xs font-medium text-muted"><span class="w-2 h-2 rounded-full bg-clsu-400"></span>Available · <span id="legendAvailable">{{ $availableRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-2xs font-medium text-muted"><span class="w-2 h-2 rounded-full bg-clsu-600"></span>Occupied · <span id="legendOccupied">{{ $occupiedRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-2xs font-medium text-muted"><span class="w-2 h-2 rounded-full bg-palay-600"></span>Reserved · <span id="legendReserved">{{ $reservedRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-2xs font-medium text-muted"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Unpaid hold · <span id="legendPending">{{ $pendingRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-2xs font-medium text-muted"><span class="w-2 h-2 rounded-full bg-sky-400"></span>Cleaning · <span id="legendCleaning">{{ $cleaningRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-2xs font-medium text-muted"><span class="w-2 h-2 rounded-full bg-ember-500"></span>Maintenance · <span id="legendMaintenance">{{ $maintenanceRooms }}</span></span>
            <span class="hidden sm:block w-px h-4 bg-stone-200" aria-hidden="true"></span>
            <x-admin.ui.button variant="primary" size="sm" type="button" id="openAddRoomBtn">
                <x-admin.ui.icon name="plus" class="w-3.5 h-3.5" stroke-width="2.5" />
                Add Room
            </x-admin.ui.button>
        </x-slot:actions>

        <!-- Controls -->
        <div class="filter-toolbar">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input id="roomSearch" type="text" placeholder="Search room, type, or wing…" aria-label="Search rooms">
            </div>
            <select id="roomStatusFilter" class="filter-select" aria-label="Filter by status">
                <option value="all">All statuses</option>
                @foreach($statusMeta as $statusKey => $sm)
                    <option value="{{ $statusKey }}">{{ $sm['label'] }}</option>
                @endforeach
            </select>
            <select id="wingFilterSelect" class="filter-select" aria-label="Filter by wing">
                <option value="all">All wings</option>
                @foreach($orderedWings as $wing)
                    <option value="{{ $wing }}">{{ $wingLabel($wing) }}</option>
                @endforeach
            </select>
            <div class="filter-toolbar-spacer"></div>
            <button type="button" id="resetFiltersBtn" class="filter-clear hidden">
                <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" />
                Clear filters
            </button>
        </div>

        @forelse($orderedWings as $wing)
            @php
                $group = $roomsByWing[$wing];
                $wingOpen = $group->filter(fn ($r) => $displayStatuses[$r->id] === 'available')->count();
            @endphp
            <div class="wing-group mb-7 last:mb-0" data-wing-group>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                    <p class="text-2xs font-bold text-faint tracking-widest uppercase">{{ $wingLabel($wing) }} Wing · <span data-wing-count>{{ $group->count() }}</span> room{{ $group->count() === 1 ? '' : 's' }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-2xs font-semibold text-faint"><span data-wing-open class="text-clsu-700 font-bold">{{ $wingOpen }}</span> available</span>
                        <div class="h-1.5 w-20 rounded-full bg-stone-200/70 overflow-hidden">
                            <div data-wing-bar class="h-full rounded-full bg-clsu-400 transition-[width] duration-300" style="width: {{ $group->count() ? round($wingOpen / $group->count() * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
                {{-- .rooms-grid (16-room-board.css) replaces the utility grid:
                     it drops to ONE column below 480px. Two 136px cards on a
                     phone wrapped every status chip onto its own line. --}}
                <div class="rooms-grid">
                    @foreach($group as $room)
                        <x-admin.rooms.room-card :room="$room" :status-meta="$statusMeta" :settable-statuses="$settableStatuses"
                                                 :display-status="$displayStatuses[$room->id]"
                                                 :stay="$stayContext[trim($room->room_number)] ?? null" />
                    @endforeach
                </div>
            </div>
        @empty
            <x-admin.ui.empty-state icon="grid" title="No rooms yet. Add your first room to get started." />
        @endforelse

        {{-- Shown when rooms exist but the search/status/wing/type filters match
             none of them. Inline display:none — .empty-state is unlayered in
             admin.css and would beat a Tailwind `hidden` utility, so
             admin-rooms.js drives it with jQuery .toggle() instead. Same
             pattern as #roomsEmpty on the front desk rooms page. --}}
        <x-admin.ui.empty-state id="noRoomsMatch" style="display:none" icon="search" title="No rooms match your filters." />

    </x-admin.ui.section-card>
</div>

<!-- ==================== Add Room Modal ==================== -->
<x-admin.ui.modal id="addRoomModal" icon="plus" title="Add New Room" scroll-body>
    <form action="{{ route('staff.rooms.store') }}" method="POST" class="px-6 py-5 space-y-4" data-busy-form>
        @csrf
        <div>
            <x-admin.ui.label for="room_number">Room Number</x-admin.ui.label>
            <x-admin.ui.field name="room_number" :value="old('room_number')" placeholder="e.g. A-101" required />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-admin.ui.label for="room-type">Room Type</x-admin.ui.label>
                {{-- Options are injected by admin-rooms.js, which finds it by id. --}}
                <x-admin.ui.field name="room_type" control="select" id="room-type" required />
            </div>
            <div>
                <x-admin.ui.label for="wing">Wing</x-admin.ui.label>
                <x-admin.ui.field name="wing" control="select" required>
                    <option value="" disabled {{ old('wing') ? '' : 'selected' }} hidden>Select wing</option>
                    @foreach($wingOrder as $w)
                        <option value="{{ $w }}" @selected(old('wing') === $w)>{{ $wingLabel($w) }}</option>
                    @endforeach
                </x-admin.ui.field>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-admin.ui.label for="price">Price (₱)</x-admin.ui.label>
                <x-admin.ui.field name="price" type="number" step="0.01" id="price" :value="old('price')" required />
            </div>
            <div>
                <x-admin.ui.label for="status">Status</x-admin.ui.label>
                {{-- Named like the rest, so it now gets the error border and
                     message its five siblings always had. It is validated
                     against Room::SETTABLE_STATUSES and was the only one of the
                     six with neither. --}}
                <x-admin.ui.field name="status" control="select">
                    @foreach($settableStatuses as $statusKey => $sm)
                        <option value="{{ $statusKey }}" @selected(old('status', 'available') === $statusKey)>{{ $sm['label'] }}</option>
                    @endforeach
                </x-admin.ui.field>
            </div>
        </div>

        <div>
            <x-admin.ui.label for="notes" hint="(optional)">Notes</x-admin.ui.label>
            <x-admin.ui.field name="notes" control="textarea" rows="2"
                              placeholder="e.g. Ground floor, near the entrance" :value="old('notes')" />
        </div>

        <div class="flex gap-2.5 justify-end pt-2">
            <x-admin.ui.modal-footer close-target="addRoomModal" submit-label="Add Room" />
        </div>
    </form>
</x-admin.ui.modal>

<!-- ==================== Edit Room Modal ==================== -->
<x-admin.ui.modal id="roomEditModal" icon="edit" title="Edit Room" scroll-body>
    <form id="roomEditForm">
        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="editRoomId">
            <p id="editFormError" class="hidden text-ember-600 text-xs bg-ember-50 border border-ember-100 rounded-lg px-3 py-2"></p>

            <div>
                <x-admin.ui.label for="editRoomNumber">Room Number</x-admin.ui.label>
                <x-admin.ui.field id="editRoomNumber" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-admin.ui.label for="editRoomType">Room Type</x-admin.ui.label>
                    <x-admin.ui.field control="select" id="editRoomType" required />
                </div>
                <div>
                    <x-admin.ui.label for="editWing">Wing</x-admin.ui.label>
                    <x-admin.ui.field control="select" id="editWing" required>
                        <option value="" disabled hidden>Select wing</option>
                        @foreach($wingOrder as $w)
                            <option value="{{ $w }}">{{ $wingLabel($w) }}</option>
                        @endforeach
                    </x-admin.ui.field>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-admin.ui.label for="editPrice">Price (₱)</x-admin.ui.label>
                    <x-admin.ui.field type="number" step="0.01" id="editPrice" required />
                </div>
                <div>
                    <x-admin.ui.label for="editStatus">Status</x-admin.ui.label>
                    <x-admin.ui.field control="select" id="editStatus">
                        @foreach($settableStatuses as $statusKey => $sm)
                            <option value="{{ $statusKey }}">{{ $sm['label'] }}</option>
                        @endforeach
                    </x-admin.ui.field>
                    {{-- Shown instead of the select when a guest is checked in: the
                         status is the booking's to change, not this form's. --}}
                    <div id="editStatusLocked" class="hidden">
                        <div class="w-full px-4 py-2.5 rounded-xl border border-clsu-200 bg-clsu-50 text-clsu-800 text-sm flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-clsu-600 shrink-0"></span>
                            <span class="font-semibold">Occupied</span>
                        </div>
                        <p class="text-2xs text-faint mt-1.5" id="editStatusLockedNote">Set by check-in. Clears on check-out.</p>
                    </div>
                </div>
            </div>

            <div>
                <x-admin.ui.label for="editNotes" hint="(optional)">Notes</x-admin.ui.label>
                <x-admin.ui.field control="textarea" id="editNotes" rows="2" placeholder="e.g. Ground floor, near the entrance" />
            </div>
        </div>
        <div class="flex gap-2.5 justify-end border-t border-stone-100 px-6 py-4">
            <x-admin.ui.modal-footer close-target="roomEditModal" submit-label="Save changes" />
        </div>
    </form>
</x-admin.ui.modal>

<!-- ==================== Add / Edit Room Type Modal ==================== -->
<x-admin.ui.modal id="typeModal" icon="tag" color="palay" title="Add Room Type" title-id="typeModalTitleText" max-width="sm">
    <form id="typeForm">
        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="typeFormId">
            <p id="typeFormError" class="hidden text-ember-600 text-xs bg-ember-50 border border-ember-100 rounded-lg px-3 py-2"></p>

            <div>
                <x-admin.ui.label for="typeFormName">Type Name</x-admin.ui.label>
                <x-admin.ui.field id="typeFormName" placeholder="e.g. Family Suite" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-admin.ui.label for="typeFormPrice">Base Price (₱)</x-admin.ui.label>
                    <x-admin.ui.field type="number" step="0.01" min="0" id="typeFormPrice" required />
                </div>
                <div>
                    <x-admin.ui.label for="typeFormCapacity">Sleeps</x-admin.ui.label>
                    <x-admin.ui.field type="number" min="1" step="1" id="typeFormCapacity" required />
                </div>
            </div>
            <p class="text-2xs text-faint">Changing the base price only affects new rooms. Existing rooms keep their current price.</p>
        </div>
        <div class="flex gap-2.5 justify-end border-t border-stone-100 px-6 py-4">
            <x-admin.ui.modal-footer close-target="typeModal" submit-label="Save Type" />
        </div>
    </form>
</x-admin.ui.modal>

<!-- ==================== Room Occupancy Modal ==================== -->
<x-admin.ui.modal id="occupancyModal" icon="eye" title="Room Occupancy">
    <div class="px-6 py-5 space-y-2.5 max-h-[60vh] overflow-y-auto" id="occupancyModalBody">
        <p class="text-center text-faint text-sm py-6">Loading…</p>
    </div>
</x-admin.ui.modal>

{{-- Behaviour: resources/js/pages/admin-rooms.js (bundled via app.js) --}}
<script type="application/json" id="admin-rooms-data">{!! $pageData !!}</script>
@endsection
