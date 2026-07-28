<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * Authorization on the payment surface.
 *
 * The real gateway has not been chosen yet — everything under /sandbox is
 * placeholder scaffolding that will be replaced. So treat this file as the
 * **acceptance criteria for whichever provider is adopted**, not as a list of
 * bugs to fix in the sandbox code. Point these tests at the real integration
 * when it lands; they encode the properties any payment surface must have:
 *
 *   - starting a payment requires authentication
 *   - a guest may only pay for their own booking
 *   - nothing but the provider can mark a payment settled
 *   - the webhook is authenticated (HMAC signature over the raw body)
 *   - payment state is not readable anonymously
 *   - re-entering checkout reuses the outstanding payment
 *
 * Two of these already pass against the sandbox and should never regress; the
 * rest fail today, which is expected for scaffolding but must not ship. The
 * one thing worth doing *before* the gateway is picked is moving these routes
 * behind `auth`, since they currently sit outside every middleware group and
 * still mutate real booking rows on a live host.
 */
class PaymentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::room('101', 'double');
    }

    private function pendingBooking(): Booking
    {
        return Make::bookingHolding(['101'], 'pending_payment');
    }

    /**
     * DEFECT PROBE — GET /booking/{booking}/pay has no `auth` middleware.
     */
    public function test_an_anonymous_visitor_cannot_start_a_payment(): void
    {
        $booking = $this->pendingBooking();

        $this->get("/booking/{$booking->id}/pay")->assertRedirect(route('login'));

        $this->assertSame(0, Payment::count(), 'No payment record should be created for an anonymous request.');
    }

    /**
     * DEFECT PROBE — PaymentController::pay() never compares the booking's
     * owner to the authenticated user, so any signed-in guest can open a
     * payment against a stranger's booking by guessing an id.
     */
    public function test_a_guest_cannot_start_a_payment_for_someone_elses_booking(): void
    {
        $booking  = $this->pendingBooking();
        $intruder = Make::user();

        $this->actingAs($intruder)->get("/booking/{$booking->id}/pay")->assertForbidden();
    }

    /**
     * DEFECT PROBE — the most serious one.
     *
     * POST /sandbox/process/{payment} flips the payment to success and the
     * booking to `paid`. It checks only that the payment is not already
     * settled; it never checks who is asking. An anonymous request with a
     * guessed payment id marks a stay as paid for free.
     */
    public function test_an_anonymous_request_cannot_mark_a_payment_as_successful(): void
    {
        $booking = $this->pendingBooking();
        $payment = Make::payment($booking, 'pending');

        $response = $this->post("/sandbox/process/{$payment->id}", ['simulate' => 'success']);

        $this->assertNotSame(
            'success',
            $payment->fresh()->status,
            'An unauthenticated caller settled a payment.',
        );

        $this->assertNotSame(
            'paid',
            $booking->fresh()->status,
            'An unauthenticated caller marked a booking as paid.',
        );

        $this->assertTrue(
            $response->isRedirect(route('login')) || $response->isForbidden(),
            'The endpoint should reject an unauthenticated caller.',
        );
    }

    public function test_a_guest_cannot_settle_another_guests_payment(): void
    {
        $booking  = $this->pendingBooking();
        $payment  = Make::payment($booking, 'pending');
        $intruder = Make::user();

        $this->actingAs($intruder)->post("/sandbox/process/{$payment->id}", ['simulate' => 'success']);

        $this->assertNotSame('success', $payment->fresh()->status);
        $this->assertNotSame('paid', $booking->fresh()->status);
    }

    /**
     * DEFECT PROBE — the webhook strips CSRF (`withoutMiddleware`) and performs
     * no signature or shared-secret check, so anyone who can reach the host can
     * confirm any payment. A real gateway webhook must be authenticated by an
     * HMAC signature over the request body.
     */
    public function test_the_payment_webhook_rejects_an_unsigned_request(): void
    {
        $booking = $this->pendingBooking();
        $payment = Make::payment($booking, 'pending');

        $this->postJson("/sandbox/webhook/{$payment->id}")->assertUnauthorized();

        $this->assertNotSame(
            'success',
            $payment->fresh()->status,
            'An unsigned webhook call confirmed a payment.',
        );
    }

    /**
     * DEFECT PROBE — GET /sandbox/status/{payment} returns payment state to
     * anyone. Low severity next to the others, but it lets an attacker
     * enumerate valid payment ids before calling /process.
     */
    public function test_payment_status_is_not_readable_anonymously(): void
    {
        $payment = Make::payment($this->pendingBooking(), 'pending');

        $response = $this->getJson("/sandbox/status/{$payment->id}");

        $this->assertTrue(
            $response->isRedirect() || $response->isForbidden() || $response->status() === 401,
            'Payment status should not be readable without authentication.',
        );
    }

    // ------------------------------------------------- webhook authentication

    private function sign(array $payload, string $secret): array
    {
        return ['X-Signature' => hash_hmac('sha256', json_encode($payload), $secret)];
    }

    public function test_a_correctly_signed_webhook_confirms_the_payment(): void
    {
        config(['payments.webhook_secret' => 'test-secret']);

        $payment = Make::payment($this->pendingBooking(), 'pending');
        $payload = ['event' => 'payment.paid'];

        $this->postJson(
            "/sandbox/webhook/{$payment->id}",
            $payload,
            $this->sign($payload, 'test-secret'),
        )->assertOk();

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertTrue((bool) $payment->fresh()->webhook_verified);
    }

    public function test_a_webhook_signed_with_the_wrong_secret_is_rejected(): void
    {
        config(['payments.webhook_secret' => 'test-secret']);

        $payment = Make::payment($this->pendingBooking(), 'pending');
        $payload = ['event' => 'payment.paid'];

        $this->postJson(
            "/sandbox/webhook/{$payment->id}",
            $payload,
            $this->sign($payload, 'the-wrong-secret'),
        )->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /**
     * The signature covers the body, so a payload swapped in transit must not
     * validate against a signature minted for the original.
     */
    public function test_a_tampered_webhook_body_is_rejected(): void
    {
        config(['payments.webhook_secret' => 'test-secret']);

        $payment   = Make::payment($this->pendingBooking(), 'pending');
        $signature = $this->sign(['amount' => 1], 'test-secret');

        $this->postJson("/sandbox/webhook/{$payment->id}", ['amount' => 999999], $signature)
            ->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /**
     * A deployment that forgot to configure the secret must decline
     * confirmations, not accept unsigned ones.
     */
    public function test_the_webhook_fails_closed_when_no_secret_is_configured(): void
    {
        config(['payments.webhook_secret' => null]);

        $payment = Make::payment($this->pendingBooking(), 'pending');
        $payload = ['event' => 'payment.paid'];

        $this->postJson("/sandbox/webhook/{$payment->id}", $payload, $this->sign($payload, ''))
            ->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /**
     * Gateways retry deliveries. A replay must be a no-op, not a second
     * confirmation.
     */
    public function test_a_replayed_webhook_is_idempotent(): void
    {
        config(['payments.webhook_secret' => 'test-secret']);

        $payment = Make::payment($this->pendingBooking(), 'pending');
        $payload = ['event' => 'payment.paid'];
        $headers = $this->sign($payload, 'test-secret');

        $this->postJson("/sandbox/webhook/{$payment->id}", $payload, $headers)->assertOk();
        $this->postJson("/sandbox/webhook/{$payment->id}", $payload, $headers)->assertOk();

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame(1, Payment::where('booking_id', $payment->booking_id)->count());
    }

    // ----------------------------------------------------------- happy path

    /**
     * The owner should of course still be able to pay. This is the control
     * case — it must keep passing after the routes are locked down, otherwise
     * the fix has broken the happy path.
     */
    public function test_the_booking_owner_can_start_a_payment(): void
    {
        $user    = Make::user();
        $booking = Make::bookingHolding(['101'], 'pending_payment', attributes: ['user_id' => $user->id]);

        $this->actingAs($user)->get("/booking/{$booking->id}/pay")->assertRedirect();

        $this->assertSame(1, Payment::where('booking_id', $booking->id)->count());
    }

    /**
     * Re-entering the payment page must reuse the outstanding payment rather
     * than opening a second one, or the payment log double-counts revenue.
     */
    public function test_revisiting_the_payment_page_reuses_the_pending_payment(): void
    {
        $user    = Make::user();
        $booking = Make::bookingHolding(['101'], 'pending_payment', attributes: ['user_id' => $user->id]);

        $this->actingAs($user)->get("/booking/{$booking->id}/pay");
        $this->actingAs($user)->get("/booking/{$booking->id}/pay");

        $this->assertSame(
            1,
            Payment::where('booking_id', $booking->id)->count(),
            'A second pending payment was opened for the same booking.',
        );
    }
}
