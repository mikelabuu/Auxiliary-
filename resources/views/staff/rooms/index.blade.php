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

    // Built here (not via @json in the script) because Blade's @json directive
    // naively explode(',')s its raw argument text, which mangles any expression
    // containing commas inside nested arrays/closures.
    $statusMetaJson = json_encode($statusMeta);
    $roomTypesJson = json_encode($roomTypes->map(fn ($t) => [
        'id' => $t->id,
        'slug' => $t->slug,
        'name' => $t->name,
        'base_price' => (float) $t->base_price,
        'capacity' => (int) $t->capacity,
        'room_count' => $t->rooms_count,
        'available_now' => $availableNowByType[$t->slug] ?? 0,
    ])->values());
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

@push('scripts')
<script>
$(function () {
    const base = "{{ url('staff/rooms') }}";
    const typesBase = "{{ url('staff/room-types') }}";
    const STATUS_META = {!! $statusMetaJson !!};
    const DERIVED_STATUSES = {!! json_encode(Room::DERIVED_STATUSES) !!};
    let roomTypes = {!! $roomTypesJson !!};
    let activeTypeFilter = null;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function peso(n) { return '₱' + Number(n || 0).toLocaleString('en-PH'); }
    function typeBySlug(slug) { return roomTypes.find(t => t.slug === slug); }
    function typeName(slug) { const t = typeBySlug(slug); return t ? t.name : (slug ? slug.charAt(0).toUpperCase() + slug.slice(1) : ''); }

    function toast(message, icon) {
        window.toast(message, icon); // unified console toasts (resources/js/app.js)
    }

    // Animated modal helpers (focus management included) live in layouts/admin.
    const openModal = (id) => window.openModal(id);
    const closeModal = (id) => window.closeModal(id);

    $('#openAddRoomBtn').on('click', () => openModal('addRoomModal'));
    $('[data-modal-close]').on('click', function () { closeModal($(this).data('modal-close')); });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('#addRoomModal, #roomEditModal, #typeModal, #occupancyModal').addClass('hidden').removeClass('flex');
            $('[data-kebab-panel]').addClass('hidden');
        }
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.room-kebab-btn, [data-kebab-panel]').length) {
            $('[data-kebab-panel]').addClass('hidden');
        }
    });

    @if($errors->any())
        @if($errors->has('name') || $errors->has('base_price') || $errors->has('capacity'))
            openModal('typeModal');
        @else
            openModal('addRoomModal');
        @endif
    @endif

    {{-- Session flash toasts fire from layouts/admin --}}

    /* ------------------- ROOM TYPE SELECT OPTIONS ------------------- */
    function populateRoomTypeSelects() {
        const options = roomTypes.map(t => '<option value="' + t.slug + '" data-price="' + t.base_price + '">' + t.name + ' · ' + peso(t.base_price) + '</option>').join('');
        const addSelect = $('#room-type');
        const editSelect = $('#editRoomType');
        const addPrevVal = addSelect.val();
        const editPrevVal = editSelect.val();
        addSelect.html('<option value="" disabled hidden>Select room type</option>' + options);
        editSelect.html(options);
        if (addPrevVal) addSelect.val(addPrevVal);
        if (editPrevVal) editSelect.val(editPrevVal);
    }
    populateRoomTypeSelects();

    /* ------------------- ROOM TYPE TILES ------------------- */
    function renderTypeTiles() {
        const grid = $('#typesGrid');
        const tiles = roomTypes.map(t => {
            const isActive = activeTypeFilter === t.slug;
            return (
                '<div class="type-tile relative bg-white rounded-xl border ' + (isActive ? 'border-clsu-400 ring-1 ring-clsu-200' : 'border-stone-200') + ' shadow-subtle hover:shadow-card overflow-hidden cursor-pointer" data-type-tile="' + t.slug + '">' +
                    '<button type="button" class="type-edit-btn absolute top-2 right-2 z-10 w-6 h-6 rounded-full bg-white/95 border border-stone-200 text-stone-400 hover:text-clsu-700 hover:border-clsu-300 flex items-center justify-center transition-colors" data-edit-type="' + t.id + '" aria-label="Edit ' + t.name + '">' +
                        '<svg class="icon w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>' +
                    '</button>' +
                    '<div class="h-14 bg-gradient-to-br from-clsu-50 via-white to-palay-50 flex items-center justify-center">' +
                        '<svg class="icon w-5 h-5 text-clsu-700/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>' +
                    '</div>' +
                    '<div class="p-3.5">' +
                        '<p class="text-xs font-bold text-stone-800 tracking-wide uppercase truncate">' + t.name + '</p>' +
                        '<p class="text-clsu-700 font-bold text-sm mt-1 font-data tabnum">' + peso(t.base_price) + '</p>' +
                        '<p class="text-[10px] text-stone-400 mt-1">Sleeps ' + t.capacity + ' · ' + t.room_count + (t.room_count === 1 ? ' room' : ' rooms') + '</p>' +
                        (t.room_count > 0
                            ? '<p class="text-[10px] font-bold mt-1 ' + (t.available_now > 0 ? 'text-clsu-600' : 'text-ember-500') + '">' + (t.available_now > 0 ? t.available_now + ' open today' : 'None open today') + '</p>'
                            : '<p class="text-[10px] font-semibold text-stone-300 mt-1">No rooms yet</p>') +
                    '</div>' +
                '</div>'
            );
        }).join('');

        const addTile = (
            '<button type="button" id="addTypeTile" class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-stone-200 hover:border-clsu-300 hover:bg-clsu-50/40 transition-colors text-stone-400 hover:text-clsu-700 py-6 min-h-[120px] cursor-pointer">' +
                '<svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                '<span class="text-xs font-semibold">Add Room Type</span>' +
            '</button>'
        );

        grid.html(tiles + addTile);
    }
    renderTypeTiles();

    $('#typesGrid').on('click', '[data-type-tile]', function () {
        const slug = $(this).data('type-tile').toString();
        activeTypeFilter = activeTypeFilter === slug ? null : slug;
        syncTypeFilterUI();
        renderTypeTiles();
        applyFilters();
    });
    $('#typesGrid').on('click', '[data-edit-type]', function (e) {
        e.stopPropagation();
        openTypeModal(Number($(this).data('edit-type')));
    });
    $('#typesGrid').on('click', '#addTypeTile', function () { openTypeModal(null); });

    function syncTypeFilterUI() {
        const btn = $('#clearTypeFilterBtn');
        if (activeTypeFilter) {
            $('#clearTypeFilterLabel').text('Showing: ' + typeName(activeTypeFilter));
            btn.removeClass('hidden').addClass('flex');
        } else {
            btn.addClass('hidden').removeClass('flex');
        }
    }
    $('#clearTypeFilterBtn').on('click', function () {
        activeTypeFilter = null;
        syncTypeFilterUI();
        renderTypeTiles();
        applyFilters();
    });

    /* ------------------- ROOM TYPE MODAL ------------------- */
    function openTypeModal(id) {
        const isEdit = typeof id === 'number';
        $('#typeFormId').val(isEdit ? id : '');
        $('#typeModalTitleText').text(isEdit ? 'Edit Room Type' : 'Add Room Type');
        $('#typeFormError').addClass('hidden').text('');

        if (isEdit) {
            const t = roomTypes.find(rt => rt.id === id);
            $('#typeFormName').val(t ? t.name : '');
            $('#typeFormPrice').val(t ? t.base_price : '');
            $('#typeFormCapacity').val(t ? t.capacity : 1);
        } else {
            $('#typeForm')[0].reset();
            $('#typeFormId').val('');
            $('#typeFormCapacity').val(2);
        }
        openModal('typeModal');
    }

    $('#typeForm').on('submit', function (e) {
        e.preventDefault();
        const idVal = $('#typeFormId').val();
        const payload = {
            name: $('#typeFormName').val().trim(),
            base_price: $('#typeFormPrice').val(),
            capacity: $('#typeFormCapacity').val(),
        };
        const isEdit = !!idVal;
        const url = isEdit ? `${typesBase}/${idVal}` : typesBase;
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({ url, method, data: payload })
            .done(function (res) {
                if (!res.success) return;
                const rt = res.roomType;
                const normalized = {
                    id: rt.id, slug: rt.slug, name: rt.name,
                    base_price: parseFloat(rt.base_price), capacity: parseInt(rt.capacity, 10),
                    room_count: rt.rooms_count ?? (typeBySlug(rt.slug)?.room_count ?? 0),
                    available_now: typeBySlug(rt.slug)?.available_now ?? 0,
                };
                const idx = roomTypes.findIndex(t => t.id === normalized.id);
                if (idx >= 0) roomTypes[idx] = normalized; else roomTypes.push(normalized);

                populateRoomTypeSelects();
                renderTypeTiles();
                $('#statTypesNum').text(roomTypes.length);
                closeModal('typeModal');
                toast(isEdit ? `"${normalized.name}" updated.` : `"${normalized.name}" added as a new room type.`);
            })
            .fail(function (xhr) {
                const errors = xhr.responseJSON?.errors;
                const msg = errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Could not save room type.');
                $('#typeFormError').removeClass('hidden').text(msg);
            });
    });

    /* ------------------- AUTO-FILL PRICE (Add form) -------------------
       Only overwrite the price if it's empty or still matches some
       type's base price (i.e. the staff hasn't customized it away from
       an auto-filled value) — otherwise re-selecting/changing the type
       would silently clobber a manually-typed custom price. */
    $('#room-type').on('change', function () {
        const t = typeBySlug($(this).val());
        if (!t) return;
        const priceField = $('#price');
        const currentlyDefault = !priceField.val() || roomTypes.some(rt => String(rt.base_price) === String(priceField.val()));
        if (currentlyDefault) priceField.val(t.base_price);
    });

    /* ------------------- ROOM CARD CLICK (Show Occupancy) ------------------- */
    $(document).on('click', '.room-card', function (e) {
        if ($(e.target).closest('.room-edit-btn, .room-kebab-btn, [data-kebab-panel]').length) return;
        const roomId = $(this).data('room-id');
        const modalBody = $('#occupancyModalBody');
        modalBody.html('<p class="text-center text-stone-400 text-sm py-6">Loading…</p>');
        openModal('occupancyModal');

        $.get(`${base}/${roomId}/occupancy`)
            .done(function (res) {
                if (!res.success) {
                    modalBody.html('<p class="text-center text-ember-600 text-sm py-6">Could not load bookings.</p>');
                    return;
                }
                renderOccupancy(res);
            })
            .fail(function () {
                modalBody.html('<p class="text-center text-ember-600 text-sm py-6">Error fetching booking info.</p>');
            });
    });

    function occupancyRow(b) {
        const isCurrent = b.timeline === 'current';
        const badge = isCurrent
            ? 'text-clsu-700 bg-clsu-50 border-clsu-200'
            : 'text-palay-800 bg-palay-100 border-palay-200';
        return `
            <div class="flex items-start gap-3 p-3.5 rounded-xl border border-stone-100 bg-stone-50/60">
                <div class="w-9 h-9 rounded-full ${isCurrent ? 'bg-clsu-100 text-clsu-700' : 'bg-palay-100 text-palay-800'} flex items-center justify-center shrink-0 font-bold text-xs">
                    ${(b.guest_name || '?').substring(0, 2).toUpperCase()}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-stone-800 truncate">${b.guest_name || 'Guest'}</p>
                    <p class="text-xs text-stone-500 mt-0.5">#${b.id} · ${b.check_in_formatted} → ${b.check_out_formatted} · ${b.nights} night${b.nights === 1 ? '' : 's'}</p>
                </div>
                <span class="text-[10px] font-bold rounded-full px-2.5 py-1 border shrink-0 whitespace-nowrap ${badge}">${b.status}</span>
            </div>`;
    }

    function renderOccupancy(res) {
        const modalBody = $('#occupancyModalBody');
        const bookings = res.bookings || [];

        if (!bookings.length) {
            const label = STATUS_META[res.room_status]?.label || 'Available';
            modalBody.html(
                '<div class="text-center py-8">' +
                    '<p class="text-sm text-stone-500">No current or upcoming reservations for this room.</p>' +
                    '<p class="text-xs text-stone-400 mt-1.5">Room ' + res.room_number + ' is currently marked <span class="font-semibold">' + label + '</span>.</p>' +
                '</div>'
            );
            return;
        }

        const current = bookings.filter(b => b.timeline === 'current');
        const upcoming = bookings.filter(b => b.timeline === 'upcoming');
        let html = '<p class="text-xs text-stone-400 mb-3">Room ' + res.room_number + ' · ' + bookings.length + ' reservation' + (bookings.length === 1 ? '' : 's') + '</p>';

        if (current.length) {
            html += '<p class="text-[10px] font-bold uppercase tracking-widest text-clsu-600 mb-2">Staying now</p>';
            html += current.map(occupancyRow).join('<div class="h-2.5"></div>');
        }
        if (upcoming.length) {
            html += '<p class="text-[10px] font-bold uppercase tracking-widest text-palay-700 mb-2 ' + (current.length ? 'mt-4' : '') + '">Upcoming</p>';
            html += upcoming.map(occupancyRow).join('<div class="h-2.5"></div>');
        }
        modalBody.html(html);
    }

    /* ------------------- ROOM EDIT ------------------- */
    $(document).on('click', '.room-edit-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const roomId = $(this).data('id');

        $.get(`${base}/${roomId}/edit`).done(function (res) {
            if (!res.success) return;
            const room = res.room;

            $('#editRoomId').val(room.id);
            $('#editRoomNumber').val(room.room_number);
            $('#editRoomType').val(room.room_type);
            $('#editWing').val(room.wing);
            $('#editPrice').val(room.price ?? (typeBySlug(room.room_type)?.base_price ?? ''));
            $('#editNotes').val(room.notes || '');
            $('#editFormError').addClass('hidden').text('');

            // 'occupied' is not one of the options, so .val() would find no match
            // and post an empty status — swap in the read-only panel instead and
            // leave the derived status to the booking that owns it.
            const derived = DERIVED_STATUSES.includes(room.status);
            $('#editStatus').toggleClass('hidden', derived).val(derived ? 'available' : room.status);
            $('#editStatusLocked').toggleClass('hidden', !derived);

            openModal('roomEditModal');
        });
    });

    $('#editRoomType').on('change', function () {
        const t = typeBySlug($(this).val());
        if (!t) return;
        const priceField = $('#editPrice');
        const currentlyDefault = !priceField.val() || roomTypes.some(rt => String(rt.base_price) === String(priceField.val()));
        if (currentlyDefault) priceField.val(t.base_price);
    });

    $('#roomEditForm').on('submit', function (e) {
        e.preventDefault();
        const roomId = $('#editRoomId').val();
        const payload = {
            room_number: $('#editRoomNumber').val(),
            room_type: $('#editRoomType').val(),
            wing: $('#editWing').val(),
            price: $('#editPrice').val(),
            notes: $('#editNotes').val(),
        };
        // Omitted entirely for an occupied room so the server leaves it untouched.
        if (!$('#editStatus').hasClass('hidden')) {
            payload.status = $('#editStatus').val();
        }

        $.ajax({
            url: `${base}/${roomId}`,
            method: 'PUT',
            data: payload,
            success: function (res) {
                if (!res.success) {
                    $('#editFormError').removeClass('hidden').text('Update failed. Please try again.');
                    return;
                }
                closeModal('roomEditModal');
                toast('Room ' + res.room.room_number + ' updated.');
                setTimeout(() => location.reload(), 900);
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                const msg = errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Update failed. Please try again.');
                $('#editFormError').removeClass('hidden').text(msg);
            }
        });
    });

    /* ------------------- KEBAB MENU (quick status + delete) ------------------- */
    $(document).on('click', '.room-kebab-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const panel = $(this).siblings('[data-kebab-panel]');
        const isOpen = !panel.hasClass('hidden');
        $('[data-kebab-panel]').addClass('hidden');
        if (!isOpen) panel.removeClass('hidden');
    });

    $(document).on('click', '.quick-status-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const card = $(this).closest('.room-card');
        const roomId = card.data('room-id');
        const newStatus = $(this).data('status-value').toString();
        const panel = $(this).closest('[data-kebab-panel]');

        $.ajax({ url: `${base}/${roomId}/status`, method: 'PATCH', data: { status: newStatus } })
            .done(function (res) {
                if (!res.success) { toast('Could not update status.', 'error'); return; }
                applyStatusToCard(card, newStatus);
                panel.addClass('hidden');
                recomputeAggregates();
                applyFilters();
                toast('Room ' + res.room.room_number + ' marked ' + STATUS_META[newStatus].label.toLowerCase() + '.');
            })
            .fail(function () { toast('Could not update status.', 'error'); });
    });

    function applyStatusToCard(card, status) {
        const meta = STATUS_META[status];
        card.attr('data-status', status);
        card.find('.status-bar').attr('class', 'status-bar h-1 rounded-t-2xl ' + meta.bar);
        card.find('.room-status-badge').attr('class', 'room-status-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' + meta.badge);
        card.find('.room-status-dot').attr('class', 'room-status-dot w-1.5 h-1.5 rounded-full ' + meta.dot);
        card.find('.room-status-text').text(meta.label);
        card.find('.room-updated').text('Updated just now');
        card.find('.quick-status-btn').each(function () {
            const isMatch = $(this).data('status-value').toString() === status;
            $(this).find('.quick-status-check').toggleClass('invisible', !isMatch);
        });
    }

    /* ------------------- DELETE ROOM (two-step confirm) ------------------- */
    $(document).on('click', '.room-delete-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const btn = $(this);
        const card = btn.closest('.room-card');
        const roomId = card.data('room-id');
        const roomNumber = card.data('room-number');

        if (btn.data('armed') !== true) {
            btn.data('armed', true);
            btn.find('.delete-label').text('Click again to confirm');
            btn.addClass('bg-ember-50');
            setTimeout(() => {
                btn.data('armed', false);
                btn.find('.delete-label').text('Delete Room');
                btn.removeClass('bg-ember-50');
            }, 3000);
            return;
        }

        const roomTypeSlug = card.data('type');

        $.ajax({ url: `${base}/${roomId}`, method: 'DELETE' })
            .done(function (res) {
                if (!res.success) { toast('Could not delete room.', 'error'); return; }
                const wingGroup = card.closest('[data-wing-group]');
                card.fadeOut(150, function () {
                    $(this).remove();
                    const remaining = wingGroup.find('.room-card').length;
                    wingGroup.find('[data-wing-count]').text(remaining);
                    if (remaining === 0) wingGroup.addClass('hidden');
                    const deletedType = typeBySlug(roomTypeSlug);
                    if (deletedType) deletedType.room_count = Math.max(0, deletedType.room_count - 1);
                    renderTypeTiles();
                    recomputeAggregates();
                    applyFilters();
                });
                toast('Room ' + roomNumber + ' deleted.');
            })
            .fail(function (xhr) {
                toast(xhr.responseJSON?.message || 'This room has booking history and cannot be deleted.', 'error');
            });
    });

    /* ------------------- AGGREGATE RECOMPUTE (after status change / delete) ------------------- */
    function recomputeAggregates() {
        const cards = $('.room-card');
        const counts = { available: 0, occupied: 0, maintenance: 0, cleaning: 0 };
        cards.each(function () {
            const s = $(this).attr('data-status');
            if (counts[s] !== undefined) counts[s]++;
        });
        const total = cards.length;

        $('#statTotalNum').text(total);
        $('#statAvailableNum').text(counts.available);
        $('#statOccupiedNum').text(counts.occupied);
        $('#statMaintenanceNum').text(counts.maintenance);
        $('#statCleaningNum').text(counts.cleaning);
        $('#legendAvailable').text(counts.available);
        $('#legendOccupied').text(counts.occupied);
        $('#legendCleaning').text(counts.cleaning);
        $('#legendMaintenance').text(counts.maintenance);

        let wingsInUse = 0;
        $('[data-wing-group]').each(function () {
            const wingCards = $(this).find('.room-card');
            if (wingCards.length > 0) wingsInUse++;
            const open = wingCards.filter('[data-status="available"]').length;
            $(this).find('[data-wing-open]').text(open);
            $(this).find('[data-wing-bar]').css('width', (wingCards.length ? Math.round(open / wingCards.length * 100) : 0) + '%');
        });
        $('#statWingsNum').text(wingsInUse);
        $('#statTotalFoot').text('Across ' + wingsInUse + ' wings');
        $('#allRoomsSubtitle').text(total + ' rooms across ' + wingsInUse + ' wings');

        // Bookable-today counts per type: housekeeping-available and not
        // held by a stay spanning today (data-held set server-side).
        roomTypes.forEach(function (t) {
            t.available_now = $('.room-card[data-type="' + t.slug + '"][data-status="available"]').not('[data-held]').length;
        });
        renderTypeTiles();
    }

    /* ------------------- SEARCH + STATUS + WING + TYPE FILTER ------------------- */
    function applyFilters() {
        const term = ($('#roomSearch').val() || '').toString().trim().toLowerCase();
        const status = $('#roomStatusFilter').val();
        const wing = $('#wingFilterSelect').val();
        let anyVisible = false;

        // Count matches per wing directly rather than reading `.room-card:visible`
        // — a card inside a wing group that's still display:none reports itself as
        // not visible, which used to leave cleared searches showing nothing.
        $('[data-wing-group]').each(function () {
            const group = $(this);
            const cards = group.find('.room-card');

            // A wing emptied by deletion has no cards; keep it hidden.
            if (cards.length === 0) { group.hide(); return; }

            let matchesInWing = 0;
            cards.each(function () {
                const el = $(this);
                const haystack = ((el.attr('data-room-number') || '') + ' ' +
                                  typeName(el.attr('data-type')) + ' ' +
                                  (el.attr('data-wing') || '')).toLowerCase();
                const matchesTerm = !term || haystack.includes(term);
                const matchesStatus = status === 'all' || el.attr('data-status') === status;
                const matchesWing = wing === 'all' || el.attr('data-wing') === wing;
                const matchesType = !activeTypeFilter || el.attr('data-type') === activeTypeFilter;
                const show = matchesTerm && matchesStatus && matchesWing && matchesType;
                el.toggle(show);
                if (show) matchesInWing++;
            });

            group.toggle(matchesInWing > 0);
            if (matchesInWing > 0) anyVisible = true;
        });

        const hasFilters = term || status !== 'all' || wing !== 'all' || activeTypeFilter;
        $('#resetFiltersBtn').toggleClass('hidden', !hasFilters);
        $('#noRoomsMatch').toggleClass('hidden', anyVisible || $('.room-card').length === 0);
    }

    $('#roomSearch').on('input', applyFilters);
    $('#roomStatusFilter').on('change', applyFilters);
    $('#wingFilterSelect').on('change', applyFilters);
    $('#resetFiltersBtn').on('click', function () {
        $('#roomSearch').val('');
        $('#roomStatusFilter').val('all');
        $('#wingFilterSelect').val('all');
        activeTypeFilter = null;
        syncTypeFilterUI();
        renderTypeTiles();
        applyFilters();
    });

    /* ------------------- LIVE STATUS POLLER -------------------
       Periodically refreshes each room's status + stay line from the server so
       the board reflects check-ins/check-outs/cleaning done elsewhere without a
       manual reload. Only touches cards whose state actually changed. */
    /* The '-pending' kinds are unpaid holds: a booking owns the room but the
       money has not been verified. Same icon as their settled twin, palay
       (amber) rather than clsu (green). Wording comes from App\Support\RoomHold. */
    const STAY_ICON_PATHS = {
        current: '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
        'current-pending': '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
        next: '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>',
        'next-pending': '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>',
        none: '<polyline points="20 6 9 17 4 12"/>',
    };
    const STAY_LINE_CLASS = {
        current: 'room-stay-line flex items-center gap-1 text-[10px] font-semibold text-clsu-700',
        'current-pending': 'room-stay-line flex items-center gap-1 text-[10px] font-semibold text-palay-700',
        next: 'room-stay-line flex items-center gap-1 text-[10px] font-semibold text-palay-700',
        'next-pending': 'room-stay-line flex items-center gap-1 text-[10px] font-semibold text-palay-700',
        none: 'room-stay-line flex items-center gap-1 text-[10px] font-medium text-stone-400',
    };

    function applyStayToCard(card, stay, held, pending) {
        const line = card.find('.room-stay-line');
        if (line.attr('data-kind') !== stay.kind) {
            line.attr('data-kind', stay.kind);
            line.attr('class', STAY_LINE_CLASS[stay.kind] || STAY_LINE_CLASS.none);
            line.find('svg.icon').html(STAY_ICON_PATHS[stay.kind] || STAY_ICON_PATHS.none);
        }
        line.find('.room-stay-text').text(stay.label);
        if (stay.title) line.attr('title', stay.title); else line.removeAttr('title');
        if (held) card.attr('data-held', '1'); else card.removeAttr('data-held');

        // Who is holding it — .text(), never .html(): a guest controls their
        // own name.
        const guestLine = card.find('.room-guest-line');
        if (stay.guest) {
            guestLine.find('.room-guest-text').text(stay.guest);
            guestLine.removeAttr('hidden');
        } else {
            guestLine.find('.room-guest-text').text('');
            guestLine.attr('hidden', 'hidden');
        }

        // The badge's visibility hangs off this attribute alone (see
        // 16-room-board.css). Toggling a `hidden` class here did nothing:
        // Tailwind emits .inline-flex after .hidden, so the badge showed on
        // every card no matter what this said.
        if (pending) card.attr('data-hold-pending', '1'); else card.removeAttr('data-hold-pending');
    }

    let livePollInFlight = false;
    function pollRoomStatus() {
        // Don't fight the user: skip while a menu/modal is open or the tab is hidden.
        if (livePollInFlight || document.hidden) return;
        if ($('[data-kebab-panel]:not(.hidden)').length) return;
        if ($('#roomEditModal, #occupancyModal, #typeModal, #addRoomModal').filter('.flex').length) return;

        livePollInFlight = true;
        $.get(`${base}/status-feed`)
            .done(function (res) {
                if (!res || !res.success) return;
                let changed = false;

                res.rooms.forEach(function (r) {
                    const card = $('.room-card[data-room-id="' + r.id + '"]');
                    if (!card.length) return;

                    if (card.attr('data-status') !== r.status) {
                        applyStatusToCard(card, r.status);
                        changed = true;
                    }

                    const line = card.find('.room-stay-line');
                    const heldNow = card.attr('data-held') === '1';
                    const pendingNow = card.attr('data-hold-pending') === '1';
                    if (line.attr('data-kind') !== r.stay.kind
                        || line.find('.room-stay-text').text() !== r.stay.label
                        || heldNow !== r.held
                        || pendingNow !== r.pending) {
                        applyStayToCard(card, r.stay, r.held, r.pending);
                        changed = true;
                    }
                });

                if (changed) {
                    recomputeAggregates();
                    applyFilters();
                }
            })
            .always(function () { livePollInFlight = false; });
    }
    setInterval(pollRoomStatus, 10000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) pollRoomStatus();
    });
    $(window).on('focus', pollRoomStatus);

    // Real-time push: when any room's status changes anywhere (another staff
    // member, a guest booking online, a check-in/out), Reverb pushes here and
    // we refetch immediately instead of waiting for the 10s poll. The interval
    // above stays as a safety net if the socket drops.
    if (window.Echo) {
        window.Echo.channel('rooms').listen('.RoomStatusChanged', function () {
            pollRoomStatus();
        });
    }
});
</script>
@endpush
@endsection
