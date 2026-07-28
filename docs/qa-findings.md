# QA findings

Automated QA pass over the booking system.
Date: 2026-07-27 · Baseline commit: `432f6fc`

Run the suite with `php artisan test`; see [`tests/README.md`](../tests/README.md)
for setup and layout.

**Initial pass: 159 tests, 148 passing, 11 failing.** Every failure was a real
defect — the tests assert intended behaviour and go green once the cause is
fixed.

**Current: 278 tests, all passing.** Zero advisories in production dependencies.

Twenty-two findings, **nineteen fixed**. The three open items are decisions for
the client rather than defects — see *What remains*.

The passes were made in five rounds:

| Round | Scope | Found |
| --- | --- | --- |
| 1 | Public booking, pricing, authorization, scheduled jobs | #1–#7 |
| 2 | Front-desk walk-in and admin manual booking | #8–#10 |
| 3 | Senior-citizen discount workflow | #11–#14 |
| 4 | Session suspension, receipts and QR verification, report builder | #15–#18 |
| 5 | Hardening: payment lockdown, dependencies, rate limiting, CI | #19–#22 |

| # | Severity | Finding | Status |
| --- | --- | --- | --- |
| 1 | **High** | Double-booking race: overlap guard and availability read different tables | ✅ Fixed |
| 2 | **High** | `/__dev-login` staff backdoor is committed and reachable | ✅ Fixed |
| 3 | **Medium** | Suspending a staff account does not revoke their active session | ✅ Fixed |
| 4 | **High** | Payment routes sit outside every middleware group | ✅ Fixed |
| 5 | Low | Logged-out staff are sent to the customer login page | ✅ Fixed |
| 6 | Process | Four migrations pending on the development database | ✅ Applied |
| 7 | Process | Migrations are not portable off MySQL, blocking CI | ⚠️ Open — accepted for now |
| 8 | **High** | `bookings.user_id` is NOT NULL in migrations — walk-in and manual booking cannot work on any database built from them | ✅ Fixed |
| 9 | **Medium** | Walk-in booking is missing three guards its sibling paths have | ✅ Fixed |
| 10 | Medium | Neither staff booking path locks rooms; occupancy is checked outside the transaction | ✅ Fixed |
| 11 | **Medium** | Discount can exceed the number of seniors the guest declared | ✅ Fixed — confirm the policy |
| 12 | **Medium** | Discount calculation uses a hardcoded room capacity, ignoring the admin-configured one | ✅ Fixed |
| 13 | Low | Discount file uploads accept an unvalidated `reservation_id` | ✅ Fixed |
| 14 | Low | The "concurrency safety" lock on discount finalisation is a no-op | ✅ Fixed |
| 15 | **Medium** | Receipts cannot be re-issued — the number collides on a UNIQUE constraint, silently | ✅ Fixed |
| 16 | **Medium** | Reports filtered by payment mode query a column that does not exist | ✅ Fixed |
| 17 | Medium | The report builder has no input validation; malformed requests return 500 | ✅ Fixed |
| 18 | Low | Receipt QR verification is staff-only, though the QR is printed on the guest's copy | ⚠️ Open — design question |
| 19 | **High** | 45 dependency advisories, including two critical in the report-export library | ✅ Fixed |
| 20 | **Medium** | No rate limiting anywhere except one route — staff login brute-forceable | ✅ Fixed |
| 21 | Low | Eloquent strict mode off, so silently-discarded attributes and N+1s go unnoticed | ✅ Fixed |
| 22 | Process | No CI — the suite only ran when someone remembered | ✅ Fixed |

---

## 1. Double-booking race — High

**Where:** [`BookingController::store()`](../app/Http/Controllers/BookingController.php#L318)
**Tests:** `DoubleBookingTest::test_a_room_held_only_by_reservation_rows_is_still_protected`,
`::test_the_availability_endpoint_and_the_booking_guard_agree`

The system keeps room occupancy in two places and the two disagree:

- The **availability endpoints** (`/rooms/available`,
  `/rooms/availability-summary`) read occupancy from the `reservations` table.
- The **double-booking guard** inside `store()`'s transaction reads it from the
  `booking_room` pivot.

The pivot rows are attached at line 318 — *after* `DB::transaction()` has already
committed. So between commit and attach, a freshly created booking holds its
rooms in `reservations` but not yet in the pivot. A concurrent booking passing
through the guard at that moment queries the pivot, finds nothing, and is
allowed through. `lockForUpdate()` does not help: it serialises the two requests
correctly, but the second one then consults a table the first has not written to
yet.

Reproduced deterministically: the test creates the pre-attach state (reservation
rows, no pivot rows), confirms the public availability endpoint already reports
the room as `booked`, then books it again successfully. **Room 101 ends up sold
twice for overlapping dates.**

This is also reachable without concurrency — any booking created through a path
that writes reservations but not the pivot is invisible to the guard.

**✅ Fixed.** The overlap guard now queries `reservations` — the same source the
availability endpoints use — so there is one source of truth for "is this room
taken". The pivot attach moved inside the transaction, while the room rows are
still locked, removing the window entirely. Both fixes were needed: reading
reservations alone would have left the pivot briefly inconsistent, and moving
the attach alone would have narrowed the window without collapsing the two
sources of truth.

---

## 2. `/__dev-login` staff backdoor — High

**Where:** [`routes/web.php:74`](../routes/web.php#L74)
**Test:** `StaffAccessControlTest::test_the_developer_login_backdoor_is_not_registered`

```php
// TEMP-DEV-LOGIN (remove before commit)
Route::get('/__dev-login', function () {
    abort_unless(app()->environment('local'), 404);
    auth('staff')->login(\App\Models\Staff::first());
    return redirect()->route('staff.bookings.index');
});
```

A `GET` request logs the caller in as the first staff row — no password, no
prompt. It is gated on `APP_ENV=local`, and the committed `.env` sets exactly
that. Any deployment or demo that ships with this environment file grants full
staff access to anyone who visits the URL, and `Staff::first()` is typically the
master admin.

The comment says "remove before commit". It was committed.

**✅ Fixed.** Route deleted. An environment check was not a control here, because
the environment it permitted is the one the project actually runs in.

---

## 3. Suspension does not revoke active sessions — Medium

**Where:** [`StaffRoleMiddleware`](../app/Http/Middleware/StaffRoleMiddleware.php)
**Test:** `StaffAccessControlTest::test_a_suspended_admin_cannot_reach_the_admin_console`

`is_suspended` is checked at login (`StaffAuthController:54`) but nowhere
afterwards. `StaffRoleMiddleware` verifies only that a staff session exists and
that the role matches. A staff member who is already signed in keeps full access
to their console after being suspended — until the session lapses on its own
(`SESSION_LIFETIME=120`, so up to two hours).

Suspension is the mechanism for cutting off a departed or compromised employee,
which is precisely the case where the account is likely to be signed in already.

**✅ Fixed, both guards.** Initially patched in `StaffRoleMiddleware`, then moved
up into `Authenticate` so it covers every `auth`-protected route rather than only
the two consoles. The middleware re-checks `is_suspended` after authentication
succeeds, tears the session down, and bounces the account to the login form
belonging to its guard — `staff.login` for staff, `login` for guests — carrying
the suspension message.

Doing it at the guard level rather than the role level matters: several staff
routes (`/verify-receipt`, the guest-history and occupancy endpoints) are
protected by `auth:staff` alone and never pass through `StaffRoleMiddleware`.
`SuspensionTest` covers those explicitly, along with the guest side that was
open after the first pass.

---

## 4. Payment routes are unauthenticated — High

**Where:** the `TEMPORARY ROUTES` block at the foot of
[`routes/web.php`](../routes/web.php#L344)
**Tests:** all six failures in `PaymentAuthorizationTest`

`GET /booking/{booking}/pay` and the whole `sandbox/*` group are registered
outside every middleware group — no `auth`, no ownership check, and the webhook
additionally strips CSRF with no signature verification. As it stands:

- an anonymous caller can `POST /sandbox/process/{payment}` with a guessed id and
  flip a stay to `paid`;
- `POST /sandbox/webhook/{payment}` confirms any payment, unsigned;
- `GET /sandbox/status/{payment}` leaks payment state and lets an attacker
  enumerate valid ids first;
- `PaymentController::pay()` never compares the booking's owner to the
  authenticated user.

**✅ Fixed.** Originally deferred on the grounds that the gateway had not been
chosen — but "deferred" is a plan, not a fix, and the routes were live and
mutating real bookings in the meantime. Closed without waiting for the provider:

- `/booking/{booking}/pay` and the whole `sandbox/*` group moved behind
  `['auth', 'verified']`, the same guard as the rest of the booking flow.
- `PaymentController::pay()` now checks `$booking->user_id === Auth::id()` and
  refuses anything not in `pending_payment`.
- `SandboxGatewayController` gained `authorizePayment()`, applied to the gateway
  page, the process action, the result page and the status endpoint.
- The webhook authenticates with an **HMAC-SHA256 signature over the raw body**,
  compared with `hash_equals()`, reading its secret from
  `config/payments.php`. It **fails closed**: with no secret configured it
  rejects everything, so a misconfigured deployment declines confirmations
  rather than accepting unsigned ones.
- The webhook is **idempotent** — a replayed delivery returns the existing state
  instead of re-confirming, because gateways retry.

`PaymentAuthorizationTest` grew from 8 tests to 13 and is now fully green. It
remains the acceptance criteria for whichever provider is adopted: only the
header name and digest algorithm in `config/payments.php` should need to change.

The one thing still outstanding is the design rule that a booking must never be
marked paid from a client-side redirect — only from a verified webhook or a
server-side confirmation call. The sandbox still flips the status in
`processPayment()`, which is correct for a stand-in but must not survive the real
integration.

---

## 5. Logged-out staff land on the customer login — Low

**Where:** [`Authenticate::redirectTo()`](../app/Http/Middleware/Authenticate.php#L13)
**Test:** `StaffAccessControlTest::test_an_expired_staff_session_lands_on_the_staff_login`

`redirectTo()` returns `route('login')` for every guard, so a staff member whose
session expires is dropped on the guest login form, where their credentials will
not work. `StaffRoleMiddleware` gets this right and redirects to `staff.login`.

Access is correctly denied either way — this is a wrong-destination bug, not a
hole.

**✅ Fixed.** `redirectTo()` now inspects the route's own middleware for
`auth:staff` and returns `route('staff.login')` when it finds it. Keyed off the
middleware rather than a URL prefix list, because the admin booking hub is
mounted at `/bookings` and would not match a `/staff/*` pattern.

---

## 6. Four migrations pending on the development database — Process

`php artisan migrate:status` shows these as **Pending** on `aux_system`:

```
2026_07_15_054016_add_room_number_index_to_reservations_table
2026_07_20_000000_fix_reviewer_and_processor_foreign_keys
2026_07_20_000001_convert_status_columns_to_string
2026_07_20_000002_drop_room_numbers_from_bookings
```

The application code already assumes they have run. `Booking::STATUSES` is
documented as the source of truth "now that `status` is a plain VARCHAR", but on
the dev database `bookings.status` is still a MySQL `ENUM` — one that does not
include every value the model lists. Writing a status the enum does not carry
will be silently coerced or rejected depending on SQL mode.

Likewise `bookings.room_numbers` is still present and `NOT NULL` with no default,
though `Booking::getRoomNumbersAttribute()` derives that value from reservations
and the column is supposed to be gone.

The test database was built from a clean `migrate`, so **the suite is testing the
intended schema, not the one you are developing against.** That difference will
produce bugs that only appear in one environment.

**Fix direction:** run `php artisan migrate` on `aux_system`.

---

## 7. Migrations are not portable off MySQL — Process

Four migrations issue raw `ALTER TABLE ... MODIFY` with no driver guard:

```
2025_09_13_010449_alter_bookings_status_column
2025_09_27_064346_add_expired_status_to_bookings_table
2025_10_12_125103_add_master_admin_role_to_staff
2026_07_20_000002_drop_room_numbers_from_bookings
```

`2026_07_20_000001` shows the pattern to follow — it guards its DDL with
`if (DB::getDriverName() !== 'mysql') return;`.

Practical effect: the schema cannot be built on SQLite, so the fast in-memory
test path is unavailable and the suite needs a live MySQL server. That is fine
locally, and arguably better for this system (see `tests/README.md`), but it
means CI needs a MySQL service container rather than a throwaway file.

---

## What passed

Worth recording, because these are the parts that carry the most risk and they
held up under deliberate attack:

- **Pricing integrity (10/10).** Forged `price_per_night` and `beds` values in
  the POST body are discarded; totals are recomputed from `RoomCatalog` every
  time. An admin rate change reaches the next booking immediately.
- **Booking validation (21/21).** All twelve business-rule guards in `store()`
  hold: capacity, guest/senior/meal arithmetic, cross-type room claims, duplicate
  rooms, housekeeping states, past dates, and `<script>` in guest names. Rejected
  submissions leave no partial rows behind.
- **Overlap arithmetic (7/7 in `DoubleBookingTest`, 12/12 in
  `RoomAvailabilityTest`).** Partial overlaps, enclosing stays and every blocking
  status are refused; back-to-back turnover days are correctly allowed.
- **Role matrix (32/32).** Every role × console combination behaves correctly.
  Front desk cannot reach the admin console, admins cannot reach the front desk,
  housekeeping reaches neither, master admin reaches both.
- **Guest data isolation (11/11).** No IDOR on viewing, cancelling, or the
  bookings list; the 30-minute cancellation cooldown holds.
- **Lifecycle jobs (21/21), including timezone boundaries.** The Manila/UTC
  handling in all three scheduled commands is correct — a guest arriving today
  survives the 00:05 no-show sweep, and auto-checkout's 2 PM guard fires on
  Manila time, not server time. The backlog guard that stops a newer stay's room
  being released also works.

---

## 8. `bookings.user_id` is NOT NULL in the migrations — High

**Where:** `2025_09_11_000000_create_bookings_table.php`
**Found by:** `StaffBookingPathsTest` (the whole file was blocked by it)

Walk-in and manual bookings are created for guests who have no account, and both
controllers write `'user_id' => null` explicitly. The original migration declared
the column `NOT NULL`, and nothing since changed it. On any database built from
the migrations, **both staff booking paths fail outright**:

```
SQLSTATE[23000]: Column 'user_id' cannot be null
```

The controllers catch this and surface a generic "Failed to create booking"
flash message, so the failure looks like a validation problem rather than a
schema one.

The development database was altered by hand at some point and is already
nullable, which is why nobody noticed. The change was never recorded as a
migration, so **the migrations did not reproduce a working schema** — a fresh
deploy, a new developer machine or a CI run would all have had a front desk that
could not take a booking.

This is the concrete consequence of the drift flagged in #6, and the reason that
finding matters more than "run a command".

**✅ Fixed.** `2026_07_27_000000_make_bookings_user_id_nullable` makes the column
nullable, dropping and restoring the CASCADE foreign key around the change so
behaviour for registered guests is identical. It is a no-op against the existing
development database and repairs every database built from scratch.

---

## 9. Walk-in booking is missing three guards — Medium

**Where:** `WalkInBookingController::store()`
**Tests:** four failures in `StaffBookingPathsTest`, now passing

The system creates bookings three ways, and each re-implements its own
validation. Comparing them turned up three guards the walk-in path simply did
not have:

| Guard | Public | Manual | Walk-in (before) |
| --- | --- | --- | --- |
| Room status (maintenance / cleaning / occupied) | ✅ | ✅ | ❌ |
| Nightly rate recomputed server-side | ✅ | ✅ | ❌ |
| Room exists and matches the claimed type | ✅ | ✅ | ❌ |
| Occupancy read from `reservations` | ✅ | ✅ | ✅ |

Consequences, in order of how bad they are at the desk:

- a room pulled for maintenance could be handed to a walk-in guest;
- the posted `price_per_night` was multiplied out and written to the books with
  no cross-check, so a stale form or a JS bug produced a wrong total;
- a triple could be booked as a "double" and charged at the double rate, because
  nothing verified the room number against the claimed type.

**✅ Fixed** by mirroring `ManualBookingController`, which already did all three
correctly: rooms are loaded up front and keyed by number, the rate comes from
`rooms.price`, the type is verified, and the status guard runs before the
transaction opens. `price_per_night` is now `nullable` in the validation rules
and treated as display-only, matching the manual path.

---

## 10. Neither staff path locks rooms — Medium

**Where:** `WalkInBookingController::store()`, `ManualBookingController::store()`

Both staff paths run their occupancy check *before* `DB::beginTransaction()` and
neither uses `lockForUpdate()`. The public path does both — it locks the room
rows and checks inside the transaction.

So two concurrent staff bookings for the same room can both pass the check and
both insert. The window is much narrower than the original #1 (it needs two
staff members booking the same room in the same moment) and in practice a front
desk has one person at the terminal — but the guarantee is absent, and a busy
check-in period with two terminals is exactly when it would bite.

**✅ Fixed.** Both controllers now open the transaction first, take
`lockForUpdate()` on the room rows, and run the occupancy and status checks
against those locked rows. Each rejection path rolls back explicitly before
returning, since the guards now sit inside the transaction. The redundant
per-room `Room::where(...)->first()` loop that built the pivot ids was replaced
with the already-locked collection.

This brings all three booking paths to the same guarantee: lock, check, insert,
commit.

Four tests pin the behaviour so it cannot silently regress:

- `test_the_walk_in_path_locks_the_rooms_before_checking_them` and its manual
  equivalent watch the query log for the `FOR UPDATE` the lock emits. A genuine
  two-connection race is not reproducible in a single-threaded test, so the
  lock is asserted structurally.
- `test_a_walk_in_rejected_for_room_status_leaves_nothing_behind` and its manual
  equivalent prove the new rollback paths leave no booking, reservation or
  payment rows.
- `test_the_connection_is_usable_after_a_rejected_walk_in` confirms the
  transaction nesting level returns to its baseline and a following booking
  still succeeds — a missed rollback would strand the connection mid-transaction.

---

## 11. The discount can exceed the declared senior count — Medium, open

**Where:** `DiscountService::calculate()`
**Test:** `DiscountWorkflowTest::test_the_discount_cannot_exceed_the_declared_senior_count`

The calculator clamps approved IDs against the reservation's `num_guests`:

```php
$maxAllowed = min($approvedSeniors, $reservation->num_guests);
```

`num_seniors` — the figure the guest declared at booking, which the booking form
validates against capacity and total guests — is never consulted. So a booking
that declared **one** senior among two guests is granted **two** senior
discounts if two IDs are uploaded and both approved.

Measured: ₱720 applied where ₱360 was expected, on a ₱3,600 booking.

**✅ Fixed, under a stated assumption.** The clamp now uses `num_seniors`, so the
declared count is the ceiling: approving more IDs than were declared earns no
extra discount.

That is the conservative reading, and the defensible default — a validated field
should not silently do nothing, and this direction cannot leak revenue. But it
*is* a business rule, so **please confirm it with the client.** The alternative
reading is that staff verify reality at the desk and the declared figure is only
an estimate, in which case the guest may legitimately under-declare.

Reversing it is one word — `num_seniors` back to `num_guests` in
`DiscountService::calculate()`, plus the matching assertion in
`test_the_discount_cannot_exceed_the_declared_senior_count`. Both are commented
to say so.

---

## 12. Discount maths uses a hardcoded room capacity — Medium

**Where:** `DiscountService::calculate()`
**Test:** `DiscountWorkflowTest::test_the_calculation_uses_the_admin_configured_capacity`

Capacity is baked into a `match` expression:

```php
$capacity = match (strtolower(trim($room->room_type))) {
    'double' => 2, 'triple' => 3, 'quadruple' => 4, ...
};
```

Room capacity is admin-editable through the Room Types & Pricing screen, and
every other part of the system reads it from `room_types`. Here it is frozen at
whatever was true when the file was written, so the per-head rate — and
therefore the statutory 20% — is wrong for any type whose capacity has since
changed. A slug not in the list silently falls through to `default => 1`,
inflating the per-head rate to the full room rate.

Measured: after setting `double` capacity to 3, the discount stayed at ₱360
instead of dropping to ₱240.

**✅ Fixed.** The calculation now derives the per-head rate entirely from the
reservation row — `price` and `capacity` as recorded when the booking was made —
rather than from live `rooms.price` plus a hardcoded capacity. The `Room` lookup
(and the N+1 query it caused, one per reservation) is gone.

Reading the reservation rather than the live room type is deliberate: it is what
the guest was actually quoted, so an admin editing a rate or capacity can no
longer retroactively change the value of a pending discount request. Three tests
pin this: an existing booking is unaffected by a later capacity change, a
reservation sold at three beds is discounted at a third of the room, and an
unrecognised room type no longer collapses to single occupancy.

---

## 13. Discount uploads accept an unvalidated `reservation_id` — Low

**Where:** `DiscountController::store()`
**Test:** `DiscountWorkflowTest::test_files_cannot_be_attached_to_another_bookings_reservation`

The reservation id comes from the form's array key and is written straight to
`discount_files.reservation_id`:

```php
foreach ($discountFiles as $reservationId => $files) {
```

Nothing checks that the reservation belongs to the booking being uploaded
against. A guest can post any integer.

Not exploitable for a larger discount today — the calculator only counts files
whose `reservation_id` matches one of *this* booking's reservations, so a
foreign id simply never matches and earns nothing. But it writes arbitrary
foreign keys into the table, and the first report or join that trusts that
column turns it into a real problem.

**✅ Fixed.** `store()` now compares the submitted keys against
`$booking->reservations->pluck('id')` and rejects the whole submission if any key
is foreign, before creating a discount record or writing a file.

---

## 14. The concurrency lock on discount finalisation does nothing — Low

**Where:** `DiscountAdminController::approve()` and `::reject()`

Both methods open a transaction and then call:

```php
$discount->lockForUpdate();
```

On a model instance this forwards to a fresh query builder, returns it, and
throws it away — no query is ever executed, so no row is locked. The
"recheck inside transaction (safety)" immediately after re-reads
`$discount->status` from the **already-loaded in-memory model**, not from the
database, so it can only ever see the same value the outer check saw.

Practical impact is small: `payable_amount` is written absolutely
(`total_price - amount`) rather than incrementally, so two simultaneous approvals
converge on the same figure, and the sequential case is caught by the outer
status check (covered by
`test_a_finalised_request_cannot_be_reviewed_again`, which passes).

It was worth fixing anyway because the code advertised a safety property it did
not have, and the next person to add an incremental write there would have
trusted it.

**✅ Fixed.** Both methods now take a real lock —
`Discount::whereKey(...)->lockForUpdate()->first()` — and recheck the status on
the row that comes back rather than on the stale in-memory model.

---

## 15. Receipts cannot be re-issued — Medium

**Where:** `BookingPaidMail::build()`
**Test:** `ReceiptVerificationTest::test_re_issuing_a_receipt_does_not_collide`

The receipt number is a pure function of the booking id:

```php
$receiptNumber = 'R-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
```

`receipts.receipt_number` is UNIQUE and the code called `Receipt::create()`, so
the *second* time this ran for a booking it threw an integrity violation. Every
route into it is a normal occurrence: a re-sent confirmation, a queued mailable
retried after a transient mail failure, or a second settled payment.

Worse, the send site wraps the call in `catch (\Exception $e)` with only a
`Log::error()`, so the failure was completely silent — the guest received no
receipt and nothing surfaced to staff.

**✅ Fixed** with `Receipt::updateOrCreate()` keyed on the receipt number. One
official receipt per booking, re-issuable, and the stored digest is refreshed to
match the PDF actually on disk — a regenerated PDF differs byte-for-byte
(embedded timestamps), so a stale hash would have failed verification.

Four tests cover it end to end by rendering the real mailable: paying produces a
verifiable receipt, re-issuing does not duplicate, a re-issued receipt still
verifies, and the number stays stable across re-issues.

**Note on the surrounding design:** generating a PDF, writing to storage and
inserting a database row all happen inside a mailable's `build()`. The
idempotency fix makes retries safe, but side effects at render time remain
awkward — this belongs in a dedicated service invoked before the mail is queued.
Not changed here, since it is a refactor rather than a defect.

---

## 16. Reports filtered by payment mode query a missing column — Medium

**Where:** `ReportQueryBuilder::mapFilterToColumn()`
**Test:** `ReportGenerationTest::test_filtering_by_payment_mode_works`

`mode` is an allowed filter for both the booking and combined reports, and it
mapped to `bookings.mode`. That column does not exist — the booking's payment
channel is `bookings.payment_mode`. Any report filtered by mode, an option the
UI offers, reached MySQL as a reference to an unknown column and failed outright.

**✅ Fixed** — the filter now maps to `bookings.payment_mode`.

---

## 17. The report builder had no input validation — Medium

**Where:** `MainReportsController::generate()` / `::export()`
**Tests:** four in `ReportGenerationTest`

Both methods passed `$request->all()` straight into `ReportQueryBuilder`, which
reads `report_type`, `date_range['type']` and `column_set` unguarded. An
incomplete or malformed body therefore failed as a PHP undefined-key error and
surfaced as a **500** — a client mistake reported as a server fault, with nothing
the UI could show. An unknown `report_type` hit `ReportSchema`'s
`default => throw new \Exception`, likewise a 500.

**✅ Fixed** with a dedicated `ReportRequest` form request: report type, column
set, date-range type and the shape of `date_range.value` (YYYY-MM for monthly,
four digits for yearly, from/to for a range, a pair for weekly) are all
validated, and the controllers now consume `validated()` rather than `all()`.
Omitting `column_set` now defaults it from the report type instead of falling
through to the mapper's `bookings.*` catch-all.

Worth recording: the **filter whitelist already held.** A filter key of
`bookings.total_price; DROP TABLE bookings` is silently dropped rather than
executed, and a filter belonging to another report type is ignored. That part of
the builder was written defensively.

---

## 18. Receipt verification is staff-only — Low, open

**Where:** `routes/web.php`, the `auth:staff` group
**Test:** `ReceiptVerificationTest::test_verification_currently_requires_staff_authentication`

`GET /verify-receipt/{number}` sits behind `auth:staff`. The QR code that points
at it is printed on the guest's own receipt PDF, so a guest who scans their
receipt lands on the staff login form.

Either that is intended — the QR exists for staff to validate a receipt a guest
presents at the desk — or public self-verification was the goal and the route is
in the wrong middleware group. The test records the current behaviour so a change
is deliberate rather than accidental.

**Not changed.** Making it public would expose booking details to anyone holding
a receipt number, and those numbers are sequential and trivially guessable
(`R-000001`, `R-000002`…). If public verification is wanted, the number needs an
unguessable component first. That is a product decision plus a schema change, not
a middleware tweak.

---

## 19. Forty-five dependency advisories — High

**Found by:** `composer audit`, prompted by an external security checklist

`composer.lock` had not moved since 11 July and carried **45 advisories across
15 packages**, several predating the lock:

| Package | Was | Worst | Used for |
| --- | --- | --- | --- |
| `phpoffice/phpspreadsheet` | 1.30.0 | **critical** ×2 | Excel report exports |
| `laravel/framework` | v12.17.0 | **high** (CVE-2025-64500, PATH_INFO authorization bypass) | everything |
| `dompdf/dompdf` | v3.1.2 | medium ×4 | receipt PDFs |
| `guzzlehttp/*`, `league/commonmark`, `symfony/*` | — | medium | transitive |

**✅ Fixed.** Updated within major versions — `laravel/framework` to v12.64.0,
`phpspreadsheet` to 1.30.6, `dompdf` to v3.1.6, plus their trees. All 278 tests
still pass, which is what made the update safe to do quickly: the 23 report tests
would have caught an export regression immediately.

`laravel/tinker` was also moved from `require` to `require-dev`. It is a REPL
tool that was shipping to production and dragging `psy/psysh` with it.

**Result: 45 advisories → 5, and zero in production dependencies.** The five
remaining (`phpunit`, `psysh`, `symfony/yaml` ×3) are dev-only and unreachable
at runtime; `composer audit --no-dev` reports clean.

This is now checked automatically — see #22.

---

## 20. No rate limiting — Medium

**Where:** `routes/web.php`, `AppServiceProvider`
**Tests:** `RateLimitTest`

The application had exactly **one** throttle, on the verification-resend route,
and no `RateLimiter::for` definitions at all. Unprotected: guest login, signup,
staff login, OTP verification, OTP resend, password reset, and booking creation.

Staff login was brute-forceable. OTP verification accepted unlimited guesses at a
six-digit code, which is what makes the second factor worth having. OTP resend
and password reset each send an outbound message per call — free mail, and real
money the moment SMS is wired up.

**✅ Fixed.** Eight named limiters in
`AppServiceProvider::configureRateLimiting()`, applied to every endpoint above.

Credential endpoints key on the **submitted identifier as well as the IP**, with
a looser per-IP ceiling alongside. That matters in both directions: one attacker
hammering a single account cannot lock every user out from a shared address, and
a distributed attack on one account cannot spread itself across IPs to evade the
limit. `test_throttling_one_account_does_not_lock_out_another` pins it.

Two tests assert the limits do not catch ordinary use — a single login and a
single booking must never see a 429.

---

## 21. Eloquent strict mode was off — Low

**Where:** `AppServiceProvider::configureStrictModels()`

`Model::shouldBeStrict()` turns three classes of silent failure into exceptions:
attributes discarded by mass-assignment guards, reads of attributes that were
never hydrated, and lazy loads that become N+1 queries under load.

It earns its place here: during this QA pass an `update()` naming a non-fillable
column (`pending_payment_since`) was silently a no-op, which cost real time to
diagnose. Strict mode makes that an exception.

**✅ Fixed**, enabled for everything except production so a warning cannot take a
live page down.

Turning it on immediately surfaced eight failures — all in **test data**, not
application code: `UserFactory` and `StaffFactory` omitted nullable columns
(`last_cancelled_at`, `remember_token`, `last_login_at`), so factory-built models
lacked attributes that a row loaded from the database always carries, and reading
them threw. The factories now set them explicitly. Worth recording as an example
of what strict mode is for: those factories were producing models unlike anything
the application actually sees.

---

## 22. No continuous integration — Process

**Where:** `.github/workflows/tests.yml`

The suite only ran when someone remembered to run it. A suite that is not
enforced decays at the first push past a red result.

**✅ Fixed.** A GitHub Actions workflow on push to `main` and on every pull
request:

- **PHPUnit** against a real MySQL 8 service container — SQLite cannot build this
  schema and cannot exercise `lockForUpdate` — on PHP **8.2 and 8.4**. 8.2 is the
  floor declared in `composer.json`, so testing it keeps that claim honest.
- **Dependency audit** (`composer audit` + `npm audit`) as a separate
  `continue-on-error` job, so a newly published CVE raises a flag without
  blocking an unrelated merge.

---

## What remains

Three open items, all decisions rather than defects:

1. **The senior-count policy** (#11) — the code now enforces the conservative
   reading; one word in `DiscountService` reverses it if the client intends the
   other.
2. **Whether receipt verification should be public** (#18) — if so, receipt
   numbers need an unguessable component first, since they are sequential.
3. **The real payment integration** — the surface is locked down and
   `PaymentAuthorizationTest` is the specification, but one rule is not yet
   enforceable against a stand-in: a booking must be marked paid only by a
   verified webhook or a server-side confirmation call, never by a client-side
   redirect. The sandbox still does the latter.

## Not covered by this pass

Worth stating plainly, because a green suite invites the wrong conclusion. These
areas have **no automated coverage** and were not assessed:

| Area | Note |
| --- | --- |
| Audit logging | The only record of who did what; nothing proves it is written |
| Staff OTP | The second factor itself — rate-limited now, but its logic is untested |
| Check-in flow | Room status transitions on arrival |
| Front-desk checkout | Manual checkout and its room-status side effects |
| Browser behaviour | Nothing here drives a browser: no XSS testing, no CSRF verification, no session-fixation or cookie-flag checks |
| Email and PDF rendering | Receipts are generated and hashed in tests, but never opened |
| Deployment | `APP_DEBUG=true`, database is `root` with no password, no HTTPS config, no security headers. All correct for local; none of it is production-ready |

Every money-touching flow now has coverage; the gaps above are operational
rather than financial. Audit logging is the most valuable of them.

Automated tests establish that the behaviours someone thought to test behave
correctly. They are not evidence that no other defects exist — twenty-two were
found here by looking, and a reviewer looking elsewhere would find more.
