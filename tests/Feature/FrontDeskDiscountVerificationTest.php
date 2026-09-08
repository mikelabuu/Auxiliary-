<?php

namespace Tests\Feature;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\DiscountFile;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontDeskDiscountVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, string $email): Staff
    {
        return Staff::create([
            'name' => ucfirst(str_replace('_', ' ', $role)) . ' Tester',
            'email' => $email,
            'password' => 'correct-horse-battery',
            'role' => $role,
            'is_suspended' => false,
        ]);
    }

    /**
     * @return array{0: User, 1: Booking, 2: Discount, 3: DiscountFile}
     */
    private function pendingDiscount(): array
    {
        $guest = User::forceCreate([
            'username' => 'discount-guest',
            'email' => 'discount-guest@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'room_number' => 'D-101',
            'room_type' => 'double',
            'wing' => 'Main',
            'status' => 'available',
            'price' => 2000,
        ]);

        $booking = Booking::create([
            'user_id' => $guest->id,
            'expected_guests' => 2,
            'guest_name' => 'Discount Guest',
            'guest_address' => 'Science City of Muñoz',
            'guest_phone' => '09171234567',
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'discount' => 0,
            'num_seniors' => 1,
            'total_price' => 2000,
            'payable_amount' => 2000,
            'wants_discount' => true,
            'status' => Booking::STATUS_PENDING_DISCOUNT,
        ]);

        $reservation = Reservation::create([
            'booking_id' => $booking->id,
            'room_number' => $room->room_number,
            'room_type' => $room->room_type,
            'capacity' => 2,
            'price' => 2000,
            'num_guests' => 2,
            'num_seniors' => 1,
        ]);

        $booking->rooms()->attach($room->id);

        $discount = Discount::create([
            'booking_id' => $booking->id,
            'amount' => 0,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        Storage::disk('local')->put('discount_temp/original-id.png', 'image bytes');

        $file = DiscountFile::create([
            'discount_id' => $discount->id,
            'reservation_id' => $reservation->id,
            'file_path' => 'discount_temp/original-id.png',
            'uploaded_at' => now(),
            'status' => 'pending',
        ]);

        return [$guest, $booking, $discount, $file];
    }

    public function test_frontdesk_and_admin_can_open_discount_verification_but_cashier_cannot(): void
    {
        $frontdesk = $this->staff('frontdesk', 'frontdesk-discounts@example.test');
        $admin = $this->staff('admin', 'admin-discounts@example.test');
        $cashier = $this->staff('cashier', 'cashier-discounts@example.test');

        $this->actingAs($frontdesk, 'staff')
            ->get(route('staff.discounts.index'))
            ->assertOk()
            ->assertSee('original Senior Citizen / PWD IDs in person');

        $this->actingAs($admin, 'staff')
            ->get(route('staff.discounts.index'))
            ->assertOk();

        $this->actingAs($cashier, 'staff')
            ->get(route('staff.discounts.index'))
            ->assertForbidden();
    }

    public function test_discount_cannot_be_granted_without_confirming_the_original_id_check(): void
    {
        Storage::fake('local');
        [, $booking, $discount, $file] = $this->pendingDiscount();
        $frontdesk = $this->staff('frontdesk', 'frontdesk-check@example.test');

        $this->actingAs($frontdesk, 'staff')
            ->post(route('staff.discounts.file.approve', [$discount, $file]))
            ->assertSessionHas('success');

        $this->post(route('staff.discounts.approve', $discount))
            ->assertSessionHasErrors('original_ids_verified');

        $this->assertSame('pending', $discount->fresh()->status);
        $this->assertSame(Booking::STATUS_PENDING_DISCOUNT, $booking->fresh()->status);
    }

    public function test_frontdesk_can_verify_the_discount_then_take_only_in_person_payment(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$guest, $booking, $discount, $file] = $this->pendingDiscount();
        $frontdesk = $this->staff('frontdesk', 'frontdesk-flow@example.test');

        $this->actingAs($frontdesk, 'staff')
            ->post(route('staff.discounts.file.approve', [$discount, $file]))
            ->assertSessionHas('success');

        $this->post(route('staff.discounts.approve', $discount), [
            'original_ids_verified' => '1',
        ])->assertRedirect(route('staff.discounts.index'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $discount->fresh()->status);
        $this->assertSame(200.0, (float) $discount->fresh()->amount);
        $this->assertSame(Booking::STATUS_PENDING_PAYMENT, $booking->fresh()->status);
        $this->assertTrue((bool) $booking->fresh()->wants_discount);
        $this->assertSame(1800.0, (float) $booking->fresh()->payable_amount);

        $this->actingAs($guest)
            ->get(route('bookings.pay', $booking))
            ->assertRedirect(route('booking.show', $booking))
            ->assertSessionHas('error', 'A Senior Citizen / PWD booking is settled at our front desk. Bring the original ID for every discounted guest — we cannot take this payment online.');

        $this->actingAs($frontdesk, 'staff')
            ->post(route('frontdesk.booking.settle', $booking), [
                'method' => 'cash',
                'reference' => 'OR-1001',
            ])->assertSessionHas('success');

        $payment = Payment::where('booking_id', $booking->id)->sole();

        $this->assertSame(Booking::STATUS_PAID, $booking->fresh()->status);
        $this->assertSame('success', $payment->status);
        $this->assertSame('cash', $payment->gateway);
        $this->assertSame($frontdesk->id, $payment->verified_by);
        Mail::assertSent(BookingPaidMail::class, 1);
    }
}
