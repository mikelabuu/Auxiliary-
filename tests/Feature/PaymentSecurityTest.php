<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression cover for the payment route lockdown.
 *
 * The whole payment flow previously carried no middleware: an anonymous
 * visitor could read any payment and drive it to completion, and any signed-in
 * guest could do the same to a stranger's booking.
 *
 * There is now exactly one way to settle a booking — send the money over GCash
 * or a bank transfer and upload the receipt, which a staff member verifies by
 * hand. The simulated card gateway that used to sit beside it (`/sandbox/*`,
 * its HMAC webhook, and the payment-method choice page) has been removed, and
 * with it the tests that covered it. Nothing in that gateway ever moved real
 * funds; it marked a booking paid on a button press with no human in the loop.
 *
 * What must remain true, and is asserted below: only the owner of a booking
 * can see or act on its payment, a claim is never self-confirming, and one
 * booking cannot stack two pending claims.
 *
 * Fixtures are PNG, not JPEG, and must stay that way: UploadedFile::fake()
 * ->image() derives its encoder from the extension, and the GD build in this
 * environment has imagepng() but not imagejpeg(). With .jpg fixtures every
 * test in this file threw "imagejpeg function is not defined" before reaching
 * a single assertion — so the payment authorisation boundary was completely
 * unguarded while the suite looked merely "red". Nothing here is
 * JPEG-specific; the upload rule accepts jpeg, png and jpg alike.
 */
class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Staff verification mails an official receipt; nothing here should
        // reach a real mailer.
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
            'payment_type' => 'manual',
            'reference_no' => 'TESTREF123',
            'gateway' => 'gcash',
        ]);
    }

    // ---------------------------------------------------------------
    // Anonymous access
    // ---------------------------------------------------------------

    public function test_anonymous_visitors_cannot_reach_any_payment_route(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $this->get("/booking/{$booking->id}/pay")->assertRedirect('/login');
        $this->post("/booking/{$booking->id}/pay/proof")->assertRedirect('/login');
    }

    public function test_anonymous_visitor_cannot_queue_a_payment_claim(): void
    {
        Storage::fake('local');

        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $this->post("/booking/{$booking->id}/pay/proof", [
            'proof_method' => 'gcash',
            'proof_reference' => '9988776655',
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ]);

        $this->assertSame('pending_payment', $booking->fresh()->status);
        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
    }

    // ---------------------------------------------------------------
    // Cross-user access (IDOR)
    // ---------------------------------------------------------------

    public function test_another_user_cannot_touch_someone_elses_payment(): void
    {
        $owner = $this->user('owner@example.test');
        $intruder = $this->user('intruder@example.test');
        $booking = $this->booking($owner);
        $this->payment($booking);

        $this->actingAs($intruder)
            ->get("/booking/{$booking->id}/pay")->assertForbidden();

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Owner happy path
    // ---------------------------------------------------------------

    public function test_pay_serves_the_receipt_upload_form(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        // /pay used to be a fork between manual settlement and the card
        // gateway. It is now the upload form itself.
        $this->actingAs($owner)
            ->get("/booking/{$booking->id}/pay")
            ->assertOk()
            ->assertSee('Submit for verification')
            // The retired gateway must not be advertised anywhere on it.
            ->assertDontSee('Pay online by card')
            ->assertDontSee('bank portal');
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

    /**
     * The retired gateway's routes are gone, not merely unlinked. A bookmarked
     * or guessed URL must 404 rather than quietly still working.
     */
    public function test_the_retired_card_gateway_routes_no_longer_exist(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);
        $payment = $this->payment($booking);

        $this->actingAs($owner)->get("/booking/{$booking->id}/pay/sandbox")->assertNotFound();
        $this->actingAs($owner)->get("/sandbox/pay/{$payment->id}")->assertNotFound();
        $this->actingAs($owner)->get("/sandbox/status/{$payment->id}")->assertNotFound();
        $this->actingAs($owner)->get("/sandbox/result/success/{$payment->id}")->assertNotFound();
        $this->actingAs($owner)->post("/sandbox/process/{$payment->id}", ['simulate' => 'success'])->assertNotFound();
        $this->postJson("/sandbox/webhook/{$payment->id}", ['event' => 'payment.success'])->assertNotFound();

        // And nothing about the booking moved on the way past.
        $this->assertSame('pending_payment', $booking->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Manual proof of payment
    // ---------------------------------------------------------------

    public function test_a_stranger_cannot_upload_proof_against_someone_elses_booking(): void
    {
        $booking = $this->booking($this->user('owner@example.test'));
        $intruder = $this->user('intruder@example.test');

        $this->actingAs($intruder)
            ->post("/booking/{$booking->id}/pay/proof", [
                'proof_method' => 'gcash',
                'proof_reference' => '123456',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])->assertForbidden();

        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
    }

    public function test_uploading_proof_queues_it_without_paying_the_booking(): void
    {
        Storage::fake('local');

        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $this->actingAs($owner)
            ->post("/booking/{$booking->id}/pay/proof", [
                'proof_method' => 'gcash',
                'proof_reference' => '9988776655',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertRedirect(route('booking.show', $booking->id));

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);

        // The claim is recorded; the money is not confirmed. Only a staff
        // member may move the booking to paid.
        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    public function test_proof_upload_rejects_a_non_image_and_an_unknown_method(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $this->actingAs($owner)
            ->post("/booking/{$booking->id}/pay/proof", [
                'proof_method' => 'gcash',
                'proof_reference' => '123456',
                'proof' => UploadedFile::fake()->create('payload.php', 12, 'application/x-php'),
            ])->assertSessionHasErrors('proof');

        $this->actingAs($owner)
            ->post("/booking/{$booking->id}/pay/proof", [
                'proof_method' => 'crypto',
                'proof_reference' => '123456',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])->assertSessionHasErrors('proof_method');

        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
    }

    public function test_a_second_proof_cannot_be_queued_while_one_is_pending(): void
    {
        Storage::fake('local');

        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $upload = fn () => $this->actingAs($owner)->post("/booking/{$booking->id}/pay/proof", [
            'proof_method' => 'gcash',
            'proof_reference' => '9988776655',
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ]);

        $upload();
        $upload()->assertSessionHas('info');

        $this->assertSame(1, Payment::where('booking_id', $booking->id)->count());
    }

    public function test_the_upload_form_is_closed_once_a_proof_is_queued(): void
    {
        Storage::fake('local');

        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        $this->actingAs($owner)->post("/booking/{$booking->id}/pay/proof", [
            'proof_method' => 'gcash',
            'proof_reference' => '9988776655',
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ]);

        // The only door closes, so one booking cannot stack two claims.
        $this->actingAs($owner)->get("/booking/{$booking->id}/pay")
            ->assertRedirect(route('booking.show', $booking->id))
            ->assertSessionHas('info');
    }

    /**
     * A rejected receipt has to say so at the moment the guest is replacing
     * it. The choice page used to carry this banner; /pay inherited it when
     * that page was removed, and losing it in the move would leave a guest
     * re-uploading the same unusable image with no idea why.
     */
    public function test_a_rejected_proof_shows_its_reason_on_the_upload_form(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner);

        Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $owner->id,
            'amount' => $booking->payable_amount,
            'status' => Payment::STATUS_REJECTED,
            'payment_type' => 'manual',
            'reference_no' => 'REJECTED01',
            'gateway' => 'gcash',
            'rejection_reason' => 'The reference number is not readable.',
        ]);

        $this->actingAs($owner)
            ->get("/booking/{$booking->id}/pay")
            ->assertOk()
            ->assertSee('The reference number is not readable.');
    }

    public function test_proof_cannot_be_uploaded_for_a_booking_not_awaiting_payment(): void
    {
        $owner = $this->user('owner@example.test');
        $booking = $this->booking($owner, 'paid');

        $this->actingAs($owner)
            ->get("/booking/{$booking->id}/pay")
            ->assertRedirect(route('booking.show', $booking->id));

        $this->actingAs($owner)
            ->post("/booking/{$booking->id}/pay/proof", [
                'proof_method' => 'gcash',
                'proof_reference' => '9988776655',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])->assertRedirect(route('booking.show', $booking->id));

        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
    }
}
