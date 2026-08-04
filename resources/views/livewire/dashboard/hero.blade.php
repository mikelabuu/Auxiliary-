{{-- Dashboard top row: welcome panel (left) + gross revenue hero (right).
     Polls as the fallback when the broadcast push isn't available. --}}
<div wire:poll.15s class="dash-hero-row">

    {{-- ── Welcome panel ─────────────────────────────────────────── --}}
    <section class="dash-welcome">
        <div class="dash-welcome__meta">
            <span class="dash-live">
            <span class="dash-welcome__date">{{ now(config('hostel.timezone'))->format('l, M j, Y') }}</span>
        </div>

        <h1 class="dash-welcome__title">
            Welcome back, <em>{{ explode(' ', Auth::guard('staff')->user()->name)[0] }}</em>
        </h1>

        <p class="dash-welcome__summary">{{ $summary }}</p>

        {{-- Today's operational counts. Overdue is the only one that ever goes
             red — everything else stays quiet so red always means "act". --}}
        <div class="dash-ops">
            <div class="dash-ops__item">
                <span class="dash-ops__label"><span class="dash-ops__dot dash-ops__dot--green"></span>Arriving</span>
                <span class="dash-ops__value tabnum">{{ $arriving }}</span>
            </div>
            <div class="dash-ops__item">
                <span class="dash-ops__label"><span class="dash-ops__dot dash-ops__dot--gold"></span>Departing</span>
                <span class="dash-ops__value tabnum">{{ $departing }}</span>
            </div>
            <div class="dash-ops__item">
                <span class="dash-ops__label"><span class="dash-ops__dot dash-ops__dot--slate"></span>In-house</span>
                <span class="dash-ops__value tabnum">{{ $inHouse }}</span>
            </div>
            <div class="dash-ops__item">
                <span class="dash-ops__label"><span class="dash-ops__dot dash-ops__dot--red"></span>Overdue</span>
                <span class="dash-ops__value tabnum {{ $overdue > 0 ? 'is-alert' : '' }}">{{ $overdue }}</span>
            </div>
        </div>

        <div class="dash-welcome__actions">
            <a href="{{ route('staff.manualbooking') }}" class="dash-btn dash-btn--primary">
                <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2.5" />
                New Booking
            </a>
            <a href="{{ route('staff.bookings.index') }}#arrivals" class="dash-btn">
                <x-admin.ui.icon name="log-in" class="w-4 h-4" />
                Arrivals &amp; departures
            </a>
            <button type="button" id="openCalendarBtn" class="dash-btn">
                <x-admin.ui.icon name="calendar" class="w-4 h-4" />
                Calendar
            </button>
            <button type="button" id="openInsightsBtn" class="dash-btn">
                <x-admin.ui.icon name="chart-bar" class="w-4 h-4" />
                Insights
            </button>
        </div>
    </section>

    {{-- ── Gross revenue hero ────────────────────────────────────── --}}
    <section class="dash-revenue">
        <div class="dash-revenue__head">
            <span class="dash-revenue__eyebrow">Gross revenue</span>
            <span class="dash-revenue__badge" aria-hidden="true">
                <x-admin.ui.icon name="receipt" class="w-4 h-4" />
            </span>
        </div>

        @php
            $revWhole = number_format($grossRevenue, 2);
            [$revInt, $revDec] = explode('.', $revWhole);
        @endphp
        <p class="dash-revenue__value tabnum">
            ₱{{ $revInt }}<span class="dash-revenue__cents">.{{ $revDec }}</span>
        </p>

        <div class="dash-revenue__delta">
            <span class="dash-chip {{ $revenueChange >= 0 ? 'dash-chip--up' : 'dash-chip--down' }}">
                <x-admin.ui.icon :name="$revenueChange >= 0 ? 'trend-up' : 'trend-down'" class="w-3 h-3" stroke-width="2.5" />
                {{ $revenueChange >= 0 ? '+' : '' }}{{ number_format($revenueChange, 1) }}%
            </span>
            <span class="dash-revenue__delta-note">vs last month</span>
        </div>

        {{-- Trailing 12 months. preserveAspectRatio=none lets it stretch to the
             card without us needing to know the rendered width. --}}
        <div class="dash-revenue__chart" aria-hidden="true">
            <svg viewBox="0 0 520 120" preserveAspectRatio="none" role="presentation">
                <defs>
                    <linearGradient id="dashRevFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3ccb7f" stop-opacity="0.42" />
                        <stop offset="100%" stop-color="#3ccb7f" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <polygon points="{{ $revenueArea }}" fill="url(#dashRevFill)" />
                <polyline points="{{ $revenueLine }}" fill="none" stroke="#5ee79b"
                          stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                          vector-effect="non-scaling-stroke" />
            </svg>
        </div>

        <div class="dash-revenue__foot">
            <span>{{ number_format($bookingsCount) }} {{ Str::plural('booking', $bookingsCount) }}
                @if($pendingPayments > 0)
                    · {{ $pendingPayments }} {{ Str::plural('payment', $pendingPayments) }} pending
                @endif
            </span>
            <a href="{{ route('staff.paymentlogs.index') }}" class="dash-revenue__link">
                Ledger <x-admin.ui.icon name="chevron-right" class="w-3.5 h-3.5" />
            </a>
        </div>
    </section>
</div>
