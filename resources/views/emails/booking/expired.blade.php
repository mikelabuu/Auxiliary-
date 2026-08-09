@component('mail::message')
# Your booking has been released

Dear {{ $booking->guest_name }},

Booking **#{{ $booking->id }}** was held for you pending payment. That window has now closed, so the {{ \Illuminate\Support\Str::plural('room', max(1, $booking->reservations->count())) }} went back on sale and the booking is marked expired.

**What was booked**
- Check-in: {{ $booking->check_in->format('M d, Y') }} ({{ $checkinTime }})
- Check-out: {{ $booking->check_out->format('M d, Y') }} ({{ $checkoutTime }})
- Guests: {{ $booking->expected_guests }}
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif

You have not been charged for this booking.

@component('mail::panel')
Still want these dates? They may well be free again — an expired hold is released immediately, so the same rooms are often the first thing available.
@endcomponent

@component('mail::button', ['url' => $rebookUrl, 'color' => 'success'])
Check availability
@endcomponent

If you did pay and think this is a mistake, reply to this email with your proof of payment and the front desk will sort it out.

**– The {{ config('app.name') }} Team**
@endcomponent
