@extends('layouts.frontdesk')
@section('title', 'Front Desk · Bookings')
@section('content')

<x-frontdesk.flash />

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <x-admin.ui.icon name="clipboard" />
            Bookings
        </h3>
        <div class="card-header-actions">
            <span class="section-label hidden sm:inline">{{ $bookings->total() }} total</span>
            <a href="{{ route('frontdesk.walkin.create') }}" class="btn btn-primary btn-sm !no-underline">
                <x-admin.ui.icon name="plus" class="h-3.5 w-3.5" stroke-width="2.5" />
                New booking
            </a>
        </div>
    </div>
    <div class="card-body">

        <form method="GET" class="filter-toolbar mb-4">
            <div class="filter-search">
                <x-admin.ui.icon name="search" stroke-width="2" />
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by booking ID" autocomplete="off">
            </div>
            <select name="sort" class="filter-select" aria-label="Sort bookings">
                <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Latest first</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest first</option>
                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Guest name</option>
                <option value="check_in" {{ request('sort') == 'check_in' ? 'selected' : '' }}>Check-in date</option>
            </select>
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
            @if(request('search') || (request('sort') && request('sort') !== 'latest') || $status !== 'all')
                <a href="{{ route('frontdesk.booking') }}" class="filter-clear !no-underline">
                    <x-admin.ui.icon name="x" stroke-width="2.5" />
                    Clear
                </a>
            @endif
        </form>

        {{-- Status tabs: fixed desk-relevant order first, then anything else in the data --}}
        @php
            $tabOrder = ['active', 'paid', 'pending_payment', 'completed', 'no_show', 'cancelled', 'expired'];
            $tabs = collect($tabOrder)->filter(fn ($s) => $statusCounts->has($s))
                ->concat($statusCounts->keys()->diff($tabOrder))->values();
            $tabQuery = array_filter(['search' => request('search'), 'sort' => request('sort')]);
        @endphp
        <div class="filter-row mb-5">
            <span class="filter-row-label">Status</span>
            <a href="{{ route('frontdesk.booking', $tabQuery) }}"
               class="filter-tab !no-underline {{ $status === 'all' ? 'selected' : '' }}">
                All <span class="ft-count">{{ $statusCounts->sum() }}</span>
            </a>
            @foreach($tabs as $s)
                <a href="{{ route('frontdesk.booking', $tabQuery + ['status' => $s]) }}"
                   class="filter-tab !no-underline {{ $status === $s ? 'selected' : '' }}">
                    {{ ucwords(str_replace('_', ' ', $s)) }} <span class="ft-count">{{ $statusCounts[$s] }}</span>
                </a>
            @endforeach
        </div>

        @if($bookings->count())
            <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Rooms</th>
                            <th class="text-right">Guests</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php
                                $initials = strtoupper(collect(explode(' ', trim($booking->guest_name ?? '')))
                                    ->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') ?: 'G');
                            @endphp
                            <tr>
                                <td><span class="ref-code">BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <span class="cell-name">
                                        <span class="avatar-initials">{{ $initials }}</span>
                                        <span class="min-w-0">
                                            <span class="block max-w-44 truncate font-semibold text-ink">{{ $booking->guest_name }}</span>
                                            <span class="block font-data text-2xs text-faint tabnum">{{ $booking->guest_phone }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $visibleRooms = $booking->reservations->take(3);
                                        $extraRooms = $booking->reservations->slice(3);
                                    @endphp
                                    <div class="flex max-w-72 flex-wrap items-center gap-1">
                                        @foreach($visibleRooms as $res)
                                            <span class="cell-tag">{{ $res->room_number }} · {{ ucfirst($res->room_type) }}</span>
                                        @endforeach
                                        @if($extraRooms->isNotEmpty())
                                            <span class="cell-tag" title="{{ $extraRooms->map(fn ($r) => $r->room_number . ' · ' . ucfirst($r->room_type))->implode(', ') }}">+{{ $extraRooms->count() }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right tabnum">{{ $booking->expected_guests ?? $booking->reservations->sum('num_guests') }}</td>
                                <td>
                                    <span class="status status-{{ $booking->status }}">{{ ucwords(str_replace('_', ' ', $booking->status)) }}</span>
                                </td>
                                <td>
                                    <div class="table-actions justify-end">
                                        <a href="{{ route('frontdesk.walkin.show', $booking) }}" class="btn btn-outline btn-sm !no-underline" title="View booking">
                                            <x-admin.ui.icon name="eye" class="h-3.5 w-3.5" />
                                            View
                                        </a>
                                        {{-- Counter payment. The only route to `paid`
                                             for a Senior/PWD booking, which cannot be
                                             settled online at all — and the first way
                                             to record cash for any other. --}}
                                        @if ($booking->status === 'pending_payment')
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    data-settle
                                                    data-settle-id="{{ $booking->id }}"
                                                    data-settle-guest="{{ $booking->guest_name }}"
                                                    data-settle-amount="{{ number_format((float) ($booking->payable_amount ?? $booking->total_price), 2) }}"
                                                    data-settle-discount="{{ $booking->wants_discount ? '1' : '' }}"
                                                    data-settle-action="{{ route('frontdesk.booking.settle', $booking->id) }}">
                                                <x-admin.ui.icon name="credit-card" class="h-3.5 w-3.5" stroke-width="2" />
                                                Settle
                                            </button>
                                        @endif
                                        @if ($booking->status == 'active')
                                            <form method="POST" action="{{ route('frontdesk.booking.checkout', $booking->id) }}"
                                                  data-busy-form
                                                  data-confirm-title="Check out this booking?"
                                                  data-confirm="The rooms will be released and the booking marked completed."
                                                  data-confirm-action="Check out">
                                                @csrf
                                                <button type="submit" data-busy-btn class="btn btn-primary btn-sm">
                                                    <x-admin.ui.icon name="log-out" class="h-3.5 w-3.5" stroke-width="2" />
                                                    Check out
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
                <div class="mt-6">
                    {{ $bookings->links('vendor.pagination.admin', ['mode' => 'links']) }}
                </div>
            @endif
        @else
            <div class="py-6 text-center">
                <x-admin.ui.empty-state icon="calendar" title="No bookings found. Try a different search, or create a manual booking." />
                <a href="{{ route('frontdesk.walkin.create') }}" class="btn btn-primary btn-sm mt-4 !no-underline">
                    <x-admin.ui.icon name="plus" class="h-3.5 w-3.5" stroke-width="2.5" />
                    New walk-in booking
                </a>
            </div>
        @endif

    </div>
</div>

{{-- Counter payment. One modal for the whole table — the row's button fills in
     which booking it is talking about, the same way the guest-side cancel modal
     rewrites its form action. --}}
<x-admin.ui.modal id="settleModal" icon="credit-card" title="Record a counter payment">
    <form id="settleForm" method="POST" class="px-6 py-5 space-y-4" data-busy-form>
        @csrf

        {{-- What is being settled, restated inside the dialog. The row that
             opened it is behind a backdrop by now, and this action takes money
             and cuts a receipt — the wrong booking is not a recoverable slip. --}}
        <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-stone-400">Booking</p>
            <p class="mt-1 text-sm font-semibold text-stone-800" data-settle-summary></p>
            <p class="mt-3 text-[10px] font-bold uppercase tracking-widest text-stone-400">Amount due</p>
            <p class="mt-1 text-lg font-black tabnum text-stone-800">₱<span data-settle-amount-text></span></p>
        </div>

        {{-- Shown only for a discounted booking, because that is the one where
             the desk has a job to do before taking the money: the discount
             exists precisely because nobody has yet seen the ID. --}}
        <p class="hidden items-start gap-2 rounded-xl border border-palay-200 bg-palay-50 px-4 py-3 text-xs font-semibold leading-relaxed text-palay-800"
           data-settle-discount-note>
            <x-admin.ui.icon name="tag" class="mt-px h-3.5 w-3.5 shrink-0" stroke-width="2" />
            <span>Senior&nbsp;/&nbsp;PWD booking — check the original ID for every discounted guest before recording this payment.</span>
        </p>

        <div>
            <x-admin.ui.label for="settle_method">How did they pay?</x-admin.ui.label>
            <x-admin.ui.field name="method" id="settle_method" control="select" required>
                @foreach(\App\Http\Controllers\Staff\FrontDesk\BookingsController::DESK_PAYMENT_METHODS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-admin.ui.field>
        </div>

        <div>
            <x-admin.ui.label for="settle_reference" hint="(optional)">Reference number</x-admin.ui.label>
            <x-admin.ui.field name="reference" id="settle_reference" maxlength="60" autocomplete="off"
                              placeholder="OR number or e-wallet reference" />
        </div>

        <div class="flex gap-2.5 justify-end pt-2">
            <x-admin.ui.modal-footer close-target="settleModal" submit-label="Record payment" data-busy-btn />
        </div>
    </form>
</x-admin.ui.modal>

@endsection

@push('scripts')
<script>
// The check-out confirmation now comes from the shared [data-confirm]
// contract (resources/js/staff-actions.js). This page and
// frontdesk/walkin/show each carried a byte-identical copy, both ending in
// form.submit() — which fires no submit event, so neither ever armed the
// double-submit guard on an action that releases rooms.

// Counter payment: the row's button carries which booking it is, the modal
// is shared. Delegated, so it survives the live refresh below re-rendering
// the table out from under any listener bound to a specific button.
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-settle]');
    if (!trigger) return;

    const form = document.getElementById('settleForm');
    if (!form) return;

    form.action = trigger.dataset.settleAction;
    form.querySelector('[data-settle-summary]').textContent =
        'BK-' + String(trigger.dataset.settleId).padStart(4, '0') + ' · ' + trigger.dataset.settleGuest;
    form.querySelector('[data-settle-amount-text]').textContent = trigger.dataset.settleAmount;

    // `flex`, not just un-hiding: the note lays its icon out beside the text.
    const note = form.querySelector('[data-settle-discount-note]');
    note.classList.toggle('hidden', !trigger.dataset.settleDiscount);
    note.classList.toggle('flex', !!trigger.dataset.settleDiscount);

    // A reference typed for the previous booking must not ride along.
    form.querySelector('#settle_reference').value = '';

    window.openModal('settleModal');
});

// Real-time push: a booking paid online, checked out at another desk, or
// expired by the scheduler shows up here immediately. Held back while a
// SweetAlert confirmation is open — see live-refresh.js.
window.liveRefresh([
    { channel: 'bookings', event: 'BookingChanged' },
]);
</script>
@endpush
