# Test suite

Automated QA coverage for the booking system. 159 tests across the public
booking flow, pricing, payments, authorization, and the scheduled lifecycle
jobs.

## Running it

The suite runs against a **real MySQL/MariaDB database**, not SQLite. Create it
once:

```bash
mysql -u root -e "CREATE DATABASE aux_system_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Then:

```bash
php artisan test
```

A single file or a single test:

```bash
php artisan test tests/Feature/Booking/DoubleBookingTest.php
```

```bash
php artisan test --filter test_a_forged_nightly_rate_in_the_payload_is_ignored
```

### Why MySQL and not SQLite

Two reasons, both specific to this codebase:

1. Four migrations issue raw `ALTER TABLE ... MODIFY` — MySQL-only DDL — with
   no driver guard, so SQLite cannot build the schema at all.
2. The double-booking guard depends on `lockForUpdate()`. SQLite has no
   row-level locking, so those tests would pass there without proving anything.

`RefreshDatabase` migrates once and wraps each test in a transaction, so the
whole suite still runs in about twelve seconds.

## Layout

| Path | Covers |
| --- | --- |
| `Feature/Booking/RoomAvailabilityTest` | Both availability endpoints, overlap maths, turnover days, housekeeping states |
| `Feature/Booking/BookingCreationTest` | Every validation and business-rule guard in `store()` |
| `Feature/Booking/PricingIntegrityTest` | Server-side price recompute; forged rates and bed counts |
| `Feature/Booking/DoubleBookingTest` | The `lockForUpdate` overlap guard |
| `Feature/Payment/PaymentAuthorizationTest` | Acceptance criteria for the payment gateway (see below) |
| `Feature/Booking/StaffBookingPathsTest` | Front-desk walk-in and admin manual booking, compared against the public path |
| `Feature/Discount/DiscountWorkflowTest` | Senior-citizen request, uploads, per-file review, and the discount arithmetic |
| `Feature/Receipt/ReceiptVerificationTest` | SHA-256 tamper detection, re-issue, and the QR verify route |
| `Feature/Reports/ReportGenerationTest` | Report joins, filter whitelisting, totals reconciling with the DB, exports |
| `Feature/Auth/BookingOwnershipTest` | IDOR — guest A reaching guest B's bookings |
| `Feature/Auth/StaffAccessControlTest` | The full role × route matrix, both consoles |
| `Feature/Auth/SuspensionTest` | Suspension holding mid-session, both guards |
| `Feature/Lifecycle/BookingLifecycleCommandsTest` | `bookings:expire`, `:mark-no-show`, `:autocheckout`, including Manila/UTC boundaries |

## Test data

Model factories live in `database/factories/`. Several models
(`Room`, `Staff`, `Payment`, `Reservation`, `RoomType`) do not use the
`HasFactory` trait, so `Model::factory()` is unavailable on them — build those
through `Tests\Support\Make` instead:

```php
Make::catalog();                                  // seed room_types (do this first)
Make::room('101', 'double');
Make::staff('admin');
Make::bookingHolding(['101'], 'paid');            // a booking that holds inventory
```

`Make::catalog()` matters more than it looks: `RoomCatalog` overlays the
`room_types` table on top of `config/room_types.php` and the **database wins**,
so any test asserting on price must seed it or the totals drift.

`Tests\Support\BookingPayload` builds the fifteen-field `POST /booking` body.
Start from a valid baseline and mutate only the field under test:

```php
BookingPayload::make()
    ->block('double', ['101'], guests: 2, pricePerNight: 1.00)   // forged rate
    ->guests(2)
    ->toArray();
```

## Two environment quirks worth knowing

**GD has no JPEG support** in this XAMPP build (`gd_info()` reports
`JPEG Support` empty), so `UploadedFile::fake()->image('x.jpg')` throws
*"imagejpeg function is not defined"*. Test fixtures use PNG. If application code
ever generates a JPEG it will fail the same way.

**`$booking->discount` is the column, not the relation.** `Booking` has both a
`discount` decimal column and a `discount()` relation, and the attribute wins —
so `$booking->discount` returns a float. Use the `discountRequest` relation when
you want the model. Two tests silently passed against `0.0` before this was
caught.

## About the failing tests

Six tests fail on a clean checkout, all in `PaymentAuthorizationTest`. The
payment gateway has not been chosen yet, so treat that file as the **acceptance
criteria for whichever provider is adopted** rather than a bug list — point it at
the real integration when it lands and the failures become the definition of
done.

Everything else is green. Full write-up of what was found and fixed in
[`docs/qa-findings.md`](../docs/qa-findings.md).
