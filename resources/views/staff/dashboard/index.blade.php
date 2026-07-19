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

    <!-- Booking Insights modal (opened from the page header) -->
    <x-admin.ui.modal id="bookingInsightsModal" icon="chart-bar" title="Booking Insights" max-width="xl" scroll-body>
        <div class="modal-body">
            <div class="flex items-center justify-between gap-3 mb-6">
                <p class="text-xs text-stone-500">Peak month: <span class="font-semibold text-palay-700">{{ $peakMonthName }} · {{ $peakMonthCount }} bookings</span></p>
                <span class="text-xs font-semibold text-stone-500 bg-stone-50 border border-stone-200 rounded-full px-3 py-1.5">{{ date('Y') }}</span>
            </div>

            <div class="pl-1">
                <div class="flex gap-3">
                    @php
                        $maxVal = empty($values) ? 0 : max($values);
                        $step = ceil($maxVal / 4);
                        if($step == 0) $step = 1;
                        $maxScale = $step * 4;
                    @endphp
                    <div class="w-4 shrink-0 flex flex-col justify-between h-40 text-[10px] text-stone-300 tabnum text-right">
                        <span>{{ $maxScale }}</span>
                        <span>{{ $maxScale - $step }}</span>
                        <span>{{ $maxScale - $step*2 }}</span>
                        <span>{{ $maxScale - $step*3 }}</span>
                        <span>0</span>
                    </div>
                    <div class="flex-1 relative h-40 border-b border-stone-100">
                        <div class="absolute inset-0 flex flex-col justify-between pb-0 pointer-events-none">
                            <div class="border-t border-dashed border-stone-100"></div>
                            <div class="border-t border-dashed border-stone-100"></div>
                            <div class="border-t border-dashed border-stone-100"></div>
                            <div class="border-t border-dashed border-stone-100"></div>
                            <div></div>
                        </div>
                        <div class="relative h-full flex items-end gap-2 px-1 chart-rise">
                            @foreach($values as $index => $val)
                                @php
                                    $heightPercent = $maxScale > 0 ? ($val / $maxScale) * 100 : 0;
                                    $isPeak = $val == $peakMonthCount && $val > 0;
                                    $barColor = $isPeak ? 'bg-gradient-to-t from-palay-600 to-palay-400' : 'bg-gradient-to-t from-clsu-600 to-clsu-400 group-hover:from-clsu-700 group-hover:to-clsu-500';
                                    if($val == 0) $barColor = 'bg-stone-100';
                                @endphp
                                <div class="flex-1 group relative flex justify-center h-full items-end">
                                    @if($val > 0)
                                        <div class="rounded-t-md w-full {{ $barColor }} transition-colors" style="height:{{ $heightPercent }}%"></div>
                                        <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[11px] font-bold opacity-0 group-hover:opacity-100 transition-opacity {{ $isPeak ? 'text-palay-700 bg-palay-50' : 'text-clsu-700 bg-clsu-50' }} rounded-full px-1.5 py-0.5 tracking-wide whitespace-nowrap">{{ $val }} · ₱{{ number_format($revenueValues[$index]) }}</span>
                                    @else
                                        <div class="rounded-t-md w-full bg-stone-100 h-0"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-2">
                    <div class="w-4 shrink-0"></div>
                    <div class="flex-1 flex gap-2 px-1 text-[10px] font-medium text-stone-400">
                        @foreach($labels as $index => $label)
                            @php
                                $isPeak = $values[$index] == $peakMonthCount && $values[$index] > 0;
                            @endphp
                            <span class="flex-1 text-center {{ $isPeak ? 'text-palay-700 font-bold' : '' }}">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-admin.ui.modal>

    <script>
      (function () {
        function openBI() { window.openModal('bookingInsightsModal'); }
        function closeBI() { window.closeModal('bookingInsightsModal'); }
        $(document).on('click', '#openInsightsBtn', openBI);
        $('#bookingInsightsModal').on('click', '[data-modal-close]', closeBI);
        $(document).on('keydown', function (e) { if (e.key === 'Escape' && $('#bookingInsightsModal').hasClass('flex')) closeBI(); });
      })();
    </script>

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

        <!-- Right Side: Calendar & Recent Activity -->
        <div class="space-y-6">
            <!-- Calendar snapshot (bespoke dark header, not a section-card) -->
            <div class="animate-in bg-white rounded-2xl border border-stone-200 shadow-card hover:shadow-card-lg transition-shadow duration-200 overflow-hidden" style="animation-delay:380ms">
                <div class="flex items-center justify-between bg-gradient-to-r from-clsu-600 to-clsu-700 text-white px-4 py-3.5">
                    <button id="prev" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/50 cursor-pointer">
                        <x-admin.ui.icon name="chevron-left" class="w-3.5 h-3.5" stroke-width="2.5" />
                    </button>
                    <p id="monthYear" class="text-xs font-bold tracking-widest tabnum uppercase"></p>
                    <button id="next" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/50 cursor-pointer">
                        <x-admin.ui.icon name="chevron-right" class="w-3.5 h-3.5" stroke-width="2.5" />
                    </button>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-7 text-center text-[10px] font-bold text-stone-400 mb-2">
                        <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                    </div>
                    <div id="calendarDays" class="grid grid-cols-7 gap-y-2 text-xs tabnum">
                        <!-- JS generated -->
                    </div>
                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-stone-100 text-[10px] font-medium text-stone-400">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-clsu-700"></span>Today</span>
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Has bookings</span>
                    </div>
                </div>
                {{-- Live month summary — counts the amber dots above --}}
                <div class="px-4 pb-4">
                    <div class="bg-gradient-to-br from-clsu-50 to-white border border-clsu-100 rounded-xl p-3.5">
                        <p class="text-xs font-semibold text-clsu-800" id="calStatTitle"></p>
                        <p class="text-[11px] text-clsu-600 mt-0.5" id="calStatSub"></p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (live Livewire component) -->
            <x-admin.ui.section-card icon="clock" title="Recent Activity" :delay="420">
                <livewire:dashboard.recent-activity />
            </x-admin.ui.section-card>
        </div>
    </div>
</div>


<script>
    const monthYear = document.getElementById("monthYear");
    const calendarDays = document.getElementById("calendarDays");
    const prevBtn = document.getElementById("prev");
    const nextBtn = document.getElementById("next");

    // Dates (YYYY-MM-DD) with at least one active/upcoming stay
    const bookedDates = new Set(@json($bookedDates));

    let date = new Date();

    const renderCalendar = () => {
      const year = date.getFullYear();
      const month = date.getMonth();

      const months = [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
      ];

      monthYear.textContent = `${months[month].toUpperCase()} ${year}`;

      const firstDay = new Date(year, month, 1).getDay();
      const lastDate = new Date(year, month + 1, 0).getDate();
      const prevLastDate = new Date(year, month, 0).getDate();

      let daysHTML = "";

      for (let i = firstDay; i > 0; i--) {
        daysHTML += `<div class="flex flex-col items-center gap-0.5"><span class="w-7 h-7 flex items-center justify-center text-stone-300">${prevLastDate - i + 1}</span><span class="w-1 h-1 rounded-full bg-transparent"></span></div>`;
      }

      for (let i = 1; i <= lastDate; i++) {
        const today = new Date();
        const isToday =
          i === today.getDate() &&
          month === today.getMonth() &&
          year === today.getFullYear();

        const cellClass = isToday
            ? "w-7 h-7 rounded-full bg-clsu-700 text-white font-bold flex items-center justify-center ring-4 ring-clsu-100"
            : "w-7 h-7 flex items-center justify-center text-stone-700 hover:bg-stone-100 rounded-full cursor-pointer transition";

        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const dotClass = bookedDates.has(dateStr) ? "bg-palay-400" : "bg-transparent";

        daysHTML += `<div class="flex flex-col items-center gap-0.5"><span class="${cellClass}">${i}</span><span class="w-1 h-1 rounded-full ${dotClass}"></span></div>`;
      }

      const totalCells = firstDay + lastDate;
      const nextDays = 7 - (totalCells % 7);
      if (nextDays < 7) {
        for (let i = 1; i <= nextDays; i++) {
          daysHTML += `<div class="flex flex-col items-center gap-0.5"><span class="w-7 h-7 flex items-center justify-center text-stone-300">${i}</span><span class="w-1 h-1 rounded-full bg-transparent"></span></div>`;
        }
      }

      calendarDays.innerHTML = daysHTML;

      // Month summary under the grid: real occupancy signal, not filler copy
      const mm = String(month + 1).padStart(2, '0');
      let bookedCount = 0;
      bookedDates.forEach(d => { if (d.startsWith(`${year}-${mm}-`)) bookedCount++; });
      const statTitle = document.getElementById('calStatTitle');
      const statSub = document.getElementById('calStatSub');
      if (statTitle && statSub) {
        statTitle.textContent = bookedCount > 0
          ? `${bookedCount} ${bookedCount === 1 ? 'day' : 'days'} with guests`
          : 'No booked dates';
        statSub.textContent = `${months[month]} ${year} · active and upcoming stays`;
      }
    };

    prevBtn.addEventListener("click", () => {
      date.setMonth(date.getMonth() - 1);
      renderCalendar();
    });

    nextBtn.addEventListener("click", () => {
      date.setMonth(date.getMonth() + 1);
      renderCalendar();
    });

    renderCalendar();
  </script>
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
