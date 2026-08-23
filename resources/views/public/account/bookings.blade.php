@extends('layouts.public.account')
@section('title', 'My Bookings')
@section('page-title', 'My Bookings')

@section('settings-content')
    <x-booking.ui.page-header title="My Bookings" subtitle="View and manage your room reservation history."></x-booking.ui.page-header>

    {{-- Search, status and sort.

         There is no Apply button. Every control here maps to one query
         parameter on a GET form, so the old flow — change a control, then
         travel to a second button and press it — charged two interactions for
         what is really a link. Chips and selects commit on change; the search
         box commits on a short debounce so typing does not fire a request per
         keystroke. The form still works with scripting off, which is why the
         button survives inside <noscript> rather than being deleted outright.

         The status chips replaced a <select>. A dropdown hides its options
         until opened and costs three interactions to use, and five of the
         seven statuses are empty for most guests. Chips show what is actually
         there, with counts, and cost one tap. Empty statuses are not rendered
         at all unless one is the active filter — a guest cannot usefully
         filter to a status they have never held. --}}
    @php
        $activeStatus = $status ?: 'all';
        $statusOptions = [
            'all'              => 'All',
            'pending_payment'  => 'Pending payment',
            'pending_discount' => 'Pending discount',
            'paid'             => 'Paid',
            'active'           => 'Active',
            'completed'        => 'Completed',
            'cancelled'        => 'Cancelled',
        ];
        $filtersOn = filled($search) || $activeStatus !== 'all';

        // "desc" means something different for money than it does for a date,
        // and "Descending" means nothing to a guest. The pair is named after
        // whatever is actually being sorted.
        $dirLabels = match ($sortBy) {
            'total_price' => ['desc' => 'Highest first', 'asc' => 'Lowest first'],
            'status'      => ['desc' => 'Z to A',        'asc' => 'A to Z'],
            default       => ['desc' => 'Newest first',  'asc' => 'Oldest first'],
        };

        $fieldClasses = 'w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm font-semibold outline-none transition-[color,background-color,border-color,box-shadow] focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200';
        $labelClasses = 'block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5';
    @endphp

    <form method="GET" action="{{ route('settings.bookings') }}" data-autofilter class="mb-5">
        <div class="flex flex-col lg:flex-row lg:items-end gap-3">
            <div class="min-w-0 flex-1">
                <label for="bookingSearch" class="{{ $labelClasses }}">Search</label>
                <div class="relative">
                    <x-booking.ui.icon-solid name="magnifying-glass" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-stone-400 text-[13px]" />
                    <input id="bookingSearch" type="search" name="search" autocomplete="off"
                           placeholder="Booking ID, name, or room…" value="{{ $search }}"
                           class="{{ $fieldClasses }} pl-10">
                </div>
            </div>

            <div class="flex items-end gap-2 lg:shrink-0">
                <div class="min-w-0 flex-1 lg:w-44 lg:flex-none">
                    <label for="bookingSort" class="{{ $labelClasses }}">Sort by</label>
                    <select id="bookingSort" name="sort_by" class="{{ $fieldClasses }} cursor-pointer">
                        <option value="created_at" @selected($sortBy === 'created_at')>Booking date</option>
                        <option value="check_in" @selected($sortBy === 'check_in')>Check-in date</option>
                        <option value="check_out" @selected($sortBy === 'check_out')>Check-out date</option>
                        <option value="total_price" @selected($sortBy === 'total_price')>Total price</option>
                        <option value="status" @selected($sortBy === 'status')>Booking status</option>
                    </select>
                </div>
                <div class="min-w-0 flex-1 lg:w-40 lg:flex-none">
                    <label for="bookingDir" class="{{ $labelClasses }}">Order</label>
                    <select id="bookingDir" name="sort_dir" class="{{ $fieldClasses }} cursor-pointer">
                        <option value="desc" @selected($sortDir === 'desc')>{{ $dirLabels['desc'] }}</option>
                        <option value="asc" @selected($sortDir === 'asc')>{{ $dirLabels['asc'] }}</option>
                    </select>
                </div>
            </div>
        </div>

        <fieldset class="mt-3.5">
            <legend class="sr-only">Filter by booking status</legend>
            {{-- Scrolls rather than wraps: the account panel is narrow between
                 lg and xl, and a wrapping chip row changed height as the counts
                 changed, which shifted the whole list under the cursor. --}}
            <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                @foreach($statusOptions as $value => $label)
                    @php
                        $count = $value === 'all' ? $statusCounts->sum() : $statusCounts->get($value, 0);
                        $on = $activeStatus === $value;
                    @endphp
                    @continue($value !== 'all' && $count === 0 && ! $on)
                    {{-- Selected state is driven by :checked, not by a Blade
                         ternary. The ternary was correct on arrival and wrong
                         for the half-second after a tap: the chip could not
                         restyle until the server sent a new page, so the press
                         landed on a control that appeared to ignore it. CSS
                         paints it on the click and the reload merely confirms
                         it — which also means the styling cannot drift from
                         what is actually checked. --}}
                    {{-- The radio is the peer and the visible chip is its
                         following sibling, so selection is styled through `~`
                         rather than `:has()`. Both express the same thing, but
                         `:has()` did not repaint the chip when the radio was
                         clicked, leaving the press looking ignored until the
                         server replied — and it is still only partial in
                         Safari 16.0-16.3. A sibling combinator has neither
                         problem. --}}
                    <label class="shrink-0 inline-flex cursor-pointer select-none">
                        <input type="radio" name="status" value="{{ $value }}" @checked($on) class="peer sr-only">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white px-3.5 py-1.5 text-xs font-bold text-stone-600 whitespace-nowrap transition-[color,background-color,border-color,box-shadow] hover:border-clsu-300 hover:text-clsu-800 peer-focus-visible:ring-2 peer-focus-visible:ring-clsu-300 peer-checked:border-clsu-600 peer-checked:bg-clsu-50 peer-checked:text-clsu-800 peer-checked:[&>span]:bg-clsu-600 peer-checked:[&>span]:text-white">
                            {{ $label }}
                            <span class="rounded-full bg-stone-100 px-1.5 py-px text-[10px] tabular-nums text-stone-500 transition-[color,background-color]">{{ $count }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <noscript>
            <div class="mt-3">
                <x-booking.ui.button variant="primary" class="py-2.5 px-5">Apply filters</x-booking.ui.button>
            </div>
        </noscript>
    </form>

    @if($filtersOn)
        <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs font-semibold text-stone-500">
            <span>
                {{ $bookings->total() }} {{ Str::plural('booking', $bookings->total()) }}
                @if(filled($search)) matching &ldquo;{{ $search }}&rdquo; @endif
            </span>
            <a href="{{ route('settings.bookings') }}"
               class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white px-3 py-1 text-stone-600 !no-underline transition-[color,background-color,border-color,box-shadow] hover:border-clsu-300 hover:text-clsu-800">
                <x-booking.ui.icon-solid name="xmark" class="text-[11px]" />
                Clear filters
            </a>
        </div>
    @endif

    {{-- Error messages --}}
    @if ($errors->any())
        <div class="mb-6">
            <x-booking.ui.alert type="danger">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-booking.ui.alert>
        </div>
    @endif

    {{-- Live update banner. Hidden until a broadcast arrives (or until one
         that arrived just before a reload is replayed from sessionStorage). --}}
    <div id="liveBookingNotice" class="hidden mb-6" role="status" aria-live="polite">
        <div class="flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm" data-notice-shell>
            {{-- Both tone glyphs ship, and show() reveals the matching one.
                 The icon used to be an <i> whose class carried the glyph, so a
                 className rewrite could swap it; an inlined SVG carries its
                 path instead, and the path is generated server-side. --}}
            <x-booking.ui.icon-solid name="circle-check" class="text-[20px] shrink-0 mt-0.5 hidden" data-notice-icon="good" />
            <x-booking.ui.icon-solid name="circle-exclamation" class="text-[20px] shrink-0 mt-0.5 hidden" data-notice-icon="bad" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold leading-relaxed" data-notice-text></p>
                <p class="text-[11px] font-semibold opacity-70 mt-1" data-notice-sub>Updating your bookings…</p>
            </div>
            <button type="button" data-notice-dismiss aria-label="Dismiss"
                    class="shrink-0 rounded-full p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer">
                <x-booking.ui.icon-solid name="xmark" class="text-[18px]" />
            </button>
        </div>
    </div>

    {{-- Booking list.
         One stay card per booking at every width — the old markup carried a
         nine-column desktop table AND a separate mobile card grid rendering
         the same eight fields twice, which is two places to update and two
         places to drift. --}}
    @if($bookings->count())
        <div class="space-y-4">
            @foreach($bookings as $booking)
                @php
                    $rail = match ($booking->status) {
                        'pending_payment', 'pending_discount' => 'stay-card--pending',
                        'active', 'paid', 'completed'         => 'stay-card--good',
                        'cancelled', 'expired'                => 'stay-card--dead',
                        default                               => '',
                    };
                    $nights = max(1, $booking->check_in->diffInDays($booking->check_out));

                    // Booking::getRoomTypeAttribute reads the booking_room pivot,
                    // which isn't always populated; reservations always are (they
                    // are what room_numbers is derived from). Prefer them, and
                    // keep the accessor as the fallback.
                    $types = $booking->reservations->pluck('room_type')->filter()->unique();
                    $typeList = $types->isNotEmpty()
                        ? $types->map(fn ($t) => Str::headline($t))
                        : collect(array_filter(explode(', ', (string) $booking->room_type)));

                    // A group booking can hold a dozen rooms. Naming them all
                    // wraps the header onto three lines and shoves the amount
                    // out of the card — summarise past three and let the detail
                    // page carry the full list.
                    $roomList = collect((array) $booking->room_numbers)->filter()->values();
                    $typeLabel = $typeList->count() > 2
                        ? $typeList->take(2)->implode(', ') . ' +' . ($typeList->count() - 2)
                        : $typeList->implode(', ');
                    $rooms = $roomList->count() > 3
                        ? $roomList->take(3)->implode(', ') . ' +' . ($roomList->count() - 3) . ' more'
                        : $roomList->implode(', ');
                @endphp

                <article class="stay-card {{ $rail }}" data-booking-row="{{ $booking->id }}">
                    <div class="stay-card__head">
                        <div class="stay-card__ref">
                            <span class="stay-card__id">#{{ $booking->id }}</span>
                            <x-booking.ui.badge :status="$booking->status" />
                            @if($typeLabel || $rooms)
                                <span class="stay-card__rooms" @if($roomList->count() > 3) title="Rooms {{ $roomList->implode(', ') }}" @endif>
                                    {{ $typeLabel ?: 'Room' }}@if($rooms) &middot; {{ Str::plural('Room', $roomList->count()) }} {{ $rooms }}@endif
                                </span>
                            @endif
                        </div>

                        <p class="stay-card__money">
                            <span class="stay-card__amount">₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}</span>
                            <span class="stay-card__amount-note">{{ in_array($booking->status, ['paid', 'active', 'completed']) ? 'Paid' : 'Total due' }}</span>
                        </p>
                    </div>

                    <div class="stay-card__dates">
                        <div>
                            <span class="stay-card__date-label">Check-in</span>
                            <span class="stay-card__date-value">{{ $booking->check_in->format('M d, Y') }}</span>
                        </div>
                        <div class="stay-card__span" aria-hidden="true">
                            <span class="stay-card__nights">{{ $nights }} {{ Str::plural('night', $nights) }}</span>
                        </div>
                        <div class="sm:text-right">
                            <span class="stay-card__date-label">Check-out</span>
                            <span class="stay-card__date-value">{{ $booking->check_out->format('M d, Y') }}</span>
                        </div>
                    </div>

                    <div class="stay-card__foot">
                        <span class="stay-card__meta">
                            <x-booking.ui.icon-solid name="tag" class="text-[11px] text-stone-400" />
                            Discount:
                            @if($booking->wants_discount)
                                <x-booking.ui.badge :status="$booking->discount_status ?? 'not_submitted'" />
                            @else
                                <x-booking.ui.badge status="no_request">No request</x-booking.ui.badge>
                            @endif
                        </span>

                        {{-- The next thing to do leads. On an unpaid booking that
                             is "Pay now", not "View" — the old row made a red
                             Cancel the widest, loudest control on the page. --}}
                        <div class="stay-card__actions">
                            @if($booking->status === 'pending_payment' && $booking->wants_discount)
                                {{-- A discounted rate is settled in person, so
                                     "Pay now" would lead to a page that turns the
                                     guest away (PaymentController::rejectIfNotPayable).
                                     Cancelling is still theirs — nothing is paid yet. --}}
                                <button type="button" onclick="openCancelModal({{ $booking->id }})" class="stay-card__quiet">Cancel</button>
                                <span class="stay-card__meta text-palay-800">
                                    <x-booking.ui.icon-solid name="building-columns" class="text-[11px]" /> Pay at front desk
                                </span>
                                <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-1.5 px-4 text-xs">View</x-booking.ui.button>
                            @elseif($booking->status === 'pending_payment')
                                <button type="button" onclick="openCancelModal({{ $booking->id }})" class="stay-card__quiet">Cancel</button>
                                <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-1.5 px-3.5 text-xs">View</x-booking.ui.button>
                                <x-booking.ui.button variant="primary" href="{{ route('bookings.pay', $booking->id) }}" class="py-1.5 px-4 text-xs">
                                    <x-booking.ui.icon-solid name="credit-card" class="text-[12px]" /> Pay now
                                </x-booking.ui.button>
                            @else
                                {{-- A paid booking has no Cancel, by policy: the money
                                     is not coming back, so the only thing left to
                                     offer is moving the stay. Three states, because
                                     "nothing here" is the wrong answer to two of them
                                     — a guest waiting on a decision should see that
                                     from the list, and a button the server would
                                     refuse is worse than no button. --}}
                                @php $openReschedule = \App\Models\RescheduleRequest::openFor($booking); @endphp
                                @if($openReschedule)
                                    <a href="{{ route('booking.reschedule.create', $booking->id) }}" class="stay-card__meta text-palay-800 !no-underline hover:text-palay-900">
                                        <x-booking.ui.icon-solid name="hourglass" class="text-[11px]" />
                                        Reschedule pending &middot; {{ $openReschedule->requested_check_in->format('M d') }}
                                    </a>
                                @elseif(\App\Models\RescheduleRequest::isOpenFor($booking))
                                    <x-booking.ui.button variant="outline" href="{{ route('booking.reschedule.create', $booking->id) }}" class="py-1.5 px-3.5 text-xs">
                                        <x-booking.ui.icon-solid name="calendar-days" class="text-[11px]" /> Reschedule
                                    </x-booking.ui.button>
                                @endif
                                <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-1.5 px-4 text-xs">
                                    View details
                                    <x-booking.ui.icon-solid name="arrow-right" class="text-[11px]" />
                                </x-booking.ui.button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pagination-wrapper mt-8 flex flex-col items-center space-y-2">
            <div>{{ $bookings->links('vendor.pagination.simple-tailwind') }}</div>
            <div class="text-stone-400 font-bold text-xs">
                Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} results
            </div>
        </div>
    @elseif($hasAnyBooking)
        {{-- A filter that matched nothing is not the same as having no
             bookings. This branch used to fall through to "No Bookings Yet —
             make your first booking today", which told a guest with a dozen
             stays that they had never booked, and offered them the one action
             that does not fix it. The way out of an over-narrow filter is to
             widen it, so that is the button. --}}
        <x-booking.ui.empty-state
            title="No bookings match these filters"
            description="Nothing here fits the search and status you picked. Try a different status, or clear the filters to see every stay."
            icon="magnifying-glass"
            actionText="Clear filters"
            :actionUrl="route('settings.bookings')"
        />
    @else
        <x-booking.ui.empty-state
            title="No Bookings Yet"
            description="You don't have any room reservations yet. Explore our available rooms and make your first booking today!"
            icon="bed"
            actionText="Book a Room"
            :actionUrl="route('home')"
        />
    @endif

    <!-- Cancellation Modal -->
    <x-booking.ui.modal id="cancelModal" title="Cancel Room Booking">
        <form id="cancelForm" method="POST" class="space-y-4" data-busy-form>
            @csrf
            <div>
                <label for="reason" class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Reason for Cancellation</label>
                <textarea name="reason" id="reason" rows="3" required placeholder="Please provide details on why you are cancelling your booking..."
                          class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"></textarea>
            </div>

            <div class="pt-4 border-t border-stone-100 flex justify-end gap-2.5">
                <button type="button" data-modal-close="cancelModal" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-stone-100 hover:bg-stone-200 text-stone-700 transition-[color,background-color,border-color,box-shadow] cursor-pointer">Close</button>
                <button type="submit" data-busy-btn class="px-5 py-2.5 rounded-xl text-sm font-bold bg-ember-600 hover:bg-ember-700 text-white shadow-sm transition-[color,background-color,border-color,box-shadow] cursor-pointer">Confirm Cancellation</button>
            </div>
        </form>
    </x-booking.ui.modal>

    <script>
        // openModal() rather than classList.remove('hidden'): the bare class
        // toggle skipped the scroll lock and the focus trap, so the page kept
        // scrolling behind the dialog and Tab left it immediately.
        function openCancelModal(bookingId) {
            document.getElementById('cancelForm').action = `/booking/${bookingId}/cancel`;
            window.openModal('cancelModal');
        }

        // The local `closeModal()` that used to live here shadowed the engine's
        // own window.closeModal for every other script on the page. Dismissal
        // is now the declarative [data-modal-close] contract.
    </script>

    <script>
    // Real-time: this list is where a guest waits after uploading a proof of
    // payment, and until now the only way to learn the verdict was to refresh
    // by hand. The account-wide private channel (App\Events\GuestBookingUpdated)
    // carries only a booking id, its new status and a message already safe to
    // show — never an amount or a contact detail.
    //
    // Reverb being down is harmless: nothing here throws, the page simply
    // behaves exactly as it did before.
    //
    // Bound on DOMContentLoaded, not inline: app.js is a Vite module and so is
    // deferred, which means window.Echo does not exist yet while this script
    // is being parsed. Deferred modules run before DOMContentLoaded fires, so
    // by the time this callback runs Echo is there.
    document.addEventListener('DOMContentLoaded', function () {
        const KEY = 'guest_booking_notice';
        const notice = document.getElementById('liveBookingNotice');
        if (!notice) return;

        const shell = notice.querySelector('[data-notice-shell]');
        const icons = notice.querySelectorAll('[data-notice-icon]');
        const text  = notice.querySelector('[data-notice-text]');
        const sub   = notice.querySelector('[data-notice-sub]');

        const TONE = {
            good: 'border-clsu-200 bg-clsu-50 text-clsu-800',
            bad:  'border-ember-200 bg-ember-50 text-ember-700',
        };

        function show(message, tone, subText) {
            const key = TONE[tone] ? tone : 'good';
            shell.className = 'flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm ' + TONE[key];
            // Both glyphs are in the DOM; reveal the one this tone calls for.
            icons.forEach(el => el.classList.toggle('hidden', el.dataset.noticeIcon !== key));
            text.textContent = message;
            sub.textContent = subText || '';
            sub.classList.toggle('hidden', !subText);
            notice.classList.remove('hidden');
        }

        notice.querySelector('[data-notice-dismiss]').addEventListener('click', function () {
            notice.classList.add('hidden');
        });

        // A message stashed just before the reload below — replay it so the
        // guest actually reads the outcome instead of watching it flash past.
        try {
            const stashed = JSON.parse(sessionStorage.getItem(KEY) || 'null');
            if (stashed) {
                sessionStorage.removeItem(KEY);
                show(stashed.message, stashed.tone, '');
                const row = document.querySelector('[data-booking-row="' + stashed.bookingId + '"]');
                if (row) {
                    row.classList.add('booking-row-flash');
                    row.scrollIntoView({
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                        block: 'center',
                    });
                }
            }
        } catch (e) { /* a corrupt stash must never break the page */ }

        if (!window.Echo) return;

        window.Echo.private('user.{{ auth()->id() }}.bookings')
            .listen('.GuestBookingUpdated', function (payload) {
                if (!payload || !payload.message) return;

                const tone = payload.status === 'paid' ? 'good' : 'bad';
                show(payload.message, tone, 'Refreshing your bookings…');

                // The status badge, the total and the available actions are
                // all computed in Blade, so a reload is the only way to show a
                // consistent row. Stash the message so it survives the trip.
                try {
                    sessionStorage.setItem(KEY, JSON.stringify({
                        message: payload.message,
                        tone: tone,
                        bookingId: payload.bookingId,
                    }));
                } catch (e) { /* private mode — the banner just won't persist */ }

                setTimeout(function () { window.location.reload(); }, 2200);
            });

        // The same refresh without a WebSocket — which is what actually runs
        // here. window.Echo is only built into the staff bundle, so the
        // listener above has never fired on this page. This watches the
        // statuses of the rows on screen and re-renders when one of them moves,
        // with no Reverb daemon required.
        //
        // Deliberately inside this callback rather than a second
        // DOMContentLoaded listener: KEY and show() are declared in this scope,
        // and reaching them from a sibling listener is a ReferenceError the
        // browser raises only once a status actually changes — long after any
        // syntax check has passed.
        const ENDPOINT = @json(route('bookings.status.feed'));
        const INTERVAL = 20000;

        let snapshot = null;   // taken on the first reply, not from the markup
        let inFlight = false;

        async function check() {
            if (inFlight || document.hidden) return;
            inFlight = true;

            try {
                const res = await fetch(ENDPOINT, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;

                const data = await res.json();
                const now = data.bookings || {};

                // First reply is the baseline — the state these rows were
                // rendered from. Only a later change is worth reloading for.
                if (snapshot === null) { snapshot = now; return; }

                const movedId = Object.keys(now).find(function (id) {
                    return snapshot[id] && snapshot[id].status !== now[id].status;
                });

                if (!movedId) return;

                // Same banner the Reverb path shows, from a line the server
                // composed — so the guest is told what happened rather than
                // finding a silently different row after the reload.
                const moved = now[movedId];
                const tone = moved.status === 'paid' ? 'good' : 'bad';

                show(moved.message, tone, 'Refreshing your bookings…');

                try {
                    sessionStorage.setItem(KEY, JSON.stringify({
                        message: moved.message,
                        tone: tone,
                        bookingId: Number(movedId),
                    }));
                } catch (e) { /* private mode — the banner just won't persist */ }

                setTimeout(function () { window.location.reload(); }, 2200);
            } catch (e) {
                // A dropped poll is never worth showing the guest; retry next tick.
            } finally {
                inFlight = false;
            }
        }

        check();
        setInterval(check, INTERVAL);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) check();
        });
    });
    </script>

    <script>
    // Auto-applying filters — the reason there is no Apply button.
    //
    // requestSubmit() rather than submit(): submit() bypasses both validation
    // and the submit event, which would quietly cut out the shared
    // [data-busy-form] handler if this form ever grows one.
    (function () {
        var form = document.querySelector('form[data-autofilter]');
        if (!form) return;

        // Chips and selects are discrete choices — commit them at once.
        form.addEventListener('change', function (e) {
            if (e.target.matches('select, input[type="radio"]')) form.requestSubmit();
        });

        var search = form.querySelector('#bookingSearch');
        if (!search) return;

        // Typing is not a choice until it stops. Without the debounce this
        // fires a full page load per keystroke.
        var timer;
        var REFOCUS = 'mybookings:refocus';
        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                // A reload throws away focus and the caret, so a guest mid-word
                // would be typing into nothing. Flag the reload as ours and put
                // them back afterwards — but only for a search-driven reload,
                // never when the page is opened or navigated back to.
                try { sessionStorage.setItem(REFOCUS, '1'); } catch (err) {}
                form.requestSubmit();
            }, 450);
        });

        try {
            if (sessionStorage.getItem(REFOCUS)) {
                sessionStorage.removeItem(REFOCUS);
                search.focus({ preventScroll: true });
                search.setSelectionRange(search.value.length, search.value.length);
            }
        } catch (err) {}
    })();
    </script>
@endsection
