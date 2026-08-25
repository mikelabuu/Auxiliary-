{{-- Welcome panel — the left cell of the dashboard hero row (the right cell is
     partials/dashboard/calendar-card, which sits outside this component so the
     poll below never wipes its rendered grid).
     Polls as the fallback when the broadcast push isn't available. --}}
<section wire:poll.15s class="dash-welcome">
    <div class="dash-welcome__meta">
        {{-- The pill was previously opened and never closed, so the date fell
             inside it and the heartbeat dot the CSS expects was never emitted.
             Both elements are explicit now. --}}

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

    {{-- The "Calendar" button that used to sit here is gone: the calendar is on
         the page now, so a button to reveal it would be a door to the room
         you're standing in. --}}
    <div class="dash-welcome__actions">
        <a href="{{ route('staff.manualbooking') }}" class="dash-btn dash-btn--primary">
            <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2.5" />
            New Booking
        </a>
        <a href="{{ route('staff.bookings.index') }}#arrivals" class="dash-btn">
            <x-admin.ui.icon name="log-in" class="w-4 h-4" />
            Arrivals &amp; departures
        </a>
        <button type="button" id="openInsightsBtn" class="dash-btn">
            <x-admin.ui.icon name="chart-bar" class="w-4 h-4" />
            Insights
        </button>
    </div>
</section>
