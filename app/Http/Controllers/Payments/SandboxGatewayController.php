<?php

namespace App\Http\Controllers\Payments;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
//For mailing
use App\Mail\BookingPaidMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SandboxGatewayController extends Controller
{
    public function showPaymentPage(Payment $payment)
    {
        $this->authorizePayment($payment);

        return view('sandbox.gateway', compact('payment'));
    }

    public function processPayment(Request $request, Payment $payment)
    {
        $this->authorizePayment($payment);

        $validated = $request->validate([
            'payment_type' => ['nullable', Rule::in(['full', 'reservation_fee'])],
            'simulate'     => ['nullable', Rule::in(['success', 'fail'])],
        ]);

        sleep(1); // simulate short processing delay

        // Only a still-pending payment may be processed. This used to check
        // for 'success' alone, so a failed payment could be re-driven.
        if ($payment->status !== 'pending') {
            return back()->with('error', 'This payment is no longer pending or has already been processed.');
        }

        $booking = $payment->booking;
        if (!$booking || $booking->status !== 'pending_payment') {
            return back()->with('error', 'Invalid or expired booking.');
        }

        $paymentType = $validated['payment_type'] ?? null; // full | reservation_fee
        $simulate = $validated['simulate'] ?? null;

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

        // $status lands directly in a view name. The route constrains it too,
        // but never build a view path from unvalidated input on trust alone.
        abort_unless(in_array($status, ['success', 'failed'], true), 404);

        return view("sandbox.$status", compact('payment'));
    }

    public function webhook(Request $request, Payment $payment)
    {
        // No session here, so ownership cannot be checked — the signature is
        // the only thing standing between this endpoint and anyone who can
        // guess a payment id.
        if (! $this->webhookSignatureIsValid($request)) {
            Log::warning('[SANDBOX-WEBHOOK] Rejected: bad or missing signature.', [
                'payment_id' => $payment->id,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // Gateways retry. Applying this twice must be harmless.
        if ($payment->status === 'success' && $payment->webhook_verified) {
            return response()->json(['message' => 'Already processed', 'status' => 'success']);
        }

        Log::info("[SANDBOX-WEBHOOK] Payment {$payment->id} confirmed by webhook.");

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
        $this->authorizePayment($payment);

        return response()->json(['status' => $payment->status]);
    }

    /**
     * A payment belongs to whoever owns its booking. The booking is the
     * authority here — payments.user_id is denormalised and could drift.
     */
    private function authorizePayment(Payment $payment): void
    {
        $booking = $payment->booking;

        abort_if($booking === null, 404);
        abort_unless($booking->user_id === Auth::id(), 403);
    }

    /**
     * HMAC-SHA256 over the raw request body, compared in constant time.
     * This is the shape a real gateway callback takes, so swapping the
     * sandbox out later does not mean redesigning the trust boundary.
     */
    private function webhookSignatureIsValid(Request $request): bool
    {
        $secret = config('services.sandbox.webhook_secret');

        // Fail closed. An unset secret must never mean "accept anything".
        if (blank($secret)) {
            Log::error('[SANDBOX-WEBHOOK] SANDBOX_WEBHOOK_SECRET is not set; rejecting.');

            return false;
        }

        $provided = (string) $request->header('X-Sandbox-Signature');

        if ($provided === '') {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $request->getContent(), $secret),
            $provided
        );
    }
}
