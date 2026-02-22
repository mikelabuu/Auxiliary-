<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
//For mailing
use App\Mail\BookingPaidMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SandboxGatewayController extends Controller
{
    public function showPaymentPage(Payment $payment)
    {
        return view('sandbox.gateway', compact('payment'));
    }

    public function processPayment(Request $request, Payment $payment)
    {
        sleep(1); // simulate short processing delay

        if ($payment->status == 'success') {
            return back()->with('error', 'This payment is no longer pending or has already been processed.');
        }

        $booking = $payment->booking;
        if (!$booking || $booking->status !== 'pending_payment') {
            return back()->with('error', 'Invalid or expired booking.');
        }

        $paymentType = $request->input('payment_type'); // full | reservation_fee
        $simulate = $request->input('simulate');
        
        $status = $simulate === 'fail' ? 'failed' : 'success';

        $payment->update([
            'status' => $status,
            'landbank_transaction_id' => 'SBX-' . strtoupper(uniqid()),
        ]);

        if ($status === 'success') {

            $booking->update([
                'status' => 'paid',
                'payment_mode' => 'card',
            ]);

            try {
                Mail::to($booking->user->email)
                    ->send(new BookingPaidMail($booking, $payment));
            } catch (\Exception $e) {
                \Log::error("Failed to send booking confirmation email: " . $e->getMessage());
            }
        }

        return redirect()->route('sandbox.result', [
            'status' => $status,
            'payment' => $payment->id,
        ]);
    }

    public function result($status, Payment $payment)
    {
        return view("sandbox.$status", compact('payment'));
    }

    public function webhook(Payment $payment)
    {   
        Log::info(" [FAKE-WEBHOOK] Payment {$payment->id} confirmed by webhook.");

        // simulate background confirmation from bank
        $payment->update([
            'status' => 'success',
            'webhook_verified' => true,
        ]);

        return response()->json(['message' => 'Webhook processed', 'status' => 'success']);
    }

    public function status(Payment $payment)
    {
        return response()->json(['status' => $payment->status]);
    }
}
