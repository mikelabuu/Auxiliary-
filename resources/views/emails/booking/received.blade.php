@component('mail::message')
# Your rooms are on hold

Dear {{ $booking->guest_name }},

Thanks for booking with **{{ config('app.name') }}**. We have your reservation — it is not confirmed until payment is settled.

**Booking #{{ $booking->id }}**
- Check-in: {{ $booking->check_in->format('M d, Y') }} (2:00 PM)
- Check-out: {{ $booking->check_out->format('M d, Y') }} (12:00 NN)
- Nights: {{ max(1, $booking->check_in->diffInDays($booking->check_out)) }}
- Guests: {{ $booking->expected_guests }}
@if($booking->reservations->isNotEmpty())
- {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}: {{ $booking->reservations->pluck('room_number')->implode(', ') }}
@endif
- Amount due: **₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}**

@if($holdEndsAt)
@component('mail::panel')
**Settle before {{ $holdEndsAt->timezone(config('hostel.timezone'))->format('g:i A, M d') }}.**
That is {{ (int) config('bookings.expiry_minutes') }} minutes from when you booked. After that the rooms are released automatically and go back on sale.
@endcomponent

@component('mail::button', ['url' => $payUrl, 'color' => 'success'])
Pay now
@endcomponent
@elseif($booking->wants_discount)
@component('mail::panel')
You asked for the Senior Citizen / PWD discount, so nothing is owed yet. Upload your IDs and our front desk will review them — we will email you the discounted amount once it is approved.
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
