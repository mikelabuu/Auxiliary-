{{--
    Booking Calendar — the inline card in the dashboard hero row.

    Markup only. The grid is filled by the shared renderer in
    partials/dashboard/calendar-modal, which owns the single copy of
    $calendarData; clicking a day here opens that modal on the chosen date, so
    the card is the glance and the modal is the detail.

    Deliberately a light surface: a month grid on a saturated fill is a
    legibility fight, and this console has no dark surfaces left.
--}}
<section class="dash-cal" aria-label="Booking calendar">
    <header class="dash-cal__head">
        <div class="dash-cal__heading">
            <span class="dash-cal__eyebrow">Booking calendar</span>
            <p class="dash-cal__month tabnum" id="dashCalMonthYear"></p>
        </div>
        <div class="dash-cal__nav">
            <button type="button" id="dashCalPrev" class="dash-cal__nav-btn" aria-label="Previous month">
                <x-admin.ui.icon name="chevron-left" class="w-4 h-4" stroke-width="2.5" />
            </button>
            <button type="button" id="dashCalToday" class="dash-cal__today">Today</button>
            <button type="button" id="dashCalNext" class="dash-cal__nav-btn" aria-label="Next month">
                <x-admin.ui.icon name="chevron-right" class="w-4 h-4" stroke-width="2.5" />
            </button>
        </div>
    </header>

    <div class="dash-cal__body">
        <div class="dash-cal__dow" aria-hidden="true">
            <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
        </div>
        <div class="dash-cal__grid" id="dashCalGrid"></div>
    </div>

    <footer class="dash-cal__foot">
        <span class="dash-cal__legend">
            <span class="dash-cal__key dash-cal__key--arrival"></span>Arrivals
        </span>
        <span class="dash-cal__legend">
            <span class="dash-cal__key dash-cal__key--departure"></span>Departures
        </span>
        <span class="dash-cal__legend">
            <span class="dash-cal__key dash-cal__key--stay"></span>In-house
        </span>
        <button type="button" id="openCalendarBtn" class="dash-cal__link">
            Full calendar <x-admin.ui.icon name="chevron-right" class="w-3.5 h-3.5" />
        </button>
    </footer>
</section>
