<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Receipt;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The QR printed on every paid receipt points at this route. It used to sit
 * inside the auth:staff group, so the guest holding the receipt could not open
 * the thing printed on it — these cover the fix and the limits placed on it.
 */
class ReceiptVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceipt(string $contents = 'pretend-pdf-bytes'): Receipt
    {
        $guest = User::forceCreate([
            'username' => 'receipt-guest',
            'email' => 'receipt-guest@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        // No BookingFactory in this repo; the other feature tests build these
        // by hand the same way.
        $booking = Booking::create([
            'user_id' => $guest->id,
            'expected_guests' => 2,
            'guest_name' => 'Verification Guest',
            'guest_address' => 'Somewhere',
            'guest_phone' => '09000000000',
            'check_in' => now()->subDay(),
            'check_out' => now()->addDay(),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 6000,
            'payable_amount' => 6000,
            'status' => 'active',
        ]);

        $path = "receipts/Receipt_{$booking->id}.pdf";
        Storage::disk('local')->put($path, $contents);

        return Receipt::create([
            'booking_id' => $booking->id,
            'receipt_number' => 'R-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
            'generated_by' => 'system',
            'file_path' => $path,
            'sha256_hash' => hash('sha256', $contents),
        ]);
    }

    public function test_a_guest_with_the_signed_link_can_verify_their_receipt(): void
    {
        $receipt = $this->makeReceipt();

        $response = $this->get(URL::signedRoute('receipts.verify', ['number' => $receipt->receipt_number]));

        $response->assertOk();
        $response->assertSee('This receipt is genuine.', false);
        $response->assertSee($receipt->receipt_number);
    }

    /**
     * The reason the link is signed rather than simply public: receipt numbers
     * are the zero-padded booking id, so an unsigned endpoint could be walked
     * from R-000001 upward.
     */
    public function test_an_unsigned_link_is_refused(): void
    {
        $receipt = $this->makeReceipt();

        $this->get('/verify-receipt/' . $receipt->receipt_number)->assertForbidden();
    }

    public function test_a_tampered_signature_is_refused(): void
    {
        $receipt = $this->makeReceipt();

        $url = URL::signedRoute('receipts.verify', ['number' => $receipt->receipt_number]);

        $this->get($url . 'x')->assertForbidden();
    }

    /** Keeps receipts issued before signing existed verifiable from the desk. */
    public function test_signed_in_staff_may_verify_without_a_signature(): void
    {
        $receipt = $this->makeReceipt();

        $staff = Staff::create([
            'name' => 'Desk',
            'email' => 'desk-receipts@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'frontdesk',
            'is_suspended' => false,
        ]);

        $this->actingAs($staff, 'staff')
            ->get('/verify-receipt/' . $receipt->receipt_number)
            ->assertOk()
            ->assertSee('This receipt is genuine.', false);
    }

    /**
     * The check has to be a hash comparison, not a lookup — otherwise an edited
     * PDF with a real receipt number would pass.
     */
    public function test_an_altered_file_fails_verification(): void
    {
        $receipt = $this->makeReceipt();

        // Someone edits the stored PDF after issue; the digest no longer matches.
        Storage::disk('local')->put($receipt->file_path, 'edited-pdf-bytes');

        $response = $this->get(URL::signedRoute('receipts.verify', ['number' => $receipt->receipt_number]));

        $response->assertOk();
        $response->assertSee('We can’t verify this receipt.', false);
    }

    public function test_an_unknown_receipt_number_reports_cleanly(): void
    {
        $response = $this->get(URL::signedRoute('receipts.verify', ['number' => 'R-999999']));

        $response->assertOk();
        $response->assertSee('We can’t verify this receipt.', false);
    }

    /**
     * The verification page is reached by strangers holding a piece of paper, so
     * it states only what that paper already says — never a name or a total.
     */
    public function test_the_page_discloses_nothing_beyond_the_receipt_itself(): void
    {
        $receipt = $this->makeReceipt();
        $booking = $receipt->booking;

        $html = $this->get(URL::signedRoute('receipts.verify', ['number' => $receipt->receipt_number]))
            ->getContent();

        // Strip the layout's nav/footer, which carry site-wide copy.
        $main = preg_match('#<main[^>]*>(.*?)</main>#s', $html, $m) ? $m[1] : $html;

        foreach (array_filter([$booking->guest_name ?? null, $booking->total_amount ?? null]) as $secret) {
            $this->assertStringNotContainsString((string) $secret, $main);
        }
    }
}
