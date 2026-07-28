<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Balance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function pay(Booking $booking)
    {
        // Without this, any signed-in guest could open a payment against
        // someone else's booking just by changing the id in the URL.
        abort_unless($booking->user_id === Auth::id(), 403);

        // Only a booking that is actually awaiting payment may start one.
        // Previously this would happily mint a fresh pending payment for an
        // already-paid or cancelled booking.
        if ($booking->status !== 'pending_payment') {
            return redirect()->route('booking.show', $booking->id)
                ->with('error', 'This booking is not awaiting payment.');
        }

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

            // $balance = Balance::create([
            //     'booking_id' => $booking->id,
            //     'user_id' => $booking->user_id,
            //     'total_amount' => $booking->payable_amount ?? $booking->total_price,
            //     'paid_amount' => 0,
            //     'remaining_balance' => $booking->payable_amount ?? $booking->total_price,
            //     'status' => 'unpaid',
            // ]);

        }

        // 3. Redirect user to sandbox gateway using that payment
        return redirect()->route('sandbox.pay', $payment->id);
    }
}