@component('mail::message')
# Your booking is cancelled

Dear {{ $booking->guest_name }},

This confirms that booking **#{{ $booking->id }}** was cancelled at your request on {{ now()->timezone(config('hostel.timezone'))->format('M d, Y \a\t g:i A') }}. Keep this email as your record of it.

**What was cancelled**
- Check-in: {{ $booking->check_in->format('M d, Y') }} ({{ $checkinTime }})
- Check-out: {{ $booking->check_out->format('M d, Y') }} ({{ $checkoutTime }})
- Guests: {{ $booking->expected_guests }}
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif
@if($reason)
- Reason given: {{ $reason }}
@endif

The booking was still awaiting payment, so nothing was charged.

@component('mail::button', ['url' => $rebookUrl, 'color' => 'success'])
Book another stay
@endcomponent

If you did not cancel this booking, reply to this email straight away.

**– The {{ config('app.name') }} Team**
@endcomponent
