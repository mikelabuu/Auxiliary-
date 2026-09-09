<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Official Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .header p { margin: 2px 0 0 0; font-size: 14px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px; }
        .footer { text-align: center; font-size: 11px; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p>Official Receipt</p>
    </div>

    <p style="font-size:15px;"><strong>Receipt No.: {{ $receipt_number }}</strong></p>
    <p><strong>Booking ID:</strong> #{{ $booking->id }}</p>
    <p><strong>Guest:</strong> {{ $booking->guest_name }}</p>
    <p><strong>Check-in:</strong> {{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}</p>
    <p><strong>Check-out:</strong> {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</p>

    <p><strong>Payment method:</strong> {{ $payment->proof_method_label }}</p>
    @if($payment->proof_reference)
        <p><strong>Payment reference:</strong> {{ $payment->proof_reference }}</p>
    @endif
    <p><strong>Payment confirmed:</strong> {{ ($payment->verified_at ?? $payment->paid_at ?? $payment->created_at)?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>Room Number(s)</th>
                <th>Total Price</th>
                <th>Discount</th>
                <th>Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @foreach($booking->room_numbers as $room)
                        {{ trim($room) }}@if(!$loop->last), @endif
                    @endforeach
                </td>
                <td>₱{{ number_format((float)$booking->total_price, 2) }}@if($booking->extra_mattress)<br><small>Includes one extra mattress: ₱{{ number_format((float)$booking->extra_mattress_amount, 2) }} per stay</small>@endif</td>
                <td>₱{{ number_format((float)$booking->discount, 2) }}</td>
                <td>
                    ₱{{ number_format((float) $payment->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <hr style="margin-top:15px;">
        <p>This receipt was generated electronically by the Farmers Hostel Auxiliary System.</p>
        <p>Keep this receipt and present it at check-in. Quote the receipt number for payment inquiries.</p>
    </div>
</body>
</html>
