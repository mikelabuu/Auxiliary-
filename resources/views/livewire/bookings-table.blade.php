<x-admin.ui.section-card wire:poll.15s icon="clipboard" title="Complete Booking Log" subtitle="Every booking that hasn't been checked out yet — search, filter, and act.">
    <x-slot:actions>
        <a href="{{ route('reports.bookings.full') }}" class="flex items-center gap-1.5 text-xs font-semibold text-clsu-700 border border-clsu-200 bg-white rounded-lg px-3 py-1.5 hover:bg-clsu-50 transition-colors !no-underline">
            <x-admin.ui.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            All
        </a>
        <a href="{{ route('reports.bookings.paid') }}" class="flex items-center gap-1.5 text-xs font-semibold text-palay-800 border border-palay-200 bg-white rounded-lg px-3 py-1.5 hover:bg-palay-50 transition-colors !no-underline">
            <x-admin.ui.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            Paid
        </a>
        <a href="{{ route('reports.bookings.completed') }}" class="flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors !no-underline">
            <x-admin.ui.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            Completed
        </a>
    </x-slot:actions>

    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
             class="flex items-center gap-2 text-sm font-medium text-clsu-800 bg-clsu-50 border border-clsu-200 rounded-xl px-4 py-2.5 mb-4" wire:ignore.self>
            <x-admin.ui.icon name="check-circle" class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
             class="flex items-center gap-2 text-sm font-medium text-ember-700 bg-ember-50 border border-ember-200 rounded-xl px-4 py-2.5 mb-4" wire:ignore.self>
            <x-admin.ui.icon name="block" class="w-4 h-4 shrink-0" />
            {{ session('error') }}
        </div>
    @endif

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
        <div class="filter-toolbar-spacer"></div>
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
        <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref code</th>
                        <th>Guest</th>
                        <th>Room(s)</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
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
                                    <span class="ref-cell-sub">Booked {{ $booking->created_at?->format('M d') ?? '—' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-name">
                                    <span class="avatar-initials">{{ $initials }}</span>
                                    <div class="cell-name-text">
                                        <p class="cell-name-primary guest-history-link cursor-pointer hover:text-clsu-700 hover:underline" data-booking-id="{{ $booking->id }}" title="{{ $booking->guest_name }} — view guest history">{{ $booking->guest_name }}</p>
                                        <p class="cell-name-secondary">#{{ $booking->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($rooms)
                                    <span class="cell-tag font-data">{{ $rooms }}</span>
                                @else
                                    <span class="text-faint">—</span>
                                @endif
                            </td>
                            <td class="font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                            <td class="font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                            <td><span class="status {{ $statusClass }}">{{ $statusText }}</span></td>
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
            {{ $bookings->links() }}
        </div>
    @endif

    @if($selectedBooking)
        <x-admin.ui.modal id="bookingDetailModal" icon="clipboard" :title="'Booking #' . $selectedBooking->id" max-width="lg" always-visible close-action="closeModal">
            @include('staff.partials.booking-detail-body', ['booking' => $selectedBooking])
        </x-admin.ui.modal>
    @endif
</x-admin.ui.section-card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // (.copy-ref clipboard handler lives globally in layouts/admin)
    $(document).on('click', '.password-verify', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('id');
        const action = $(this).data('action');

        Swal.fire({
            title: 'Enter your password',
            input: 'password',
            inputAttributes: {
                placeholder: 'Password',
                autocapitalize: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Verify',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                return $.ajax({
                    url: "{{ route('staff.bookings.verify-password') }}",
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        password: password
                    }
                }).then(response => {
                    if (!response.success) {
                        throw new Error(response.message);
                    }
                    return response.success;
                }).catch(err => {
                    Swal.showValidationMessage(err.responseJSON?.message || err.message);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (action === 'view') {
                    Livewire.dispatch('openBookingModal', { bookingId });
                } else if (action === 'cancel') {
                    Livewire.dispatch('cancelBookingConfirmed', { bookingId });
                }else if (action === 'checkout') {
                    Livewire.dispatch('checkoutBookingConfirmed', { bookingId });
                }
            }
        });
    });
});
</script>
@endpush
