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
    /**
     * A guest may only ever act on a payment attached to their own booking.
     * Payment ids are sequential and were previously unguarded, so a guessed id
     * was enough to drive someone else's checkout.
     */
    private function authorizePayment(Payment $payment): void
    {
        abort_unless($payment->booking?->user_id === Auth::id(), 403);
    }

    public function showPaymentPage(Payment $payment)
    {
        $this->authorizePayment($payment);

        return view('sandbox.gateway', compact('payment'));
    }

    public function processPayment(Request $request, Payment $payment)
    {
        $this->authorizePayment($payment);

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
        $this->authorizePayment($payment);

        return view("sandbox.$status", compact('payment'));
    }

    /**
     * Server-to-server confirmation.
     *
     * This cannot sit behind session auth — the caller is a gateway, not a
     * browser — so it authenticates with an HMAC signature over the raw request
     * body, compared in constant time. Previously it had CSRF stripped and no
     * verification of any kind, which meant anyone who could reach the host
     * could confirm any payment by id.
     *
     * Fails closed: with no secret configured, nothing is accepted. Whichever
     * provider is eventually chosen, the shape of this check stays the same —
     * only the header name and digest algorithm change.
     */
    public function webhook(Request $request, Payment $payment)
    {
        $secret = config('payments.webhook_secret');

        if (blank($secret) || ! $this->signatureIsValid($request, $secret)) {
            Log::warning("Rejected unsigned webhook for payment {$payment->id}.", [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // Providers retry, so a replayed delivery must be a no-op rather than a
        // second confirmation.
        if ($payment->webhook_verified) {
            return response()->json(['message' => 'Already processed', 'status' => $payment->status]);
        }

        Log::info("Payment {$payment->id} confirmed by webhook.");

        $payment->update([
            'status' => 'success',
            'webhook_verified' => true,
        ]);

        // The booking status is untouched here, but the payment panel staff
        // read in the booking dossier is not — nudge the consoles to re-query.
        Realtime::emit(new BookingChanged());

        return response()->json(['message' => 'Webhook processed', 'status' => 'success']);
    }

    private function signatureIsValid(Request $request, string $secret): bool
    {
        $provided = (string) $request->header(config('payments.signature_header'));

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        // hash_equals, not ===, so a wrong signature cannot be recovered a byte
        // at a time by timing the response.
        return hash_equals($expected, $provided);
    }

    public function status(Payment $payment)
    {
        $this->authorizePayment($payment);

        return response()->json(['status' => $payment->status]);
    }
}
