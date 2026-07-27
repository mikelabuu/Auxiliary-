<?php

namespace App\Http\Controllers\Payments;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Realtime;
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

            // A guest paying is the one booking change staff never trigger
            // themselves, so without this every staff console sat on its poll
            // interval before the booking left "pending payment".
            Realtime::emit(new BookingChanged());
            if (BookingStatusChanged::shouldEmitFor($booking)) {
                Realtime::emit(BookingStatusChanged::for($booking->refresh()));
            }

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

        // The booking status is untouched here, but the payment panel staff
        // read in the booking dossier is not — nudge the consoles to re-query.
        Realtime::emit(new BookingChanged());

        return response()->json(['message' => 'Webhook processed', 'status' => 'success']);
    }

    public function status(Payment $payment)
    {
        return response()->json(['status' => $payment->status]);
    }
}
