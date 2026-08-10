@extends('layouts.admin')

@section('title', 'Admin - Dashboard')
@section('page-title', 'Dashboard')
@section('content')
{{-- The csrf-token meta and $.ajaxSetup that used to sit here were a duplicate
     of the one layouts/admin already emits in <head>; the setup call now lives
     in resources/js/pages/admin-dashboard.js. --}}

<div class="space-y-6 max-w-[1680px] mx-auto">

    {{-- Top row: welcome panel (today's ops at a glance + the actions that start
         a task) beside the booking calendar. Replaces the old page-header — its
         buttons live in the panel now — and the quick-action tile grid, which
         repeated those same destinations a second time.

         The calendar card is a plain include rather than part of the Livewire
         component: the panel polls every 15s, and a re-render would wipe the
         grid the shared renderer paints into it. Revenue used to hold this
         slot; it is a KPI now (livewire/dashboard/stat-cards). --}}
    <div class="dash-hero-row">
        <livewire:dashboard.hero />
        @include('partials.dashboard.calendar-card')
    </div>

    {{-- Stat cards + secondary strip: live Livewire component (polls + follows
         the same broadcast pushes as the room map). --}}
    <livewire:dashboard.stat-cards />

    {{-- Booking Insights + Calendar modals. Insights opens from the panel
         button; the calendar modal is now the detail layer behind the inline
         card, opened by clicking a day or "Full calendar".
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

            {{-- Laid out by wing, in the same order and with the same
                 "N available" bar as Room Management, so the two boards read
                 as one building rather than two ways of slicing it.

                 The wing header carries data-wing-* hooks; admin-dashboard.js
                 recomputes the counts and bars from the live feed, so a
                 check-in elsewhere moves them without a reload. --}}
            <div class="space-y-5">
                @foreach($roomsByWing as $wing => $wingRooms)
                    @php
                        $wingOpen = $wingRooms->where('display_status', 'available')->count();
                    @endphp
                    <div data-map-wing="{{ $wing }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <p class="text-2xs font-bold text-faint tracking-widest uppercase">
                                {{ \App\Models\Room::wingLabel($wing) }} Wing · {{ $wingRooms->count() }} room{{ $wingRooms->count() === 1 ? '' : 's' }}
                            </p>
                            <div class="flex items-center gap-2">
                                <span class="text-2xs font-semibold text-faint">
                                    <span data-wing-open class="text-clsu-700 font-bold">{{ $wingOpen }}</span> available
                                </span>
                                <div class="h-1.5 w-20 rounded-full bg-stone-200/70 overflow-hidden">
                                    <div data-wing-bar class="h-full rounded-full bg-clsu-400 transition-[width] duration-300"
                                         style="width: {{ $wingRooms->count() ? round($wingOpen / $wingRooms->count() * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($wingRooms as $room)
                                <x-admin.rooms.map-tile :room="$room" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
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

{{-- Behaviour: resources/js/pages/admin-dashboard.js (bundled via admin.js) --}}
@push('scripts')
<script type="application/json" id="admin-dashboard-data">@json([
    'roomMapFeed' => route('staff.dashboard.roomMapFeed'),
])</script>
@endpush
