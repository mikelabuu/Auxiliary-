<div class="mt-6 space-y-4">
    <h2 class="text-lg font-semibold">Active Bookings</h2>

    @if($activeBookings->isEmpty())
        <p class="text-gray-500">No active bookings at the moment.</p>
    @else
        <table class="min-w-full bg-white border rounded-lg">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th>Booking ID</th>
                    <th>Guest Name</th>
                    <th>Check-in Date</th>
                    <th>Check-out Date</th>
                    <th>Room</th>
                    <th>Room Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeBookings as $booking)
                    @foreach($booking->reservations as $res)
                    <tr class="border-b">
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->guest_name }}</td>
                        <td>{{ $booking->check_in->format('M d, Y') }}</td>
                        <td>{{ $booking->check_out->format('M d, Y') }}</td>
                        <td>{{ $res->room_number }}</td>
                        <td>{{ $res->room->room_type }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif
</div>
