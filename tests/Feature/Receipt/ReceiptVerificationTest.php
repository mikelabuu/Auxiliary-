<?php

namespace Tests\Feature\Receipt;

use App\Models\Receipt;
use Database\Factories\ReceiptFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * Receipt integrity verification — GET /verify-receipt/{number}.
 *
 * Every paid booking gets a PDF receipt whose SHA-256 digest is recorded at
 * generation time. The QR code printed on the receipt points at this endpoint,
 * which re-hashes the stored file and reports whether it still matches. It is
 * the system's only tamper-evidence mechanism for a financial document, so a
 * verifier that cannot distinguish an altered file from an intact one is worse
 * than none at all.
 */
class ReceiptVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Make::catalog();
        Make::room('101', 'double');
    }

    private function staff()
    {
        return $this->actingAs(Make::staff('admin'), 'staff');
    }

    private function receipt(): Receipt
    {
        $booking = Make::bookingHolding(['101'], 'paid');

        return ReceiptFactory::new()->forBooking($booking)->withFile()->create();
    }

    public function test_an_intact_receipt_verifies(): void
    {
        $receipt = $this->receipt();

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('valid', true);
    }

    /**
     * The whole point of the digest: if the stored PDF is edited after the fact,
     * verification must fail.
     */
    public function test_an_altered_receipt_fails_verification(): void
    {
        $receipt = $this->receipt();

        Storage::disk('local')->put($receipt->file_path, '%PDF-1.4 total is now 10.00');

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('valid', false);
    }

    /**
     * A single flipped byte must be caught — the check has to be a real digest
     * comparison, not a length or timestamp heuristic.
     */
    public function test_a_single_byte_change_is_detected(): void
    {
        $receipt  = $this->receipt();
        $original = Storage::disk('local')->get($receipt->file_path);

        Storage::disk('local')->put($receipt->file_path, substr($original, 0, -1) . 'X');

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertViewHas('valid', false);
    }

    public function test_a_missing_file_fails_verification(): void
    {
        $receipt = $this->receipt();

        Storage::disk('local')->delete($receipt->file_path);

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('valid', false);
    }

    public function test_an_unknown_receipt_number_fails_verification(): void
    {
        $this->staff()->get('/verify-receipt/R-999999')
            ->assertOk()
            ->assertViewHas('valid', false);
    }

    /**
     * The number is user-supplied and goes into a where clause. A quoted string
     * should simply not match rather than error or match everything.
     */
    public function test_a_hostile_receipt_number_is_handled_safely(): void
    {
        $this->receipt();

        $this->staff()->get('/verify-receipt/' . urlencode("' OR '1'='1"))
            ->assertOk()
            ->assertViewHas('valid', false);
    }

    public function test_verification_reports_the_booking_it_belongs_to(): void
    {
        $receipt = $this->receipt();

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('receipt', fn ($shown) => $shown->booking_id === $receipt->booking_id);
    }

    // ------------------------------------------------------- authorization

    /**
     * The route sits behind `auth:staff`. Recording the current behaviour
     * because it is a design question worth revisiting: the QR code is printed
     * on the guest's own receipt, and a guest who scans it lands on the staff
     * login form. If public self-verification was the intent, this route is in
     * the wrong middleware group.
     */
    public function test_verification_currently_requires_staff_authentication(): void
    {
        $receipt = $this->receipt();

        $this->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertRedirect(route('staff.login'));
    }

    public function test_a_guest_account_cannot_verify_receipts(): void
    {
        $receipt = $this->receipt();

        $this->actingAs(Make::user())
            ->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertRedirect();
    }

    public function test_front_desk_staff_can_verify_a_receipt(): void
    {
        $receipt = $this->receipt();

        $this->actingAs(Make::staff('frontdesk'), 'staff')
            ->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('valid', true);
    }

    // ----------------------------------------------------------- numbering

    // -------------------------------------------------- generation, re-issue

    /**
     * Renders the real mailable, which is where receipt generation lives —
     * BookingPaidMail::build() writes the PDF, hashes it and creates the row.
     */
    private function issueReceiptFor($booking): void
    {
        $payment = Make::payment($booking, 'success');

        (new \App\Mail\BookingPaidMail($booking, $payment))->render();
    }

    public function test_paying_produces_a_verifiable_receipt(): void
    {
        $booking = Make::bookingHolding(['101'], 'paid');

        $this->issueReceiptFor($booking);

        $receipt = Receipt::where('booking_id', $booking->id)->firstOrFail();

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('valid', true);
    }

    /**
     * The receipt number is a pure function of the booking id, and
     * `receipts.receipt_number` is UNIQUE. Re-issuing therefore has to update
     * the existing row rather than insert a second one — a resent confirmation,
     * a queued mailable retried after a transient failure, or a second settled
     * payment all re-enter this path.
     *
     * The send site catches \Exception and only logs, so a constraint violation
     * here is silent: the guest receives no receipt and nothing surfaces.
     */
    public function test_re_issuing_a_receipt_does_not_collide(): void
    {
        $booking = Make::bookingHolding(['101'], 'paid');

        $this->issueReceiptFor($booking);
        $this->issueReceiptFor($booking);

        $this->assertSame(
            1,
            Receipt::where('booking_id', $booking->id)->count(),
            'Re-issuing should update the one official receipt, not add another.',
        );
    }

    /**
     * And the re-issued receipt must still verify — the stored digest has to
     * track the PDF that is actually on disk, since a regenerated PDF differs
     * byte-for-byte from the original.
     */
    public function test_a_re_issued_receipt_still_verifies(): void
    {
        $booking = Make::bookingHolding(['101'], 'paid');

        $this->issueReceiptFor($booking);
        $this->issueReceiptFor($booking);

        $receipt = Receipt::where('booking_id', $booking->id)->firstOrFail();

        $this->staff()->get("/verify-receipt/{$receipt->receipt_number}")
            ->assertOk()
            ->assertViewHas('valid', true);
    }

    public function test_the_receipt_number_stays_stable_across_re_issues(): void
    {
        $booking = Make::bookingHolding(['101'], 'paid');

        $this->issueReceiptFor($booking);
        $first = Receipt::where('booking_id', $booking->id)->firstOrFail()->receipt_number;

        $this->issueReceiptFor($booking);
        $second = Receipt::where('booking_id', $booking->id)->firstOrFail()->receipt_number;

        $this->assertSame($first, $second, 'An official receipt number must not change once issued.');
    }

    public function test_two_bookings_get_distinct_receipt_numbers(): void
    {
        Make::room('102', 'double');

        $first  = Make::bookingHolding(['101'], 'paid');
        $second = Make::bookingHolding(['102'], 'paid');

        $this->issueReceiptFor($first);
        $this->issueReceiptFor($second);

        $this->assertSame(2, Receipt::count());
        $this->assertNotSame(
            Receipt::where('booking_id', $first->id)->first()->receipt_number,
            Receipt::where('booking_id', $second->id)->first()->receipt_number,
        );
    }
}
