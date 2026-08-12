@component('mail::message')
# {{ $approved ? 'Your stay has been moved' : 'We could not move your stay' }}

Dear {{ $booking->guest_name }},

@if ($approved)
Our front desk has approved your request to move booking **#{{ $booking->id }}**. Your rooms are the same — only the dates have changed.
@else
Our front desk was not able to move booking **#{{ $booking->id }}** to the dates you asked for. The booking still stands exactly as it was.
@endif

@if ($approved)
**Your stay now**
- Check-in: {{ $booking->check_in->format('M d, Y') }} ({{ $checkinTime }})
- Check-out: {{ $booking->check_out->format('M d, Y') }} ({{ $checkoutTime }})
- Nights: {{ max(1, $booking->check_in->diffInDays($booking->check_out)) }}
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif
- Total for the stay: **₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}**

Previously {{ $reschedule->original_check_in->format('M d, Y') }} – {{ $reschedule->original_check_out->format('M d, Y') }} ({{ $reschedule->original_nights }} {{ \Illuminate\Support\Str::plural('night', $reschedule->original_nights) }}).
@else
**The booking as it stands**
- Check-in: {{ $booking->check_in->format('M d, Y') }} ({{ $checkinTime }})
- Check-out: {{ $booking->check_out->format('M d, Y') }} ({{ $checkoutTime }})
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif

You asked to move it to {{ $reschedule->requested_check_in->format('M d, Y') }} – {{ $reschedule->requested_check_out->format('M d, Y') }}.
@endif

@if ($reschedule->decision_note)
@component('mail::panel')
**From our front desk:** {{ $reschedule->decision_note }}
@endcomponent
@endif

@if ($approved)
{{-- Said plainly because the amount can genuinely have moved: a stay is
     billed by the night, so more nights is a bigger total. Fewer nights is
     not a smaller one — there is no refund policy, and pretending otherwise
     here would have guests arriving expecting money back. --}}
If your new stay is longer, the difference is settled at our front desk when you arrive. If it is shorter, the amount you have already paid stands — we do not issue refunds.

The same rule applies to your new dates: if you cannot make them either, tell us by {{ $deadline->format('g:i A, M d') }} — a full 24 hours before your {{ $booking->check_in->format('M d') }} check-in. After that the booking is forfeited and there is no refund.
@else
@component('mail::panel')
**Please call us.** Your booking is still for {{ $booking->check_in->format('M d') }}, and if nobody checks in that day it is forfeited with no refund. We would much rather find you dates that work — and we need 24 hours' notice, so talk to our front desk by {{ $deadline->format('g:i A, M d') }}.
@endcomponent
@endif

@component('mail::button', ['url' => $bookingUrl, 'color' => $approved ? 'success' : 'primary'])
View booking
@endcomponent

**– The {{ config('app.name') }} Team**
@endcomponent
