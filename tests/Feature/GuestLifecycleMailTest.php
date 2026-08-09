<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingExpiredMail;
use App\Mail\BookingNoShowMail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        // Fresh hold: the status mutator stamps pending_payment_since as now(),
        // so this one is comfortably inside its window.
        $this->booking($guest, 'pending_payment');

        $this->artisan('bookings:expire')->assertSuccessful();

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

    /**
     * PRODUCT.md: tell the truth or say nothing. This system has no refund
     * policy written down, so the no-show mail — the one going to a guest who
     * has already paid — must not imply one either way.
     */
    public function test_the_no_show_mail_promises_nothing_about_money(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'no_show');

        $html = (new BookingNoShowMail($booking))->render();

        foreach (['refund', 'non-refundable', 'forfeit', 'no refund'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, strip_tags($html));
        }

        $this->assertStringContainsStringIgnoringCase('front desk', strip_tags($html));
    }
}
