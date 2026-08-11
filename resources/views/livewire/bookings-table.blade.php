<x-admin.ui.section-card wire:poll.15s icon="clipboard" title="Complete Booking Log" subtitle="Every booking that has not been checked out yet.">
    {{-- data-no-loader: these serve a spreadsheet download and leave the page
         where it is, so the navigation curtain (partials/page-loader) must not
         be raised for them. --}}
    <x-slot:actions>
        <a href="{{ route('reports.bookings.full') }}" data-no-loader class="flex items-center gap-1.5 text-xs font-semibold text-clsu-700 border border-clsu-200 bg-white rounded-lg px-3 py-1.5 hover:bg-clsu-50 transition-colors !no-underline">
            <x-admin.ui.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            All
        </a>
        <a href="{{ route('reports.bookings.paid') }}" data-no-loader class="flex items-center gap-1.5 text-xs font-semibold text-palay-800 border border-palay-200 bg-white rounded-lg px-3 py-1.5 hover:bg-palay-50 transition-colors !no-underline">
            <x-admin.ui.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            Paid
        </a>
        <a href="{{ route('reports.bookings.completed') }}" data-no-loader class="flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors !no-underline">
            <x-admin.ui.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            Completed
        </a>
    </x-slot:actions>

    {{-- Action feedback arrives as toasts ($this->dispatch('toast', …)) --}}

    @php
        $statusPills = [
            '' => 'All',
            'pending_payment' => 'Pending Payment',
            'pending_discount' => 'Pending Discount',
            'paid' => 'Paid',
            'active' => 'Active',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            'no_show' => 'No Show',
        ];
        $statusClassMap = [
            'paid' => 'status-paid', 'active' => 'status-active',
            'pending_payment' => 'status-pending_payment', 'pending_discount' => 'status-pending_discount',
            'cancelled' => 'status-cancelled', 'expired' => 'status-expired', 'no_show' => 'status-cancelled',
        ];
    @endphp

    {{-- Search + date --}}
    <div class="filter-toolbar">
        <div class="filter-search">
            <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search ref, guest, or room…" aria-label="Search bookings">
        </div>
        <select wire:model.live="dateFilter" class="filter-select" aria-label="Filter by date">
            <option value="">Any date</option>
            <option value="today_checkin">Check-in today</option>
            <option value="tomorrow_checkin">Check-in tomorrow</option>
            <option value="today_checkout">Check-out today</option>
            <option value="tomorrow_checkout">Check-out tomorrow</option>
        </select>
        {{-- Sorting has to live somewhere other than the headers: below ~1200px
             the table folds to four columns, and check-out and status are not
             among them. Matches the sort selects on payment logs and completed
             bookings; the column headers keep working where they are shown. --}}
        @php $currentSort = $this->sortValue(); @endphp
        <select wire:change="setSort($event.target.value)" class="filter-select" aria-label="Sort bookings">
            <option value="" @selected($currentSort === '')>Newest first</option>
            <option value="check_in:asc" @selected($currentSort === 'check_in:asc')>Check-in, earliest</option>
            <option value="check_in:desc" @selected($currentSort === 'check_in:desc')>Check-in, latest</option>
            <option value="check_out:asc" @selected($currentSort === 'check_out:asc')>Check-out, earliest</option>
            <option value="check_out:desc" @selected($currentSort === 'check_out:desc')>Check-out, latest</option>
            <option value="guest_name:asc" @selected($currentSort === 'guest_name:asc')>Guest, A to Z</option>
            <option value="guest_name:desc" @selected($currentSort === 'guest_name:desc')>Guest, Z to A</option>
            <option value="status:asc" @selected($currentSort === 'status:asc')>Status, A to Z</option>
        </select>
        <div class="filter-toolbar-spacer"></div>
        <span class="refresh-chip" wire:loading.delay.flex wire:target="search, dateFilter, setStatus, toggleDate, toggleStatus, resetFilters, gotoPage, previousPage, nextPage">
            <svg class="spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
            Updating
        </span>
        @if($search !== '' || $statusFilter !== '' || $dateFilter !== '')
            <button type="button" wire:click="resetFilters" class="filter-clear">
                <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" />
                Clear filters
            </button>
        @endif
    </div>

    {{-- Status pills --}}
    <div class="filter-row mb-3">
        <span class="filter-row-label">Status</span>
        @foreach($statusPills as $value => $label)
            <button type="button" wire:click="setStatus('{{ $value }}')"
                    @class(['filter-tab', 'selected' => $statusFilter === $value])>
                {{ $label }}
                <span class="ft-count">{{ $value === '' ? $statusCounts->sum() : ($statusCounts[$value] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    {{-- Quick filters --}}
    <div class="filter-row mb-6">
        <span class="filter-row-label">Quick filters</span>
        <button type="button" wire:click="toggleDate('today_checkin')" @class(['filter-tab', 'selected' => $dateFilter === 'today_checkin'])>Arriving today</button>
        <button type="button" wire:click="toggleDate('today_checkout')" @class(['filter-tab', 'selected' => $dateFilter === 'today_checkout'])>Departing today</button>
        <button type="button" wire:click="toggleStatus('active')" @class(['filter-tab', 'selected' => $statusFilter === 'active'])>Active stays</button>
    </div>

    @if($bookings->isEmpty())
        <x-admin.ui.empty-state icon="clipboard" title="No bookings found." />
    @else
        {{-- table-fold makes this the query container for the four-column
             layout (see 20-table-fold.css). It sits on .wire-panel rather than
             on .scroll-x on purpose: container-type applies containment, and
             putting that on the scroll container itself disturbs the sticky
             Actions column it holds. --}}
        <div class="wire-panel table-fold" wire:loading.delay.class="is-refreshing" wire:target="search, dateFilter, setStatus, toggleDate, toggleStatus, resetFilters, gotoPage, previousPage, nextPage">
        <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
            <table class="data-table data-table-foldable" data-server-sort>
                <thead>
                    <tr>
                        {{-- Three columns fold away below the breakpoint; their
                             content reappears inside the columns that remain,
                             and their sorts stay reachable from the toolbar
                             select. --}}
                        <x-admin.ui.sort-th field="id" :active="$sortField" :dir="$sortDirection">Ref code</x-admin.ui.sort-th>
                        <x-admin.ui.sort-th field="guest_name" :active="$sortField" :dir="$sortDirection">Guest</x-admin.ui.sort-th>
                        <th class="col-fold">Room(s)</th>
                        <x-admin.ui.sort-th field="check_in" :active="$sortField" :dir="$sortDirection">
                            <span class="fold-hide">Check-in</span><span class="fold-show">Stay</span>
                        </x-admin.ui.sort-th>
                        <x-admin.ui.sort-th field="check_out" :active="$sortField" :dir="$sortDirection" class="col-fold">Check-out</x-admin.ui.sort-th>
                        <x-admin.ui.sort-th field="status" :active="$sortField" :dir="$sortDirection" class="col-fold">Status</x-admin.ui.sort-th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        @php
                            $statusClass = $statusClassMap[$booking->status] ?? 'status-neutral';
                            $statusText = ucwords(str_replace('_', ' ', $booking->status));
                            $ref = 'BK-' . str_pad($booking->id, 4, '0', STR_PAD_LEFT);
                            $initials = collect(explode(' ', trim($booking->guest_name)))
                                ->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                            $initials = strtoupper($initials ?: 'G');
                            $rooms = $booking->reservations->pluck('room_number')->filter()->implode(', ');
                            // Shown only in the folded layout, where check-out
                            // has no column of its own and the span has to read
                            // as one thing.
                            $nights = $booking->check_in->diffInDays($booking->check_out);
                        @endphp
                        <tr>
                            <td>
                                <div class="ref-cell">
                                    <div class="ref-cell-top">
                                        <span class="ref-code">{{ $ref }}</span>
                                        <button type="button" class="copy-ref" data-copy="{{ $ref }}" title="Copy reference">
                                            <x-admin.ui.icon name="clipboard" class="w-3 h-3" />
                                            Copy
                                        </button>
                                    </div>
                                    <span class="status {{ $statusClass }} fold-show">{{ $statusText }}</span>
                                    <span class="ref-cell-sub">Booked {{ $booking->created_at?->format('M d') ?? '—' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-name">
                                    <span class="avatar-initials">{{ $initials }}</span>
                                    <div class="cell-name-text">
                                        <p class="cell-name-primary guest-history-link cursor-pointer hover:text-clsu-700 hover:underline" data-booking-id="{{ $booking->id }}" title="{{ $booking->guest_name }} — view guest history">{{ $booking->guest_name }}</p>
                                        <p class="cell-name-secondary">
                                            <span class="fold-hide">#{{ $booking->id }}</span>
                                            <span class="fold-show">{{ $rooms ? 'Room ' . $rooms : 'No room assigned' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="col-fold">
                                @if($rooms)
                                    <span class="cell-tag font-data">{{ $rooms }}</span>
                                @else
                                    <span class="text-faint">—</span>
                                @endif
                            </td>
                            <td class="font-data tabnum">
                                {{ $booking->check_in->format('M d, Y') }}
                                <span class="fold-show stay-span">
                                    to {{ $booking->check_out->format('M d, Y') }}
                                    <span class="stay-nights">{{ $nights }} {{ Str::plural('night', $nights) }}</span>
                                </span>
                            </td>
                            <td class="font-data tabnum col-fold">{{ $booking->check_out->format('M d, Y') }}</td>
                            <td class="col-fold"><span class="status {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="password-verify btn btn-outline btn-sm cursor-pointer" data-action="view" data-id="{{ $booking->id }}">
                                        <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" />
                                        View
                                    </button>
                                    @if($booking->status === 'pending_payment')
                                        <button type="button" class="password-verify btn btn-danger btn-sm cursor-pointer" data-action="cancel" data-id="{{ $booking->id }}">
                                            <x-admin.ui.icon name="x" class="w-3.5 h-3.5" stroke-width="2" />
                                            Cancel
                                        </button>
                                    @endif
                                    @if($booking->status === 'active')
                                        <button type="button" class="password-verify btn btn-gold btn-sm cursor-pointer" data-action="checkout" data-id="{{ $booking->id }}">
                                            <x-admin.ui.icon name="log-out" class="w-3.5 h-3.5" />
                                            Checkout
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $bookings->links('vendor.pagination.admin') }}
        </div>
        </div>
    @endif

    {{-- The booking-detail modal is NOT rendered here.
         It used to be, driven by a $selectedBooking Livewire property, which
         made "View" one of the most expensive clicks in the console — see the
         note on the button handler below. It is now fetched on demand by
         window.openBookingDetail() (layouts/admin), the same path the guest
         name has always used, rendering the same partial. --}}
</x-admin.ui.section-card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // (.copy-ref clipboard handler lives globally in layouts/admin)
    $(document).on('click', '.password-verify', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('id');
        const action = $(this).data('action');

        // View is read-only — open it straight away, no interstitial.
        //
        // Fetched, not dispatched to Livewire. `openBookingModal` set a full
        // Booking model (with reservations.room and payments eager-loaded)
        // into a public Livewire property, which meant every click round-
        // tripped the whole component: the status-count query, the paginated
        // 15-row query and the entire table markup were rebuilt just to show a
        // dialog — and because this card is wire:poll.15s, that model graph was
        // then re-serialised and the table re-rendered every 15 seconds for as
        // long as the modal stayed open. That is the lag.
        //
        // The guest name beside it already fetched the same modal, from the
        // same partial, without touching the table. Now both do.
        if (action === 'view') {
            window.openBookingDetail(bookingId);
            return;
        }

        // Cancel / checkout change state, so keep a lightweight confirm
        // (no password) to guard against an accidental click.
        const cfg = action === 'cancel'
            ? { title: 'Cancel this booking?', text: 'This releases the pending booking.', icon: 'warning', confirmButtonText: 'Yes, cancel', event: 'cancelBookingConfirmed' }
            : { title: 'Check out this guest?', text: 'This marks the booking as checked out.', icon: 'question', confirmButtonText: 'Yes, check out', event: 'checkoutBookingConfirmed' };

        Swal.fire({
            title: cfg.title,
            text: cfg.text,
            icon: cfg.icon,
            showCancelButton: true,
            confirmButtonText: cfg.confirmButtonText
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch(cfg.event, { bookingId });
            }
        });
    });
});
</script>
@endpush
