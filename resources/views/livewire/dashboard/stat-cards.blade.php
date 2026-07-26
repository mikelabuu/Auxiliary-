{{-- The four business KPIs. Revenue is no longer here — it leads the page in
     the hero row (livewire/dashboard/hero) — so this row is inventory,
     throughput, audience and live availability. Each card's meter shows the
     number's denominator. --}}
<div wire:poll.30s class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="bed" badge="ALL WINGS" label="Total Rooms" :delay="40" :href="route('staff.rooms')"
                              :progress="$roomsServiceablePct"
                              :progress-label="round($roomsServiceablePct) . '% serviceable'">
            {{ $totalRooms }}
            <x-slot:footnote>
                <p class="stat-foot">
                    <x-admin.ui.icon name="wrench" class="w-3 h-3" />
                    {{ $roomsUnderMaintenance }} under maintenance
                </p>
            </x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="clipboard" badge="ALL-TIME" label="Total Bookings" :delay="80" :href="route('staff.bookings.index')"
                              :spark="$bookingSpark"
                              spark-label="New bookings per week, last 12 weeks">
            {{ $totalBookings }}
            <x-slot:footnote>
                <p class="stat-foot {{ $bookingPercentChange >= 0 ? 'is-up' : 'is-down' }}">
                    <x-admin.ui.icon :name="$bookingPercentChange >= 0 ? 'trend-up' : 'trend-down'" class="w-3 h-3" stroke-width="2.5" />
                    {{ $bookingPercentChange >= 0 ? '+' : '' }}{{ number_format($bookingPercentChange, 1) }}% vs last month
                </p>
            </x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="users" color="sky" badge="REGISTERED" label="Guest Accounts" :delay="120" :href="route('staff.userrecords.index')"
                              :spark="$userSpark"
                              spark-label="New sign-ups per week, last 12 weeks">
            {{ $totalUsers }}
            <x-slot:footnote>
                <p class="stat-foot is-up">
                    <x-admin.ui.icon name="user" class="w-3 h-3" stroke-width="2.5" />
                    +{{ $newUsersThisWeek }} new this week
                </p>
            </x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check-circle" color="palay" badge="RIGHT NOW" label="Available Now" :delay="160" :href="route('staff.rooms')"
                              :progress="$sellablePct"
                              :progress-label="round($sellablePct) . '% sellable'">
            {{ $availableCount }}
            <x-slot:footnote>
                <p class="stat-foot">
                    <x-admin.ui.icon name="zap" class="w-3 h-3" />
                    {{ round($sellablePct) }}% of inventory sellable
                </p>
            </x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    {{-- Secondary metrics strip. "Rooms available now" used to lead this row,
         but that number is already the Available Now KPI above *and* the
         Occupancy Snapshot — three copies of one figure. Replaced with this
         month's revenue, which the dashboard otherwise never states (the hero
         card shows all-time gross). --}}
    <div class="animate-in grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6" style="animation-delay:180ms">
        <x-admin.ui.mini-stat icon="receipt" label="Revenue this month">₱{{ number_format($revenueThisMonth, 2) }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="arrival" label="Check-ins this week">{{ $checkinsThisWeek }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="departure" label="Check-outs this week">{{ $checkoutsThisWeek }}</x-admin.ui.mini-stat>
        <a href="{{ route('staff.discounts.index') }}" class="!no-underline">
            <x-admin.ui.mini-stat icon="tag" color="palay" label="Pending discount requests" class="h-full hover:shadow-card-lg transition-shadow">{{ $pendingDiscounts }}</x-admin.ui.mini-stat>
        </a>
    </div>
</div>
