<?php

namespace Tests\Feature\Reports;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * The configurable report builder — POST /staff/reports/generate and /export.
 *
 * These are the numbers management makes decisions on, and the only place the
 * system hands out an aggregate view of revenue. The builder assembles raw SQL
 * from client-supplied report type, date range, filters and column set, so it
 * carries two distinct risks: wrong figures, and a request shape that reaches
 * the database as invalid SQL.
 */
class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    private const GENERATE = '/staff/reports/generate';
    private const EXPORT   = '/staff/reports/export';

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::rooms(['101', '102', '103'], 'double');
    }

    private function admin()
    {
        return $this->actingAs(Make::staff('admin'), 'staff');
    }

    /**
     * A minimal well-formed request body.
     */
    private function params(array $overrides = []): array
    {
        return array_replace_recursive([
            'report_type' => 'booking',
            'column_set'  => 'booking_summary',
            'date_range'  => [
                'type'  => 'yearly',
                'value' => now()->year,
            ],
        ], $overrides);
    }

    // ------------------------------------------------------- authorization

    public function test_an_admin_can_generate_a_report(): void
    {
        $this->admin()->postJson(self::GENERATE, $this->params())->assertOk();
    }

    public function test_front_desk_staff_cannot_generate_reports(): void
    {
        $this->actingAs(Make::staff('frontdesk'), 'staff')
            ->postJson(self::GENERATE, $this->params())
            ->assertForbidden();
    }

    public function test_a_guest_cannot_generate_reports(): void
    {
        $this->actingAs(Make::user())->post(self::GENERATE, $this->params())->assertRedirect();
    }

    public function test_an_anonymous_visitor_cannot_generate_reports(): void
    {
        $this->post(self::GENERATE, $this->params())->assertRedirect();
    }

    public function test_an_anonymous_visitor_cannot_export_reports(): void
    {
        $this->post(self::EXPORT, $this->params())->assertRedirect();
    }

    // ------------------------------------------------------------ contents

    public function test_a_booking_report_returns_the_bookings_in_range(): void
    {
        Make::bookingHolding(['101'], 'paid');
        Make::bookingHolding(['102'], 'completed');

        $response = $this->admin()->postJson(self::GENERATE, $this->params());

        $response->assertOk();
        $this->assertSame(2, $response->json('total'));
    }

    public function test_a_booking_report_excludes_other_years(): void
    {
        Make::bookingHolding(['101'], 'paid');

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'date_range' => ['type' => 'yearly', 'value' => now()->subYears(3)->year],
        ]));

        $response->assertOk();
        $this->assertSame(0, $response->json('total'));
    }

    public function test_a_status_filter_narrows_the_report(): void
    {
        Make::bookingHolding(['101'], 'paid');
        Make::bookingHolding(['102'], 'cancelled');
        Make::bookingHolding(['103'], 'cancelled');

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'filters' => ['booking_status' => ['cancelled']],
        ]));

        $response->assertOk();
        $this->assertSame(2, $response->json('total'));
    }

    /**
     * The filter whitelist is what stops an arbitrary column name reaching the
     * where clause. An unrecognised field must be dropped, not passed through.
     */
    public function test_an_unrecognised_filter_is_ignored_rather_than_executed(): void
    {
        Make::bookingHolding(['101'], 'paid');

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'filters' => ['bookings.total_price; DROP TABLE bookings' => ['1']],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_filter_not_allowed_for_this_report_type_is_ignored(): void
    {
        Make::bookingHolding(['101'], 'paid');

        // `gateway` belongs to payment/combined reports, not booking.
        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'filters' => ['gateway' => ['sandbox']],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
    }

    public function test_a_payment_report_joins_bookings(): void
    {
        $booking = Make::bookingHolding(['101'], 'paid');
        Make::payment($booking, 'success');

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'report_type' => 'payment',
            'column_set'  => 'financial',
            'date_range'  => ['type' => 'yearly', 'value' => now()->year],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertArrayHasKey('payment_status', $response->json('data.0'));
    }

    /**
     * A payment report must not invent rows for unpaid bookings — its join is
     * an inner one precisely so revenue is not overstated.
     */
    public function test_a_payment_report_omits_bookings_with_no_payment(): void
    {
        $paid = Make::bookingHolding(['101'], 'paid');
        Make::payment($paid, 'success');
        Make::bookingHolding(['102'], 'pending_payment');   // no payment row

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'report_type' => 'payment',
            'column_set'  => 'financial',
        ]));

        $this->assertSame(1, $response->json('total'));
    }

    /**
     * The combined report left-joins, so an unpaid booking still appears — with
     * an empty payment status rather than being dropped.
     */
    public function test_the_combined_report_keeps_bookings_without_payments(): void
    {
        $paid = Make::bookingHolding(['101'], 'paid');
        Make::payment($paid, 'success');
        Make::bookingHolding(['102'], 'pending_payment');

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'report_type' => 'combined',
            'column_set'  => 'combined',
        ]));

        $response->assertOk();
        $this->assertSame(2, $response->json('total'));
    }

    /**
     * The figures have to match the database. A report that quietly disagrees
     * with the bookings table is worse than no report.
     */
    public function test_the_reported_amounts_reconcile_with_the_database(): void
    {
        Make::bookingHolding(['101'], 'paid', attributes: ['payable_amount' => 1500.00]);
        Make::bookingHolding(['102'], 'paid', attributes: ['payable_amount' => 2500.00]);

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'report_type' => 'combined',
            'column_set'  => 'combined',
        ]));

        $reported = collect($response->json('data'))->sum(fn ($row) => (float) $row['payable_amount']);

        $this->assertEquals(
            (float) Booking::sum('payable_amount'),
            $reported,
            'The report total does not match the bookings table.',
        );
        $this->assertEquals(4000.00, $reported);
    }

    public function test_a_monthly_range_selects_only_that_month(): void
    {
        Make::bookingHolding(['101'], 'paid');

        $thisMonth = $this->admin()->postJson(self::GENERATE, $this->params([
            'date_range' => ['type' => 'monthly', 'value' => now()->format('Y-m')],
        ]));
        $lastYear = $this->admin()->postJson(self::GENERATE, $this->params([
            'date_range' => ['type' => 'monthly', 'value' => now()->subYear()->format('Y-m')],
        ]));

        $this->assertSame(1, $thisMonth->json('total'));
        $this->assertSame(0, $lastYear->json('total'));
    }

    public function test_an_explicit_date_range_selects_only_that_window(): void
    {
        Make::bookingHolding(['101'], 'paid');

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'date_range' => [
                'type'  => 'range',
                'value' => [
                    'from' => now()->subDay()->toDateString(),
                    'to'   => now()->addDay()->toDateString(),
                ],
            ],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
    }

    // -------------------------------------------------------------- export

    public function test_an_admin_can_export_a_report(): void
    {
        Make::bookingHolding(['101'], 'paid');

        $response = $this->admin()->post(self::EXPORT, $this->params());

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition') ?? '',
        );
    }

    public function test_front_desk_staff_cannot_export_reports(): void
    {
        $this->actingAs(Make::staff('frontdesk'), 'staff')
            ->post(self::EXPORT, $this->params())
            ->assertForbidden();
    }

    // ------------------------------------------------------ malformed input

    /**
     * DEFECT PROBE — MainReportsController passes `$request->all()` straight
     * into the query builder with no validation.
     *
     * ReportQueryBuilder then reads $params['report_type'],
     * $params['date_range']['type'] and $params['column_set'] unguarded, so an
     * incomplete body reaches PHP as an undefined-key error and surfaces as a
     * 500. A malformed request is a client error and should be answered with a
     * 422 and a message the UI can show.
     */
    public function test_a_request_with_no_report_type_is_a_client_error(): void
    {
        $response = $this->admin()->postJson(self::GENERATE, [
            'column_set' => 'booking_summary',
            'date_range' => ['type' => 'yearly', 'value' => now()->year],
        ]);

        $this->assertSame(422, $response->status(), 'A missing report_type produced a server error.');
    }

    public function test_a_request_with_no_date_range_is_a_client_error(): void
    {
        $response = $this->admin()->postJson(self::GENERATE, [
            'report_type' => 'booking',
            'column_set'  => 'booking_summary',
        ]);

        $this->assertSame(422, $response->status(), 'A missing date_range produced a server error.');
    }

    public function test_an_unknown_report_type_is_a_client_error(): void
    {
        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'report_type' => 'everything',
        ]));

        $this->assertSame(422, $response->status(), 'An unknown report_type produced a server error.');
    }

    public function test_a_malformed_monthly_value_is_a_client_error(): void
    {
        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'date_range' => ['type' => 'monthly', 'value' => 'not-a-month'],
        ]));

        $this->assertSame(422, $response->status(), 'A malformed monthly value produced a server error.');
    }

    /**
     * DEFECT PROBE — `mode` is an allowed filter for the booking and combined
     * reports, and ReportQueryBuilder maps it to `bookings.mode`. That column
     * does not exist; the booking payment channel is `bookings.payment_mode`.
     *
     * So any report filtered by mode — an option the UI offers — reaches MySQL
     * as a reference to an unknown column and fails outright.
     */
    public function test_filtering_by_payment_mode_works(): void
    {
        Make::bookingHolding(['101'], 'paid', attributes: ['payment_mode' => 'card']);
        Make::bookingHolding(['102'], 'paid', attributes: ['payment_mode' => 'manual']);

        $response = $this->admin()->postJson(self::GENERATE, $this->params([
            'filters' => ['mode' => ['card']],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
    }
}
