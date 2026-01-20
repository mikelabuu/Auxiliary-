<div id="bookingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-3/5 p-8 relative border border-gray-200">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 border-b pb-3">
            <h2 class="text-2xl font-semibold text-gray-800">
                Booking #{{ $booking->id }}
            </h2>
            <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
                Completed
            </span>
        </div>

        <!-- Booking Info -->
        <div class="grid grid-cols-2 gap-4 mb-6 text-sm text-gray-700">
            <p><strong>Booked At:</strong> {{ $booking->updated_at->format('M d, Y g:i A') }}</p>
            <p><strong>Guest:</strong> {{ $booking->guest_name }}</p>
            <p><strong>Address:</strong> {{ $booking->guest_address }}</p>
            <p><strong>Phone:</strong> {{ $booking->guest_phone }}</p>
            <p><strong>Check-in:</strong> {{ $booking->check_in->format('M d, Y') }}</p>
            <p><strong>Check-out:</strong> {{ $booking->check_out->format('M d, Y') }}</p>
            <p><strong>Expected Guests:</strong> {{ $booking->expected_guests }}</p>
        </div>

        <!-- Pricing Section -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">💰 Pricing Summary</h3>
            <div class="grid grid-cols-2 gap-4 text-gray-700">
                <p><strong>Total Price:</strong> ₱{{ number_format($booking->total_price, 2) }}</p>
                <p><strong>Discount:</strong> ₱{{ number_format($booking->discount, 2) }}</p>
                <p><strong>Payable Amount:</strong>
                    ₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}
                </p>
            </div>
        </div>

        <!-- Rooms Section -->
        <div class="space-y-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800">🛏️ Room Details</h3>
            @foreach ($booking->reservations as $res)
                <div class="p-4 rounded-lg border border-gray-200 shadow-sm bg-white hover:bg-gray-50 transition">
                    <p class="font-semibold text-gray-800 mb-1">
                        Room {{ $res->room_number }} ({{ $res->room->room_type }})
                    </p>
                    <div class="text-sm text-gray-700 grid grid-cols-2 gap-2">
                        <p><strong>Guests:</strong> {{ $res->num_guests }}</p>
                        <p><strong>Seniors/PWD:</strong> {{ $res->num_seniors }}</p>
                        <p><strong>Meals:</strong> {{ collect($res->meal)->filter(fn($v) => $v == '1')->keys()->implode(', ') ?: 'None' }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Payment Section -->
        @if ($booking->payments)
            <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">💳 Payment Details</h3>
                <div class="grid grid-cols-2 gap-4 text-gray-700 text-sm">
                    <p><strong>Reference #:</strong> {{ $booking->payments->reference_no ?? 'N/A' }}</p>
                    <p><strong>Bank:</strong> Landbank</p>
                    <p><strong>Amount Paid:</strong> ₱{{ number_format($booking->payments->amount, 2) }}</p>
                    <p><strong>Status:</strong>
                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">
                            {{ ucfirst($booking->payments->status) }}
                        </span>
                    </p>
                </div>
            </div>
        @endif

        <!-- Close Button -->
        <div class="flex justify-end">
            <button id="closeBookingModal" class="px-5 py-2 bg-gray-700 text-white font-medium rounded-lg hover:bg-gray-800 transition">
                Close
            </button>
        </div>
    </div>
</div>
