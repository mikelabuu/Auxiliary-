<?php

namespace Tests\Feature;

use App\Mail\BookingPaidMail;
use App\Mail\StaffBookingAlertMail;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use App\Support\StaffAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The email is navigation into the cashier's authenticated workflow, never an
 * approval capability by itself. These tests pin that boundary down alongside
 * the state transition and staff attribution it eventually leads to.
 */
class PaymentVerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        config([
            'app.name' => 'Farmers Hostel',
            'app.url' => 'https://booking.example.test',
            'staff.otp_enabled' => false,
            'staff.alerts.cashier_to' => 'cashier@example.test',
            'staff.alerts.admin_to' => null,
        ]);
    }

    private function guest(string $email = 'guest@example.test'): User
    {
        return User::forceCreate([
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'password' => Hash::make('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);
    }

    private function staff(string $role = 'cashier', array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'name' => 'Cashier Test',
            'email' => $role . '-' . uniqid() . '@example.test',
            'password' => 'correct-horse-battery',
            'role' => $role,
            'is_suspended' => false,
        ], $overrides));
    }

    private function booking(?User $guest = null): Booking
    {
        $guest ??= $this->guest();

        return Booking::create([
            'user_id' => $guest->id,
            'guest_name' => 'Email Review Guest',
            'guest_address' => 'Science City of Muñoz',
            'guest_phone' => '09171234567',
            'check_in' => now()->addDays(5),
            'check_out' => now()->addDays(7),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 3200,
            'payable_amount' => 3200,
            'status' => Booking::STATUS_PENDING_PAYMENT,
        ]);
    }

    private function awaitingPayment(Booking $booking, array $overrides = []): Payment
    {
        $path = 'payment_proofs/review-' . uniqid() . '.png';
        Storage::disk('local')->put($path, 'private-proof-bytes');

        return Payment::create(array_merge([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->payable_amount,
            'status' => Payment::STATUS_AWAITING_VERIFICATION,
            'payment_type' => 'manual',
            'reference_no' => 'EMAIL' . strtoupper(substr(uniqid(), -7)),
            'gateway' => 'gcash',
            'proof_path' => $path,
            'proof_method' => 'gcash',
            'proof_reference' => '9988776655',
            'proof_submitted_at' => now(),
        ], $overrides));
    }

    public function test_proof_submission_emails_the_configured_cashier_with_the_exact_claim(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest);
        $this->staff('cashier', ['email' => 'cashier@example.test']);

        $this->withHeader('Host', 'attacker.example.test')
            ->actingAs($guest)
            ->post(route('bookings.pay.proof.store', $booking), [
                'proof_method' => 'gcash',
                'proof_reference' => '9988776655',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertRedirect(route('booking.show', $booking));

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $captured = null;

        Mail::assertSent(StaffBookingAlertMail::class, function ($mail) use ($booking, $payment, &$captured) {
            $captured = $mail;

            return $mail->hasTo('cashier@example.test')
                && $mail->booking->is($booking)
                && $mail->payment?->is($payment);
        });

        $rendered = $captured->render();
        $html = html_entity_decode($rendered);
        $reviewUrl = 'https://booking.example.test'
            . route('staff.paymentverification.show', $payment, absolute: false);

        $this->assertStringContainsString('Review payment proof', $rendered);
        $this->assertStringContainsString(
            'Opening the review page does not approve the payment.',
            $rendered
        );
        $this->assertStringContainsString('payment proof for booking', $rendered);
        $this->assertStringContainsString('Stay at CLSU', $rendered);
        $this->assertStringContainsString('Farmers Hostel', $rendered);
        $this->assertStringContainsString('Reservations &amp; Guest Services', $rendered);
        $this->assertStringContainsString('mail-status', $rendered);
        $this->assertStringNotContainsString('image/derived/fh-mark-120.png', $rendered);
        $this->assertStringContainsString('class="hostel-logo"', $rendered);
        $this->assertStringContainsString('src="cid:farmers-hostel-logo@clsu"', $rendered);
        $this->assertStringNotContainsString('/email-assets/', $rendered);
        $this->assertStringNotContainsString('clsu.logo', $rendered);
        $this->assertStringNotContainsString('clsu-logo', $rendered);
        $this->assertStringNotContainsString('Central Luzon State University', $rendered);
        $this->assertStringContainsString($reviewUrl, $html);
        $this->assertStringNotContainsString('attacker.example.test', $html);
        $this->assertStringNotContainsString("/{$payment->id}/approve", $html);
        $this->assertStringNotContainsString('09171234567', $html);
    }

    public function test_review_link_requires_an_authorized_active_staff_session(): void
    {
        $booking = $this->booking();
        $payment = $this->awaitingPayment($booking);
        $url = route('staff.paymentverification.show', $payment);

        $this->get($url)->assertRedirect(route('login'));
        $this->post(route('staff.paymentverification.approve', $payment))
            ->assertRedirect(route('login'));
        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->fresh()->status);
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);

        $this->actingAs($this->guest('signed-in-guest@example.test'))
            ->get($url)
            ->assertRedirect(route('login'));

        $this->actingAs($this->staff('housekeeping'), 'staff')
            ->get($url)
            ->assertForbidden();

        $this->actingAs($this->staff('frontdesk'), 'staff')
            ->get($url)
            ->assertForbidden();

        $this->actingAs($this->staff('cashier', ['is_suspended' => true]), 'staff')
            ->get($url)
            ->assertRedirect(route('login'));
    }

    public function test_staff_login_returns_the_cashier_to_the_exact_email_review(): void
    {
        $payment = $this->awaitingPayment($this->booking());
        $cashier = $this->staff();
        $url = route('staff.paymentverification.show', $payment);

        $this->get($url)->assertRedirect(route('login'));

        $this->post(route('login.attempt'), [
            'email' => $cashier->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect($url);

        $this->assertAuthenticatedAs($cashier, 'staff');
    }

    public function test_cashier_skips_admin_only_otp_and_keeps_the_exact_email_review_destination(): void
    {
        config([
            'staff.otp_enabled' => true,
            'staff.otp_roles' => ['admin', 'master_admin'],
        ]);

        $payment = $this->awaitingPayment($this->booking());
        $cashier = $this->staff();
        $url = route('staff.paymentverification.show', $payment);

        $this->get($url)->assertRedirect(route('login'));

        $this->post(route('login.attempt'), [
            'email' => $cashier->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect($url);

        $this->assertAuthenticatedAs($cashier, 'staff');
        $this->assertDatabaseMissing('staff_otps', ['staff_id' => $cashier->id]);
    }

    public function test_authorized_review_is_read_only_and_disables_sensitive_response_caching(): void
    {
        $booking = $this->booking();
        $payment = $this->awaitingPayment($booking);

        $response = $this->actingAs($this->staff(), 'staff')
            ->get(route('staff.paymentverification.show', $payment))
            ->assertOk()
            ->assertSee('Email Review Guest')
            ->assertSee('9988776655')
            ->assertSee('Verify &amp; mark paid', false);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->fresh()->status);
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
        $this->assertSame(0, AuditLog::where('action', 'payment_proof_verified')->count());
        Mail::assertNotSent(BookingPaidMail::class);

        $proofResponse = $this->get(route('staff.paymentverification.proof', $payment))
            ->assertOk();

        $this->assertStringContainsString('private', (string) $proofResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $proofResponse->headers->get('Cache-Control'));
    }

    public function test_review_route_refuses_a_payment_without_uploaded_proof(): void
    {
        $booking = $this->booking();
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->payable_amount,
            'status' => 'pending',
            'payment_type' => 'manual',
            'reference_no' => 'NOPROOF01',
            'gateway' => 'gcash',
        ]);

        $this->actingAs($this->staff(), 'staff')
            ->get(route('staff.paymentverification.show', $payment))
            ->assertNotFound();
    }

    public function test_cashier_can_approve_once_and_the_actor_is_audited(): void
    {
        $booking = $this->booking();
        $payment = $this->awaitingPayment($booking);
        $cashier = $this->staff();
        $approveUrl = route('staff.paymentverification.approve', $payment);

        $this->actingAs($cashier, 'staff')->post($approveUrl)->assertRedirect();

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('success', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame($cashier->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame(Booking::STATUS_PAID, $booking->status);
        $this->assertSame('gcash', $booking->payment_mode);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment_proof_verified',
            'staff_id' => $cashier->id,
            'target_type' => 'Payment',
            'target_id' => $payment->id,
        ]);
        Mail::assertSent(BookingPaidMail::class, 1);

        // A stale email tab or a second cashier cannot issue a second receipt.
        $this->actingAs($cashier, 'staff')->post($approveUrl)->assertSessionHas('error');
        Mail::assertSent(BookingPaidMail::class, 1);
        $this->assertSame(1, AuditLog::where('action', 'payment_proof_verified')->count());
    }

    public function test_only_cashier_can_change_a_payment_verification(): void
    {
        foreach (['frontdesk', 'admin', 'master_admin'] as $role) {
            $booking = $this->booking($this->guest("{$role}-guest@example.test"));
            $payment = $this->awaitingPayment($booking);
            $staff = $this->staff($role, ['email' => "{$role}@example.test"]);

            $this->actingAs($staff, 'staff')
                ->post(route('staff.paymentverification.approve', $payment))
                ->assertForbidden();

            $this->actingAs($staff, 'staff')
                ->post(route('staff.paymentverification.reject', $payment), [
                    'rejection_reason' => 'Should not be accepted.',
                ])
                ->assertForbidden();

            $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->fresh()->status);
            $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
        }

        Mail::assertNothingSent();
    }

    public function test_admin_can_inspect_an_awaiting_proof_but_sees_no_decision_controls(): void
    {
        $payment = $this->awaitingPayment($this->booking());

        $this->actingAs($this->staff('admin'), 'staff')
            ->get(route('staff.paymentverification.show', $payment))
            ->assertOk()
            ->assertSee('Awaiting cashier verification')
            ->assertDontSee('Verify &amp; mark paid', false)
            ->assertDontSee(route('staff.paymentverification.approve', $payment), false)
            ->assertDontSee(route('staff.paymentverification.reject', $payment), false);
    }

    public function test_cashier_verification_notifies_only_active_admin_roles(): void
    {
        $booking = $this->booking();
        $payment = $this->awaitingPayment($booking);
        $cashier = $this->staff('cashier', ['email' => 'cashier-actor@example.test']);
        $admin = $this->staff('admin', ['email' => 'admin@example.test']);
        $master = $this->staff('master_admin', ['email' => 'master@example.test']);
        $this->staff('frontdesk', ['email' => 'frontdesk@example.test']);
        $this->staff('admin', [
            'email' => 'suspended-admin@example.test',
            'is_suspended' => true,
        ]);

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.approve', $payment))
            ->assertRedirect();

        $notice = null;
        Mail::assertSent(StaffBookingAlertMail::class, function (StaffBookingAlertMail $mail) use ($admin, $master, $cashier, &$notice) {
            if ($mail->kind !== StaffBookingAlertMail::KIND_PAYMENT_VERIFIED) {
                return false;
            }

            $notice = $mail;

            return $mail->hasTo($admin->email)
                && $mail->hasTo($master->email)
                && ! $mail->hasTo($cashier->email)
                && ! $mail->hasTo('frontdesk@example.test')
                && ! $mail->hasTo('suspended-admin@example.test');
        });

        $rendered = html_entity_decode($notice->render());
        $this->assertStringContainsString('Cashier Test', $rendered);
        $this->assertStringContainsString('9988776655', $rendered);
        $this->assertStringContainsString('No second payment approval is required.', $rendered);
        $this->assertStringNotContainsString("/{$payment->id}/approve", $rendered);
        $this->assertStringNotContainsString('09171234567', $rendered);

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.approve', $payment))
            ->assertSessionHas('error');

        Mail::assertSent(StaffBookingAlertMail::class, 1);
        Mail::assertSent(BookingPaidMail::class, 1);
    }

    public function test_configured_cashier_recipient_must_be_an_active_cashier_account(): void
    {
        $this->staff('frontdesk', ['email' => 'cashier@example.test']);
        $this->staff('cashier', ['email' => 'suspended-cashier@example.test', 'is_suspended' => true]);
        config(['staff.alerts.cashier_to' => 'cashier@example.test,suspended-cashier@example.test']);

        $this->assertSame([], StaffAlert::cashierRecipients());

        $active = $this->staff('cashier', ['email' => 'active-cashier@example.test']);
        config(['staff.alerts.cashier_to' => 'cashier@example.test,active-cashier@example.test']);

        $this->assertSame([$active->email], StaffAlert::cashierRecipients());
    }

    public function test_changed_booking_amount_cannot_be_verified(): void
    {
        $booking = $this->booking();
        $payment = $this->awaitingPayment($booking, ['amount' => '1.00']);

        $this->actingAs($this->staff(), 'staff')
            ->post(route('staff.paymentverification.approve', $payment))
            ->assertSessionHas('error');

        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->fresh()->status);
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_one_normalized_bank_reference_cannot_pay_two_bookings(): void
    {
        $cashier = $this->staff();
        $firstBooking = $this->booking($this->guest('first-reference-guest@example.test'));
        $firstPayment = $this->awaitingPayment($firstBooking, [
            'proof_reference' => 'GCASH-9081 7726',
        ]);
        $secondBooking = $this->booking($this->guest('second-reference-guest@example.test'));
        $secondPayment = $this->awaitingPayment($secondBooking, [
            'proof_reference' => 'gcash90817726',
        ]);

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.approve', $firstPayment))
            ->assertSessionHas('success');

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.approve', $secondPayment))
            ->assertSessionHas('error', 'This bank or GCash reference was already accepted for another booking. Do not verify it again.');

        $this->assertSame('success', $firstPayment->fresh()->status);
        $this->assertSame('gcash:GCASH90817726', $firstPayment->fresh()->accepted_reference_key);
        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $secondPayment->fresh()->status);
        $this->assertNull($secondPayment->fresh()->accepted_reference_key);
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $secondBooking->fresh()->status);
        $this->assertSame(1, AuditLog::where('action', 'payment_proof_verified')->count());
        Mail::assertSent(BookingPaidMail::class, 1);
    }

    public function test_a_reference_from_a_rejected_claim_can_be_accepted_later(): void
    {
        $cashier = $this->staff();
        $rejectedBooking = $this->booking($this->guest('rejected-reference-guest@example.test'));
        $rejectedPayment = $this->awaitingPayment($rejectedBooking, [
            'gateway' => 'bank_transfer',
            'proof_method' => 'bank_transfer',
            'proof_reference' => 'BANK-123-456',
        ]);

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.reject', $rejectedPayment), [
                'rejection_reason' => 'The receipt image does not match this reference.',
            ])
            ->assertSessionHas('success');

        $acceptedBooking = $this->booking($this->guest('accepted-reference-guest@example.test'));
        $acceptedPayment = $this->awaitingPayment($acceptedBooking, [
            'gateway' => 'bank_transfer',
            'proof_method' => 'bank_transfer',
            'proof_reference' => 'bank 123456',
        ]);

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.approve', $acceptedPayment))
            ->assertSessionHas('success');

        $this->assertSame(Payment::STATUS_REJECTED, $rejectedPayment->fresh()->status);
        $this->assertNull($rejectedPayment->fresh()->accepted_reference_key);
        $this->assertSame('success', $acceptedPayment->fresh()->status);
        $this->assertSame('bank_transfer:BANK123456', $acceptedPayment->fresh()->accepted_reference_key);
    }

    public function test_frontdesk_cannot_bypass_cashier_with_a_transfer_settlement(): void
    {
        foreach (['gcash', 'bank_transfer'] as $method) {
            $booking = $this->booking($this->guest("{$method}-guest@example.test"));

            $this->actingAs($this->staff('frontdesk', ['email' => "{$method}-desk@example.test"]), 'staff')
                ->post(route('frontdesk.booking.settle', $booking), [
                    'method' => $method,
                    'reference' => 'BYPASS-ATTEMPT',
                ])
                ->assertSessionHasErrors('method');

            $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
            $this->assertDatabaseMissing('payments', [
                'booking_id' => $booking->id,
                'status' => 'success',
            ]);
        }
    }

    public function test_frontdesk_cannot_overwrite_an_awaiting_transfer_with_cash(): void
    {
        $booking = $this->booking();
        $payment = $this->awaitingPayment($booking);

        $this->actingAs($this->staff('frontdesk'), 'staff')
            ->post(route('frontdesk.booking.settle', $booking), [
                'method' => 'cash',
            ])
            ->assertSessionHas('error');

        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->fresh()->status);
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
    }

    public function test_cashier_cannot_enter_other_staff_workspaces_or_the_general_alert_feed(): void
    {
        $cashier = $this->staff();

        foreach ([
            route('staff.dashboard'),
            route('staff.staffrecords.index'),
            route('frontdesk.dashboard.index'),
            route('staff.notifications.feed'),
        ] as $url) {
            $this->actingAs($cashier, 'staff')->get($url)->assertForbidden();
        }
    }

    public function test_admin_notice_is_sent_even_when_the_booking_has_no_guest_email(): void
    {
        $booking = $this->booking();
        $booking->forceFill(['user_id' => null])->save();
        $booking->unsetRelation('user');
        $payment = $this->awaitingPayment($booking, ['user_id' => null]);
        $cashier = $this->staff();
        $admin = $this->staff('admin');

        $this->actingAs($cashier, 'staff')
            ->post(route('staff.paymentverification.approve', $payment))
            ->assertSessionHas('success')
            ->assertSessionHas('error', 'No guest email on file, so no receipt was sent.');

        $this->assertSame('success', $payment->fresh()->status);
        Mail::assertSent(StaffBookingAlertMail::class, fn (StaffBookingAlertMail $mail) =>
            $mail->kind === StaffBookingAlertMail::KIND_PAYMENT_VERIFIED
            && $mail->hasTo($admin->email)
        );
        Mail::assertNotSent(BookingPaidMail::class);
    }

    public function test_rejection_restarts_the_guest_payment_window(): void
    {
        $booking = $this->booking();
        $this->staff('admin');
        $booking->forceFill([
            'pending_payment_since' => now()->subMinutes((int) config('bookings.expiry_minutes') + 10),
        ])->save();
        $payment = $this->awaitingPayment($booking);
        $beforeDecision = now()->subSecond();

        $this->actingAs($this->staff(), 'staff')
            ->post(route('staff.paymentverification.reject', $payment), [
                'rejection_reason' => 'The reference number is not readable.',
            ])
            ->assertRedirect();

        $this->assertSame(Payment::STATUS_REJECTED, $payment->fresh()->status);
        $this->assertTrue($booking->fresh()->pending_payment_since->greaterThan($beforeDecision));

        $this->artisan('bookings:expire')->assertSuccessful();
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
        Mail::assertNotSent(StaffBookingAlertMail::class);
    }
}
