<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function pay(Booking $booking)
    {
        // Booking ids are sequential and exposed in the URL. Without this, any
        // signed-in guest could open a payment against a stranger's booking by
        // guessing an id — the route had no ownership check at all.
        abort_unless($booking->user_id === Auth::id(), 403);

        // Only a booking actually awaiting payment can be paid. Guards against
        // re-paying a settled stay or paying one that has expired or been
        // cancelled out from under the page.
        abort_unless($booking->status === 'pending_payment', 403);

        // 1. Check for an existing pending payment
        $payment = Payment::where('booking_id', $booking->id)
            ->where('status', 'pending')
            ->first();

        // 2. If none found, create a new one
        if (!$payment) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'amount' => $booking->payable_amount ?? $booking->total_price,
                'status' => 'pending',
                'payment_type' => 'online',
                'reference_no' => strtoupper(Str::random(10)),
                'gateway' => 'sandbox',
            ]);
        }

        // 3. Redirect user to sandbox gateway using that payment
        return redirect()->route('sandbox.pay', $payment->id);
    }
}
