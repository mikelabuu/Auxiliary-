@extends('layouts.admin')

@section('title', 'Admin - Dashboard')
@section('page-title', 'Dashboard')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>

<div class="space-y-6 max-w-[1680px] mx-auto">

    {{-- Top row: welcome panel (today's ops at a glance + the four actions that
         start a task) beside the gross-revenue hero. Replaces the old
         page-header — its buttons live in the panel now — and the quick-action
         tile grid, which repeated those same destinations a second time. --}}
    <livewire:dashboard.hero />

    {{-- Stat cards + secondary strip: live Livewire component (polls + follows
         the same broadcast pushes as the room map). --}}
    <livewire:dashboard.stat-cards />

    {{-- Booking Insights + Calendar modals (opened from the header buttons)
         Pushed to the body level so they aren't trapped in the animated content flow --}}
    @push('modals')
        @include('partials.dashboard.insights-modal')
        @include('partials.dashboard.calendar-modal')
    @endpush

    <!-- Rooms & occupancy row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Room Status Map (original design + proportional legend bars) -->
        <x-admin.ui.section-card id="room-map" icon="grid" title="Room Status Map" :subtitle="'All ' . $totalRooms . ' rooms at a glance'" class="scroll-mt-20 lg:col-span-2" :delay="260">
            <x-slot:actions>
                @php
                    $legendItems = [
                        ['key' => 'available',   'label' => 'Available',   'count' => $availableCount,   'mod' => 'available'],
                        ['key' => 'occupied',    'label' => 'Occupied',    'count' => $occupiedCount,    'mod' => 'occupied'],
                        ['key' => 'reserved',    'label' => 'Reserved',    'count' => $reservedCount,    'mod' => 'reserved'],
                        ['key' => 'pending',     'label' => 'Unpaid hold', 'count' => $pendingCount,     'mod' => 'pending'],
                        ['key' => 'cleaning',    'label' => 'Cleaning',    'count' => $cleaningCount,    'mod' => 'cleaning'],
                        ['key' => 'maintenance', 'label' => 'Maintenance', 'count' => $maintenanceCount, 'mod' => 'maintenance'],
                    ];
                @endphp
                @foreach($legendItems as $li)
                    @php $pct = $totalRooms > 0 ? round(($li['count'] / $totalRooms) * 100) : 0; @endphp
                    <span class="map-stat map-stat--{{ $li['mod'] }}">
                        <span class="map-stat__top">
                            <span class="map-key map-key--{{ $li['mod'] }}"></span>
                            {{ $li['label'] }} · <span class="map-stat__n" data-map-count="{{ $li['key'] }}">{{ $li['count'] }}</span>
                        </span>
                        <span class="map-stat__bar" aria-hidden="true">
                            <span class="map-stat__fill map-stat__fill--{{ $li['mod'] }}"
                                  data-map-fill="{{ $li['key'] }}"
                                  style="width:{{ $pct }}%"></span>
                        </span>
                    </span>
                @endforeach
            </x-slot:actions>

            <div class="space-y-5">
                <div>
                    <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">Dorm Rooms · {{ $dormBeds->count() }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($dormBeds as $room)
                            <x-admin.rooms.map-tile :room="$room" />
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">Standard Rooms · {{ $standardRooms->count() }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($standardRooms as $room)
                            <x-admin.rooms.map-tile :room="$room" />
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">Deluxe Rooms · {{ $deluxeRooms->count() }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($deluxeRooms as $room)
                            <x-admin.rooms.map-tile :room="$room" />
                        @endforeach
                    </div>
                </div>
            </div>
        </x-admin.ui.section-card>

        <!-- Occupancy Snapshot component -->
        <livewire:dashboard.occupancy-snapshot />
    </div>

    <!-- Bottom row -->


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Arrivals & Departures -->
        <div class="lg:col-span-2">
            <livewire:dashboard.arrivals-departures />
        </div>

        <!-- Right Side: Recent Activity (calendar moved to a header-button modal) -->
        <div class="space-y-6">
            <!-- Recent Activity (live Livewire component) -->
            <x-admin.ui.section-card icon="clock" title="Recent Activity" :delay="420">
                <livewire:dashboard.recent-activity />
            </x-admin.ui.section-card>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
// ── Real-time Room Status Map ────────────────────────────────────────────────
// Targets .room-map-btn (original flat buttons).
// Also keeps proportional legend bars (data-map-fill) in sync.
document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('.room-map-btn')) return;

    // Must stay in step with the same maps in components/admin/rooms/map-tile.
    const CLASSES = {
        available:   'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300 border-solid',
        occupied:    'bg-clsu-600 text-white border-clsu-700 hover:bg-clsu-700 border-solid',
        reserved:    'bg-palay-100 text-palay-800 border-palay-300 hover:bg-palay-200 border-dashed',
        pending:     'bg-palay-50 text-palay-700 border-palay-400 hover:bg-palay-100 border-dashed',
        cleaning:    'bg-sky-50 text-sky-800 border-sky-300 hover:bg-sky-100 hover:border-sky-400 border-dotted',
        maintenance: 'bg-ember-50 text-ember-800 border-ember-300 hover:bg-ember-100 border-double border-[3px]',
    };
    const STATUS_LABELS = {
        available: 'Available', occupied: 'Occupied', reserved: 'Reserved',
        pending: 'Reserved · awaiting payment',
        cleaning: 'Cleaning', maintenance: 'Maintenance',
    };
    const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : s;

    function patchButton(btn, status, occupant, updatedAt) {
        const prev = btn.dataset.displayStatus;
        if (prev === status && (btn.dataset.occupant || '') === (occupant || '')) return;

        // Swap Tailwind classes
        if (prev && CLASSES[prev]) {
            CLASSES[prev].split(/\s+/).forEach(c => btn.classList.remove(c));
        }
        if (CLASSES[status]) {
            CLASSES[status].split(/\s+/).forEach(c => btn.classList.add(c));
        }
        btn.dataset.displayStatus = status;

        // Occupant data attr
        if (occupant) { btn.dataset.occupant = occupant; } else { delete btn.dataset.occupant; }

        // Update status dot/shape class for accessibility
        const dot = btn.querySelector('[data-status-dot]');
        if (dot) {
            dot.className = 'absolute top-1.5 right-1.5';
            if (status === 'available') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-clsu-400';
            } else if (status === 'occupied') {
                dot.className += ' w-1.5 h-1.5 rounded-full bg-white';
            } else if (status === 'reserved') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-dashed border-palay-500';
            } else if (status === 'pending') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-dashed border-palay-600';
            } else if (status === 'cleaning') {
                dot.className += ' w-1.5 h-1.5 rounded-full border border-dotted border-sky-500';
            } else if (status === 'maintenance') {
                dot.className += ' w-1.5 h-1.5 bg-ember-500 rotate-45';
            }
        }

        // Tooltip: number · type (from existing title if present) · status · occupant [· Updated last-updated]
        const existing = btn.title || '';
        const numMatch = existing.match(/^([^\s·]+)/);
        const typeMatch = existing.match(/·\s*([^·]+?)\s*·/);
        const num  = numMatch  ? numMatch[1]  : (btn.dataset.roomNumber || btn.textContent.trim());
        const type = typeMatch ? typeMatch[1].trim() : '';
        let newTitle = num + (type ? ' · ' + type : '') + ' · ' + (STATUS_LABELS[status] || cap(status));
        if (occupant) {
            newTitle += ' · ' + occupant;
        }
        if (updatedAt) {
            newTitle += ' · Updated ' + updatedAt;
        }
        btn.title = newTitle;
    }

    function updateLegendBars(counts) {
        if (!counts) return;
        const total = Object.values(counts).reduce((a, b) => a + b, 0);
        if (!total) return;
        Object.keys(counts).forEach(k => {
            const fill = document.querySelector('[data-map-fill="' + k + '"]');
            if (fill) fill.style.width = Math.round((counts[k] / total) * 100) + '%';
        });
    }

    function applyFeed(data) {
        if (!data || !data.success) return;
        (data.rooms || []).forEach(r => {
            const btn = document.querySelector('.room-map-btn[data-room-btn="' + r.id + '"]');
            if (btn) patchButton(btn, r.display_status, r.occupant, r.updated_at);
        });
        if (data.counts) {
            Object.keys(data.counts).forEach(k => {
                document.querySelectorAll('[data-map-count="' + k + '"]').forEach(el => {
                    el.textContent = data.counts[k];
                });
            });
            updateLegendBars(data.counts);
        }
    }

    let inFlight = false;
    function fetchMap() {
        if (inFlight || document.hidden) return;
        inFlight = true;
        fetch("{{ route('staff.dashboard.roomMapFeed') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(applyFeed)
            .catch(() => {})
            .finally(() => { inFlight = false; });
    }

    let mapTimer = null;
    function scheduleFetch() {
        clearTimeout(mapTimer);
        mapTimer = setTimeout(function () {
            fetchMap();
            if (window.Livewire) {
                Livewire.dispatch('refreshOccupancy');
                Livewire.dispatch('refreshDashboardStats');
                Livewire.dispatch('refreshRecentActivity');
            }
        }, 400);
    }
    if (window.Echo) {
        window.Echo.channel('rooms').listen('.RoomStatusChanged', scheduleFetch);
        window.Echo.channel('bookings').listen('.BookingChanged', scheduleFetch);
    }

    setInterval(fetchMap, 20000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) fetchMap(); });
    window.addEventListener('focus', fetchMap);

    // Clickable tiles → expandable bento (resources/js/expandable-bento.js): each
    // room morphs into an occupancy detail card, fetched from
    // staff/rooms/{id}/occupancy and built by the roomOccupancy renderer that the
    // map-tile component registers. The real-time feed above keeps tiles patched.
});
</script>
@endpush
