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
                                            <span class="block font-data text-[11px] text-faint tabnum">{{ $booking->guest_phone }}</span>
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
                                        @if ($booking->status == 'active')
                                            <form method="POST" action="{{ route('frontdesk.booking.checkout', $booking->id) }}" class="js-checkout-form">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">
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

@endsection

@push('scripts')
<script>
$(function () {
    // Confirm before checking a booking out (releases the rooms)
    $(document).on('submit', '.js-checkout-form', function (e) {
        if ($(this).data('confirmed')) return;
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Check out this booking?',
            text: 'The rooms will be released and the booking marked completed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Check out',
            confirmButtonColor: '#099250'
        }).then(function (res) {
            if (res.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });
});
</script>
@endpush
