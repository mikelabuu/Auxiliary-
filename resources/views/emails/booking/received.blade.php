@component('mail::message')
# Your rooms are on hold

Dear {{ $booking->guest_name }},

Thanks for booking with **{{ config('app.name') }}**. We have your reservation — it is not confirmed until payment is settled.

**Booking #{{ $booking->id }}**
- Check-in: {{ $booking->check_in->format('M d, Y') }} ({{ $checkinTime }})
- Check-out: {{ $booking->check_out->format('M d, Y') }} ({{ $checkoutTime }})
- Nights: {{ max(1, $booking->check_in->diffInDays($booking->check_out)) }}
- Guests: {{ $booking->expected_guests }}
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif
- Amount due: **₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}**

@if($holdEndsAt && $paysAtDesk)
@component('mail::panel')
**Settle at our front desk before {{ $holdEndsAt->timezone(config('hostel.timezone'))->format('g:i A, M d') }}.**
@if($endsAtArrival)
That is your check-in time — your stay starts before the usual {{ $holdLabel }} would be up, so this is the deadline that applies.
@else
That is {{ $holdLabel }} from when your discount was approved.
@endif
Bring the original Senior Citizen / PWD ID for every discounted guest — a discounted booking cannot be paid online, because the ID has to be seen in person. After that the rooms are released automatically and go back on sale.
@endcomponent

@component('mail::button', ['url' => $bookingUrl, 'color' => 'success'])
View booking
@endcomponent
@elseif($holdEndsAt)
@component('mail::panel')
**Settle before {{ $holdEndsAt->timezone(config('hostel.timezone'))->format('g:i A, M d') }}.**
@if($endsAtArrival)
That is your check-in time. Your stay starts before the usual {{ $holdLabel }} would be up, so this is the deadline that applies.
@else
That is {{ $holdLabel }} from when you booked.
@endif
After that the rooms are released automatically and go back on sale.
@endcomponent

@component('mail::button', ['url' => $payUrl, 'color' => 'success'])
Pay now
@endcomponent
@elseif($booking->wants_discount)
@component('mail::panel')
You asked for the Senior Citizen / PWD discount, so nothing is owed yet. Upload your IDs and our front desk will review them — we will email you the discounted amount once it is approved.

A discounted booking is settled at our front desk, not online: bring the original ID for every discounted guest when you come to pay.
@endcomponent

@component('mail::button', ['url' => $bookingUrl])
Upload IDs
@endcomponent
@endif

You can check the status of this booking at any time:

@component('mail::button', ['url' => $bookingUrl, 'color' => 'primary'])
View booking
@endcomponent

If you did not make this booking, reply to this email and we will cancel it.

**– The {{ config('app.name') }} Team**
@endcomponent
