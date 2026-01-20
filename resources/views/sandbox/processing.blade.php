<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Processing Payment</title>
    <link rel="stylesheet" href="{{ asset('css/payment.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="payment-page">
    <div class="payment-container">
        <div class="payment-card">
            <h1>Processing Payment...</h1>
            <p>Please wait while we confirm your transaction with the bank.</p>

            <div class="spinner"></div>
            <p class="status-text">This might take a few seconds.</p>
        </div>
    </div>

    <script>
        // poll every 3 seconds to check payment status
        const interval = setInterval(async () => {
            try {
                const response = await fetch("{{ route('sandbox.status', $payment->id) }}");
                const data = await response.json();

                if (data.status === 'success') {
                    clearInterval(interval);
                    window.location.href = "{{ route('sandbox.result', ['status' => 'success', 'payment' => $payment->id]) }}";
                } else if (data.status === 'failed') {
                    clearInterval(interval);
                    window.location.href = "{{ route('sandbox.result', ['status' => 'failed', 'payment' => $payment->id]) }}";
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }, 3000);
    </script>
</body>
</html>
