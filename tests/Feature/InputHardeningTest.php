<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression cover for batch 3 — input handling.
 *
 * The room boards build their markup as HTML strings from room fields that
 * staff type in freely, so a room named `"><img src=x onerror=...>` executed
 * script in every front-desk browser. The views escape now; these tests cover
 * the other half — keeping the payload out of the column in the first place.
 */
class InputHardeningTest extends TestCase
{
    use RefreshDatabase;

    public const PAYLOADS = [
        '"><img src=x onerror=alert(1)>',
        "' onfocus=alert(1) autofocus x='",
        '</script><script>alert(1)</script>',
        '<svg/onload=alert(1)>',
    ];

    private function admin(): Staff
    {
        return Staff::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password-12345',
            'role' => 'master_admin',
            'is_suspended' => false,
        ]);
    }

    // ---------------------------------------------------------------
    // Room fields
    // ---------------------------------------------------------------

    public function test_room_number_rejects_markup(): void
    {
        $admin = $this->admin();

        foreach (self::PAYLOADS as $payload) {
            $this->actingAs($admin, 'staff')
                ->post('/staff/rooms/store', [
                    'room_number' => $payload,
                    'room_type' => 'deluxe',
                    'wing' => 'rooster',
                    'price' => 1000,
                ])
                ->assertSessionHasErrors('room_number');
        }

        $this->assertSame(0, Room::count());
    }

    public function test_room_type_and_wing_reject_markup(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->post('/staff/rooms/store', [
                'room_number' => '101',
                'room_type' => '<svg/onload=alert(1)>',
                'wing' => 'rooster',
                'price' => 1000,
            ])
            ->assertSessionHasErrors('room_type');

        $this->actingAs($admin, 'staff')
            ->post('/staff/rooms/store', [
                'room_number' => '101',
                'room_type' => 'deluxe',
                'wing' => '"><img src=x>',
                'price' => 1000,
            ])
            ->assertSessionHasErrors('wing');

        $this->assertSame(0, Room::count());
    }

    /** The rule must not be so tight that real room numbers stop working. */
    public function test_legitimate_room_labels_are_still_accepted(): void
    {
        $admin = $this->admin();

        foreach ([['101', 'deluxe', 'rooster'], ['A-12', 'double', 'chev_re'], ['210 B', 'dormitory1', 'tumana']] as $i => [$number, $type, $wing]) {
            $this->actingAs($admin, 'staff')
                ->post('/staff/rooms/store', [
                    'room_number' => $number,
                    'room_type' => $type,
                    'wing' => $wing,
                    'price' => 1000,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Room::count());
    }

    public function test_room_type_name_rejects_markup(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->post('/staff/room-types', [
                'name' => '<img src=x onerror=alert(1)>',
                'base_price' => 1000,
                'capacity' => 2,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, RoomType::count());
    }

    public function test_legitimate_room_type_names_are_accepted(): void
    {
        $admin = $this->admin();

        foreach (['Deluxe', 'Dormitory1', 'Bed & Breakfast', 'Twin-Share'] as $name) {
            $this->actingAs($admin, 'staff')
                ->post('/staff/room-types', [
                    'name' => $name,
                    'base_price' => 1000,
                    'capacity' => 2,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(4, RoomType::count());
    }

    // ---------------------------------------------------------------
    // Discount file uploads
    // ---------------------------------------------------------------

    public function test_discount_files_cannot_be_filed_against_another_bookings_reservation(): void
    {
        Storage::fake('local');

        $owner = User::create([
            'username' => 'owner', 'email' => 'owner@example.test',
            'password' => Hash::make('password-12345'),
        ]);
        $owner->email_verified_at = now();
        $owner->save();

        $stranger = User::create([
            'username' => 'stranger', 'email' => 'stranger@example.test',
            'password' => Hash::make('password-12345'),
        ]);

        $booking = Booking::create([
            'user_id' => $owner->id, 'guest_name' => 'Owner', 'guest_address' => 'X',
            'guest_phone' => '09000000000', 'check_in' => now()->addDays(3),
            'check_out' => now()->addDays(4), 'discount' => 0, 'num_seniors' => 1,
            'total_price' => 1000, 'payable_amount' => 1000, 'status' => 'pending_payment',
        ]);

        $mine = Reservation::create([
            'booking_id' => $booking->id, 'room_number' => '101', 'room_type' => 'deluxe',
            'capacity' => 2, 'price' => 1000, 'num_seniors' => 1, 'num_guests' => 1,
        ]);

        $strangersBooking = Booking::create([
            'user_id' => $stranger->id, 'guest_name' => 'Stranger', 'guest_address' => 'Y',
            'guest_phone' => '09111111111', 'check_in' => now()->addDays(3),
            'check_out' => now()->addDays(4), 'discount' => 0, 'num_seniors' => 1,
            'total_price' => 1000, 'payable_amount' => 1000, 'status' => 'pending_payment',
        ]);

        $theirs = Reservation::create([
            'booking_id' => $strangersBooking->id, 'room_number' => '202', 'room_type' => 'double',
            'capacity' => 2, 'price' => 1000, 'num_seniors' => 1, 'num_guests' => 1,
        ]);

        $this->actingAs($owner)->post("/discount/{$booking->id}", [
            'discount_files' => [
                $mine->id   => [UploadedFile::fake()->image('mine.jpg')],
                $theirs->id => [UploadedFile::fake()->image('theirs.jpg')],
            ],
        ]);

        $discount = Discount::where('booking_id', $booking->id)->firstOrFail();
        $reservationIds = $discount->files()->pluck('reservation_id')->all();

        $this->assertContains($mine->id, $reservationIds);
        $this->assertNotContains(
            $theirs->id,
            $reservationIds,
            'a file was filed against a reservation belonging to another booking'
        );
    }

    // ---------------------------------------------------------------
    // Report input
    // ---------------------------------------------------------------

    public function test_report_endpoint_rejects_malformed_input_instead_of_500ing(): void
    {
        $admin = $this->admin();

        // Previously $request->all() went straight through and an unknown
        // report_type surfaced as an unhandled exception.
        $this->actingAs($admin, 'staff')
            ->postJson('/staff/reports/generate', [])
            ->assertStatus(422);

        $this->actingAs($admin, 'staff')
            ->postJson('/staff/reports/generate', [
                'report_type' => 'not_a_real_report',
                'date_range' => ['type' => 'yearly', 'value' => '2026'],
            ])
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Settings password flow
    // ---------------------------------------------------------------

    public function test_password_only_update_succeeds_without_a_validation_error(): void
    {
        $user = User::create([
            'username' => 'guest1', 'email' => 'guest@example.test',
            'password' => Hash::make('old-password-1'),
        ]);
        $user->email_verified_at = now();
        $user->save();

        // No username/email in the payload — this used to change the password
        // and *then* fail the profile-block validation.
        $this->actingAs($user)
            ->put('/settings', [
                'current_password' => 'old-password-1',
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_wrong_current_password_does_not_change_anything(): void
    {
        $user = User::create([
            'username' => 'guest1', 'email' => 'guest@example.test',
            'password' => Hash::make('old-password-1'),
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->actingAs($user)
            ->put('/settings', [
                'current_password' => 'not-the-password',
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }
}
