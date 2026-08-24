<?php

namespace Tests\Feature;

use App\Events\StaffNotification;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ReportService;
use App\Support\RoomBoard;
use App\Support\RoomCatalog;
use App\Support\StaySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the behaviour introduced while collecting hardcoded values into
 * config, and the reporting fixes that came with it.
 *
 * Every bug these guard against was silent. The occupancy groups missed an
 * entire room type, the report summary could double a revenue figure, the
 * reminder could be scheduled against the wrong deadline, and the alert could
 * arrive already marked read. None of them raised anything; they were found by
 * reading, which is not a repeatable strategy.
 */
class StayScheduleAndReportingTest extends TestCase
{
    use RefreshDatabase;

    // ── Room types ───────────────────────────────────────────────────────────

    public function test_every_room_type_belongs_to_exactly_one_occupancy_group(): void
    {
        $dorm = RoomCatalog::dormTypes();
        $standard = RoomCatalog::standardTypes();
        $all = array_keys(RoomCatalog::all());

        // The original bug: 'deluxe' existed in the catalog and in neither of
        // the two hand-written arrays, so 7 of 22 rooms were counted by the
        // occupancy snapshot in neither group.
        foreach ($all as $slug) {
            $in = (int) in_array($slug, $dorm, true) + (int) in_array($slug, $standard, true);
            $this->assertSame(1, $in, "Room type '{$slug}' is in {$in} occupancy group(s); it must be in exactly one.");
        }

        $this->assertSame(count($all), count($dorm) + count($standard));
    }

    // ── The room map's layout ────────────────────────────────────────────────

    /** $rooms as [room_number, room_type, wing]. */
    private function seedRooms(array $rooms): void
    {
        foreach ($rooms as [$number, $type, $wing]) {
            Room::create([
                'room_number' => $number,
                'room_type' => $type,
                'wing' => $wing,
                'status' => 'available',
                'price' => 3000,
            ]);
        }
    }

    public function test_the_room_map_draws_every_room_exactly_once(): void
    {
        // The invariant as the page experiences it: tiles rendered must equal
        // rooms owned. The map used to group by room type from three filters
        // it built itself, and standardTypes() means "not a dormitory" —
        // deluxe included — so every deluxe room was drawn in the Standard row
        // AND the Deluxe row. 29 tiles for 22 rooms, under a subtitle reading
        // "All 22 rooms at a glance".
        $this->seedRooms([
            ['101', 'deluxe', 'rooster'], ['102', 'deluxe', 'rooster'],
            ['201', 'dormitory1', 'chev_re'], ['202', 'dormitory2', 'chev_re'],
            ['301', 'double', 'tumana'], ['302', 'triple', 'torii'],
            ['303', 'quadruple', 'torii'],
        ]);

        $rooms = RoomBoard::state();
        $drawn = Room::groupByWing($rooms)->flatten(1);

        $this->assertSame($rooms->count(), $drawn->count());
        $this->assertSame($rooms->count(), $drawn->pluck('id')->unique()->count());
    }

    public function test_the_map_walks_the_wings_in_order(): void
    {
        // Same order Room Management uses, from the same helper, so the two
        // boards read as one building.
        $this->seedRooms([
            ['401', 'deluxe', 'torii'],
            ['402', 'deluxe', 'rooster'],
            ['403', 'deluxe', 'chev_re'],
            ['404', 'deluxe', 'tumana'],
        ]);

        $this->assertSame(
            ['rooster', 'tumana', 'chev_re', 'torii'],
            Room::groupByWing(Room::all())->keys()->all()
        );
    }

    public function test_a_wing_nobody_listed_still_gets_drawn(): void
    {
        // A wing added in Room Management that is not in WING_ORDER must show
        // up somewhere rather than silently vanish from both boards.
        $this->seedRooms([
            ['501', 'deluxe', 'rooster'],
            ['502', 'deluxe', 'annex'],
        ]);

        $wings = Room::groupByWing(Room::all());

        $this->assertSame(['rooster', 'annex'], $wings->keys()->all());
        $this->assertSame(2, $wings->flatten(1)->count());
    }

    public function test_a_room_with_no_wing_is_not_dropped(): void
    {
        $this->seedRooms([['601', 'deluxe', 'rooster']]);
        Room::create([
            'room_number' => '602',
            'room_type' => 'deluxe',
            'wing' => '',
            'status' => 'available',
            'price' => 3000,
        ]);

        $this->assertSame(2, Room::groupByWing(Room::all())->flatten(1)->count());
    }

    public function test_room_capacity_follows_the_database_not_a_hardcoded_map(): void
    {
        RoomType::forceCreate(['slug' => 'double', 'name' => 'Double Room', 'base_price' => 1600, 'capacity' => 2]);

        $this->assertSame(2, RoomCatalog::capacityFor('double'));

        // Staff raise the capacity in Room Types & Pricing. The senior-discount
        // per-head price divides by this, so a stale copy silently overcharges.
        RoomType::where('slug', 'double')->update(['capacity' => 5]);

        // The catalog is memoised per request; an admin edit lands in the next
        // one. Within a single test we have to stand in for that boundary.
        RoomCatalog::flush();

        $this->assertSame(5, RoomCatalog::capacityFor('double'), 'Capacity did not follow the admin-editable value.');
        $this->assertSame(1, RoomCatalog::capacityFor('no-such-type', 1), 'Unknown type should fall back, not throw.');
    }

    // ── Stay schedule ────────────────────────────────────────────────────────

    public function test_reminder_time_is_derived_from_checkout_time(): void
    {
        config(['hostel.checkout_time' => '14:00', 'hostel.checkout_reminder.lead_hours' => 2]);
        $this->assertSame('12:00', StaySchedule::reminderTimeOfDay());

        // Moving check-out must move the reminder with it. Held as its own
        // absolute time, this stayed at 12:00 and quietly fired four hours early.
        config(['hostel.checkout_time' => '16:00']);
        $this->assertSame('14:00', StaySchedule::reminderTimeOfDay());
    }

    public function test_a_lead_crossing_midnight_is_clamped_to_the_same_day(): void
    {
        config(['hostel.checkout_time' => '14:00', 'hostel.checkout_reminder.lead_hours' => 30]);

        // Unclamped this lands on the previous date, where the "leaving today"
        // query cannot match it — the reminder would never appear at all.
        $this->assertSame('00:00', StaySchedule::reminderTimeOfDay());
        $this->assertTrue(
            StaySchedule::reminderOn('2026-08-04')->isSameDay(StaySchedule::deadlineOn('2026-08-04'))
        );
    }

    /**
     * The list opens at the earliest arrival the desk can actually honour,
     * which is checkout_time — the moment the previous guest must be out — not
     * check-in. This test asserted '2:00 PM' from back when the slots simply
     * started at check-in; early check-in is the whole reason
     * StaySchedule::earlyCheckinHours() exists, and it moved the opening slot
     * down to the checkout boundary.
     */
    public function test_arrival_slots_start_at_the_earliest_honourable_arrival(): void
    {
        config(['hostel.checkin_time' => '14:00', 'hostel.checkout_time' => '12:00']);
        $slots = StaySchedule::arrivalSlots();

        $this->assertSame('12:00 PM', reset($slots));
        $this->assertSame('After midnight', end($slots));

        // A later check-in widens the early-arrival window rather than pushing
        // the list forward — the room is free from checkout time either way.
        config(['hostel.checkin_time' => '16:00']);
        $later = StaySchedule::arrivalSlots();

        $this->assertSame('12:00 PM', reset($later));
        $this->assertArrayHasKey('14:00', $later);

        // Checkout is the bound that does move the start.
        config(['hostel.checkout_time' => '15:00']);
        $tighter = StaySchedule::arrivalSlots();

        $this->assertSame('3:00 PM', reset($tighter));
        $this->assertArrayNotHasKey('14:00', $tighter);
    }

    // ── Checkout-due alert ───────────────────────────────────────────────────

    public function test_checkout_due_alert_is_stable_and_not_pre_marked_read(): void
    {
        config(['hostel.checkout_time' => '14:00', 'hostel.checkout_reminder.lead_hours' => 2]);

        $booking = $this->booking('active', now()->addDay());

        $first = StaffNotification::checkoutDue($booking)->broadcastWith();
        $second = StaffNotification::checkoutDue($booking->fresh('reservations'))->broadcastWith();

        // Re-running the command must not put a second card in front of a desk
        // that already dismissed the first.
        $this->assertSame($first['id'], $second['id'], 'Alert id is not stable across runs.');
        $this->assertSame($first['at'], $second['at'], 'Alert timestamp is not stable across runs.');

        // 'Mark all read' treats anything older than its timestamp as read, so
        // an `at` of midnight arrives already greyed out for anyone who cleared
        // the bell that morning.
        $at = \Carbon\Carbon::createFromTimestamp($first['at'], config('hostel.timezone'));
        $this->assertSame('12:00', $at->format('H:i'), 'Alert `at` should be the reminder time, not midnight.');
    }

    // ── Reporting ────────────────────────────────────────────────────────────

    public function test_combined_report_does_not_double_count_a_booking_with_two_payments(): void
    {
        $booking = $this->booking('completed', now());

        // A rejected payment then a successful retry — Payment::STATUS_REJECTED
        // is documented as "the guest may retry", so this is a real shape even
        // though no booking has two rows today.
        $this->payment($booking, 'rejected', 6000);
        $this->payment($booking, 'success', 6000);

        $summary = collect(app(ReportService::class)->generate([
            'report_type' => 'combined',
            'column_set' => 'combined',
            'date_range' => ['type' => 'yearly', 'value' => now()->year],
            'filters' => [],
        ])['summary'])->keyBy('label');

        $this->assertSame(1, $summary['Bookings']['value'], 'One booking with two payments counted more than once.');
        $this->assertSame(6000.0, $summary['Collected']['value'], 'Revenue double-counted across the payments join.');
    }

    public function test_report_sorting_rejects_a_column_the_report_does_not_select(): void
    {
        $this->booking('completed', now());

        $params = [
            'report_type' => 'booking',
            'column_set' => 'booking_summary',
            'date_range' => ['type' => 'yearly', 'value' => now()->year],
            'filters' => [],
            'direction' => 'asc',
        ];

        // ORDER BY cannot be parameter-bound, so the alias whitelist is the
        // guard rather than an optimisation.
        $rows = app(ReportService::class)->generate($params + ['sort' => "id; DROP TABLE bookings--"])['rows'];

        $this->assertSame(1, $rows->total());
        $this->assertNotNull(Booking::first(), 'Sort input reached the database.');
    }

    public function test_excel_export_has_a_heading_row(): void
    {
        $this->booking('completed', now());

        $export = new \App\Exports\GenericReportExport(
            app(\App\Services\ReportQueryBuilder::class)->build([
                'report_type' => 'booking',
                'column_set' => 'booking_summary',
                'date_range' => ['type' => 'yearly', 'value' => now()->year],
                'filters' => [],
            ])->get()
        );

        // Without headings the download opened as unlabelled columns of ids,
        // names and dates — fine on screen, anonymous once it left the browser.
        $this->assertSame(
            ['Id', 'Guest Name', 'Check In', 'Check Out', 'Expected Guests', 'Status'],
            $export->headings()
        );
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function booking(string $status, $checkOut): Booking
    {
        $guest = User::forceCreate([
            'username' => 'guest' . uniqid(),
            'email' => uniqid() . '@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        return Booking::create([
            'user_id' => $guest->id,
            'expected_guests' => 2,
            'guest_name' => 'Test Guest',
            'guest_address' => 'Somewhere',
            'guest_phone' => '09000000000',
            'check_in' => now()->subDay(),
            'check_out' => $checkOut,
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 6000,
            'payable_amount' => 6000,
            'status' => $status,
        ]);
    }

    private function payment(Booking $booking, string $status, float $amount): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $amount,
            'status' => $status,
            'payment_type' => 'manual',
            'reference_no' => 'REF' . uniqid(),
            'gateway' => 'gcash',
        ]);
    }
}
