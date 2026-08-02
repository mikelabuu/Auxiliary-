@extends('layouts.admin')

@section('title', 'Admin - Room Management')
@section('page-title', 'Room Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    use App\Models\Room;

    $wingLabel = fn ($w) => ucwords(str_replace('_', ' ', $w));
    $statusMeta = [
        'available'   => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200', 'dot' => 'bg-clsu-400', 'bar' => 'bg-clsu-400', 'label' => 'Available'],
        'occupied'    => ['badge' => 'bg-clsu-600 text-white border-clsu-700', 'dot' => 'bg-white/70', 'bar' => 'bg-clsu-600', 'label' => 'Occupied'],
        'maintenance' => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200', 'dot' => 'bg-ember-500', 'bar' => 'bg-ember-500', 'label' => 'Maintenance'],
        'cleaning'    => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200', 'dot' => 'bg-palay-500', 'bar' => 'bg-palay-400', 'label' => 'Cleaning'],
    ];

    // "Occupied" is owned by the booking lifecycle — check-in sets it, check-out
    // clears it — so it is shown and filterable but never hand-picked. Setting it
    // by hand produced a room with no guest, no end date and nothing to clear it.
    // Housekeeping states below are the ones staff actually control.
    $settableStatuses = collect($statusMeta)->except(Room::DERIVED_STATUSES)->all();
    $roomsByWing = $rooms->groupBy('wing');
    $wingOrder = ['rooster', 'tumana', 'chev_re', 'torii'];
    $orderedWings = collect($wingOrder)->filter(fn ($w) => $roomsByWing->has($w))
        ->concat($roomsByWing->keys()->diff($wingOrder)->sort());

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
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('staff.dashboard') . '#room-map'">
                <x-admin.ui.icon name="grid" class="w-4 h-4" />
                Status Map
            </x-admin.ui.button>
            <x-admin.ui.button variant="primary" type="button" id="openAddRoomBtn">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                Add Room
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
            <x-slot:footnote><p id="statTotalFoot" class="text-xs text-stone-400">Across {{ $roomsByWing->count() }} wings</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check-circle" badge="READY" label="Available" :delay="80" value-id="statAvailableNum">
            {{ $availableRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">Ready for check-in</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="users" badge="IN USE" label="Occupied" :delay="120" value-id="statOccupiedNum" dark>
            {{ $occupiedRooms }}
            <x-slot:footnote><p class="text-xs text-clsu-300">Currently hosting guests</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="wrench" color="ember" badge="NEEDS ATTENTION" label="Maintenance" :delay="160" value-id="statMaintenanceNum">
            {{ $maintenanceRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">Out of rotation</p></x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    <!-- Secondary metrics strip -->
    <div class="animate-in grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6" style="animation-delay:200ms">
        <x-admin.ui.mini-stat icon="droplet" color="palay" label="Rooms being cleaned" value-id="statCleaningNum">{{ $cleaningRooms }}</x-admin.ui.mini-stat>
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
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-400"></span>Available · <span id="legendAvailable">{{ $availableRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-600"></span>Occupied · <span id="legendOccupied">{{ $occupiedRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Cleaning · <span id="legendCleaning">{{ $cleaningRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-ember-500"></span>Maintenance · <span id="legendMaintenance">{{ $maintenanceRooms }}</span></span>
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
                $wingOpen = $group->where('status', 'available')->count();
            @endphp
            <div class="wing-group mb-7 last:mb-0" data-wing-group>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                    <p class="text-[10px] font-bold text-stone-400 tracking-widest uppercase">{{ $wingLabel($wing) }} Wing · <span data-wing-count>{{ $group->count() }}</span> room{{ $group->count() === 1 ? '' : 's' }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-semibold text-stone-400"><span data-wing-open class="text-clsu-700 font-bold">{{ $wingOpen }}</span> available</span>
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
                        <x-admin.rooms.room-card :room="$room" :status-meta="$statusMeta" :settable-statuses="$settableStatuses" :stay="$stayContext[trim($room->room_number)] ?? null" />
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
    <form action="{{ route('staff.rooms.store') }}" method="POST" class="px-6 py-5 space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Number</label>
            <input type="text" name="room_number" value="{{ old('room_number') }}" placeholder="e.g. A-101" required class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('room_number') ? 'border-ember-300 focus:ring-ember-300 focus:border-ember-300' : 'border-stone-200 focus:ring-clsu-500/25 focus:border-clsu-500' }} bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 transition-colors">
            @error('room_number')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Type</label>
                <select name="room_type" id="room-type" required class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('room_type') ? 'border-ember-300 focus:ring-ember-300 focus:border-ember-300' : 'border-stone-200 focus:ring-clsu-500/25 focus:border-clsu-500' }} bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 cursor-pointer transition-colors"></select>
                @error('room_type')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Wing</label>
                <select name="wing" required class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('wing') ? 'border-ember-300 focus:ring-ember-300 focus:border-ember-300' : 'border-stone-200 focus:ring-clsu-500/25 focus:border-clsu-500' }} bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 cursor-pointer transition-colors">
                    <option value="" disabled {{ old('wing') ? '' : 'selected' }} hidden>Select wing</option>
                    @foreach($wingOrder as $w)
                        <option value="{{ $w }}" @selected(old('wing') === $w)>{{ $wingLabel($w) }}</option>
                    @endforeach
                </select>
                @error('wing')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Price (₱)</label>
                <input type="number" step="0.01" name="price" id="price" value="{{ old('price') }}" required class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('price') ? 'border-ember-300 focus:ring-ember-300 focus:border-ember-300' : 'border-stone-200 focus:ring-clsu-500/25 focus:border-clsu-500' }} bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 transition-colors">
                @error('price')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors">
                    @foreach($settableStatuses as $statusKey => $sm)
                        <option value="{{ $statusKey }}" @selected(old('status', 'available') === $statusKey)>{{ $sm['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Notes <span class="text-stone-400 font-normal normal-case">(optional)</span></label>
            <textarea name="notes" rows="2" placeholder="e.g. Ground floor, near the entrance" class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('notes') ? 'border-ember-300 focus:ring-ember-300 focus:border-ember-300' : 'border-stone-200 focus:ring-clsu-500/25 focus:border-clsu-500' }} bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 transition-colors resize-none">{{ old('notes') }}</textarea>
            @error('notes')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
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
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Number</label>
                <input type="text" id="editRoomNumber" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Type</label>
                    <select id="editRoomType" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors" required></select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Wing</label>
                    <select id="editWing" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors" required>
                        <option value="" disabled hidden>Select wing</option>
                        @foreach($wingOrder as $w)
                            <option value="{{ $w }}">{{ $wingLabel($w) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Price (₱)</label>
                    <input type="number" step="0.01" id="editPrice" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Status</label>
                    <select id="editStatus" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors">
                        @foreach($settableStatuses as $statusKey => $sm)
                            <option value="{{ $statusKey }}">{{ $sm['label'] }}</option>
                        @endforeach
                    </select>
                    {{-- Shown instead of the select when a guest is checked in: the
                         status is the booking's to change, not this form's. --}}
                    <div id="editStatusLocked" class="hidden">
                        <div class="w-full px-4 py-2.5 rounded-xl border border-clsu-200 bg-clsu-50 text-clsu-800 text-sm flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-clsu-600 shrink-0"></span>
                            <span class="font-semibold">Occupied</span>
                        </div>
                        <p class="text-[11px] text-stone-400 mt-1.5" id="editStatusLockedNote">Set by check-in. Clears on check-out.</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Notes <span class="text-stone-400 font-normal normal-case">(optional)</span></label>
                <textarea id="editNotes" rows="2" placeholder="e.g. Ground floor, near the entrance" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors resize-none"></textarea>
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
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Type Name</label>
                <input type="text" id="typeFormName" placeholder="e.g. Family Suite" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Base Price (₱)</label>
                    <input type="number" step="0.01" min="0" id="typeFormPrice" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Sleeps</label>
                    <input type="number" min="1" step="1" id="typeFormCapacity" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
                </div>
            </div>
            <p class="text-[11px] text-stone-400">Changing the base price only affects new rooms. Existing rooms keep their current price.</p>
        </div>
        <div class="flex gap-2.5 justify-end border-t border-stone-100 px-6 py-4">
            <x-admin.ui.modal-footer close-target="typeModal" submit-label="Save Type" />
        </div>
    </form>
</x-admin.ui.modal>

<!-- ==================== Room Occupancy Modal ==================== -->
<x-admin.ui.modal id="occupancyModal" icon="eye" title="Room Occupancy">
    <div class="px-6 py-5 space-y-2.5 max-h-[60vh] overflow-y-auto" id="occupancyModalBody">
        <p class="text-center text-stone-400 text-sm py-6">Loading…</p>
    </div>
</x-admin.ui.modal>

{{-- Behaviour: resources/js/pages/admin-rooms.js (bundled via app.js) --}}
<script type="application/json" id="admin-rooms-data">{!! $pageData !!}</script>
@endsection
