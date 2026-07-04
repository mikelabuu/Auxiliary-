@extends('layouts.admin')

@section('title', 'Admin - Room Management')
@section('page-title', 'Room Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $wingLabel = fn ($w) => ucwords(str_replace('_', ' ', $w));
    $statusMeta = [
        'available'   => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200', 'dot' => 'bg-clsu-400', 'bar' => 'bg-clsu-400', 'label' => 'Available'],
        'occupied'    => ['badge' => 'bg-clsu-800 text-white border-clsu-900', 'dot' => 'bg-white/70', 'bar' => 'bg-clsu-800', 'label' => 'Occupied'],
        'maintenance' => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200', 'dot' => 'bg-ember-500', 'bar' => 'bg-ember-500', 'label' => 'Maintenance'],
        'cleaning'    => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200', 'dot' => 'bg-palay-500', 'bar' => 'bg-palay-400', 'label' => 'Cleaning'],
    ];
    $roomsByWing = $rooms->groupBy('wing');
    $wingOrder = ['rooster', 'tumana', 'chev_re', 'torii'];
    $orderedWings = collect($wingOrder)->filter(fn ($w) => $roomsByWing->has($w))
        ->concat($roomsByWing->keys()->diff($wingOrder)->sort());

    // Built here (not via @json in the script) because Blade's @json directive
    // naively explode(',')s its raw argument text, which mangles any expression
    // containing commas inside nested arrays/closures.
    $statusMetaJson = json_encode(collect($statusMeta)->map(fn ($m) => [
        'label' => $m['label'],
        'badge' => $m['badge'],
        'dot'   => $m['dot'],
        'bar'   => $m['bar'],
    ]));
    $roomTypesJson = json_encode($roomTypes->map(fn ($t) => [
        'id' => $t->id,
        'slug' => $t->slug,
        'name' => $t->name,
        'base_price' => (float) $t->base_price,
        'capacity' => (int) $t->capacity,
        'room_count' => $t->rooms_count,
    ])->values());
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.page-header subtitle="Manage availability, wings, and pricing across all rooms.">
        Room <span class="font-display italic font-medium text-clsu-800">Management</span>
        <x-slot:actions>
            <a href="{{ route('staff.dashboard') }}#room-map" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.icon name="grid" class="w-4 h-4" />
                Status Map
            </a>
            <button type="button" id="openAddRoomBtn" class="flex items-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-4 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all cursor-pointer">
                <x-admin.icon name="plus" class="w-4 h-4" stroke-width="2" />
                Add Room
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Primary stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.stat-card icon="bed" badge="ALL WINGS" label="Total Rooms" :delay="40" value-id="statTotalNum">
            {{ $totalRooms }}
            <x-slot:footnote><p id="statTotalFoot" class="text-xs text-stone-400">Across {{ $roomsByWing->count() }} wings</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="check-circle" badge="READY" label="Available" :delay="80" value-id="statAvailableNum">
            {{ $availableRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">Ready for check-in</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="users" badge="IN USE" label="Occupied" :delay="120" value-id="statOccupiedNum" dark>
            {{ $occupiedRooms }}
            <x-slot:footnote><p class="text-xs text-clsu-300">Currently hosting guests</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="wrench" color="ember" badge="NEEDS ATTENTION" label="Maintenance" :delay="160" value-id="statMaintenanceNum">
            {{ $maintenanceRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">Out of rotation</p></x-slot:footnote>
        </x-admin.stat-card>
    </div>

    <!-- Secondary metrics strip -->
    <div class="animate-in grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6" style="animation-delay:200ms">
        <x-admin.mini-stat icon="droplet" color="palay" label="Rooms being cleaned" value-id="statCleaningNum">{{ $cleaningRooms }}</x-admin.mini-stat>
        <x-admin.mini-stat icon="grid" label="Wings in use" value-id="statWingsNum">{{ $roomsByWing->count() }}</x-admin.mini-stat>
        <x-admin.mini-stat icon="tag" label="Room types offered" value-id="statTypesNum">{{ $roomTypes->count() }}</x-admin.mini-stat>
    </div>

    <!-- Room Types & Pricing -->
    <x-admin.section-card icon="tag" title="Room Types & Pricing" subtitle="Base nightly rates by category — click a type to filter rooms below" :delay="240">
        <x-slot:actions>
            <button type="button" id="clearTypeFilterBtn" class="hidden shrink-0 items-center gap-1.5 text-xs font-semibold text-clsu-700 bg-clsu-50 hover:bg-clsu-100 rounded-full px-3 py-1.5 transition-colors cursor-pointer">
                <x-admin.icon name="x" class="w-3 h-3" stroke-width="2.5" />
                <span id="clearTypeFilterLabel">Showing: All</span>
            </button>
        </x-slot:actions>
        <div id="typesGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
    </x-admin.section-card>

    <!-- All Rooms -->
    <x-admin.section-card icon="grid" title="All Rooms" :subtitle="$totalRooms . ' rooms across ' . $roomsByWing->count() . ' wings'" subtitle-id="allRoomsSubtitle" :delay="280">
        <x-slot:actions>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-400"></span>Available · <span id="legendAvailable">{{ $availableRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-800"></span>Occupied · <span id="legendOccupied">{{ $occupiedRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Cleaning · <span id="legendCleaning">{{ $cleaningRooms }}</span></span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-ember-500"></span>Maintenance · <span id="legendMaintenance">{{ $maintenanceRooms }}</span></span>
        </x-slot:actions>

        <!-- Controls -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1 max-w-xs">
                <x-admin.icon name="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" stroke-width="2" />
                <input id="roomSearch" type="text" placeholder="Search room, type, or wing…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-full pl-10 pr-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
            </div>
            <select id="roomStatusFilter" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
                <option value="all">All statuses</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="cleaning">Cleaning</option>
                <option value="maintenance">Maintenance</option>
            </select>
            <select id="wingFilterSelect" class="w-full sm:w-40 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
                <option value="all">All wings</option>
                @foreach($orderedWings as $wing)
                    <option value="{{ $wing }}">{{ $wingLabel($wing) }}</option>
                @endforeach
            </select>
            <button type="button" id="resetFiltersBtn" class="hidden text-xs font-semibold text-stone-500 hover:text-clsu-700 px-2 transition-colors">Clear filters</button>
        </div>

        @forelse($orderedWings as $wing)
            @php $group = $roomsByWing[$wing]; @endphp
            <div class="wing-group mb-7 last:mb-0" data-wing-group>
                <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2.5 uppercase">{{ $wingLabel($wing) }} Wing · <span data-wing-count>{{ $group->count() }}</span> room{{ $group->count() === 1 ? '' : 's' }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($group as $room)
                        <x-admin.rooms.room-card :room="$room" :status-meta="$statusMeta" />
                    @endforeach
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="grid" title="No rooms yet. Add your first room to get started." />
        @endforelse

        <x-admin.empty-state id="noRoomsMatch" icon="search" title="No rooms match your search or filters." class="hidden" />
    </x-admin.section-card>
</div>

<!-- ==================== Add Room Modal ==================== -->
<x-admin.modal id="addRoomModal" icon="plus" title="Add New Room" scroll-body>
    <form action="{{ route('staff.rooms.store') }}" method="POST" class="px-6 py-5 space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Number</label>
            <input type="text" name="room_number" value="{{ old('room_number') }}" placeholder="e.g. A-101" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
            @error('room_number')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Type</label>
                <select name="room_type" id="room-type" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors"></select>
                @error('room_type')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Wing</label>
                <select name="wing" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
                    <option value="" disabled {{ old('wing') ? '' : 'selected' }} hidden>Select wing</option>
                    <option value="rooster" @selected(old('wing') === 'rooster')>Rooster</option>
                    <option value="tumana" @selected(old('wing') === 'tumana')>Tumana</option>
                    <option value="chev_re" @selected(old('wing') === 'chev_re')>Chev Re</option>
                    <option value="torii" @selected(old('wing') === 'torii')>Torii</option>
                </select>
                @error('wing')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Price (₱)</label>
                <input type="number" step="0.01" name="price" id="price" value="{{ old('price') }}" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
                @error('price')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
                    <option value="available" selected>Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Under Maintenance</option>
                    <option value="cleaning">Cleaning</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Notes <span class="text-stone-400 font-normal normal-case">(optional)</span></label>
            <textarea name="notes" rows="2" placeholder="e.g. Ground floor, near the entrance" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors resize-none">{{ old('notes') }}</textarea>
            @error('notes')<p class="text-ember-600 text-xs mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-2.5 justify-end pt-2">
            <button type="button" data-modal-close="addRoomModal" class="text-sm font-medium text-stone-600 border border-stone-200 bg-white rounded-xl px-4 py-2.5 hover:bg-stone-50 transition-colors cursor-pointer">Cancel</button>
            <button type="submit" class="text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-5 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all cursor-pointer">Add Room</button>
        </div>
    </form>
</x-admin.modal>

<!-- ==================== Edit Room Modal ==================== -->
<x-admin.modal id="roomEditModal" icon="edit" title="Edit Room" scroll-body>
    <form id="roomEditForm">
        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="editRoomId">
            <p id="editFormError" class="hidden text-ember-600 text-xs bg-ember-50 border border-ember-100 rounded-lg px-3 py-2"></p>

            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Number</label>
                <input type="text" id="editRoomNumber" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Room Type</label>
                    <select id="editRoomType" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors" required></select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Wing</label>
                    <select id="editWing" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors" required>
                        <option value="" disabled hidden>Select wing</option>
                        <option value="rooster">Rooster</option>
                        <option value="tumana">Tumana</option>
                        <option value="chev_re">Chev Re</option>
                        <option value="torii">Torii</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Price (₱)</label>
                    <input type="number" step="0.01" id="editPrice" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Status</label>
                    <select id="editStatus" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Under Maintenance</option>
                        <option value="cleaning">Cleaning</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Notes <span class="text-stone-400 font-normal normal-case">(optional)</span></label>
                <textarea id="editNotes" rows="2" placeholder="e.g. Ground floor, near the entrance" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors resize-none"></textarea>
            </div>
        </div>
        <div class="flex gap-2.5 justify-end border-t border-stone-100 px-6 py-4">
            <button type="button" data-modal-close="roomEditModal" class="text-sm font-medium text-stone-600 border border-stone-200 bg-white rounded-xl px-4 py-2.5 hover:bg-stone-50 transition-colors cursor-pointer">Cancel</button>
            <button type="submit" class="text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-5 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all cursor-pointer">Save changes</button>
        </div>
    </form>
</x-admin.modal>

<!-- ==================== Add / Edit Room Type Modal ==================== -->
<x-admin.modal id="typeModal" icon="tag" color="palay" title="Add Room Type" title-id="typeModalTitleText" max-width="sm">
    <form id="typeForm">
        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="typeFormId">
            <p id="typeFormError" class="hidden text-ember-600 text-xs bg-ember-50 border border-ember-100 rounded-lg px-3 py-2"></p>

            <div>
                <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Type Name</label>
                <input type="text" id="typeFormName" placeholder="e.g. Family Suite" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Base Price (₱)</label>
                    <input type="number" step="0.01" min="0" id="typeFormPrice" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Sleeps</label>
                    <input type="number" min="1" step="1" id="typeFormCapacity" required class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
                </div>
            </div>
            <p class="text-[11px] text-stone-400">Changing the base price only affects new rooms — existing rooms keep their current price.</p>
        </div>
        <div class="flex gap-2.5 justify-end border-t border-stone-100 px-6 py-4">
            <button type="button" data-modal-close="typeModal" class="text-sm font-medium text-stone-600 border border-stone-200 bg-white rounded-xl px-4 py-2.5 hover:bg-stone-50 transition-colors cursor-pointer">Cancel</button>
            <button type="submit" class="text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-5 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all cursor-pointer">Save Type</button>
        </div>
    </form>
</x-admin.modal>

<!-- ==================== Room Occupancy Modal ==================== -->
<x-admin.modal id="occupancyModal" icon="eye" title="Current Occupancy">
    <div class="px-6 py-5 space-y-2.5 max-h-[60vh] overflow-y-auto" id="occupancyModalBody">
        <p class="text-center text-stone-400 text-sm py-6">Loading…</p>
    </div>
</x-admin.modal>

@push('scripts')
<script>
$(function () {
    const base = "{{ url('staff/rooms') }}";
    const typesBase = "{{ url('staff/room-types') }}";
    const STATUS_META = {!! $statusMetaJson !!};
    let roomTypes = {!! $roomTypesJson !!};
    let activeTypeFilter = null;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function peso(n) { return '₱' + Number(n || 0).toLocaleString('en-PH'); }
    function typeBySlug(slug) { return roomTypes.find(t => t.slug === slug); }
    function typeName(slug) { const t = typeBySlug(slug); return t ? t.name : (slug ? slug.charAt(0).toUpperCase() + slug.slice(1) : ''); }

    function toast(message, icon) {
        Swal.fire({ toast: true, position: 'bottom-end', icon: icon || 'success', title: message, showConfirmButton: false, timer: 2400, timerProgressBar: true });
    }

    function openModal(id) { $('#' + id).removeClass('hidden').addClass('flex'); }
    function closeModal(id) { $('#' + id).addClass('hidden').removeClass('flex'); }

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

    @if(session('success'))
        toast(@json(session('success')));
    @endif

    /* ------------------- ROOM TYPE SELECT OPTIONS ------------------- */
    function populateRoomTypeSelects() {
        const options = roomTypes.map(t => '<option value="' + t.slug + '" data-price="' + t.base_price + '">' + t.name + ' — ' + peso(t.base_price) + '</option>').join('');
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
                '<div class="type-tile relative bg-white rounded-xl border ' + (isActive ? 'border-clsu-400 ring-1 ring-clsu-200' : 'border-stone-200/70') + ' shadow-subtle hover:shadow-card transition-all duration-200 overflow-hidden cursor-pointer" data-type-tile="' + t.slug + '">' +
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

    /* ------------------- AUTO-FILL PRICE (Add form) ------------------- */
    $('#room-type').on('change', function () {
        const t = typeBySlug($(this).val());
        if (t) $('#price').val(t.base_price);
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
                if (!res.bookings.length) {
                    modalBody.html('<p class="text-center text-stone-400 text-sm py-6">No active bookings for this room.</p>');
                    return;
                }
                modalBody.html(res.bookings.map(b => `
                    <div class="flex items-start gap-3 p-3.5 rounded-xl border border-stone-100 bg-stone-50/60">
                        <div class="w-9 h-9 rounded-full bg-clsu-100 text-clsu-700 flex items-center justify-center shrink-0 font-bold text-xs">
                            ${(b.guest_name || '?').substring(0, 2).toUpperCase()}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-stone-800 truncate">${b.guest_name}</p>
                            <p class="text-xs text-stone-500 mt-0.5">${b.check_in_formatted} → ${b.check_out_formatted}</p>
                        </div>
                        <span class="text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 shrink-0 whitespace-nowrap">${b.status}</span>
                    </div>
                `).join('<div class="h-2.5"></div>'));
            })
            .fail(function () {
                modalBody.html('<p class="text-center text-ember-600 text-sm py-6">Error fetching booking info.</p>');
            });
    });

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
            $('#editStatus').val(room.status);
            $('#editNotes').val(room.notes || '');
            $('#editFormError').addClass('hidden').text('');

            openModal('roomEditModal');
        });
    });

    $('#editRoomType').on('change', function () {
        const t = typeBySlug($(this).val());
        if (t) $('#editPrice').val(t.base_price);
    });

    $('#roomEditForm').on('submit', function (e) {
        e.preventDefault();
        const roomId = $('#editRoomId').val();
        const payload = {
            room_number: $('#editRoomNumber').val(),
            room_type: $('#editRoomType').val(),
            wing: $('#editWing').val(),
            price: $('#editPrice').val(),
            status: $('#editStatus').val(),
            notes: $('#editNotes').val(),
        };

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

        $.ajax({ url: `${base}/${roomId}`, method: 'DELETE' })
            .done(function (res) {
                if (!res.success) { toast('Could not delete room.', 'error'); return; }
                const wingGroup = card.closest('[data-wing-group]');
                card.fadeOut(150, function () {
                    $(this).remove();
                    const remaining = wingGroup.find('.room-card').length;
                    wingGroup.find('[data-wing-count]').text(remaining);
                    if (remaining === 0) wingGroup.addClass('hidden');
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
            if ($(this).find('.room-card').length > 0) wingsInUse++;
        });
        $('#statWingsNum').text(wingsInUse);
        $('#statTotalFoot').text('Across ' + wingsInUse + ' wings');
        $('#allRoomsSubtitle').text(total + ' rooms across ' + wingsInUse + ' wings');
    }

    /* ------------------- SEARCH + STATUS + WING + TYPE FILTER ------------------- */
    function applyFilters() {
        const term = ($('#roomSearch').val() || '').toString().trim().toLowerCase();
        const status = $('#roomStatusFilter').val();
        const wing = $('#wingFilterSelect').val();

        $('.room-card').each(function () {
            const el = $(this);
            const haystack = (el.data('room-number') + ' ' + typeName(el.data('type')) + ' ' + el.data('wing')).toString().toLowerCase();
            const matchesTerm = !term || haystack.includes(term);
            const matchesStatus = status === 'all' || el.attr('data-status') === status;
            const matchesWing = wing === 'all' || el.data('wing').toString() === wing;
            const matchesType = !activeTypeFilter || el.data('type').toString() === activeTypeFilter;
            el.toggle(matchesTerm && matchesStatus && matchesWing && matchesType);
        });

        let anyVisible = false;
        $('[data-wing-group]').each(function () {
            if ($(this).hasClass('hidden')) return; // emptied by a delete, stays hidden regardless of filters
            const visibleCount = $(this).find('.room-card:visible').length;
            $(this).toggle(visibleCount > 0);
            if (visibleCount > 0) anyVisible = true;
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
});
</script>
@endpush
@endsection
