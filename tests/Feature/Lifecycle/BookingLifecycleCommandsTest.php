<?php

namespace Tests\Feature\Lifecycle;

use App\Models\Booking;
use App\Models\Checkout;
use App\Models\ExpiryLog;
use App\Models\NoShowLog;
use App\Models\Room;
use Carbon\Carbon;
use Database\Factories\ReservationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * The three scheduled commands that move bookings through their lifecycle
 * without anyone clicking anything:
 *
 *   bookings:expire        every minute
 *   bookings:mark-no-show  00:05 Manila, daily
 *   bookings:autocheckout  every 30 min, no-ops before 14:00 Manila
 *
 * Nobody watches these run, and a booking they mishandle is inventory the
 * hotel either cannot sell or sells twice. They are also the most
 * timezone-sensitive code in the system: the app runs in UTC while every
 * business rule is written in Asia/Manila (UTC+8).
 */
class BookingLifecycleCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::rooms(['101', '102'], 'double');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------ expiry

    public function test_an_unpaid_booking_past_its_window_is_expired(): void
    {
        $booking = Booking::factory()->stalePayment(61)->create();

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame('expired', $booking->fresh()->status);
    }

    public function test_an_unpaid_booking_inside_its_window_is_left_alone(): void
    {
        $booking = Booking::factory()->stalePayment(30)->create();

        $this->artisan('bookings:expire');

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    /**
     * The window is configurable, and the command must honour the config
     * rather than a hardcoded hour.
     */
    public function test_the_expiry_window_follows_the_configured_value(): void
    {
        config(['bookings.expiry_minutes' => 15]);

        $booking = Booking::factory()->stalePayment(20)->create();

        $this->artisan('bookings:expire');

        $this->assertSame('expired', $booking->fresh()->status);
    }

    public function test_expiring_a_booking_writes_an_audit_trail(): void
    {
        $booking = Booking::factory()->stalePayment(61)->create();

        $this->artisan('bookings:expire');

        $log = ExpiryLog::where('booking_id', $booking->id)->first();

        $this->assertNotNull($log, 'Every automatic expiry must be logged.');
        $this->assertSame('pending_payment', $log->previous_status);
        $this->assertSame('expired', $log->new_status);
    }

    /**
     * The outstanding payment attempt must be closed out too, or the payment
     * log keeps a pending row against a dead booking forever.
     */
    public function test_expiring_a_booking_fails_its_pending_payment(): void
    {
        $booking = Booking::factory()->stalePayment(61)->create();
        $payment = Make::payment($booking, 'pending');

        $this->artisan('bookings:expire');

        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_expiry_never_touches_a_paid_booking(): void
    {
        $booking = Booking::factory()->create([
            'status'                => 'paid',
            'pending_payment_since' => now()->subDay(),
        ]);

        $this->artisan('bookings:expire');

        $this->assertSame('paid', $booking->fresh()->status);
    }

    /**
     * Expiry releases the rooms. A booking that expires but keeps blocking its
     * rooms is unsellable inventory nobody will notice.
     */
    public function test_an_expired_booking_releases_its_rooms(): void
    {
        $in  = now('Asia/Manila')->addDay()->toDateString();
        $out = now('Asia/Manila')->addDays(3)->toDateString();

        $booking = Make::bookingHolding(['101'], 'pending_payment', $in, $out);

        // forceFill, not update(): `pending_payment_since` is deliberately not
        // fillable — the status mutator owns it — so a guarded update is a no-op.
        $booking->forceFill(['pending_payment_since' => now()->subMinutes(61)])->save();

        $this->artisan('bookings:expire');

        $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => $in,
            'check_out' => $out,
        ])->assertJsonPath('rooms.0.status', 'available');
    }

    // ----------------------------------------------------------- no-show

    public function test_a_paid_booking_whose_checkin_day_has_passed_becomes_a_no_show(): void
    {
        $booking = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => now('Asia/Manila')->subDays(2)->toDateString(),
            'check_out' => now('Asia/Manila')->subDay()->toDateString(),
        ]);

        $this->artisan('bookings:mark-no-show')->assertSuccessful();

        $this->assertSame('no_show', $booking->fresh()->status);
    }

    /**
     * A guest arriving today has not failed to show up yet. Marking them a
     * no-show while they are on the road cancels a stay they paid for.
     */
    public function test_a_guest_arriving_today_is_not_a_no_show(): void
    {
        $booking = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => now('Asia/Manila')->toDateString(),
            'check_out' => now('Asia/Manila')->addDays(2)->toDateString(),
        ]);

        $this->artisan('bookings:mark-no-show');

        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_a_future_booking_is_not_a_no_show(): void
    {
        $booking = Booking::factory()->create([
            'status'   => 'paid',
            'check_in' => now('Asia/Manila')->addDays(3)->toDateString(),
        ]);

        $this->artisan('bookings:mark-no-show');

        $this->assertSame('paid', $booking->fresh()->status);
    }

    /**
     * TIMEZONE PROBE.
     *
     * The command compares against Carbon::now('Asia/Manila')->toDateString(),
     * while the scheduler fires at 00:05 Manila. Between 16:00 and 23:59 UTC
     * the Manila date is already tomorrow. This pins the intended behaviour at
     * the exact moment the job runs: a guest who checks in *today* Manila time
     * must survive the 00:05 sweep.
     */
    public function test_the_no_show_sweep_respects_manila_dates_at_the_scheduled_hour(): void
    {
        // 00:05 Manila on the 10th == 16:05 UTC on the 9th.
        Carbon::setTestNow(Carbon::parse('2026-08-09 16:05:00', 'UTC'));

        $arrivingToday = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => '2026-08-10',
            'check_out' => '2026-08-12',
        ]);

        $missedYesterday = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => '2026-08-09',
            'check_out' => '2026-08-11',
        ]);

        $this->artisan('bookings:mark-no-show');

        $this->assertSame(
            'paid',
            $arrivingToday->fresh()->status,
            'A guest arriving today (Manila) was marked a no-show by the 00:05 sweep.',
        );
        $this->assertSame('no_show', $missedYesterday->fresh()->status);
    }

    public function test_a_no_show_is_logged(): void
    {
        $booking = Booking::factory()->create([
            'status'   => 'paid',
            'check_in' => now('Asia/Manila')->subDays(2)->toDateString(),
        ]);

        $this->artisan('bookings:mark-no-show');

        $this->assertNotNull(NoShowLog::where('booking_id', $booking->id)->first());
    }

    /**
     * Only `paid` bookings can be no-shows. An `active` booking means the guest
     * is physically in the room.
     */
    public function test_an_active_stay_is_never_marked_a_no_show(): void
    {
        $booking = Booking::factory()->create([
            'status'   => 'active',
            'check_in' => now('Asia/Manila')->subDays(2)->toDateString(),
        ]);

        $this->artisan('bookings:mark-no-show');

        $this->assertSame('active', $booking->fresh()->status);
    }

    // ----------------------------------------------------- auto-checkout

    public function test_auto_checkout_skips_before_two_pm_manila(): void
    {
        // 09:00 Manila == 01:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-08-10 01:00:00', 'UTC'));

        $booking = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-08',
            'check_out' => '2026-08-10',
        ]);

        $this->artisan('bookings:autocheckout');

        $this->assertSame('active', $booking->fresh()->status);
    }

    public function test_auto_checkout_runs_after_two_pm_manila(): void
    {
        // 15:00 Manila == 07:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:00:00', 'UTC'));

        $booking = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-08',
            'check_out' => '2026-08-10',
        ]);

        $this->artisan('bookings:autocheckout')->assertSuccessful();

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_the_force_flag_bypasses_the_time_guard(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 01:00:00', 'UTC'));   // 09:00 Manila

        $booking = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-08',
            'check_out' => '2026-08-10',
        ]);

        $this->artisan('bookings:autocheckout', ['--force' => true]);

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_a_stay_still_in_progress_is_not_checked_out(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:00:00', 'UTC'));   // 15:00 Manila

        $booking = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-09',
            'check_out' => '2026-08-14',
        ]);

        $this->artisan('bookings:autocheckout');

        $this->assertSame('active', $booking->fresh()->status);
    }

    public function test_auto_checkout_releases_the_room(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:00:00', 'UTC'));

        Room::where('room_number', '101')->update(['status' => 'occupied']);

        $booking = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-08',
            'check_out' => '2026-08-10',
        ]);
        ReservationFactory::new()->forBooking($booking)->room('101', 'double')->create();

        $this->artisan('bookings:autocheckout');

        $this->assertSame('available', Room::where('room_number', '101')->first()->status);
    }

    /**
     * Catching up on a backlog must not free a room that a newer active stay
     * already occupies — that would hand an occupied room to the next guest.
     */
    public function test_auto_checkout_leaves_a_room_held_by_a_newer_stay_occupied(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:00:00', 'UTC'));

        Room::where('room_number', '101')->update(['status' => 'occupied']);

        $old = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-05',
            'check_out' => '2026-08-08',
        ]);
        ReservationFactory::new()->forBooking($old)->room('101', 'double')->create();

        $current = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-09',
            'check_out' => '2026-08-14',
        ]);
        ReservationFactory::new()->forBooking($current)->room('101', 'double')->create();

        $this->artisan('bookings:autocheckout');

        $this->assertSame('completed', $old->fresh()->status);
        $this->assertSame(
            'occupied',
            Room::where('room_number', '101')->first()->status,
            'A room occupied by a current guest was released by the backlog sweep.',
        );
    }

    public function test_auto_checkout_records_a_checkout_row(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:00:00', 'UTC'));

        $booking = Booking::factory()->create([
            'status'    => 'active',
            'check_in'  => '2026-08-08',
            'check_out' => '2026-08-10',
        ]);

        $this->artisan('bookings:autocheckout');

        $checkout = Checkout::where('booking_id', $booking->id)->first();

        $this->assertNotNull($checkout);
        $this->assertSame('auto', $checkout->method);
    }

    public function test_auto_checkout_ignores_bookings_that_are_not_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 07:00:00', 'UTC'));

        $paid = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => '2026-08-08',
            'check_out' => '2026-08-10',
        ]);

        $this->artisan('bookings:autocheckout');

        $this->assertSame('paid', $paid->fresh()->status);
    }
}
