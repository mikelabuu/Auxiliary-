{{-- States the forfeiture, because the guest agreed to it at checkout and it is
     the outcome this mail is reporting — see App\Mail\BookingNoShowMail. Then
     it hands the guest to a person, because the policy is the default and not
     the last word on their circumstances. --}}
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
**A booking nobody checks in to is forfeited, and there is no refund.** That is the arrangement you agreed to when you booked: a paid stay cannot be cancelled, only moved — and moving it had to be asked for by {{ $deadline->format('g:i A, M d') }}, a full 24 hours before your check-in.
@endcomponent

**If you were delayed, still travelling, or believe you did check in, contact the front desk.** Whether anything can be done here is a decision a person makes, not this system — please talk to us rather than rebooking straight away.

@component('mail::button', ['url' => $bookingUrl, 'color' => 'primary'])
View booking
@endcomponent

**– The {{ config('app.name') }} Team**
@endcomponent
