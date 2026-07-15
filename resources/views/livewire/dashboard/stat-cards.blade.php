{{-- Markup moved verbatim from staff/dashboard/index.blade.php; single root
     wrapper because Livewire requires one, poll as the fallback when the
     socket push isn't available. --}}
<div wire:poll.30s class="space-y-6">
    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="bed" badge="ALL ACTIVE" label="Total Rooms" :delay="40">
            {{ $totalRooms }}
            <x-slot:footnote><p class="text-xs text-stone-400">{{ $roomsUnderMaintenance }} under maintenance</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="clipboard" badge="ALL-TIME" label="Total Bookings" :delay="80">
            {{ $totalBookings }}
            <x-slot:footnote>
                <p class="text-xs font-semibold {{ $bookingPercentChange >= 0 ? 'text-clsu-600' : 'text-red-500' }} flex items-center gap-1">
                    <x-admin.ui.icon :name="$bookingPercentChange >= 0 ? 'trend-up' : 'trend-down'" class="w-3 h-3" stroke-width="2.5" />
                    {{ $bookingPercentChange >= 0 ? '+' : '' }}{{ number_format($bookingPercentChange, 1) }}% vs last month
                </p>
            </x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="users" badge="REGISTERED" label="Users" :delay="120">
            {{ $totalUsers }}
            <x-slot:footnote>
                <p class="text-xs font-semibold text-clsu-600 flex items-center gap-1">
                    <x-admin.ui.icon name="trend-up" class="w-3 h-3" stroke-width="2.5" />
                    +{{ $newUsersThisWeek }} new this week
                </p>
            </x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="receipt" badge="GROSS" label="Revenue" :delay="160" dark>
            ₱{{ number_format($totalRevenue, 2) }}
            <x-slot:footnote>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-palay-300 flex items-center gap-1">
                        <x-admin.ui.icon :name="$revenuePercentChange >= 0 ? 'trend-up' : 'trend-down'" class="w-3 h-3" stroke-width="2.5" />
                        {{ $revenuePercentChange >= 0 ? '+' : '' }}{{ number_format($revenuePercentChange, 1) }}% vs last month
                    </p>
                    <svg width="70" height="24" viewBox="0 0 70 24" class="text-palay-300/80" aria-label="Monthly revenue trend">
                        <polyline points="{{ $revenueSparkline }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    <!-- Secondary metrics strip -->
    <div class="animate-in grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6" style="animation-delay:180ms">
        <x-admin.ui.mini-stat icon="check-circle" label="Rooms available now">{{ $availableCount }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="arrival" label="Check-ins this week">{{ $checkinsThisWeek }}</x-admin.ui.mini-stat>
        <x-admin.ui.mini-stat icon="departure" label="Check-outs this week">{{ $checkoutsThisWeek }}</x-admin.ui.mini-stat>
        <a href="{{ route('staff.discounts.index') }}" class="!no-underline">
            <x-admin.ui.mini-stat icon="tag" color="palay" label="Pending discount requests" class="h-full hover:shadow-card-lg transition-shadow">{{ $pendingDiscounts }}</x-admin.ui.mini-stat>
        </a>
    </div>
</div>
