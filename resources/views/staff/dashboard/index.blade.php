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
      <!-- Page intro + quick action -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 animate-in">
        <div>
          <p class="text-xl sm:text-2xl text-stone-900 tracking-tight">Welcome back, <span class="font-display italic font-medium text-clsu-800">{{ explode(' ', Auth::guard('staff')->user()->name)[0] }}</span></p>
          <p class="text-sm text-stone-500 mt-1">Here's what's happening at Farmers Hostel today.</p>
        </div>
        <div class="flex items-center gap-2.5">
          <a href="{{ route('staff.reports.index') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
            <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
            View Reports
          </a>
          <a href="{{ route('staff.manualbooking') }}" class="flex items-center gap-2 text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-4 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all !no-underline">
            <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Booking
          </a>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div class="animate-in grid grid-cols-2 lg:grid-cols-4 gap-3" style="animation-delay:20ms">
        <a href="{{ route('staff.manualbooking') }}" class="group flex items-center gap-3 bg-white rounded-xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:border-clsu-200 transition-all p-3.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 !no-underline">
          <div class="w-9 h-9 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0 group-hover:bg-clsu-100 transition-colors">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-stone-800 truncate">Check-in Guest</p>
            <p class="text-[11px] text-stone-400 truncate">Mark an arrival</p>
          </div>
        </a>
        <a href="{{ route('staff.rooms') }}" class="group flex items-center gap-3 bg-white rounded-xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:border-clsu-200 transition-all p-3.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 !no-underline">
          <div class="w-9 h-9 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0 group-hover:bg-clsu-100 transition-colors">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-stone-800 truncate">Block a Room</p>
            <p class="text-[11px] text-stone-400 truncate">Mark unavailable</p>
          </div>
        </a>
        <a href="{{ route('staff.paymentlogs.index') }}" class="group flex items-center gap-3 bg-white rounded-xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:border-clsu-200 transition-all p-3.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 !no-underline">
          <div class="w-9 h-9 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0 group-hover:bg-clsu-100 transition-colors">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-stone-800 truncate">Log a Payment</p>
            <p class="text-[11px] text-stone-400 truncate">Record a receipt</p>
          </div>
        </a>
        <a href="{{ route('staff.audit.index') }}" class="group flex items-center gap-3 bg-white rounded-xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:border-clsu-200 transition-all p-3.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400 !no-underline">
          <div class="w-9 h-9 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0 group-hover:bg-clsu-100 transition-colors">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.7 6.3 3 3 4-4-1.5-1.5a3 3 0 0 0-4.24 0L14.7 5.06a1 1 0 0 0 0 1.24z"/><path d="M13.6 9.6 3 20.2V21h.8L14.4 10.4"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-stone-800 truncate">Maintenance Note</p>
            <p class="text-[11px] text-stone-400 truncate">Flag an issue</p>
          </div>
        </a>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:-translate-y-0.5 transition-all duration-200 p-6" style="animation-delay:40ms">
          <div class="flex items-start justify-between">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-clsu-50 to-clsu-100 text-clsu-700 flex items-center justify-center ring-1 ring-clsu-100">
              <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"/><path d="M2 18h20"/><path d="M6 10V7a2 2 0 0 1 2-2h3v5"/></svg>
            </div>
            <span class="text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 tracking-wide">ALL ACTIVE</span>
          </div>
          <p class="text-sm text-stone-500 mt-4">Total Rooms</p>
          <p class="text-3xl font-bold font-data tabnum text-stone-900 mt-1">{{ $totalRooms }}</p>
          <p class="text-xs text-stone-400 mt-2">{{ $roomsUnderMaintenance }} under maintenance</p>
        </div>

        <div class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:-translate-y-0.5 transition-all duration-200 p-6" style="animation-delay:80ms">
          <div class="flex items-start justify-between">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-clsu-50 to-clsu-100 text-clsu-700 flex items-center justify-center ring-1 ring-clsu-100">
              <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>
            </div>
            <span class="text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 tracking-wide">ALL-TIME</span>
          </div>
          <p class="text-sm text-stone-500 mt-4">Total Bookings</p>
          <p class="text-3xl font-bold font-data tabnum text-stone-900 mt-1">{{ $totalBookings }}</p>
          <p class="text-xs font-semibold {{ $bookingPercentChange >= 0 ? 'text-clsu-600' : 'text-red-500' }} mt-2 flex items-center gap-1">
            <svg class="icon w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              @if($bookingPercentChange >= 0)
                <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
              @else
                <line x1="7" y1="7" x2="17" y2="17"/><polyline points="17 7 17 17 7 17"/>
              @endif
            </svg>
            {{ $bookingPercentChange >= 0 ? '+' : '' }}{{ number_format($bookingPercentChange, 1) }}% vs last month
          </p>
        </div>

        <div class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg hover:-translate-y-0.5 transition-all duration-200 p-6" style="animation-delay:120ms">
          <div class="flex items-start justify-between">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-clsu-50 to-clsu-100 text-clsu-700 flex items-center justify-center ring-1 ring-clsu-100">
              <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.4"/><path d="M15.8 14.3c2.4.4 4.7 2 4.7 5.7"/></svg>
            </div>
            <span class="text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 tracking-wide">REGISTERED</span>
          </div>
          <p class="text-sm text-stone-500 mt-4">Users</p>
          <p class="text-3xl font-bold font-data tabnum text-stone-900 mt-1">{{ $totalUsers }}</p>
          <p class="text-xs font-semibold text-clsu-600 mt-2 flex items-center gap-1">
            <svg class="icon w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
            +{{ $newUsersThisWeek }} new this week
          </p>
        </div>

        <div class="animate-in relative overflow-hidden bg-gradient-to-br from-clsu-800 via-clsu-900 to-clsu-950 rounded-2xl shadow-glow hover:-translate-y-0.5 transition-all duration-200 p-6 text-white" style="animation-delay:160ms">
          <div aria-hidden="true" class="absolute -right-8 -bottom-10 w-40 h-40 rounded-full bg-palay-400/10 blur-2xl"></div>
          <div class="flex items-start justify-between relative z-10">
            <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center ring-1 ring-white/10">
              <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h.01"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <span class="text-[10px] font-bold bg-palay-400/20 text-palay-100 rounded-full px-2.5 py-1 tracking-wide">GROSS</span>
          </div>
          <p class="text-sm text-clsu-200 mt-4 relative z-10">Revenue</p>
          <p class="text-3xl font-bold font-data tabnum mt-1 relative z-10">₱{{ number_format($totalRevenue, 2) }}</p>
          <div class="flex items-center justify-between mt-2 relative z-10">
            <p class="text-xs font-semibold text-palay-300 flex items-center gap-1">
              <svg class="icon w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                @if($revenuePercentChange >= 0)
                  <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
                @else
                  <line x1="7" y1="7" x2="17" y2="17"/><polyline points="17 7 17 17 7 17"/>
                @endif
              </svg>
              {{ $revenuePercentChange >= 0 ? '+' : '' }}{{ number_format($revenuePercentChange, 1) }}% vs last month
            </p>
            <svg width="70" height="24" viewBox="0 0 70 24" class="text-palay-300/80">
              <polyline points="0,18 12,15 24,17 36,9 48,11 60,3 70,5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Secondary metrics strip -->
      <div class="animate-in grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6" style="animation-delay:180ms">
        <div class="bg-white rounded-2xl border border-stone-200/70 shadow-card p-4 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
          </div>
          <div>
            <p class="text-lg font-bold font-data text-stone-900 leading-none">{{ $checkinsThisWeek }}</p>
            <p class="text-[11px] text-stone-500 mt-1">Check-ins this week</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-stone-200/70 shadow-card p-4 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-lg bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          </div>
          <div>
            <p class="text-lg font-bold font-data text-stone-900 leading-none">{{ $checkoutsThisWeek }}</p>
            <p class="text-[11px] text-stone-500 mt-1">Check-outs this week</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-stone-200/70 shadow-card p-4 flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-lg bg-ember-50 text-ember-600 flex items-center justify-center shrink-0">
            <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.7 6.3 3 3 4-4-1.5-1.5a3 3 0 0 0-4.24 0L14.7 5.06a1 1 0 0 0 0 1.24z"/><path d="M13.6 9.6 3 20.2V21h.8L14.4 10.4"/></svg>
          </div>
          <div>
            <p class="text-lg font-bold font-data text-stone-900 leading-none">{{ $roomsUnderMaintenance }}</p>
            <p class="text-[11px] text-stone-500 mt-1">Rooms under maintenance</p>
          </div>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Bookings insights -->
        <div class="animate-in lg:col-span-2 bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-6" style="animation-delay:200ms">
          <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center">
                <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="20" x2="4" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="20" y1="20" x2="20" y2="14"/></svg>
              </div>
              <p class="font-semibold text-stone-900 text-sm">Bookings Insights</p>
            </div>
            <span class="text-xs font-medium text-stone-500 bg-stone-50 border border-stone-200 rounded-full px-3 py-1.5">{{ date('Y') }}</span>
          </div>
          <p class="text-xs text-stone-400 mb-5 ml-[42px]">Peak month: <span class="font-semibold text-palay-700">{{ $peakMonthName }} · {{ $peakMonthCount }} bookings</span></p>

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
                          <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[9px] font-bold opacity-0 group-hover:opacity-100 transition-opacity {{ $isPeak ? 'text-palay-700 bg-palay-50' : 'text-clsu-700 bg-clsu-50' }} rounded-full px-1.5 py-0.5 tracking-wide whitespace-nowrap">{{ $val }}</span>
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

        <!-- Occupancy Snapshot component -->
        <livewire:dashboard.occupancy-snapshot />
      </div>

      <!-- Room Status Map (signature feature) -->
      <div id="room-map" class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-6 scroll-mt-20" style="animation-delay:300ms">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center shrink-0">
              <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
            </div>
            <div>
              <p class="font-semibold text-stone-900 text-sm">Room Status Map</p>
              <p class="text-xs text-stone-400">All {{ $totalRooms }} rooms at a glance</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1.5 text-[11px] font-medium text-stone-500">
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-clsu-400"></span>Available · {{ $availableCount }}</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-clsu-800"></span>Occupied · {{ $occupiedCount }}</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Reserved · {{ $reservedCount }}</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-ember-500"></span>Maintenance · {{ $maintenanceCount }}</span>
          </div>
        </div>

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
      </div>

      <!-- Bottom row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Arrivals & Departures -->
        <div class="lg:col-span-2">
            <livewire:dashboard.arrivals-departures />
        </div>

        <!-- Right Side: Calendar & Recent Activity -->
        <div class="space-y-6">
          <!-- Calendar snapshot -->
          <div class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg transition-shadow duration-200 overflow-hidden" style="animation-delay:380ms">
            <div class="flex items-center justify-between bg-gradient-to-r from-clsu-900 to-clsu-950 text-white px-4 py-3.5">
              <button id="prev" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 cursor-pointer">
                <svg class="icon w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <p id="monthYear" class="text-xs font-bold tracking-widest tabnum uppercase"></p>
              <button id="next" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 cursor-pointer">
                <svg class="icon w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
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
          <div class="animate-in bg-white rounded-2xl border border-stone-200/70 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-6" style="animation-delay:420ms">
            <div class="flex items-center gap-2.5 mb-5">
              <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center shrink-0">
                <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
              </div>
              <p class="font-semibold text-stone-900 text-sm">Recent Activity</p>
            </div>
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
          </div>
        </div>
      </div>
</div>  


<script>
    const monthYear = document.getElementById("monthYear");
    const calendarDays = document.getElementById("calendarDays");
    const prevBtn = document.getElementById("prev");
    const nextBtn = document.getElementById("next");

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

        daysHTML += `<div class="flex flex-col items-center gap-0.5"><span class="${cellClass}">${i}</span><span class="w-1 h-1 rounded-full bg-transparent"></span></div>`;
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
