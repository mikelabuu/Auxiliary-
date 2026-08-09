{{-- Says what happened and stops. No refund terms are stated anywhere in this
     system, so none are stated here — see App\Mail\BookingNoShowMail. --}}
@component('mail::message')
# We missed you

Dear {{ $booking->guest_name }},

Your check-in date for booking **#{{ $booking->id }}** has passed and you were not checked in, so the booking is now recorded as a no-show and the {{ \Illuminate\Support\Str::plural('room', max(1, $booking->reservations->count())) }} has been released.

**The booking**
- Check-in: {{ $booking->check_in->format('M d, Y') }} ({{ $checkinTime }})
- Check-out: {{ $booking->check_out->format('M d, Y') }} ({{ $checkoutTime }})
- Guests: {{ $booking->expected_guests }}
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif

@component('mail::panel')
**If you were delayed, still travelling, or believe you did check in, contact the front desk.** This is a paid booking, and what happens next is a decision a person makes, not this system — please talk to us rather than rebooking straight away.
@endcomponent

@component('mail::button', ['url' => $bookingUrl, 'color' => 'primary'])
View booking
@endcomponent

**– The {{ config('app.name') }} Team**
@endcomponent
