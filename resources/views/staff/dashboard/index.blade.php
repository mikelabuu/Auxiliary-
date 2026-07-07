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

    <x-admin.page-header subtitle="Here's what's happening at Farmers Hostel today.">
        Welcome back, <span class="font-display italic font-medium text-clsu-800">{{ explode(' ', Auth::guard('staff')->user()->name)[0] }}</span>
        <x-slot:actions>
            <a href="{{ route('staff.reports.index') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.icon name="calendar" class="w-4 h-4" />
                View Reports
            </a>
            <a href="{{ route('staff.manualbooking') }}" class="flex items-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-4 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all !no-underline">
                <x-admin.icon name="plus" class="w-4 h-4" stroke-width="2" />
                New Booking
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Quick Actions Grid -->
    <div class="animate-in grid grid-cols-2 lg:grid-cols-4 gap-3" style="animation-delay:20ms">
        <x-admin.quick-action icon="log-in" title="Check-in Guest" subtitle="Mark an arrival" :href="route('staff.manualbooking')" />
        <x-admin.quick-action icon="block" title="Block a Room" subtitle="Mark unavailable" :href="route('staff.rooms')" />
        <x-admin.quick-action icon="credit-card" title="Log a Payment" subtitle="Record a receipt" :href="route('staff.paymentlogs.index')" />
        <x-admin.quick-action icon="wrench" title="Maintenance Note" subtitle="Flag an issue" :href="route('staff.audit.index')" />
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.stat-card icon="bed" badge="ALL ACTIVE" label="Total Rooms" :delay="40">
            {{ $totalRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">{{ $roomsUnderMaintenance }} under maintenance</p></x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="clipboard" badge="ALL-TIME" label="Total Bookings" :delay="80">
            {{ $totalBookings }}
            <x-slot:footnote>
                <p class="text-xs font-semibold {{ $bookingPercentChange >= 0 ? 'text-clsu-600' : 'text-red-500' }} flex items-center gap-1">
                    <x-admin.icon :name="$bookingPercentChange >= 0 ? 'trend-up' : 'trend-down'" class="w-3 h-3" stroke-width="2.5" />
                    {{ $bookingPercentChange >= 0 ? '+' : '' }}{{ number_format($bookingPercentChange, 1) }}% vs last month
                </p>
            </x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="users" badge="REGISTERED" label="Users" :delay="120">
            {{ $totalUsers }}
            <x-slot:footnote>
                <p class="text-xs font-semibold text-clsu-600 flex items-center gap-1">
                    <x-admin.icon name="trend-up" class="w-3 h-3" stroke-width="2.5" />
                    +{{ $newUsersThisWeek }} new this week
                </p>
            </x-slot:footnote>
        </x-admin.stat-card>

        <x-admin.stat-card icon="receipt" badge="GROSS" label="Revenue" :delay="160" dark>
            ₱{{ number_format($totalRevenue, 2) }}
            <x-slot:footnote>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-palay-300 flex items-center gap-1">
                        <x-admin.icon :name="$revenuePercentChange >= 0 ? 'trend-up' : 'trend-down'" class="w-3 h-3" stroke-width="2.5" />
                        {{ $revenuePercentChange >= 0 ? '+' : '' }}{{ number_format($revenuePercentChange, 1) }}% vs last month
                    </p>
                    <svg width="70" height="24" viewBox="0 0 70 24" class="text-palay-300/80" aria-label="Monthly revenue trend">
                        <polyline points="{{ $revenueSparkline }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </x-slot:footnote>
        </x-admin.stat-card>
    </div>

    <!-- Secondary metrics strip -->
    <div class="animate-in grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6" style="animation-delay:180ms">
        <x-admin.mini-stat icon="arrival" label="Check-ins this week">{{ $checkinsThisWeek }}</x-admin.mini-stat>
        <x-admin.mini-stat icon="departure" label="Check-outs this week">{{ $checkoutsThisWeek }}</x-admin.mini-stat>
        <a href="{{ route('staff.discounts.index') }}" class="!no-underline">
            <x-admin.mini-stat icon="tag" color="palay" label="Pending discount requests" class="h-full hover:shadow-card-lg transition-shadow">{{ $pendingDiscounts }}</x-admin.mini-stat>
        </a>
        <x-admin.mini-stat icon="wrench" color="ember" label="Rooms under maintenance">{{ $roomsUnderMaintenance }}</x-admin.mini-stat>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Bookings insights -->
        <x-admin.section-card icon="chart-bar" title="Bookings Insights" class="lg:col-span-2" :delay="200">
            <x-slot:actions>
                <span class="text-xs font-medium text-stone-500 bg-stone-50 border border-stone-200 rounded-full px-3 py-1.5">{{ date('Y') }}</span>
            </x-slot:actions>

            <p class="text-xs text-stone-400 mb-5 -mt-4 ml-[42px]">Peak month: <span class="font-semibold text-palay-700">{{ $peakMonthName }} · {{ $peakMonthCount }} bookings</span></p>

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
                        <div class="relative h-full flex items-end gap-2 px-1">
                            @foreach($values as $index => $val)
                                @php
                                    $heightPercent = $maxScale > 0 ? ($val / $maxScale) * 100 : 0;
                                    $isPeak = $val == $peakMonthCount && $val > 0;
                                    $barColor = $isPeak ? 'bg-gradient-to-t from-palay-600 to-palay-400' : 'bg-gradient-to-t from-clsu-700 to-clsu-400 group-hover:from-clsu-800 group-hover:to-clsu-500';
                                    if($val == 0) $barColor = 'bg-stone-100';
                                @endphp
                                <div class="flex-1 group relative flex justify-center h-full items-end">
                                    @if($val > 0)
                                        <div class="rounded-t-md w-full {{ $barColor }} transition-colors" style="height:{{ $heightPercent }}%"></div>
                                        <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[9px] font-bold opacity-0 group-hover:opacity-100 transition-opacity {{ $isPeak ? 'text-palay-700 bg-palay-50' : 'text-clsu-700 bg-clsu-50' }} rounded-full px-1.5 py-0.5 tracking-wide whitespace-nowrap">{{ $val }} · ₱{{ number_format($revenueValues[$index]) }}</span>
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
        </x-admin.section-card>

        <!-- Occupancy Snapshot component -->
        <livewire:dashboard.occupancy-snapshot />
    </div>

    <!-- Room Status Map (signature feature) -->
    <x-admin.section-card id="room-map" icon="grid" title="Room Status Map" :subtitle="'All ' . $totalRooms . ' rooms at a glance'" class="scroll-mt-20" :delay="300">
        <x-slot:actions>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-400"></span>Available · {{ $availableCount }}</span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-800"></span>Occupied · {{ $occupiedCount }}</span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Reserved · {{ $reservedCount }}</span>
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-stone-500"><span class="w-2 h-2 rounded-full bg-ember-500"></span>Maintenance · {{ $maintenanceCount }}</span>
        </x-slot:actions>

        <div class="space-y-5">
            <div>
                <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">Dorm Rooms · {{ $dormBeds->count() }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($dormBeds as $room)
                        @php
                            $btnClass = 'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300';
                            if($room['display_status'] === 'occupied') $btnClass = 'bg-clsu-800 text-white border-clsu-900 hover:bg-clsu-950';
                            if($room['display_status'] === 'reserved') $btnClass = 'bg-palay-100 text-palay-800 border-palay-200 hover:bg-palay-200';
                            if($room['display_status'] === 'maintenance') $btnClass = 'bg-ember-50 text-ember-800 border-ember-200 hover:bg-ember-100';
                        @endphp
                        <button type="button" title="{{ $room['room_number'] }} · {{ ucfirst($room['display_status']) }}" class="w-14 h-11 rounded-lg border text-xs font-bold font-data flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 {{ $btnClass }}">{{ $room['room_number'] }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">Standard Rooms · {{ $standardRooms->count() }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($standardRooms as $room)
                        @php
                            $btnClass = 'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300';
                            if($room['display_status'] === 'occupied') $btnClass = 'bg-clsu-800 text-white border-clsu-900 hover:bg-clsu-950';
                            if($room['display_status'] === 'reserved') $btnClass = 'bg-palay-100 text-palay-800 border-palay-200 hover:bg-palay-200';
                            if($room['display_status'] === 'maintenance') $btnClass = 'bg-ember-50 text-ember-800 border-ember-200 hover:bg-ember-100';
                        @endphp
                        <button type="button" title="{{ $room['room_number'] }} · {{ ucfirst($room['display_status']) }}" class="w-14 h-11 rounded-lg border text-xs font-bold font-data flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 {{ $btnClass }}">{{ $room['room_number'] }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-[10px] font-bold text-stone-400 tracking-widest mb-2 uppercase">Deluxe Rooms · {{ $deluxeRooms->count() }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($deluxeRooms as $room)
                        @php
                            $btnClass = 'bg-clsu-50 text-clsu-800 border-clsu-200 hover:bg-clsu-100 hover:border-clsu-300';
                            if($room['display_status'] === 'occupied') $btnClass = 'bg-clsu-800 text-white border-clsu-900 hover:bg-clsu-950';
                            if($room['display_status'] === 'reserved') $btnClass = 'bg-palay-100 text-palay-800 border-palay-200 hover:bg-palay-200';
                            if($room['display_status'] === 'maintenance') $btnClass = 'bg-ember-50 text-ember-800 border-ember-200 hover:bg-ember-100';
                        @endphp
                        <button type="button" title="{{ $room['room_number'] }} · {{ ucfirst($room['display_status']) }}" class="w-14 h-11 rounded-lg border text-xs font-bold font-data flex items-center justify-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 {{ $btnClass }}">{{ $room['room_number'] }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <!-- Bottom row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Arrivals & Departures -->
        <div class="lg:col-span-2">
            <livewire:dashboard.arrivals-departures />
        </div>

        <!-- Right Side: Calendar & Recent Activity -->
        <div class="space-y-6">
            <!-- Calendar snapshot (bespoke dark header, not a section-card) -->
            <div class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg transition-shadow duration-200 overflow-hidden" style="animation-delay:380ms">
                <div class="flex items-center justify-between bg-gradient-to-r from-clsu-900 to-clsu-950 text-white px-4 py-3.5">
                    <button id="prev" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 cursor-pointer">
                        <x-admin.icon name="chevron-left" class="w-3.5 h-3.5" stroke-width="2.5" />
                    </button>
                    <p id="monthYear" class="text-xs font-bold tracking-widest tabnum uppercase"></p>
                    <button id="next" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 cursor-pointer">
                        <x-admin.icon name="chevron-right" class="w-3.5 h-3.5" stroke-width="2.5" />
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
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-clsu-800"></span>Today</span>
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Has bookings</span>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <div class="bg-gradient-to-br from-clsu-50 to-white border border-clsu-100 rounded-xl p-3.5">
                        <p class="text-xs font-semibold text-clsu-800">Check dates to plan ahead</p>
                        <p class="text-[11px] text-clsu-600 mt-0.5">Use manual booking to block rooms.</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <x-admin.section-card icon="clock" title="Recent Activity" :delay="420">
                <div class="space-y-5">
                    @foreach($recentActivities as $activity)
                        <div class="timeline-item flex gap-3.5">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 ring-4 ring-white z-10 {{ $activity['color_class'] }}">
                                <span class="material-icons text-sm">{{ $activity['icon'] }}</span>
                            </div>
                            <div class="pb-1">
                                <p class="text-sm text-stone-700">{!! $activity['description'] !!}</p>
                                <p class="text-xs text-stone-400 mt-0.5">{{ $activity['created_at'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.section-card>
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
            ? "w-7 h-7 rounded-full bg-gradient-to-br from-clsu-600 to-clsu-800 text-white font-bold flex items-center justify-center ring-4 ring-clsu-100"
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
