<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regression cover for the payment route lockdown.
 *
 * The whole payment flow previously carried no middleware: an anonymous
 * visitor could read any payment and drive it to completion, and any signed-in
 * guest could do the same to a stranger's booking. The gateway itself is a
 * simulation, but the auth and ownership boundary tested here is what the real
 * gateway will sit behind.
 */
class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // processPayment() mails a confirmation on success.
        Mail::fake();
    }

    private function user(string $email): User
    {
        $user = User::create([
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'password' => Hash::make('password-12345'),
        ]);

        $user->email_verified_at = now(); // not fillable
        $user->save();

        return $user;
    }

    private function booking(User $user, string $status = 'pending_payment'): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'guest_name' => 'Test Guest',
            'guest_address' => 'Somewhere',
            'guest_phone' => '09000000000',
            'check_in' => now()->addDays(5),
            'check_out' => now()->addDays(6),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 1500,
            'payable_amount' => 1500,
            'status' => $status,
        ]);
    }

    private function payment(Booking $booking, string $status = 'pending'): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->payable_amount,
            'status' => $status,
            'payment_type' => 'online',
            'reference_no' => 'TESTREF123',
            'gateway' => 'sandbox',
        ]);
    }

    // ---------------------------------------------------------------
    // Anonymous access
    // ---------------------------------------------------------------

    public function test_anonymous_visitors_cannot_reach_any_payment_route(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);
        $payment = $this->payment($booking);

        $this->get("/booking/{$booking->id}/pay")->assertRedirect('/login');
        $this->get("/sandbox/pay/{$payment->id}")->assertRedirect('/login');
        $this->get("/sandbox/status/{$payment->id}")->assertRedirect('/login');
        $this->get("/sandbox/result/success/{$payment->id}")->assertRedirect('/login');
        $this->post("/sandbox/process/{$payment->id}")->assertRedirect('/login');
    }

    public function test_anonymous_visitor_cannot_mark_a_booking_paid(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);
        $payment = $this->payment($booking);

        $this->post("/sandbox/process/{$payment->id}", ['simulate' => 'success']);

        $this->assertSame('pending_payment', $booking->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Cross-user access (IDOR)
    // ---------------------------------------------------------------

    public function test_another_user_cannot_touch_someone_elses_payment(): void
    {
        $owner = $this->user('owner@example.test');
        $intruder = $this->user('intruder@example.test');
        $booking = $this->booking($owner);
        $payment = $this->payment($booking);

        $this->actingAs($intruder)
            ->get("/booking/{$booking->id}/pay")->assertForbidden();

        $this->actingAs($intruder)
            ->get("/sandbox/pay/{$payment->id}")->assertForbidden();

        $this->actingAs($intruder)
            ->get("/sandbox/status/{$payment->id}")->assertForbidden();

        $this->actingAs($intruder)
            ->post("/sandbox/process/{$payment->id}", ['simulate' => 'success'])
            ->assertForbidden();

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Owner happy path
    // ---------------------------------------------------------------

    public function test_owner_can_complete_a_payment(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $this->actingAs($owner)
            ->get("/booking/{$booking->id}/pay")
            ->assertRedirectContains('/sandbox/pay/');

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();

        $this->actingAs($owner)
            ->post("/sandbox/process/{$payment->id}", ['simulate' => 'success'])
            ->assertRedirectContains("/sandbox/result/success/{$payment->id}");

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame('success', $payment->fresh()->status);
    }

    public function test_payment_cannot_be_started_for_a_booking_not_awaiting_payment(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner, 'paid');

        $this->actingAs($owner)
            ->get("/booking/{$booking->id}/pay")
            ->assertRedirect(route('booking.show', $booking->id));

        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
    }

    public function test_an_already_processed_payment_cannot_be_re_driven(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);
        $payment = $this->payment($booking, 'failed');

        $this->actingAs($owner)
            ->post("/sandbox/process/{$payment->id}", ['simulate' => 'success']);

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    // ---------------------------------------------------------------
    // View-name injection
    // ---------------------------------------------------------------

    public function test_result_status_cannot_address_an_arbitrary_view(): void
    {
        $owner = $this->user('owner@example.test');
        $payment = $this->payment($this->booking($owner));

        foreach (['gateway', 'layouts.app', 'welcome'] as $status) {
            $this->actingAs($owner)
                ->get("/sandbox/result/{$status}/{$payment->id}")
                ->assertNotFound();
        }
    }

    // ---------------------------------------------------------------
    // Webhook signature
    // ---------------------------------------------------------------

    public function test_webhook_rejects_missing_and_wrong_signatures(): void
    {
        config(['services.sandbox.webhook_secret' => 'test-secret']);

        $payment = $this->payment($this->booking($this->user('owner@example.test')));
        $body = ['event' => 'payment.success'];

        $this->postJson("/sandbox/webhook/{$payment->id}", $body)
            ->assertStatus(401);

        $this->postJson("/sandbox/webhook/{$payment->id}", $body, [
            'X-Sandbox-Signature' => 'deadbeef',
        ])->assertStatus(401);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_webhook_accepts_a_correct_signature(): void
    {
        config(['services.sandbox.webhook_secret' => 'test-secret']);

        $payment = $this->payment($this->booking($this->user('owner@example.test')));
        $body = json_encode(['event' => 'payment.success']);

        $this->call(
            'POST',
            "/sandbox/webhook/{$payment->id}",
            [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SANDBOX_SIGNATURE' => hash_hmac('sha256', $body, 'test-secret'),
            ],
            $body
        )->assertOk();

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->webhook_verified);
    }

    public function test_webhook_fails_closed_when_no_secret_is_configured(): void
    {
        // An unset secret must never degrade into "accept anything".
        config(['services.sandbox.webhook_secret' => null]);

        $payment = $this->payment($this->booking($this->user('owner@example.test')));

        $this->postJson("/sandbox/webhook/{$payment->id}", ['event' => 'x'])
            ->assertStatus(401);

        $this->assertSame('pending', $payment->fresh()->status);
    }
}
