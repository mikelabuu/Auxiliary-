<?php

namespace Tests\Feature;

use App\Mail\RescheduleDecidedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\RescheduleRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Services\ReceiptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReschedulePolicyTest extends TestCase
{
    use RefreshDatabase;

    private array $receiptHtml = [];

    private function prepareReceipt(Booking $booking): array
    {
        Storage::fake('local');
        // Capture the actual Blade input while still rendering real PDF bytes.
        Pdf::shouldReceive('loadView')->andReturnUsing(function ($view, $data) {
            $this->receiptHtml[] = view($view, $data)->render();

            $pdf = new \Barryvdh\DomPDF\PDF(app('dompdf'), app('config'), app('files'), app('view'));

            return $pdf->loadView($view, $data);
        });
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => 3200,
            'status' => 'success',
            'payment_type' => 'manual',
            'reference_no' => 'RESCHEDULE-' . $booking->id,
            'gateway' => 'cash',
            'paid_at' => now(),
        ]);

        return [$payment, app(ReceiptService::class)->issue($booking, $payment)];
    }

    public function test_approval_updates_the_receipt_and_email_without_changing_the_payment(): void
    {
        $booking = $this->booking($this->guest(), 'RECEIPT-1');
        [$payment, $original] = $this->prepareReceipt($booking);
        $originalBytes = Storage::disk('local')->get($original->file_path);
        $request = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 7, [
            'requested_check_out' => $booking->check_out->copy()->addDays(8),
        ]);

        $this->actingAs($this->frontDesk(), 'staff')
            ->post(route('staff.reschedules.approve', $request))->assertSessionHas('success');

        $revised = $original->fresh();
        $this->assertSame(1, Receipt::count());
        $this->assertSame($original->receipt_number, $revised->receipt_number);
        $this->assertNotSame($original->file_path, $revised->file_path);
        $this->assertSame($originalBytes, Storage::disk('local')->get($original->file_path));
        $bytes = Storage::disk('local')->get($revised->file_path);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertNotSame($original->sha256_hash, $revised->sha256_hash);
        $this->assertSame(hash('sha256', $bytes), $revised->sha256_hash);
        $this->assertEquals(4800, $booking->fresh()->payable_amount);
        $this->assertEquals(3200, $payment->fresh()->amount);
        $this->assertStringContainsString('Check-in:</strong> ' . $request->requested_check_in->format('M d, Y'), $this->receiptHtml[1]);
        $this->assertStringContainsString('Check-out:</strong> ' . $request->requested_check_out->format('M d, Y'), $this->receiptHtml[1]);
        $this->assertStringNotContainsString('Check-in:</strong> ' . $booking->check_in->format('M d, Y'), $this->receiptHtml[1]);
        $this->assertStringContainsString('₱3,200.00', $this->receiptHtml[1]);

        $mail = new RescheduleDecidedMail($booking->fresh(), $request->fresh());
        $this->assertStringContainsString('Your updated official receipt is attached', $mail->render());
        $this->assertTrue($mail->hasAttachmentFromStorageDisk('local', $revised->file_path, $revised->receipt_number . '.pdf', ['mime' => 'application/pdf']));
        $this->get(route('receipts.download', $booking))->assertOk()->assertStreamedContent($bytes);
        $this->get(route('receipts.verify', $revised->receipt_number))->assertOk()->assertViewHas('valid', true);
        $this->assertCount(2, $this->receiptHtml);
    }

    public function test_downloading_a_previously_rescheduled_booking_repairs_its_old_receipt(): void
    {
        $booking = $this->booking($this->guest(), 'RECEIPT-2');
        [$payment, $original] = $this->prepareReceipt($booking);
        $request = $this->reschedule($booking, RescheduleRequest::STATUS_APPROVED);
        $booking->update([
            'check_in' => $request->requested_check_in,
            'check_out' => $request->requested_check_out,
        ]);

        $this->actingAs($booking->user)->get(route('receipts.download', $booking))->assertOk();

        $this->assertNotSame($original->file_path, $original->fresh()->file_path);
        $this->assertStringContainsString('Check-in:</strong> ' . $request->requested_check_in->format('M d, Y'), $this->receiptHtml[1]);
        $this->assertSame(1, Receipt::count());
        $this->get(route('receipts.download', $booking))->assertOk();
        $this->assertCount(2, $this->receiptHtml);
    }

    public function test_pending_and_declined_reschedules_keep_the_issued_receipt(): void
    {
        $booking = $this->booking($this->guest(), 'RECEIPT-3');
        [$payment, $original] = $this->prepareReceipt($booking);
        $request = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING);
        $this->assertSame($original->sha256_hash, app(ReceiptService::class)->issue($booking, $payment)->sha256_hash);

        $this->actingAs($this->frontDesk(), 'staff')->post(route('staff.reschedules.decline', $request), [
            'decision_note' => 'The requested dates are unavailable.',
        ])->assertSessionHas('success');

        $mail = new RescheduleDecidedMail($booking->fresh(), $request->fresh());
        $mail->build();
        $this->assertEmpty($mail->diskAttachments);
        $this->get(route('receipts.download', $booking))->assertOk();
        $this->assertSame($original->sha256_hash, $original->fresh()->sha256_hash);
        $this->assertCount(1, $this->receiptHtml);
    }

    public function test_receipt_failure_preserves_the_approved_move_and_download_can_retry(): void
    {
        $booking = $this->booking($this->guest(), 'RECEIPT-4');
        [$payment, $original] = $this->prepareReceipt($booking);
        $request = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING);
        $this->mock(ReceiptService::class, fn ($mock) => $mock->shouldReceive('issue')->twice()
            ->andThrow(new \RuntimeException('Storage unavailable')));

        $this->actingAs($this->frontDesk(), 'staff')->post(route('staff.reschedules.approve', $request))
            ->assertSessionHas('success')->assertSessionHas('error');

        $this->assertSame(RescheduleRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertTrue($booking->fresh()->check_in->equalTo($request->requested_check_in));
        $this->assertSame($original->sha256_hash, $original->fresh()->sha256_hash);
        $mail = new RescheduleDecidedMail($booking->fresh(), $request->fresh());
        $this->assertStringContainsString('Your stay has been moved', $mail->render());
        $this->assertEmpty($mail->diskAttachments);
        $this->app->forgetInstance(ReceiptService::class);
        $this->get(route('receipts.download', $booking))->assertOk();
        $this->assertNotSame($original->file_path, $original->fresh()->file_path);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config([
            'app.name' => 'Farmers Hostel',
            'app.url' => 'https://booking.example.test',
        ]);
    }

    private function guest(): User
    {
        $suffix = uniqid();

        return User::forceCreate([
            'username' => 'reschedule-' . $suffix,
            'email' => "reschedule-{$suffix}@example.test",
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);
    }

    private function frontDesk(): Staff
    {
        return Staff::create([
            'name' => 'Reschedule Reviewer',
            'email' => 'reschedule-reviewer-' . uniqid() . '@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'frontdesk',
            'is_suspended' => false,
        ]);
    }

    private function booking(User $guest, string $roomNumber): Booking
    {
        Room::create([
            'room_number' => $roomNumber,
            'room_type' => 'double',
            'wing' => 'rooster',
            'price' => 1600,
            'status' => 'available',
        ]);

        $booking = Booking::create([
            'user_id' => $guest->id,
            'expected_guests' => 2,
            'guest_name' => 'Reschedule Guest',
            'guest_address' => 'Science City of Muñoz',
            'guest_phone' => '09171234567',
            'check_in' => now()->addDays(14)->toDateString(),
            'check_out' => now()->addDays(16)->toDateString(),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 3200,
            'payable_amount' => 3200,
            'status' => Booking::STATUS_PAID,
        ]);

        Reservation::create([
            'booking_id' => $booking->id,
            'room_number' => $roomNumber,
            'room_type' => 'double',
            'capacity' => 2,
            'price' => 1600,
            'num_guests' => 2,
            'num_seniors' => 0,
        ]);

        return $booking->refresh();
    }

    private function reschedule(
        Booking $booking,
        string $status,
        int $moveByDays = 7,
        array $overrides = []
    ): RescheduleRequest {
        return RescheduleRequest::create(array_merge([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'status' => $status,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'requested_check_in' => $booking->check_in->copy()->addDays($moveByDays),
            'requested_check_out' => $booking->check_out->copy()->addDays($moveByDays),
            'reason' => 'The event dates changed.',
            'submitted_at' => now(),
            'reviewed_at' => $status === RescheduleRequest::STATUS_PENDING ? null : now(),
        ], $overrides));
    }

    private function requestPayload(Booking $booking, int $moveByDays = 7): array
    {
        return [
            'requested_check_in' => $booking->check_in->copy()->addDays($moveByDays)->toDateString(),
            'requested_check_out' => $booking->check_out->copy()->addDays($moveByDays)->toDateString(),
            'reason' => 'The event dates changed again.',
        ];
    }

    public function test_rescheduling_preserves_the_one_time_mattress_charge(): void
    {
        $booking = $this->booking($this->guest(), 'M-101');
        $booking->update(['extra_mattress' => 1, 'extra_mattress_amount' => 500, 'total_price' => 3700, 'payable_amount' => 3700]);
        $request = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 7, [
            'requested_check_out' => $booking->check_out->copy()->addDays(8),
        ]);
        $this->actingAs($this->frontDesk(), 'staff')->post(route('staff.reschedules.approve', $request))->assertSessionHas('success');
        $this->assertEquals(5300, (float) $booking->fresh()->total_price);
        $this->assertEquals(5300, (float) $booking->fresh()->payable_amount);
        $this->assertEquals(500, (float) $booking->fresh()->extra_mattress_amount);
    }

    public function test_an_approved_reschedule_permanently_blocks_another_guest_request(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'R-101');
        $this->reschedule($booking, RescheduleRequest::STATUS_APPROVED);

        $this->assertFalse(RescheduleRequest::isOpenFor($booking));

        $this->actingAs($guest)
            ->get(route('booking.reschedule.create', $booking))
            ->assertRedirect(route('booking.show', $booking))
            ->assertSessionHas('info', fn ($message) => str_contains($message, 'one allowed reschedule'));

        $this->actingAs($guest)
            ->post(route('booking.reschedule.store', $booking), $this->requestPayload($booking))
            ->assertRedirect(route('booking.show', $booking))
            ->assertSessionHas('info', fn ($message) => str_contains($message, 'one allowed reschedule'));

        $this->assertSame(1, RescheduleRequest::where('booking_id', $booking->id)->count());

        $this->actingAs($guest)
            ->get(route('booking.show', $booking))
            ->assertOk()
            ->assertSee('Reschedule used')
            ->assertDontSee('Request a reschedule');
    }

    public function test_declined_and_withdrawn_requests_do_not_consume_the_allowance(): void
    {
        foreach ([RescheduleRequest::STATUS_DECLINED, RescheduleRequest::STATUS_WITHDRAWN] as $index => $status) {
            $guest = $this->guest();
            $booking = $this->booking($guest, 'R-20' . $index);
            $this->reschedule($booking, $status);

            $this->assertTrue(RescheduleRequest::isOpenFor($booking));

            $this->actingAs($guest)
                ->post(route('booking.reschedule.store', $booking), $this->requestPayload($booking, 8))
                ->assertRedirect(route('booking.show', $booking))
                ->assertSessionHas('success');

            $this->assertSame(1, RescheduleRequest::where('booking_id', $booking->id)->pending()->count());
            $this->assertSame(2, RescheduleRequest::where('booking_id', $booking->id)->count());
        }
    }

    public function test_staff_cannot_approve_a_second_stale_request_for_the_same_booking(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'R-301');
        $first = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 7);
        $second = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 12);
        $staff = $this->frontDesk();

        $this->actingAs($staff, 'staff')
            ->post(route('staff.reschedules.approve', $first))
            ->assertSessionHas('success');

        $approvedCheckIn = $first->requested_check_in->toDateString();
        $approvedCheckOut = $first->requested_check_out->toDateString();

        $this->actingAs($staff, 'staff')
            ->post(route('staff.reschedules.approve', $second))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'one allowed reschedule'));

        $this->assertSame(1, RescheduleRequest::where('booking_id', $booking->id)->approved()->count());
        $this->assertSame(RescheduleRequest::STATUS_PENDING, $second->fresh()->status);
        $this->assertSame($approvedCheckIn, $booking->fresh()->check_in->toDateString());
        $this->assertSame($approvedCheckOut, $booking->fresh()->check_out->toDateString());
    }

    public function test_the_anniversary_checkin_is_allowed_and_the_next_day_is_rejected(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'YEAR-101');
        $last = RescheduleRequest::latestCheckInFor($booking)->startOfDay();
        $this->actingAs($guest)->post(route('booking.reschedule.store', $booking), [
            'requested_check_in' => $last->copy()->addDay()->toDateString(),
            'requested_check_out' => $last->copy()->addDays(3)->toDateString(),
            'reason' => 'Event postponed.',
        ])->assertSessionHasErrors('requested_check_in');
        $this->assertSame(0, RescheduleRequest::count());
        $this->actingAs($guest)->post(route('booking.reschedule.store', $booking), [
            'requested_check_in' => $last->toDateString(),
            'requested_check_out' => $last->copy()->addDays(2)->toDateString(),
            'reason' => 'Event postponed.',
        ])->assertSessionHas('success');
        $this->assertSame($last->toDateString(), RescheduleRequest::sole()->requested_check_in->toDateString());
    }

    public function test_one_year_uses_the_calendar_anniversary_without_leap_day_overflow(): void
    {
        $booking = new Booking(['check_in' => '2028-02-29']);
        $this->assertSame('2029-02-28', RescheduleRequest::latestCheckInFor($booking)->toDateString());
    }

    public function test_a_year_of_new_dates_does_not_extend_the_request_deadline(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'YEAR-102');
        $this->travelTo(RescheduleRequest::deadlineFor($booking)->addMinute());
        $this->actingAs($guest)->post(route('booking.reschedule.store', $booking), $this->requestPayload($booking))
            ->assertSessionHas('error');
        $this->assertSame(0, RescheduleRequest::count());
        $this->travelBack();
    }

    public function test_staff_cannot_approve_a_request_beyond_the_year_limit(): void
    {
        $booking = $this->booking($this->guest(), 'YEAR-103');
        $request = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 7, [
            'requested_check_in' => RescheduleRequest::latestCheckInFor($booking)->addDay(),
            'requested_check_out' => RescheduleRequest::latestCheckInFor($booking)->addDays(3),
        ]);
        $this->actingAs($this->frontDesk(), 'staff')->post(route('staff.reschedules.approve', $request))
            ->assertSessionHas('error');
        $this->assertSame(RescheduleRequest::STATUS_PENDING, $request->fresh()->status);
    }

    public function test_approval_email_says_the_one_reschedule_has_been_used(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'R-401');
        $reschedule = $this->reschedule($booking, RescheduleRequest::STATUS_APPROVED);

        $text = html_entity_decode(strip_tags((new RescheduleDecidedMail($booking, $reschedule))->render()));

        $this->assertStringContainsStringIgnoringCase('one reschedule', $text);
        $this->assertStringContainsStringIgnoringCase('dates above are now final', $text);
        $this->assertStringNotContainsStringIgnoringCase('same rule applies to your new dates', $text);
    }
}
