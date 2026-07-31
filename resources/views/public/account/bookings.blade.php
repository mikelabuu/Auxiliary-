@extends('layouts.public.account')
@section('title', 'My Bookings')
@section('page-title', 'My Bookings')

@section('settings-content')
    <x-booking.ui.page-header title="My Bookings" subtitle="View and manage your room reservation history."></x-booking.ui.page-header>

    {{-- Search + Filters.
         Was a 5-column grid whose last cell held three controls, so Apply and
         Reset were squeezed past the card edge at every width below xl. The
         controls now get their own row on small screens and their own column
         on large ones, so nothing overflows. --}}
    <form method="GET" action="{{ route('settings.bookings') }}" class="bg-stone-50/60 border border-stone-200/70 p-5 rounded-2xl mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-4 items-end">
            <div class="sm:col-span-2 xl:col-span-4">
                <label for="bookingSearch" class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Search</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-stone-400 text-[13px]"></i>
                    <input id="bookingSearch" type="text" name="search" placeholder="Booking ID, name, or room…" value="{{ request('search') }}"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold">
                </div>
            </div>

            <div class="xl:col-span-2">
                <label for="bookingStatus" class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Status</label>
                <select id="bookingStatus" name="status" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] cursor-pointer font-semibold">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                    <option value="pending_discount" {{ request('status') == 'pending_discount' ? 'selected' : '' }}>Pending Discount</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="xl:col-span-4">
                <label for="bookingSort" class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Sort by</label>
                <div class="flex gap-2">
                    <select id="bookingSort" name="sort_by" class="min-w-0 flex-1 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] cursor-pointer font-semibold">
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Booking Date</option>
                        <option value="check_in" {{ request('sort_by') == 'check_in' ? 'selected' : '' }}>Check-in Date</option>
                        <option value="check_out" {{ request('sort_by') == 'check_out' ? 'selected' : '' }}>Check-out Date</option>
                        <option value="total_price" {{ request('sort_by') == 'total_price' ? 'selected' : '' }}>Total Price</option>
                        <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Booking Status</option>
                    </select>
                    {{-- Direction is an attribute of the sort, so it sits with
                         it rather than reading as a fourth, unrelated filter. --}}
                    <select name="sort_dir" aria-label="Sort direction" class="w-[4.75rem] shrink-0 px-2 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] cursor-pointer font-semibold">
                        <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>↓</option>
                        <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>↑</option>
                    </select>
                </div>
            </div>

            <div class="sm:col-span-2 xl:col-span-2 flex gap-2">
                <x-booking.ui.button variant="primary" class="flex-1 py-2.5 px-4">Apply</x-booking.ui.button>
                @if(request()->filled('search') || request()->filled('status'))
                    <x-booking.ui.button variant="neutral" href="{{ route('settings.bookings') }}" class="py-2.5 px-4 shrink-0" title="Clear filters">
                        <i class="fa-solid fa-arrows-rotate text-[13px]"></i>
                        <span class="sr-only">Reset filters</span>
                    </x-booking.ui.button>
                @endif
            </div>
        </div>
    </form>

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
            <i class="fa-solid fa-bell text-[20px] shrink-0 mt-0.5" data-notice-icon></i>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold leading-relaxed" data-notice-text></p>
                <p class="text-[11px] font-semibold opacity-70 mt-1" data-notice-sub>Updating your bookings…</p>
            </div>
            <button type="button" data-notice-dismiss aria-label="Dismiss"
                    class="shrink-0 rounded-full p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer">
                <i class="fa-solid fa-xmark text-[18px]"></i>
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
                            <i class="fa-solid fa-tag text-[11px] text-stone-400"></i>
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
                            @if($booking->status === 'pending_payment')
                                <button type="button" onclick="openCancelModal({{ $booking->id }})" class="stay-card__quiet">Cancel</button>
                                <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-1.5 px-3.5 text-xs">View</x-booking.ui.button>
                                <x-booking.ui.button variant="primary" href="{{ route('bookings.pay', $booking->id) }}" class="py-1.5 px-4 text-xs">
                                    <i class="fa-solid fa-credit-card text-[12px]"></i> Pay now
                                </x-booking.ui.button>
                            @else
                                <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-1.5 px-4 text-xs">
                                    View details
                                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
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
        <form id="cancelForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="reason" class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Reason for Cancellation</label>
                <textarea name="reason" id="reason" rows="3" required placeholder="Please provide details on why you are cancelling your booking..."
                          class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"></textarea>
            </div>

            <div class="pt-4 border-t border-stone-100 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-stone-100 hover:bg-stone-200 text-stone-700 transition-[color,background-color,border-color,box-shadow] cursor-pointer">Close</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-ember-600 hover:bg-ember-700 text-white shadow-sm transition-[color,background-color,border-color,box-shadow] cursor-pointer">Confirm Cancellation</button>
            </div>
        </form>
    </x-booking.ui.modal>

    <script>
        function openCancelModal(bookingId) {
            const modal = document.getElementById('cancelModal');
            const form = document.getElementById('cancelForm');
            form.action = `/booking/${bookingId}/cancel`;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            window.pubModalClose('cancelModal');
        }
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
        const icon  = notice.querySelector('[data-notice-icon]');
        const text  = notice.querySelector('[data-notice-text]');
        const sub   = notice.querySelector('[data-notice-sub]');

        // Font Awesome classes, not Material ligature text: the icon is an <i>
        // whose glyph comes from its class, so writing a name into textContent
        // printed the literal word ("task_alt") next to the message.
        const TONE = {
            good: { shell: 'border-clsu-200 bg-clsu-50 text-clsu-800', icon: 'fa-circle-check' },
            bad:  { shell: 'border-ember-200 bg-ember-50 text-ember-700', icon: 'fa-circle-exclamation' },
        };

        function show(message, tone, subText) {
            const t = TONE[tone] || TONE.good;
            shell.className = 'flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm ' + t.shell;
            icon.className = 'fa-solid ' + t.icon + ' text-[20px] shrink-0 mt-0.5';
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
    });
    </script>
@endsection
