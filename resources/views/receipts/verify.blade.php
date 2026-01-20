<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt Verification</title>
    <style>
        body { font-family: sans-serif; text-align:center; margin-top:50px; }
        .valid { color: green; font-size: 20px; font-weight: bold; }
        .invalid { color: red; font-size: 20px; font-weight: bold; }
        .details { margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
    @if($valid)
        <div class="valid">✅ Verified OK</div>
    @else
        <div class="invalid">❌ Invalid Receipt</div>
        <p>{{ $reason }}</p>
    @endif

    @if($receipt)
        <div class="details">
            <p><strong>Receipt #:</strong> {{ $receipt->receipt_number }}</p>
            <p><strong>Booking ID:</strong> #{{ $receipt->booking_id }}</p>
            <p><strong>Date:</strong> {{ $receipt->created_at->format('Y-m-d H:i') }}</p>
        </div>
    @endif
</body>
</html>
