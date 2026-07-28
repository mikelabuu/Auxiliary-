<?php

namespace Tests\Feature\Discount;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\DiscountFile;
use App\Models\Reservation;
use App\Models\RoomType;
use Database\Factories\DiscountFactory;
use Database\Factories\ReservationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * The senior-citizen / PWD discount workflow.
 *
 * A guest declares seniors at booking, uploads an ID per senior, and staff
 * approve or reject each document. On the final decision the booking's
 * `payable_amount` is rewritten and payment is unblocked.
 *
 * This is money logic spanning three tables (`discounts`, `discount_files`,
 * `bookings`) plus private file storage, and it is the last large flow with no
 * coverage. It is also a statutory discount, so getting the arithmetic wrong is
 * a compliance problem as well as an accounting one.
 */
class DiscountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Make::catalog();
        Make::rooms(['101', '102'], 'double');   // 1800/night, sleeps 2
    }

    /**
     * A booking that declared seniors and is parked awaiting proof.
     * Two nights, one double room.
     */
    private function bookingAwaitingProof(int $seniors = 1, int $guests = 2, int $capacity = 2, string $roomType = 'double'): Booking
    {
        $booking = Booking::factory()->create([
            'user_id'        => Make::user()->id,
            'status'         => 'pending_discount',
            'wants_discount' => true,
            'num_seniors'    => $seniors,
            'expected_guests' => $guests,
            'total_price'    => 3600.00,
            'check_in'       => now('Asia/Manila')->addDay()->toDateString(),
            'check_out'      => now('Asia/Manila')->addDays(3)->toDateString(),
        ]);

        ReservationFactory::new()->forBooking($booking)->room('101', $roomType)->create([
            'num_guests'  => $guests,
            'num_seniors' => $seniors,
            'capacity'    => $capacity,
        ]);

        return $booking;
    }

    /**
     * PNG rather than JPEG deliberately: the bundled GD in this XAMPP build
     * has PNG support but not JPEG, so `fake()->image('x.jpg')` throws
     * "imagejpeg function is not defined". The upload rule accepts both.
     */
    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('senior-id.png');
    }

    // ------------------------------------------------------- guest: submit

    public function test_the_owner_can_open_the_discount_form(): void
    {
        $booking = $this->bookingAwaitingProof();

        $this->actingAs($booking->user)->get("/discount/{$booking->id}/create")->assertOk();
    }

    public function test_a_guest_can_submit_ids_for_their_seniors(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [$this->image()]],
        ])->assertRedirect(route('booking.show', $booking->id));

        $discount = $booking->fresh()->discountRequest;

        $this->assertNotNull($discount);
        $this->assertSame('pending', $discount->status);
        $this->assertSame(1, $discount->files()->count());
        $this->assertSame('pending', $discount->files()->first()->status);
    }

    /**
     * Senior IDs are government identity documents. They must not land
     * anywhere the public disk can serve.
     */
    public function test_uploaded_ids_are_stored_off_the_public_disk(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [$this->image()]],
        ]);

        $path = $booking->fresh()->discountRequest->files()->first()->file_path;

        $this->assertStringNotContainsString('public', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_a_booking_with_no_seniors_cannot_request_a_discount(): void
    {
        $booking     = $this->bookingAwaitingProof(seniors: 0);
        $reservation = $booking->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [$this->image()]],
        ]);

        $this->assertNull($booking->fresh()->discountRequest);
    }

    public function test_a_second_discount_request_is_refused(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();
        DiscountFactory::new()->forBooking($booking)->create();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [$this->image()]],
        ]);

        $this->assertSame(1, Discount::where('booking_id', $booking->id)->count());
    }

    public function test_a_submission_with_no_files_is_rejected(): void
    {
        $booking = $this->bookingAwaitingProof();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [])
            ->assertSessionHasErrors('discount_files');

        $this->assertNull($booking->fresh()->discountRequest);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [UploadedFile::fake()->create('payload.php', 40)]],
        ])->assertSessionHasErrors();

        $this->assertNull($booking->fresh()->discountRequest);
    }

    public function test_an_oversized_upload_is_rejected(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            // A well-formed image that is simply too big, so only max:2048 trips.
            'discount_files' => [$reservation->id => [UploadedFile::fake()->create('huge.png', 4096, 'image/png')]],
        ])->assertSessionHasErrors();
    }

    public function test_another_guest_cannot_submit_against_someone_elses_booking(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();

        $this->actingAs(Make::user())->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [$this->image()]],
        ])->assertForbidden();

        $this->assertNull($booking->fresh()->discountRequest);
    }

    /**
     * The array keys in the upload are reservation ids chosen by the client.
     * A guest must not be able to tag a document against a room that belongs to
     * someone else's booking — the id goes straight into a foreign key column.
     */
    public function test_files_cannot_be_attached_to_another_bookings_reservation(): void
    {
        $booking              = $this->bookingAwaitingProof();
        $strangersReservation = $this->bookingAwaitingProof()->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$strangersReservation->id => [$this->image()]],
        ])->assertSessionHasErrors('discount_files');

        $this->assertNull($booking->fresh()->discountRequest);
        $this->assertSame(
            0,
            DiscountFile::where('reservation_id', $strangersReservation->id)->count(),
            'A discount file was tagged with a reservation belonging to another booking.',
        );
    }

    // ------------------------------------------------------- guest: cancel

    public function test_the_owner_can_withdraw_a_pending_request(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = DiscountFactory::new()->forBooking($booking)->create();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}/cancel");

        $this->assertNull($booking->fresh()->discountRequest);
        $this->assertSame('pending_payment', $booking->fresh()->status);
        $this->assertEquals(3600.00, $booking->fresh()->payable_amount);
    }

    public function test_a_reviewed_request_cannot_be_withdrawn(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = DiscountFactory::new()->forBooking($booking)->approved()->create();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}/cancel");

        $this->assertNotNull($booking->fresh()->discountRequest);
        $this->assertSame('approved', $discount->fresh()->status);
    }

    public function test_another_guest_cannot_withdraw_a_request(): void
    {
        $booking = $this->bookingAwaitingProof();
        DiscountFactory::new()->forBooking($booking)->create();

        $this->actingAs(Make::user())->post("/discount/{$booking->id}/cancel")->assertForbidden();

        $this->assertNotNull($booking->fresh()->discountRequest);
    }

    // ------------------------------------------------------ staff: review

    /**
     * Build a submitted request with $count pending ID files.
     */
    private function submitted(Booking $booking, int $count = 1): Discount
    {
        $discount    = DiscountFactory::new()->forBooking($booking)->create();
        $reservation = $booking->reservations->first();

        for ($i = 0; $i < $count; $i++) {
            $discount->files()->create([
                'reservation_id' => $reservation->id,
                'file_path'      => "discount_temp/id-{$booking->id}-{$i}.jpg",
                'uploaded_at'    => now(),
                'status'         => 'pending',
            ]);
        }

        return $discount;
    }

    public function test_staff_can_approve_an_individual_file(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        $this->actingAs(Make::staff('admin'), 'staff')
            ->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");

        $this->assertSame('approved', $file->fresh()->status);
        $this->assertNotNull($file->fresh()->reviewed_by);
    }

    public function test_a_file_cannot_be_reviewed_twice(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/reject");

        $this->assertSame('approved', $file->fresh()->status, 'A reviewed file was overwritten by a second decision.');
    }

    public function test_a_file_from_another_request_cannot_be_reviewed(): void
    {
        $mine      = $this->submitted($this->bookingAwaitingProof());
        $theirs    = $this->submitted($this->bookingAwaitingProof());
        $foreign   = $theirs->files->first();

        $this->actingAs(Make::staff('admin'), 'staff')
            ->post("/staff/discounts/{$mine->id}/file/{$foreign->id}/approve")
            ->assertForbidden();

        $this->assertSame('pending', $foreign->fresh()->status);
    }

    public function test_a_request_cannot_be_finalised_while_files_are_unreviewed(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking, count: 2);

        $this->actingAs(Make::staff('admin'), 'staff')
            ->post("/staff/discounts/{$discount->id}/approve");

        $this->assertSame('pending', $discount->fresh()->status);
        $this->assertSame('pending_discount', $booking->fresh()->status);
    }

    public function test_approving_a_request_applies_the_discount_and_unblocks_payment(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        $booking = $booking->fresh();

        // per-head = (1800 / 2 beds) × 2 nights = 1800; 20% = 360
        $this->assertSame('approved', $discount->fresh()->status);
        $this->assertEquals(360.00, $discount->fresh()->amount);
        $this->assertEquals(360.00, $booking->discount);
        $this->assertEquals(3240.00, $booking->payable_amount);
        $this->assertSame('pending_payment', $booking->status);
    }

    public function test_rejecting_a_request_restores_the_full_price(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking);

        $this->actingAs(Make::staff('admin'), 'staff')
            ->post("/staff/discounts/{$discount->id}/reject");

        $booking = $booking->fresh();

        $this->assertSame('rejected', $discount->fresh()->status);
        $this->assertEquals(0.00, $booking->discount);
        $this->assertEquals(3600.00, $booking->payable_amount);
        $this->assertSame('pending_payment', $booking->status);
    }

    public function test_a_rejected_id_earns_no_discount(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/reject");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        $this->assertEquals(0.00, $discount->fresh()->amount);
        $this->assertEquals(3600.00, $booking->fresh()->payable_amount);
    }

    public function test_a_finalised_request_cannot_be_reviewed_again(): void
    {
        $booking  = $this->bookingAwaitingProof();
        $discount = $this->submitted($booking);

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/reject");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        $this->assertSame('rejected', $discount->fresh()->status, 'A finalised request was re-decided.');
        $this->assertEquals(3600.00, $booking->fresh()->payable_amount);
    }

    public function test_front_desk_staff_cannot_review_discounts(): void
    {
        $discount = $this->submitted($this->bookingAwaitingProof());

        $this->actingAs(Make::staff('frontdesk'), 'staff')
            ->post("/staff/discounts/{$discount->id}/approve")
            ->assertForbidden();

        $this->assertSame('pending', $discount->fresh()->status);
    }

    public function test_a_guest_cannot_review_discounts(): void
    {
        $discount = $this->submitted($this->bookingAwaitingProof());

        $this->actingAs(Make::user())
            ->post("/staff/discounts/{$discount->id}/approve")
            ->assertRedirect();

        $this->assertSame('pending', $discount->fresh()->status);
    }

    // --------------------------------------------------- the calculation

    public function test_two_approved_seniors_earn_twice_the_discount(): void
    {
        $booking  = $this->bookingAwaitingProof(seniors: 2, guests: 2);
        $discount = $this->submitted($booking, count: 2);

        $staff = Make::staff('admin');
        foreach ($discount->files as $file) {
            $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        }
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        $this->assertEquals(720.00, $discount->fresh()->amount);
        $this->assertEquals(2880.00, $booking->fresh()->payable_amount);
    }

    /**
     * The declared senior count is the ceiling. Approving more IDs than the
     * guest declared seniors for a room must not earn extra discount — the
     * figure is validated at booking against both capacity and total guests,
     * and it is what the quoted price was based on.
     *
     * If the business rule is ever inverted (staff verify reality at the desk;
     * the declared figure is only an estimate) this is the test to change,
     * alongside the one-word clamp in DiscountService.
     */
    public function test_the_discount_cannot_exceed_the_declared_senior_count(): void
    {
        $booking  = $this->bookingAwaitingProof(seniors: 1, guests: 2);
        $discount = $this->submitted($booking, count: 2);

        $staff = Make::staff('admin');
        foreach ($discount->files as $file) {
            $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        }
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        $this->assertEquals(
            360.00,
            $discount->fresh()->amount,
            'One declared senior earned two senior discounts.',
        );
    }

    /**
     * The per-head rate comes from the reservation, which records what the
     * guest was quoted. A booking already sold at two-to-a-room must keep its
     * discount even if an admin later reconfigures that room type — otherwise
     * a pricing tweak silently rewrites the value of every pending request.
     */
    public function test_a_later_capacity_change_does_not_alter_an_existing_booking(): void
    {
        $booking  = $this->bookingAwaitingProof(seniors: 1, guests: 2, capacity: 2);
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        RoomType::where('slug', 'double')->update(['capacity' => 3]);

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        // Sold as a 2-bed room: (1800 / 2) × 2 nights = 1800 per head; 20% = 360
        $this->assertEquals(360.00, $discount->fresh()->amount);
    }

    /**
     * And the converse: a reservation recorded against three beds is
     * discounted at a third of the room, not a hardcoded half.
     */
    public function test_the_discount_follows_the_capacity_the_room_was_sold_at(): void
    {
        $booking  = $this->bookingAwaitingProof(seniors: 1, guests: 2, capacity: 3);
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        // (1800 / 3) × 2 nights = 1200 per head; 20% = 240
        $this->assertEquals(240.00, $discount->fresh()->amount);
    }

    /**
     * A room type the calculator does not recognise must not collapse to a
     * capacity of one — that would charge the discount against the entire room
     * rate rather than one guest's share, roughly doubling it on a double.
     */
    public function test_an_unfamiliar_room_type_does_not_inflate_the_per_head_rate(): void
    {
        $booking  = $this->bookingAwaitingProof(seniors: 1, guests: 2, capacity: 2, roomType: 'garden-suite');
        $discount = $this->submitted($booking);
        $file     = $discount->files->first();

        $staff = Make::staff('admin');
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/file/{$file->id}/approve");
        $this->actingAs($staff, 'staff')->post("/staff/discounts/{$discount->id}/approve");

        $this->assertEquals(
            360.00,
            $discount->fresh()->amount,
            'An unrecognised room type was treated as a single-occupancy room.',
        );
    }

    /**
     * The IDs are identity documents with no reason to outlive the decision.
     * Both approve and reject delete them from storage.
     */
    public function test_the_id_images_are_deleted_once_a_decision_is_made(): void
    {
        $booking     = $this->bookingAwaitingProof();
        $reservation = $booking->reservations->first();

        $this->actingAs($booking->user)->post("/discount/{$booking->id}", [
            'discount_files' => [$reservation->id => [$this->image()]],
        ]);

        $discount = $booking->fresh()->discountRequest;
        $path     = $discount->files()->first()->file_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs(Make::staff('admin'), 'staff')
            ->post("/staff/discounts/{$discount->id}/reject");

        Storage::disk('local')->assertMissing($path);
    }
}
