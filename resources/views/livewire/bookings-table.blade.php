<x-admin.section-card wire:poll.15s icon="clipboard" title="All Bookings" subtitle="Every booking that hasn't been checked out yet.">
    <x-slot:actions>
        <a href="{{ route('reports.bookings.full') }}" class="flex items-center gap-1.5 text-xs font-semibold text-clsu-700 border border-clsu-200 bg-white rounded-lg px-3 py-1.5 hover:bg-clsu-50 transition-colors !no-underline">
            <x-admin.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            All
        </a>
        <a href="{{ route('reports.bookings.paid') }}" class="flex items-center gap-1.5 text-xs font-semibold text-palay-800 border border-palay-200 bg-white rounded-lg px-3 py-1.5 hover:bg-palay-50 transition-colors !no-underline">
            <x-admin.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            Paid
        </a>
        <a href="{{ route('reports.bookings.completed') }}" class="flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors !no-underline">
            <x-admin.icon name="download" class="w-3.5 h-3.5" stroke-width="2" />
            Completed
        </a>
    </x-slot:actions>

    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
             class="flex items-center gap-2 text-sm font-medium text-clsu-800 bg-clsu-50 border border-clsu-200 rounded-xl px-4 py-2.5 mb-4" wire:ignore.self>
            <x-admin.icon name="check-circle" class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
             class="flex items-center gap-2 text-sm font-medium text-ember-700 bg-ember-50 border border-ember-200 rounded-xl px-4 py-2.5 mb-4" wire:ignore.self>
            <x-admin.icon name="block" class="w-4 h-4 shrink-0" />
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1 max-w-xs">
            <x-admin.icon name="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" stroke-width="2" />
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search guest, ID, or room…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-full pl-10 pr-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
        </div>
        <select wire:model.live="dateFilter" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
            <option value="">All Dates</option>
            <option value="today_checkin">Check-in Today</option>
            <option value="tomorrow_checkin">Check-in Tomorrow</option>
            <option value="today_checkout">Check-out Today</option>
            <option value="tomorrow_checkout">Check-out Tomorrow</option>
        </select>
        <select wire:model.live="statusFilter" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-palay-300 focus:border-palay-300 cursor-pointer transition-colors">
            <option value="">All Status</option>
            <option value="pending_payment">Pending Payment</option>
            <option value="pending_discount">Pending Discount</option>
            <option value="paid">Paid</option>
            <option value="active">Active</option>
            <option value="cancelled">Cancelled</option>
            <option value="expired">Expired</option>
            <option value="no_show">No Show</option>
        </select>
    </div>

    @if($bookings->isEmpty())
        <x-admin.empty-state icon="clipboard" title="No bookings found." />
    @else
        <div class="-mx-6 -mb-6 border-t border-stone-100 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50/70 border-b border-stone-100">
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">ID</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Guest Name</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-in</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-out</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Status</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusColorMap = [
                            'paid' => 'clsu', 'active' => 'clsu',
                            'pending_payment' => 'palay', 'pending_discount' => 'palay',
                            'cancelled' => 'ember', 'expired' => 'ember', 'no_show' => 'ember',
                        ];
                        $badgeClassMap = [
                            'clsu'  => 'bg-clsu-50 text-clsu-700 border-clsu-200',
                            'palay' => 'bg-palay-100 text-palay-800 border-palay-200',
                            'ember' => 'bg-ember-50 text-ember-700 border-ember-200',
                        ];
                    @endphp
                    @foreach($bookings as $booking)
                        @php
                            $color = $statusColorMap[$booking->status] ?? 'stone';
                            $badgeClass = $badgeClassMap[$color] ?? 'bg-stone-100 text-stone-600 border-stone-200';
                            $statusText = ucwords(str_replace('_', ' ', $booking->status));
                        @endphp
                        <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                            <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $booking->id }}</td>
                            <td class="px-6 py-3 text-stone-800 font-medium">{{ $booking->guest_name }}</td>
                            <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">{{ $statusText }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" class="password-verify flex items-center gap-1.5 text-xs font-semibold text-clsu-700 border border-clsu-200 bg-white rounded-lg px-2.5 py-1.5 hover:bg-clsu-50 transition-colors cursor-pointer" data-action="view" data-id="{{ $booking->id }}">
                                        <x-admin.icon name="eye" class="w-3.5 h-3.5" />
                                        View
                                    </button>
                                    @if($booking->status === 'pending_payment')
                                        <button type="button" class="password-verify flex items-center gap-1.5 text-xs font-semibold text-ember-700 border border-ember-200 bg-white rounded-lg px-2.5 py-1.5 hover:bg-ember-50 transition-colors cursor-pointer" data-action="cancel" data-id="{{ $booking->id }}">
                                            <x-admin.icon name="x" class="w-3.5 h-3.5" stroke-width="2" />
                                            Cancel
                                        </button>
                                    @endif
                                    @if($booking->status === 'active')
                                        <button type="button" class="password-verify flex items-center gap-1.5 text-xs font-semibold text-palay-800 border border-palay-200 bg-white rounded-lg px-2.5 py-1.5 hover:bg-palay-50 transition-colors cursor-pointer" data-action="checkout" data-id="{{ $booking->id }}">
                                            <x-admin.icon name="log-out" class="w-3.5 h-3.5" />
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
        <x-admin.modal id="bookingDetailModal" icon="clipboard" :title="'Booking #' . $selectedBooking->id" max-width="lg" always-visible close-action="closeModal">
            @include('staff.partials.booking-detail-body', ['booking' => $selectedBooking])
        </x-admin.modal>
    @endif
</x-admin.section-card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
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
