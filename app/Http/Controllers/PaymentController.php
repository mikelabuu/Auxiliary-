<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Balance;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function pay(Booking $booking)
    {
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
                'payment_type' => null,
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