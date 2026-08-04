# Input Hardening — Security Documentation

Batch 3 of the security audit, and the last. Covers **stored XSS in the room
boards** and three input-handling defects.

- Batch 1 — [`security-auth-hardening.md`](security-auth-hardening.md) (login, sessions, OTP)
- Batch 2 — [`security-payment-hardening.md`](security-payment-hardening.md) (payment routes)

---

## Table of Contents

1. [Fix 1 — Stored XSS in the room boards](#1-fix-1--stored-xss-in-the-room-boards)
2. [Fix 2 — Discount files and the reservation id](#2-fix-2--discount-files-and-the-reservation-id)
3. [Fix 3 — Unvalidated report input](#3-fix-3--unvalidated-report-input)
4. [Fix 4 — The password-change fall-through](#4-fix-4--the-password-change-fall-through)
5. [Testing](#5-testing)
6. [Remaining work](#6-remaining-work)
7. [File manifest](#7-file-manifest)

---

## 1. Fix 1 — Stored XSS in the room boards

**Severity: medium-high.** Requires staff access to plant, but crosses a
privilege boundary once planted.

### What was wrong

The manual-booking and walk-in room boards build their markup as HTML strings
and assign it with `innerHTML`. Room fields went in raw:

```js
// BEFORE
return `<button type="button" data-room-tile="${room.room_number}" ...>
    <span class="font-data text-sm font-extrabold tabnum">${room.room_number}</span>`;
```

`room_number`, `room_type`, `wing` and the room type's `name` are all free-text
fields staff type in. Validation permitted any characters:

```php
'room_number' => 'required|unique:rooms,room_number',   // BEFORE — no format rule
```

So an admin saving a room called `" onfocus=alert(1) autofocus x="` planted
script that ran in **every front-desk browser** that opened the board. An admin
attacking front-desk staff is a real privilege boundary — and it is also the
shape a compromised admin account takes.

Nine sinks across the two files: the type pill (text + `data-type-pill`
attribute), the board section heading and `data-board-section`, the room tile
(`data-room-tile` attribute + visible number), the wing label, three hidden
form inputs, and the summary line.

`textContent` assignments in the same files were already safe and were left
alone.

### What changed

**Output** — both views now carry the same `esc()` helper that
`partials/dashboard/calendar-modal.blade.php` already defined and used
correctly. This was an inconsistency, not an unknown:

```js
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
```

Applied at all nine sinks. It escapes `"` and `'` as well as angle brackets, so
it is correct for quoted-attribute context as well as text.

One subtlety worth preserving: the wing label falls back to a literal
`&nbsp;`, which must *not* be escaped. The fallback stays outside the call:

```js
${esc(wingLabel(room.wing)) || '&nbsp;'}
```

**Input** — the real defence is escaping at render, but a room label has no
business containing markup, so it is now rejected at the door:

```php
private const SAFE_LABEL = 'regex:/^[A-Za-z0-9][A-Za-z0-9 _\-]*$/';
```

Applied to `room_number`, `room_type` and `wing` in both `store()` and
`update()`, with plain-language error messages. `RoomType::$name` gets a
Unicode-aware equivalent that also allows `&`, since it is a display name:

```php
private const SAFE_NAME = 'regex:/^[\pL\pN][\pL\pN \-&]*$/u';
```

The room type **slug** needed nothing — `Str::slug()` already produces
`[a-z0-9-]` only.

### Checked against real data first

Existing values were surveyed before tightening, so no current room fails to
save on edit:

| Field | Existing values |
|---|---|
| `room_number` | `101`–`217`, all numeric |
| `room_type` | `deluxe`, `triple`, `double`, `quadruple`, `dormitory1`, `dormitory2` |
| `wing` | `rooster`, `tumana`, `chev_re`, `torii` |
| `RoomType::name` | `Deluxe`, `Dormitory1`, `Double`, `Quadruple`, `Triple` |

`A-12`, `210 B` and `chev_re` all still validate. So do `Bed & Breakfast` and
`Twin-Share` as type names.

---

## 2. Fix 2 — Discount files and the reservation id

`DiscountController::store()` iterated the uploaded files keyed by reservation
id and wrote that key straight to the database:

```php
foreach ($discountFiles as $reservationId => $files) {   // BEFORE
    // ...
    $discount->files()->create([
        'reservation_id' => $reservationId,   // never checked
```

The array **key** is entirely attacker-controlled. A guest could file their
senior/PWD IDs against a reservation belonging to somebody else's booking —
corrupting the record staff review when approving a discount.

Now only reservations belonging to the booking being discounted are accepted;
anything else is skipped:

```php
$ownReservationIds = $booking->reservations()->pluck('id')->all();

foreach ($discountFiles as $reservationId => $files) {
    if (!in_array((int) $reservationId, $ownReservationIds, true)) {
        continue;
    }
```

---

## 3. Fix 3 — Unvalidated report input

`MainReportsController::generate()` and `export()` handed `$request->all()`
directly to the report service.

This was **not** injectable — `ReportQueryBuilder` whitelists filter fields
against the schema and `ReportColumnMapper` falls back to a safe default for an
unknown column set. The problem was robustness: a missing `date_range` key threw
an undefined-index error and an unknown `report_type` surfaced as a raw
exception, both as 500s.

Both endpoints now share one `validated()` method that pins the shape:
`report_type` and `column_set` to known values, `date_range.type` to the four
the builder handles, and `filters` to `field => list-of-strings`. Bad input gets
a 422 instead of a stack trace.

---

## 4. Fix 4 — The password-change fall-through

`SettingsController::update()` handles password changes and profile edits in one
method. The password branch changed the password and then **fell through** to
the profile block, which requires `username` and `email`:

```php
if ($request->filled('current_password') || $request->filled('password')) {
    // ... verify, hash, save, Auth::logout()
}                                    // BEFORE — no return

$request->validate([                 // a password-only form fails here
    'username' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email,' . $user->id,
]);
```

A password-only submission changed the password and *then* showed a validation
error — telling the user their change failed when it had already succeeded.

The branch now returns, and tears down the session properly on the way out
(`Auth::logout()` alone leaves the session record usable):

```php
Auth::logout();
$request->session()->invalidate();
$request->session()->regenerateToken();

return redirect()->route('login')
    ->with('status', 'Password updated. Please sign in again.');
```

The email-change path had the same weak logout and got the same treatment.

---

## 5. Testing

`tests/Feature/InputHardeningTest.php` — 9 tests. Full suite: **33 passing**.

```bash
php artisan test --filter=InputHardeningTest
```

| Test | Guards against |
|---|---|
| `room_number_rejects_markup` | payloads reaching the column |
| `room_type_and_wing_reject_markup` | the other two room fields |
| `legitimate_room_labels_are_still_accepted` | the rule being too tight |
| `room_type_name_rejects_markup` | the board section heading |
| `legitimate_room_type_names_are_accepted` | over-tightening display names |
| `discount_files_cannot_be_filed_against_another_bookings_reservation` | the reservation-id key |
| `report_endpoint_rejects_malformed_input_instead_of_500ing` | unvalidated report input |
| `password_only_update_succeeds_without_a_validation_error` | the fall-through |
| `wrong_current_password_does_not_change_anything` | the password guard |

### Escaping verified separately

Validation and escaping are independent defences — data planted **before** the
validation existed must still render safely. That was checked by extracting the
real `esc()` definition out of each Blade file and running four payloads
through it, rather than reimplementing it in the test:

| Payload | Result |
|---|---|
| `"><img src=x onerror=alert(1)>` | neutralised |
| `' onfocus=alert(1) autofocus x='` | neutralised |
| `</script><script>alert(1)</script>` | neutralised |
| `"><svg/onload=alert(1)>` | neutralised |

A negative control confirmed the checker flags unescaped input, so the pass is
meaningful rather than a check that never fails.

A room row carrying `"><img src=x onerror=alert(1)>` was also inserted directly
into the dev database (bypassing validation, as pre-existing data would) and
removed afterwards. All 197 compiled Blade templates lint clean — worth doing
explicitly in this project, since `view:cache` succeeding does not by itself
prove the compiled PHP parses.

---

## 6. Remaining work

The audit's three batches are complete. What is left is the deployment
checklist from batch 1 §12, none of which is a code change:

- `APP_DEBUG=true` and `APP_ENV=local` — correct locally, must not ship.
- `SESSION_SECURE_COOKIE` unset — set once the site is on HTTPS.
- ~~`/__dev-login` in `routes/web.php`~~ — removed.
- Password minimum is 6 characters across signup, reset and staff creation.
  Consider `Password::defaults()` with an uncompromised check.
- `SANDBOX_WEBHOOK_SECRET` and `STAFF_OTP_ENABLED` must be set in `.env` on any
  deployment target. Both are in `.env.example`.

### Dead code

`app/Http/Middleware/VerifyCsrfToken.php` is unreferenced since batch 2. Its
`$except` array reads like live configuration and is inert — the exact
confusion that hid the broken webhook exemption. Safe to delete.

### Worth knowing

`Reservation::$fillable` lists `check_in` and `check_out`, but the
`reservations` table has no such columns — mass assignment silently drops them.
Harmless today (dates are read from the parent booking) but misleading, and it
cost time while writing these tests.

---

## 7. File manifest

### Added

| File | Purpose |
|---|---|
| `tests/Feature/InputHardeningTest.php` | 9 regression tests |
| `docs/security-input-hardening.md` | This document |

### Modified

| File | Change |
|---|---|
| `resources/views/staff/manualbooking/index.blade.php` | `esc()` helper + 9 sinks escaped |
| `resources/views/staff/frontdesk/walkin/create.blade.php` | same |
| `app/Http/Controllers/Staff/RoomController.php` | `SAFE_LABEL` format rule on `room_number`, `room_type`, `wing` in store + update |
| `app/Http/Controllers/Staff/RoomTypeController.php` | `SAFE_NAME` format rule on `name` in store + update |
| `app/Http/Controllers/DiscountController.php` | reservation id checked against the booking |
| `app/Http/Controllers/Staff/Reports/MainReportsController.php` | shared `validated()` for both endpoints |
| `app/Http/Controllers/SettingsController.php` | password branch returns; session invalidated on password and email change |
