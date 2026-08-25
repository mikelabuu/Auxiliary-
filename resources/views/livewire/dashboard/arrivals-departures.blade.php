<div wire:poll.15s data-component="arrivals-departures" class="bg-white rounded-2xl border border-stone-200 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-0 flex flex-col h-full overflow-hidden">
    {{-- Header --}}
    <div class="p-5 border-b border-stone-100 bg-stone-50/50">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center shrink-0">
                    <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </div>
                <p class="font-semibold text-stone-900 text-sm">Arrivals &amp; Departures</p>
            </div>

            {{-- Day navigation --}}
            <div class="flex items-center gap-1.5">
                <span class="refresh-chip" wire:loading.delay.flex wire:target="filterType, sortBy, gotoPage, previousPage, nextPage, previousDay, nextDay, goToday">
                    <svg class="spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
                    Updating
                </span>
                <button wire:click="previousDay" class="w-11 h-11 flex items-center justify-center rounded-lg border border-stone-200 text-muted hover:bg-stone-50 hover:text-clsu-700 transition cursor-pointer" aria-label="Previous day">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <span class="min-w-[76px] text-center text-xs font-bold text-stone-700 tabnum">{{ $viewLabel }}</span>
                <button wire:click="nextDay" class="w-11 h-11 flex items-center justify-center rounded-lg border border-stone-200 text-muted hover:bg-stone-50 hover:text-clsu-700 transition cursor-pointer" aria-label="Next day">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                @unless($isToday)
                    <button wire:click="goToday" class="ml-0.5 text-2xs font-bold text-clsu-700 bg-clsu-50 border border-clsu-200 rounded-lg px-2 py-1 hover:bg-clsu-100 transition cursor-pointer">Today</button>
                @endunless
            </div>
        </div>

        {{-- Summary chips + filter tabs --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-clsu-50 text-clsu-700 border border-clsu-100 px-2.5 py-1 text-xs font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-clsu-500"></span>{{ $arrivalsCount }} arriving</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-palay-100 text-palay-800 border border-palay-200 px-2.5 py-1 text-xs font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-palay-400"></span>{{ $departuresCount }} departing</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 text-stone-600 border border-stone-200 px-2.5 py-1 text-xs font-semibold">{{ $inHouseCount }} in-house</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white text-muted border border-stone-200 px-2.5 py-1 text-xs font-semibold">{{ $upcomingCount }} upcoming</span>
            </div>

            <div class="flex bg-stone-100 rounded-full p-1 text-xs font-semibold w-fit shrink-0">
                <button wire:click="$set('filterType', 'all')"
                    class="px-4 py-3 rounded-full transition-[color,background-color,transform] duration-200 active:scale-95 cursor-pointer {{ $filterType === 'all' ? 'bg-white text-clsu-800 shadow-sm' : 'text-faint hover:text-clsu-700' }}">
                    All
                </button>
                <button wire:click="$set('filterType', 'arrival')"
                    class="px-4 py-3 rounded-full transition-[color,background-color,transform] duration-200 active:scale-95 cursor-pointer {{ $filterType === 'arrival' ? 'bg-white text-clsu-800 shadow-sm' : 'text-faint hover:text-clsu-700' }}">
                    Arrivals
                </button>
                <button wire:click="$set('filterType', 'departure')"
                    class="px-4 py-3 rounded-full transition-[color,background-color,transform] duration-200 active:scale-95 cursor-pointer {{ $filterType === 'departure' ? 'bg-white text-clsu-800 shadow-sm' : 'text-faint hover:text-clsu-700' }}">
                    Departures
                </button>
                {{-- Everyone in a room right now, whether or not they are due
                     out. The way to reach a guest who has to leave early: they
                     are on no other tab until their own check-out day. --}}
                <button wire:click="$set('filterType', 'inhouse')"
                    class="px-4 py-3 rounded-full transition-[color,background-color,transform] duration-200 active:scale-95 cursor-pointer {{ $filterType === 'inhouse' ? 'bg-white text-clsu-800 shadow-sm' : 'text-faint hover:text-clsu-700' }}">
                    In-house
                </button>
            </div>
        </div>
    </div>

    {{-- Needs attention: overdue check-outs + missed arrivals (relative to real today) --}}
    @if($overdueCheckouts->isNotEmpty() || $missedArrivals->isNotEmpty())
        <div class="px-5 pt-4">
            <div class="rounded-xl border border-ember-300 bg-ember-50/70 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 border-b border-ember-100">
                    <svg class="w-4 h-4 text-ember-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <p class="text-2xs font-bold text-ember-800 uppercase tracking-wide">Needs attention</p>
                    <span class="ml-auto text-2xs font-bold text-ember-800 bg-ember-100 rounded-full px-2 py-0.5">{{ $overdueCheckouts->count() + $missedArrivals->count() }}</span>
                </div>
                {{-- Same row shape as the list below, so the two sets of rows
                     in this panel reflow the same way and read as one thing.
                     These used to be a bare flex row with `min-w-0` on the text
                     and `shrink-0` on everything else, which at the panel's
                     narrow widths squeezed "Room 108 · Was due Aug 13" down to
                     one word per line. --}}
                <ul class="arrival-list divide-y divide-ember-100">
                    @foreach($overdueCheckouts as $b)
                        <li class="arrival-row arrival-row--attention">
                            <div class="arrival-row-main">
                                <p class="arrival-row-name">
                                    <span class="guest-history-link" data-booking-id="{{ $b->id }}" title="{{ $b->guest_name }} — view guest history">{{ $b->guest_name }}</span>
                                </p>
                                <p class="arrival-row-meta">
                                    <span class="font-semibold text-stone-600">Room {{ $b->room_numbers_str }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>Check-out was {{ \Carbon\Carbon::parse($b->date)->format('M d') }}</span>
                                </p>
                            </div>
                            <span class="cell-tag shrink-0" style="color:var(--color-ember-800,#912018);background:var(--color-ember-100,#fee4e2);border-color:var(--color-ember-300,#fda29b);">Overdue</span>
                            <div class="arrival-row-actions">
                                <button class="password-verify-arrivals btn btn-primary btn-sm cursor-pointer" data-action="checkout" data-id="{{ $b->id }}" data-guest="{{ $b->guest_name }}" data-room="{{ $b->room_numbers_str }}" data-checkout="{{ \Carbon\Carbon::parse($b->date)->format('M d, Y') }}">Check Out</button>
                            </div>
                        </li>
                    @endforeach
                    @foreach($missedArrivals as $b)
                        <li class="arrival-row arrival-row--attention">
                            <div class="arrival-row-main">
                                <p class="arrival-row-name">
                                    <span class="guest-history-link" data-booking-id="{{ $b->id }}" title="{{ $b->guest_name }} — view guest history">{{ $b->guest_name }}</span>
                                </p>
                                <p class="arrival-row-meta">
                                    <span class="font-semibold text-stone-600">Room {{ $b->room_numbers_str }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>Was due {{ \Carbon\Carbon::parse($b->date)->format('M d') }}</span>
                                </p>
                            </div>
                            <span class="cell-tag shrink-0" style="color:var(--color-ember-800,#912018);background:var(--color-ember-100,#fee4e2);border-color:var(--color-ember-300,#fda29b);">No-show risk</span>
                            <div class="arrival-row-actions">
                                <button class="password-verify-arrivals btn btn-outline btn-sm cursor-pointer" data-action="noshow" data-id="{{ $b->id }}" data-guest="{{ $b->guest_name }}" data-room="{{ $b->room_numbers_str }}" data-checkin="{{ \Carbon\Carbon::parse($b->date)->format('M d, Y') }}">No Show</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto flex-1 p-5 wire-panel" wire:loading.delay.class="is-refreshing" wire:target="filterType, sortBy, gotoPage, previousPage, nextPage, previousDay, nextDay, goToday">
        <div class="rounded-xl border border-stone-200 overflow-hidden">
            @if($arrivalsDepartures->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-clsu-50 to-clsu-100 flex items-center justify-center text-clsu-500 mb-3 ring-1 ring-clsu-100">
                    <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="16" y1="3" x2="16" y2="7"/><path d="m9 14 2 2 4-4"/></svg>
                  </div>
                  <p class="text-sm font-semibold text-stone-700">No arrivals or departures {{ $isToday ? 'today' : 'on ' . $viewLabel }}</p>
                  <p class="text-xs text-faint mt-1 max-w-xs">Guest check-ins and check-outs will show up here automatically as they happen.</p>
                  <a href="{{ route('staff.manualbooking') }}" class="btn btn-primary mt-4 !no-underline">Create manual booking</a>
                </div>
            @else
                {{-- Sorting used to be the two clickable column headers. A list
                     has no headers, so it is stated here instead — and named
                     for what the desk sorts by rather than for the column:
                     "Arrival" rather than "check_in". --}}
                <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-stone-50 border-b border-stone-200">
                    <p class="text-2xs font-bold text-clsu-700 uppercase tracking-wide">{{ $total }} {{ \Illuminate\Support\Str::plural('guest', $total) }} {{ $isToday ? 'today' : 'on ' . $viewLabel }}</p>
                    <div class="flex items-center gap-1">
                        <span class="text-2xs font-semibold text-faint mr-0.5">Sort</span>
                        @foreach (['guest_name' => 'Name', 'check_in' => 'Arrival'] as $field => $label)
                            <button wire:click="sortBy('{{ $field }}')"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-2xs font-bold transition-[color,background-color] cursor-pointer {{ $sortField === $field ? 'bg-white text-clsu-800 border border-clsu-200 shadow-sm' : 'text-faint hover:text-clsu-700 border border-transparent' }}"
                                    aria-pressed="{{ $sortField === $field ? 'true' : 'false' }}">
                                {{ $label }}
                                @if($sortField === $field)
                                    <svg class="w-2.5 h-2.5 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- A list, not a table.

                     This panel is 741px wide inside the dashboard grid, and the
                     table that used to be here needed 1135px. Everything past
                     the guest name and room number — the stay, the state, and
                     the reason a row could not be checked in — sat outside the
                     scrollport, behind an Actions column pinned over the top of
                     it. The desk could not read the row it was about to act on
                     without scrolling sideways first.

                     The Needs-attention block directly above has always been a
                     list and has always fitted, so this is the same shape: one
                     guest per row, every fact on screen at once, and the button
                     where the eye already ends up. --}}
                <ul class="arrival-list divide-y divide-stone-100">
                    @foreach ($arrivalsDepartures as $item)
                        @php
                            // The row's state in the words someone at the counter
                            // would use. This used to be two columns — a "Type"
                            // tag reading Arrival / Departure and beside it the
                            // raw database word, Paid or Active — describing the
                            // same row twice, one of them in the accounts'
                            // vocabulary rather than the desk's.
                            $state = match (true) {
                                $item->status === 'paid' => ['Due to arrive', 'color:var(--color-g-700);background:var(--color-g-50);border-color:var(--color-g-200);'],
                                $item->status === 'active' && $item->due_out => ['Due to check out', 'color:var(--color-au-800);background:var(--color-au-100);border-color:#fedf89;'],
                                $item->status === 'active' => ['In room', 'color:var(--color-stone-600);background:var(--color-stone-100);border-color:var(--color-stone-200);'],
                                default => [ucfirst(str_replace('_', ' ', $item->status)), 'color:var(--color-stone-600);background:var(--color-stone-100);border-color:var(--color-stone-200);'],
                            };

                            // Everything the confirm dialog needs to name what it
                            // is about to do, carried on the button itself.
                            $rowData = [
                                'data-guest'    => $item->guest_name,
                                'data-room'     => $item->room_numbers_str,
                                'data-checkin'  => \Carbon\Carbon::parse($item->check_in)->format('M d, Y'),
                                'data-checkout' => \Carbon\Carbon::parse($item->check_out)->format('M d, Y'),
                                'data-nights'   => $item->nights,
                            ];
                        @endphp
                        <li class="arrival-row">
                            <x-admin.ui.avatar class="shrink-0" />

                            <div class="arrival-row-main">
                                <p class="arrival-row-name">
                                    <span class="guest-history-link" data-booking-id="{{ $item->id }}" title="{{ $item->guest_name }} — view guest history">{{ $item->guest_name }}</span>
                                    {{-- The booking number rides with the name.
                                         On the meta line below it was the fourth
                                         item and the first to wrap, pushing a
                                         one-line row to two for the least useful
                                         thing on it. --}}
                                    <span class="arrival-row-id tabnum">#{{ $item->id }}</span>
                                </p>
                                {{-- Room, stay and length on one line, in that
                                     order: the room is what the desk says out
                                     loud, the dates are what the guest checks. --}}
                                <p class="arrival-row-meta">
                                    <span class="font-semibold text-stone-600">Room {{ $item->room_numbers_str }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span class="tabnum">{{ \Carbon\Carbon::parse($item->check_in)->format('M d') }} &rarr; {{ \Carbon\Carbon::parse($item->check_out)->format('M d') }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span class="tabnum">{{ $item->nights }} {{ \Illuminate\Support\Str::plural('night', $item->nights) }}</span>
                                </p>
                                @if($isToday && $item->status === 'paid' && $item->checkin_block)
                                    {{-- Why the desk cannot act on this row, said
                                         next to the row rather than as a toast
                                         after the button has already been pressed
                                         in front of a waiting guest. --}}
                                    <p class="arrival-row-block">
                                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ $item->checkin_block }}
                                    </p>
                                @endif
                            </div>

                            <span class="cell-tag shrink-0" style="{{ $state[1] }}">{{ $state[0] }}</span>

                            <div class="arrival-row-actions">
                                @if($isToday)
                                    @if($item->status === 'paid')
                                        @if($item->checkin_block)
                                            {{-- No green button that would only
                                                 refuse. What is offered instead is
                                                 the way out of the block — and for
                                                 the case that actually happens, a
                                                 receipt nobody has approved yet,
                                                 that is a screen to go to. --}}
                                            @if($item->checkin_block === 'Payment not verified')
                                                <a href="{{ route('staff.paymentverification.index') }}" class="btn btn-outline btn-sm cursor-pointer !no-underline shrink-0">Verify payment</a>
                                            @endif
                                        @else
                                            <button class="password-verify-arrivals btn btn-primary btn-sm cursor-pointer" data-action="checkin" data-id="{{ $item->id }}" @foreach($rowData as $k => $v) {{ $k }}="{{ $v }}" @endforeach>Check In</button>
                                            <button class="password-verify-arrivals btn btn-outline btn-sm cursor-pointer" data-action="noshow" data-id="{{ $item->id }}" @foreach($rowData as $k => $v) {{ $k }}="{{ $v }}" @endforeach>No Show</button>
                                        @endif
                                    @elseif($item->status === 'active')
                                        @if($item->due_out)
                                            <button class="password-verify-arrivals btn btn-primary btn-sm cursor-pointer" data-action="checkout" data-id="{{ $item->id }}" @foreach($rowData as $k => $v) {{ $k }}="{{ $v }}" @endforeach>Check Out</button>
                                        @else
                                            {{-- Not due out yet. Deliberately the quiet outline
                                                 button and not the primary one: this is the
                                                 exception, sitting where the green button all
                                                 day means "the expected thing". --}}
                                            <button class="password-verify-arrivals btn btn-outline btn-sm cursor-pointer !text-ember-700 !border-ember-300 hover:!bg-ember-50"
                                                    data-action="emergency"
                                                    data-id="{{ $item->id }}"
                                                    data-room="{{ $item->room_numbers_str }}"
                                                    data-nights="{{ $item->nights }}"
                                                    data-guest="{{ $item->guest_name }}"
                                                    data-checkin="{{ \Carbon\Carbon::parse($item->check_in)->format('M d, Y') }}"
                                                    data-checkout="{{ \Carbon\Carbon::parse($item->check_out)->format('M d, Y') }}"
                                                    title="End this stay before {{ \Carbon\Carbon::parse($item->check_out)->format('M d') }} — emergencies only">
                                                End stay early
                                            </button>
                                        @endif
                                    @endif
                                @else
                                    {{-- Browsing another day. A dash here read as
                                         "this row has no actions", when the truth
                                         is the panel only ever acts on today. --}}
                                    <button wire:click="goToday" class="text-2xs font-semibold text-clsu-700 hover:underline cursor-pointer" title="Check-in and check-out can only be done on the day itself">Go to today</button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Upcoming Section --}}
        @if($upcomingBookings->isNotEmpty())
            <div class="mt-6">
                <p class="text-2xs font-bold text-faint tracking-widest mb-2 uppercase">UPCOMING THIS WEEK</p>
                <div class="divide-y divide-stone-100 rounded-xl border border-stone-200 overflow-hidden">
                    @foreach($upcomingBookings as $booking)
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-clsu-50/60 transition-colors">
                            <x-admin.ui.avatar size="sm" class="shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-stone-800 truncate">{{ $booking['guest_name'] }}</p>
                                <p class="text-xs text-faint">{{ $booking['details'] }}</p>
                            </div>
                            <span class="text-2xs font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 shrink-0 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-clsu-500"></span>
                                {{ $booking['status'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if ($arrivalsDepartures->hasPages())
        <div class="px-5 py-3 border-t border-stone-150 bg-white">
            {{ $arrivalsDepartures->links('vendor.pagination.admin') }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // The guest supplies their own name, so it is escaped before it goes
    // anywhere near innerHTML — same rule the room board follows with .text().
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    // A row of the little summary every dialog now opens with.
    const line = (label, value) => !value ? '' :
        '<div style="display:flex;gap:.75rem;justify-content:space-between;align-items:baseline;padding:.3rem 0">'
        + '<span style="font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#78716c">' + esc(label) + '</span>'
        + '<span style="font-size:13px;font-weight:700;color:#1c1917;text-align:right">' + esc(value) + '</span>'
        + '</div>';

    // Who this is about, and what the desk is committing to.
    //
    // Every dialog here used to be one line — "Check in this guest?" — with no
    // name, no room and no dates on it. Working down a list of similar rows,
    // the only thing that told you which guest you had clicked was the row you
    // had already looked away from, and the only way to find out you had picked
    // the wrong one was the success toast afterwards. The confirm step exists to
    // be read; it should show the thing being confirmed.
    const summary = (d) => {
        const stay = d.checkin && d.checkout ? d.checkin + ' \u2192 ' + d.checkout : '';
        const nights = d.nights ? d.nights + (Number(d.nights) === 1 ? ' night' : ' nights') : '';
        return '<div style="text-align:left;border:1px solid #e7e5e4;border-radius:12px;padding:.6rem .9rem;background:#fafaf9;margin:.25rem 0 .9rem">'
             + line('Room', d.room && d.room !== '\u2014' ? d.room : 'Not assigned')
             + line('Stay', stay)
             + line('Nights', nights)
             + '</div>';
    };

    const note = (text, tone) => {
        const c = tone === 'warn'
            ? { bg: '#fef3f2', bd: '#fecdca', fg: '#912018' }
            : { bg: '#ecfdf3', bd: '#abefc6', fg: '#085d3a' };
        return '<p style="margin:0;text-align:left;font-size:12.5px;line-height:1.5;font-weight:600;color:' + c.fg
             + ';background:' + c.bg + ';border:1px solid ' + c.bd + ';border-radius:10px;padding:.55rem .75rem">' + text + '</p>';
    };

    // Each action states its own consequence, because each one moves a room.
    const DIALOG = {
        checkin: (d) => ({
            title: 'Check in ' + (d.guest || 'this guest') + '?',
            confirmButtonText: 'Check in',
            html: summary(d) + note('The room is marked <b>occupied</b> and the stay starts now.'),
        }),
        checkout: (d) => ({
            title: 'Check out ' + (d.guest || 'this guest') + '?',
            confirmButtonText: 'Check out',
            html: summary(d) + note('The stay is closed and the room goes back on the board as <b>available</b>.'),
        }),
        noshow: (d) => ({
            title: 'Mark ' + (d.guest || 'this guest') + ' as a no-show?',
            icon: 'warning',
            confirmButtonText: 'Mark no-show',
            html: summary(d) + note('They were due in today. The booking is closed, the room goes back on sale, and this cannot be undone from here.', 'warn'),
        }),
        emergency: (d) => ({
            title: 'End ' + (d.guest || 'this guest') + '\u2019s stay early?',
            icon: 'warning',
            confirmButtonText: 'Check out now',
            html: summary(d)
                + note('They are not due out until <b>' + esc(d.checkout || 'later') + '</b>. The room goes back on sale straight away, and <b>no refund is made</b>.', 'warn'),
            input: 'text',
            inputLabel: 'Reason for the early check-out',
            inputPlaceholder: 'e.g. medical emergency',
            inputAttributes: { maxlength: 255, autocapitalize: 'sentences' },
            inputValidator: (value) => (value || '').trim() ? undefined : 'Please give a reason.',
        }),
    };

    const handleClick = (btn) => {
        const bookingId = btn.dataset.id;
        const action = btn.dataset.action;
        const build = DIALOG[action];

        // Password re-auth dropped — a plain confirm still guards the state change.
        const opts = build
            ? build(btn.dataset)
            : { title: 'Confirm this action?', icon: 'question', confirmButtonText: 'Confirm' };

        opts.showCancelButton = true;
        opts.cancelButtonText = 'Cancel';
        opts.reverseButtons = true;
        // The two irreversible ones get the ember confirm button, not the
        // console's affirmative green (see .swal2-confirm.is-danger).
        const destructive = action === 'noshow' || action === 'emergency';
        opts.focusCancel = destructive;
        if (destructive) opts.customClass = { confirmButton: 'is-danger' };

        Swal.fire(opts).then(result => {
            if (result.isConfirmed) {
                window.Livewire.dispatch('arrivalsPasswordConfirmed', {
                    payload: { bookingId, action, reason: result.value ?? null },
                });
            }
        });
    };

    // Delegated event listener
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.password-verify-arrivals');
        if (btn) {
            e.preventDefault();
            handleClick(btn);
        }
    });

});
</script>
@endpush
