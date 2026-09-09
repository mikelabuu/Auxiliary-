<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingExpiredMail;
use App\Mail\BookingNoShowMail;
use App\Mail\BookingReceivedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Part\DataPart;
use Tests\TestCase;

/**
 * Three moments that used to change a booking's fate in total silence: the
 * payment window lapsing, an overnight no-show sweep, and the guest's own
 * cancellation. Each now sends the guest a mail, and none of them may fail the
 * operation they are reporting on.
 */
class GuestLifecycleMailTest extends TestCase
{
    use RefreshDatabase;

    private function guest(string $email = 'lifecycle@example.test'): User
    {
        return User::forceCreate([
            'username' => 'lifecycle-' . uniqid(),
            'email' => $email,
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * `pending_payment_since` is deliberately absent from Booking::$fillable —
     * a status mutator stamps it the moment status becomes pending_payment, so
     * passing it to create() is silently dropped. Ageing a hold therefore has to
     * happen after the fact, which is what $pendingSince does.
     */
    private function booking(
        ?User $guest,
        string $status,
        array $overrides = [],
        ?\DateTimeInterface $pendingSince = null
    ): Booking {
        $booking = Booking::create(array_merge([
            'user_id' => $guest?->id,
            'expected_guests' => 2,
            'guest_name' => 'Lifecycle Guest',
            'guest_address' => 'Somewhere',
            'guest_phone' => '09000000000',
            'check_in' => now()->subDay(),
            'check_out' => now()->addDay(),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 6000,
            'payable_amount' => 6000,
            'status' => $status,
        ], $overrides));

        if ($pendingSince) {
            $booking->forceFill(['pending_payment_since' => $pendingSince])->save();
        }

        return $booking->refresh();
    }

    /** A hold whose payment window has already run out. */
    private function lapsedHold(?User $guest): Booking
    {
        return $this->booking(
            $guest,
            'pending_payment',
            [],
            now()->subMinutes((int) config('bookings.expiry_minutes') + 5)
        );
    }

    public function test_expiring_a_booking_emails_the_guest(): void
    {
        Mail::fake();

        $guest = $this->guest();
        $booking = $this->lapsedHold($guest);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame('expired', $booking->fresh()->status);
        Mail::assertSent(BookingExpiredMail::class, fn ($mail) => $mail->hasTo($guest->email));
    }

    public function test_a_booking_still_inside_its_window_is_left_alone(): void
    {
        Mail::fake();

        $guest = $this->guest();
        // Fresh hold *and* a future arrival — both halves matter. The status
        // mutator stamps pending_payment_since as now(), but a hold also dies
        // the moment its guests were due (PaymentWindow::deadlineFor takes the
        // earlier of the two), and the helper's default check_in is yesterday.
        // Built with that default this booking was already past its arrival,
        // so bookings:expire was right to expire it and the test was wrong.
        $this->booking($guest, 'pending_payment', [
            'check_in'  => now()->addDays(3),
            'check_out' => now()->addDays(5),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_a_booking_waiting_on_payment_verification_does_not_expire(): void
    {
        Mail::fake();

        $guest = $this->guest();
        $booking = $this->booking($guest, Booking::STATUS_PENDING_PAYMENT, [
            'check_in' => now()->addDays(3),
            'check_out' => now()->addDays(5),
        ], now()->subMinutes((int) config('bookings.expiry_minutes') + 5));

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $guest->id,
            'amount' => $booking->payable_amount,
            'status' => Payment::STATUS_AWAITING_VERIFICATION,
            'payment_type' => 'manual',
            'reference_no' => 'WAITVERIFY1',
            'gateway' => 'gcash',
            'proof_path' => 'payment_proofs/waiting.png',
            'proof_method' => 'gcash',
            'proof_reference' => '9988776655',
            'proof_submitted_at' => now(),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
        $this->assertSame(Payment::STATUS_AWAITING_VERIFICATION, $payment->fresh()->status);
        $this->assertDatabaseMissing('expiry_logs', ['booking_id' => $booking->id]);
        Mail::assertNothingSent();
    }

    public function test_marking_a_no_show_emails_the_guest(): void
    {
        Mail::fake();

        $guest = $this->guest();
        $booking = $this->booking($guest, 'paid', [
            'check_in' => now()->subDays(2),
            'check_out' => now()->subDay(),
        ]);

        $this->artisan('bookings:mark-no-show')->assertSuccessful();

        $this->assertSame('no_show', $booking->fresh()->status);
        Mail::assertSent(BookingNoShowMail::class, fn ($mail) => $mail->hasTo($guest->email));
    }

    public function test_a_guest_cancelling_gets_written_confirmation(): void
    {
        Mail::fake();

        $guest = $this->guest();
        $booking = $this->booking($guest, 'pending_payment');

        $this->actingAs($guest)
            ->post(route('booking.cancel', $booking), ['reason' => 'Change of plans'])
            ->assertRedirect();

        $this->assertSame('cancelled', $booking->fresh()->status);
        Mail::assertSent(BookingCancelledMail::class, fn ($mail) => $mail->hasTo($guest->email));
    }

    public function test_guest_email_shell_embeds_the_farmers_hostel_logo(): void
    {
        config(['app.name' => 'Farmers Hostel']);

        $guest = $this->guest('brand@example.test');
        $booking = $this->booking($guest, Booking::STATUS_CANCELLED);
        $messages = [
            (new BookingCancelledMail($booking, 'Plans changed'))->render(),
            (new BookingReceivedMail($booking))->render(),
            view('emails.booking.paid', [
                'booking' => $booking->load('reservations'),
                'payment' => new Payment,
                'receipt' => null,
            ])->render(),
        ];

        foreach ($messages as $html) {
            $this->assertStringContainsString('Stay at CLSU', $html);
            $this->assertStringContainsString('Farmers Hostel', $html);
            $this->assertStringContainsString('Reservations &amp; Guest Services', $html);
            $this->assertStringContainsString('Science City of Muñoz, Nueva Ecija', $html);
            $this->assertStringContainsString('mail-details', $html);
            $this->assertStringContainsString('mail-status', $html);
            $this->assertStringNotContainsString('image/derived/fh-mark-120.png', $html);
            $this->assertStringContainsString('class="hostel-logo"', $html);
            $this->assertStringContainsString('alt="Farmers Hostel"', $html);
            $this->assertStringContainsString('src="cid:farmers-hostel-logo@clsu"', $html);
            $this->assertStringNotContainsString('/email-assets/', $html);
            $this->assertStringNotContainsString('clsu.logo', $html);
            $this->assertStringNotContainsString('Central Luzon State University', $html);
        }

        $plainText = app(Markdown::class)->renderText('emails.booking.paid', [
            'booking' => $booking,
            'payment' => new Payment,
            'receipt' => null,
        ]);

        $this->assertStringContainsString('Check-in:', $plainText);
        $this->assertStringContainsString('Total paid:', $plainText);
    }

    public function test_outgoing_email_contains_the_inline_farmers_hostel_logo(): void
    {
        $guest = $this->guest('inline-logo@example.test');
        $booking = $this->booking($guest, Booking::STATUS_CANCELLED);
        $transport = Mail::mailer()->getSymfonyTransport();

        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $transport->flush();

        Mail::to($guest->email)->send(new BookingCancelledMail($booking));

        $email = $transport->messages()->last()->getOriginalMessage();
        $logo = collect($email->getAttachments())->first(
            fn (DataPart $attachment) => $attachment->hasContentId()
                && $attachment->getContentId() === 'farmers-hostel-logo@clsu'
        );

        $this->assertInstanceOf(DataPart::class, $logo);
        $this->assertSame('inline', $logo->getDisposition());
        $this->assertSame('image/png', $logo->getContentType());
        $this->assertStringContainsString(
            'src="cid:farmers-hostel-logo@clsu"',
            $email->getHtmlBody()
        );
    }

    /**
     * Walk-ins and desk-entered bookings have no account behind them. That is
     * normal — the batch must skip them, not blow up on a null email.
     */
    public function test_a_booking_with_no_account_is_skipped_quietly(): void
    {
        Mail::fake();

        $booking = $this->lapsedHold(null);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame('expired', $booking->fresh()->status);
        Mail::assertNothingSent();
    }

    /**
     * The whole point of routing these through GuestNotice: the booking has
     * already been expired and the rooms already released, so a dead mail host
     * must not take the command down and leave the batch half-processed.
     */
    public function test_a_failing_mail_host_does_not_fail_the_expiry_run(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $guest = $this->guest();
        $booking = $this->lapsedHold($guest);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame('expired', $booking->fresh()->status);
    }

    /** The no-show notice must match the non-refundable checkout policy. */
    public function test_the_no_show_mail_explains_forfeiture_and_how_to_contact_staff(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'no_show');

        $html = (new BookingNoShowMail($booking))->render();

        $this->assertStringContainsStringIgnoringCase('no refund', strip_tags($html));
        $this->assertStringContainsStringIgnoringCase('forfeited', strip_tags($html));
        $this->assertStringContainsStringIgnoringCase('24 hours', strip_tags($html));

        $this->assertStringContainsStringIgnoringCase('front desk', strip_tags($html));
    }
}
