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

    <x-admin.ui.page-header subtitle="Here's what's happening at Farmers Hostel today.">
        Welcome back, <span class="text-clsu-700">{{ explode(' ', Auth::guard('staff')->user()->name)[0] }}</span>
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" type="button" id="openInsightsBtn">
                <x-admin.ui.icon name="chart-bar" class="w-4 h-4" />
                Booking insights
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" type="button" id="openCalendarBtn">
                <x-admin.ui.icon name="calendar" class="w-4 h-4" />
                Calendar
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" :href="route('staff.reports.index')">
                <x-admin.ui.icon name="calendar" class="w-4 h-4" />
                View Reports
            </x-admin.ui.button>
            <x-admin.ui.button variant="primary" :href="route('staff.manualbooking')">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New Booking
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    {{-- Stat cards + secondary strip: live Livewire component (polls + follows
         the same broadcast pushes as the room map). KPIs lead the page — the
         numbers are what a manager scans first; actions follow. --}}
    <livewire:dashboard.stat-cards />

    <!-- Quick Actions Grid — each tile lands on the page where the task is actually completed -->
    <div class="animate-in grid grid-cols-2 lg:grid-cols-4 gap-3" style="animation-delay:20ms">
        <x-admin.ui.quick-action icon="plus" title="New Booking" subtitle="Walk-in or phone" :href="route('staff.manualbooking')" />
        <x-admin.ui.quick-action icon="log-in" title="Arrivals & Departures" subtitle="Check guests in / out" :href="route('staff.bookings.index') . '#arrivals'" />
        <x-admin.ui.quick-action icon="bed" title="Room Status" subtitle="Block or free rooms" :href="route('staff.rooms')" />
        <x-admin.ui.quick-action icon="credit-card" title="Payments" subtitle="Review the ledger" :href="route('staff.paymentlogs.index')" />
    </div>

    {{-- Booking Insights + Calendar modals (opened from the header buttons) --}}
    @include('partials.dashboard.insights-modal')
    @include('partials.dashboard.calendar-modal')

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

    const CLASSES = {
        available:   'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300 border-solid',
        occupied:    'bg-clsu-600 text-white border-clsu-700 hover:bg-clsu-700 border-solid',
        reserved:    'bg-palay-100 text-palay-800 border-palay-300 hover:bg-palay-200 border-dashed',
        cleaning:    'bg-sky-50 text-sky-800 border-sky-300 hover:bg-sky-100 hover:border-sky-400 border-dotted',
        maintenance: 'bg-ember-50 text-ember-800 border-ember-300 hover:bg-ember-100 border-double border-[3px]',
    };
    const STATUS_LABELS = {
        available: 'Available', occupied: 'Occupied', reserved: 'Reserved',
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
        const num  = numMatch  ? numMatch[1]  : btn.textContent.trim();
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

    // ── Clickable tiles: occupancy popover ──────────────────────────────────
    // Click a room → who's in it now and who arrives next, from the existing
    // staff.rooms.occupancy endpoint. Guest names inside open guest history.
    const pop = document.createElement('div');
    pop.id = 'roomMapPopover';
    pop.className = 'hidden fixed z-[210] w-[320px] bg-white rounded-2xl border border-stone-200 shadow-card-lg overflow-hidden';
    document.body.appendChild(pop);
    let popRoomId = null;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function hidePopover() { pop.classList.add('hidden'); popRoomId = null; }

    function positionPopover(btn) {
        const r = btn.getBoundingClientRect();
        const w = 320;
        let left = Math.min(Math.max(12, r.left), window.innerWidth - w - 12);
        pop.style.left = left + 'px';
        pop.style.visibility = 'hidden';
        pop.classList.remove('hidden');
        const h = pop.offsetHeight;
        // Below the tile unless it would clip the viewport bottom
        let top = r.bottom + 8;
        if (top + h > window.innerHeight - 12) top = Math.max(12, r.top - h - 8);
        pop.style.top = top + 'px';
        pop.style.visibility = '';
    }

    function popoverHeader(btn) {
        const status = btn.dataset.displayStatus || 'available';
        const room = esc(btn.textContent.trim());
        const occupant = btn.dataset.occupant ? '<p class="text-xs text-stone-500 mt-0.5">' + esc(btn.dataset.occupant) + '</p>' : '';
        return '<div class="px-4 py-3 border-b border-stone-100 bg-stone-50/60">'
            + '<div class="flex items-center justify-between gap-2">'
            + '<p class="text-sm font-bold text-stone-900 font-data">Room ' + room + '</p>'
            + '<span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border ' + (CLASSES[status] || '').split(/\s+/).slice(0, 3).join(' ') + '">' + esc(STATUS_LABELS[status] || cap(status)) + '</span>'
            + '</div>' + occupant + '</div>';
    }

    function bookingRow(b) {
        const chip = b.timeline === 'current'
            ? '<span class="text-[9px] font-bold uppercase tracking-wide text-clsu-700 bg-clsu-50 border border-clsu-200 rounded-full px-1.5 py-0.5">Now</span>'
            : '<span class="text-[9px] font-bold uppercase tracking-wide text-palay-800 bg-palay-100 border border-palay-200 rounded-full px-1.5 py-0.5">Upcoming</span>';
        return '<div class="px-4 py-2.5 border-b border-stone-50 last:border-0">'
            + '<div class="flex items-center justify-between gap-2">'
            + '<p class="guest-history-link cursor-pointer hover:underline text-sm font-semibold text-stone-800 truncate" data-booking-id="' + esc(b.id) + '" title="View guest history">' + esc(b.guest_name) + '</p>'
            + chip + '</div>'
            + '<p class="text-xs text-stone-500 mt-0.5 font-data tabnum">' + esc(b.check_in_formatted) + ' - ' + esc(b.check_out_formatted)
            + ' · ' + esc(b.nights) + (b.nights == 1 ? ' night' : ' nights') + ' · ' + esc(b.status) + '</p>'
            + '</div>';
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.room-map-btn');
        if (!btn) {
            if (!e.target.closest('#roomMapPopover')) hidePopover();
            return;
        }
        const roomId = btn.getAttribute('data-room-btn');
        if (popRoomId === roomId && !pop.classList.contains('hidden')) { hidePopover(); return; }
        popRoomId = roomId;

        pop.innerHTML = popoverHeader(btn) + '<div class="px-4 py-3 text-xs text-stone-400">Loading…</div>';
        positionPopover(btn);

        fetch('{{ url('staff/rooms') }}/' + roomId + '/occupancy', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(res => {
                if (popRoomId !== roomId) return; // user moved on
                let body;
                if (res && res.success && res.bookings && res.bookings.length) {
                    body = res.bookings.map(bookingRow).join('');
                } else {
                    body = '<div class="px-4 py-3 text-xs text-stone-400">No current or upcoming stays.</div>';
                }
                pop.innerHTML = popoverHeader(btn) + '<div class="max-h-64 overflow-y-auto">' + body + '</div>'
                    + '<div class="px-4 py-2 border-t border-stone-100 bg-stone-50/60">'
                    + '<a href="{{ route('staff.rooms') }}" class="text-[11px] font-bold text-clsu-700 !no-underline hover:underline">Manage rooms →</a></div>';
                positionPopover(btn);
            })
            .catch(() => {
                if (popRoomId !== roomId) return;
                pop.innerHTML = popoverHeader(btn) + '<div class="px-4 py-3 text-xs text-ember-600">Could not load occupancy.</div>';
            });
    });

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hidePopover(); });
    window.addEventListener('scroll', hidePopover, { passive: true });
    window.addEventListener('resize', hidePopover);
});
</script>
@endpush
