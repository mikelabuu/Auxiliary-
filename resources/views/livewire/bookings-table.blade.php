<div>
    {{-- Search & Filters --}}
    <div class="flex justify-between items-center mb-4">
        @if (session()->has('success'))
            <div x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 3000)"
                class="bg-green-100 text-green-800 px-4 py-2 rounded mb-3"
                wire:ignore.self>
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 3000)"
                class="bg-red-100 text-red-800 px-4 py-2 rounded mb-3"
                wire:ignore.self>
                {{ session('error') }}
            </div>
        @endif
        <input type="text" wire:model.live="search" placeholder="Search bookings ID..." class="border rounded-lg px-3 py-2 w-1/3">
        <div class="flex items-center gap-3">
            <select wire:model.live="dateFilter" class="border rounded-lg px-3 py-2">
                <option value="">All Dates</option>
                <option value="today_checkin">Check-in Today</option>
                <option value="tomorrow_checkin">Check-in Tomorrow</option>
                <option value="today_checkout">Check-out Today</option>
                <option value="tomorrow_checkout">Check-out Tomorrow</option>
            </select>
            <select wire:model.live="statusFilter" class="border rounded-lg px-3 py-2">
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
    </div>

    {{-- Bookings Table --}}
    <table class="min-w-full bg-white border rounded-lg">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-3">ID</th>
                <th class="p-3">Guest Name</th>
                <th class="p-3">Check-in</th>
                <th class="p-3">Check-out</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr class="border-t">
                    <td class="p-3">{{ $booking->id }}</td>
                    <td class="p-3">{{ $booking->guest_name }}</td>
                    <td class="p-3">{{ $booking->check_in->format('M d, Y') }}</td>
                    <td class="p-3">{{ $booking->check_out->format('M d, Y') }}</td>
                    @php
                        $statusColors = [
                            'pending_payment' => 'bg-red-100 text-white-800',
                            'pending_discount' => 'bg-yellow-100 text-black-800',
                            'paid' => 'bg-blue-200 text-white-900',
                            'active' => 'bg-green-500 text-white',
                            'cancelled' => 'bg-gray-100 text-white-800',
                            'no_show' => 'bg-gray-200 text-white-900',
                        ];

                        $status = $booking->status;
                        $statusText = ucwords(str_replace('_', ' ', $status));
                    @endphp

                    <td class="p-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-200 text-gray-800' }}">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="p-3">
                        <button 
                            class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition password-verify" 
                            data-action="view" 
                            data-id="{{ $booking->id }}">
                            View
                        </button>

                        @if(in_array($booking->status, ['pending_payment']))
                            <button 
                                class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition password-verify" 
                                data-action="cancel" 
                                data-id="{{ $booking->id }}">
                                Cancel
                            </button>
                        @endif
                        @if(in_array($booking->status, ['active']))
                            <button 
                                class="px-3 py-1 text-sm font-medium text-black-600 rounded-md bg-yellow-500 transition password-verify" 
                                data-action="checkout" 
                                data-id="{{ $booking->id }}">
                                checkout
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center p-4 text-gray-500">No bookings found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $bookings->links() }}
    </div>

    {{-- Booking Details Modal --}}
    @if($selectedBooking)
        <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-2xl w-3/5 p-8 relative border border-gray-200">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6 border-b pb-3">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Booking #{{ $selectedBooking->id }}
                    </h2>
                    <span class="px-3 py-1 rounded-full text-sm font-medium 
                        @if($selectedBooking->status === 'active') bg-green-100 text-green-700 
                        @elseif($selectedBooking->status === 'paid') bg-blue-100 text-blue-700 
                        @elseif($selectedBooking->status === 'cancelled') bg-red-100 text-red-700 
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ ucfirst($selectedBooking->status) }}
                    </span>
                </div>

                <!-- Booking Info -->
                <div class="grid grid-cols-2 gap-4 mb-6 text-sm text-gray-700">
                    <p><strong>Booked At:</strong> {{ \Carbon\Carbon::parse($selectedBooking->updated_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</p>
                    <p><strong>Guest:</strong> {{ $selectedBooking->guest_name }}</p>
                    <p><strong>Address:</strong> {{ $selectedBooking->guest_address }}</p>
                    <p><strong>Phone:</strong> {{ $selectedBooking->guest_phone }}</p>
                    <p><strong>Check-in:</strong> {{ $selectedBooking->check_in->format('M d, Y') }}</p>
                    <p><strong>Check-out:</strong> {{ $selectedBooking->check_out->format('M d, Y') }}</p>
                    <p><strong>Expected Guests:</strong> {{ $selectedBooking->expected_guests }}</p>
                </div>

                <!-- Pricing Section -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">💰 Pricing Summary</h3>
                    <div class="grid grid-cols-2 gap-4 text-gray-700">
                        <p><strong>Total Price:</strong> ₱{{ number_format($selectedBooking->total_price, 2) }}</p>
                        <p><strong>Discount:</strong> ₱{{ number_format($selectedBooking->discount, 2) }}</p>
                        <p><strong>Payable Amount:</strong>
                            ₱{{ number_format($selectedBooking->payable_amount > 0 ? $selectedBooking->payable_amount : $selectedBooking->total_price, 2) }}
                        </p>
                    </div>
                </div>

                <!-- Rooms Section -->
                <div class="space-y-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">🛏️ Room Details</h3>
                    @foreach ($selectedBooking->reservations as $res)
                        <div class="p-4 rounded-lg border border-gray-200 shadow-sm bg-white hover:bg-gray-50 transition">
                            <p class="font-semibold text-gray-800 mb-1">Room {{ $res->room_number }} ({{ $res->room->room_type }})</p>
                            <div class="text-sm text-gray-700 grid grid-cols-2 gap-2">
                                <p><strong>Guests:</strong> {{ $res->num_guests }}</p>
                                <p><strong>Seniors/PWD:</strong> {{ $res->num_seniors }}</p>
                                <p><strong>Meals:</strong> {{ collect($res->meal)->filter(fn($v) => $v == '1')->keys()->implode(', ') ?: 'None' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Payment Section -->
                @if ($selectedBooking->payments)
                    <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200">
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">💳 Payment Details</h3>
                        <div class="grid grid-cols-2 gap-4 text-gray-700 text-sm">
                            <p><strong>Reference #:</strong> {{ $selectedBooking->payments->reference_no ?? 'N/A' }}</p>
                            <p><strong>Bank:</strong> Landbank</p>
                            <p><strong>Amount Paid:</strong> ₱{{ number_format($selectedBooking->payments->amount, 2) }}</p>
                            <p><strong>Status:</strong> 
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    @if($selectedBooking->payments->status === 'success') bg-green-100 text-green-700 
                                    @elseif($selectedBooking->payments->status === 'pending') bg-yellow-100 text-yellow-700 
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($selectedBooking->payments->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Close Button -->
                <div class="flex justify-end">
                    <button wire:click="closeModal"
                        class="px-5 py-2 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
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