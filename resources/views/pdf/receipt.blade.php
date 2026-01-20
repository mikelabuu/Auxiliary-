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

    <p><strong>Booking ID:</strong> #{{ $booking->id }}</p>
    <p><strong>Guest:</strong> {{ $booking->guest_name }}</p>
    <p><strong>Check-in:</strong> {{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}</p>
    <p><strong>Check-out:</strong> {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</p>

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
                <td>₱{{ number_format((float)$booking->total_price, 2) }}</td>
                <td>₱{{ number_format((float)$booking->discount, 2) }}</td>
                <td>
                    @if($booking->payable_amount > 0)
                        ₱{{ number_format((float)$booking->payable_amount, 2) }}
                    @else
                        ₱{{ number_format((float)$booking->total_price, 2) }}
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @php
        $payment = $booking->payments->first();
    @endphp

    @if ($payment)
        <p style="margin-top:15px;"><strong>Transaction ID:</strong> {{ (string)$payment->landbank_transaction_id }}</p>
        <p><strong>Date:</strong> {{ $payment->updated_at->format('M d, Y h:i A') }}</p>
    @endif

    <div style="text-align:center; margin-top:20px;">
        @if(isset($qrBase64))
            <img src="data:image/png;base64,{{ trim($qrBase64) }}" width="120" alt="QR Code">
        @endif
    </div>

    <div class="footer">
        <hr style="margin-top:15px;">
        <p>This receipt was generated electronically by the Farmers Hostel Auxiliary System.</p>
        <p>Verify authenticity by letting a staff scan the QR code above for your check-in.</p>
    </div>
</body>
</html>
