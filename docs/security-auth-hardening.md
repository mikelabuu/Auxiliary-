# Authentication Hardening — Security Documentation

This document records the first batch of fixes from the security audit of the
booking system: the **authentication and session layer**. It covers what was
wrong, why each issue mattered, what changed, and how to verify or tune the
result.

It is written so a future developer can answer three questions without reading
the diff: *what was the risk*, *what is the rule now*, and *where do I change
it*.

---

## Table of Contents

1. [Scope](#1-scope)
2. [Fix 1 — The `000000` OTP backdoor](#2-fix-1--the-000000-otp-backdoor)
3. [Fix 2 — Staff email enumeration](#3-fix-2--staff-email-enumeration)
4. [Fix 3 — Unthrottled customer login](#4-fix-3--unthrottled-customer-login)
5. [Fix 4 — Suspension not enforced mid-session](#5-fix-4--suspension-not-enforced-mid-session)
6. [Fix 5 — The staff password oracle](#6-fix-5--the-staff-password-oracle)
7. [New building blocks](#7-new-building-blocks)
8. [Rate limit reference](#8-rate-limit-reference)
9. [Testing](#9-testing)
10. [Supporting change — migration portability](#10-supporting-change--migration-portability)
11. [Operational notes](#11-operational-notes)
12. [Known follow-ups](#12-known-follow-ups)
13. [File manifest](#13-file-manifest)

---

## 1. Scope

This batch covers authentication only. It does **not** cover the payment
routes, stored XSS in the room boards, or the input-validation cleanups — those
are tracked in [Known follow-ups](#12-known-follow-ups).

Nothing here changes how a legitimate user logs in. Correct credentials still
work exactly as before; the changes only affect failure paths, throttling, and
what happens to a session after an account is suspended.

---

## 2. Fix 1 — The `000000` OTP backdoor

**Severity: critical.**

### What was wrong

`StaffAuthController::verifyOtp()` special-cased the literal code `000000`:

```php
// BEFORE
if ($request->otp_code === '000000') {
    $otpRecord = StaffOtp::where('staff_id', $staffId)->latest()->first();
} else {
    $otpRecord = StaffOtp::where('staff_id', $staffId)
        ->where('otp_code', $request->otp_code)
        ->whereNull('used_at')
        ->where('otp_expires_at', '>', now())
        ->latest()
        ->first();
}
```

The backdoor branch dropped **three** checks at once: the code match, the
`used_at` check, and the expiry check. It returned the most recent OTP row for
the account whatever its state, and the caller treated a non-null result as
success.

### Why it mattered

Reaching step 2 requires a correct email and password, so this was not a way in
from nothing. What it destroyed was the *second factor*: anyone holding a
leaked, phished, or reused staff password typed `000000` and was in. The OTP
step became decoration.

It was also silent. Nothing in the audit log distinguishes a `000000` login
from a real one.

### Current state at time of the fix

`STAFF_OTP_ENABLED` was unset, so `config('staff.otp_enabled')` was `false` and
the OTP step was being skipped entirely — the backdoor was **dormant, not
active**. That is precisely why it was worth fixing now: the flag exists to be
turned on for production, and the day it is, 2FA would have silently done
nothing.

### What changed

The special case is gone. There is one query, and it enforces all four
conditions. Additionally, a successful verification now burns *every*
outstanding code for that account, not just the one that matched — otherwise an
earlier unused OTP stayed replayable until its own expiry.

```php
// AFTER
$otpRecord = StaffOtp::where('staff_id', $staffId)
    ->where('otp_code', $request->otp_code)
    ->whereNull('used_at')
    ->where('otp_expires_at', '>', now())
    ->latest()
    ->first();

if ($otpRecord) {
    StaffOtp::where('staff_id', $staffId)
        ->whereNull('used_at')
        ->update(['used_at' => now()]);
    // ...
}
```

The OTP attempt limiter was also re-keyed from `staff_id|ip` to `staff_id`
alone. Reaching this step already requires a valid password, so there is no
lockout-DoS to protect against — and including the IP let an attacker rotating
addresses walk the whole six-digit space.

> **If you need a test bypass**, gate it on the environment, never on a magic
> value: `if (app()->environment('local') && $code === config('staff.test_otp'))`.
> A constant in a `!== 'production'` branch is still a constant someone can try.

### Follow-up — making OTP fit to switch on

Removing the backdoor made OTP *meaningful*; three further problems made it not
yet *safe to enable*. All three are fixed.

**The codes were predictable.** Both generation sites used
`rand(100000, 999999)`. `rand()` is Mersenne Twister — observing a handful of
codes is enough to infer the generator state and predict the next one, which
defeats the second factor for anyone patient enough to collect samples. Now
`random_int()`, which draws from the OS CSPRNG.

**A mail failure stranded the login.** The old order was: create the OTP row →
`notify()` → set `staff_pending_id`. The notification is *not* `ShouldQueue`
and `QUEUE_CONNECTION=sync`, so the mail goes out inline on the login request.
A throwing SMTP call produced a 500 *before* the session key was set, leaving
the staff member unable to reach the OTP form at all — not even to press
Resend. The session key is now set first, and delivery failure is caught,
logged, and surfaced as a usable error.

**The two paths could drift.** Login and Resend each had their own copy of the
generate/store/audit/send sequence. They now share one private helper,
`issueOtp(Staff $staff, string $action, string $description): bool`, which
returns `false` on delivery failure and lets each caller word its own message.

---

## 3. Fix 2 — Staff email enumeration

**Severity: high.**

### What was wrong

Three problems in one code path in `loginStaff()`:

1. A missing account returned `"No account found with this email."` while a bad
   password returned `"Invalid staff credentials"` — the response told an
   attacker which staff emails are real.
2. The `!$staff` branch returned **before** the rate limiter was consulted, so
   enumeration was completely unlimited.
3. The missing-account branch returned without running a hash comparison, so it
   answered in ~1 ms where a real account took ~100 ms of bcrypt. Even after
   unifying the message, response time alone still leaked the answer.

### What changed

The order is now: **throttle check → account lookup → password check**. Both
failure cases return the identical message, and the missing-account path
compares against a dummy bcrypt hash so the timing matches.

```php
private const DUMMY_HASH = '$2y$12$uls4l0TXeBb6YY3SqUj4UOzd1KZDhgzXHxTtRt0clkhrK99q0nGf2';

if (!$staff) {
    Hash::check($credentials['staff_password'], self::DUMMY_HASH);
    RateLimiter::hit($key, 900);

    return back()->withErrors(['staff_email' => 'Invalid staff credentials'])
        ->onlyInput('staff_email');
}
```

`DUMMY_HASH` is a real 60-character bcrypt hash of a random string. It must be
a valid bcrypt digest — Laravel's hasher throws
`"This password does not use the Bcrypt algorithm"` on a malformed one.

The suspension check moved to *after* the password is verified. Announcing "this
account is suspended" to someone who has not proven the password would reintroduce
the same enumeration leak.

---

## 4. Fix 3 — Unthrottled customer login

**Severity: high.**

`AuthController::loginUser()` had no rate limiting whatsoever. Every guest
account was open to unlimited password guessing. Staff login had a limiter;
the customer side simply never got one.

`loginUser()` now mirrors the staff pattern — 5 attempts keyed on
`email|ip` with a 15-minute decay, cleared on success — plus a per-IP backstop
on the route (see [§8](#8-rate-limit-reference)).

Two smaller fixes in the same method:

- **Validation.** `'email' => 'required'` became `'required|email'`.
  `Auth::attempt()` only ever matches on the email column, so accepting
  arbitrary strings served no purpose.
- **Suspended-user sessions.** The suspension branch called `Auth::logout()`,
  which clears the authenticated user but leaves the session record intact. It
  now calls `session()->invalidate()` and `session()->regenerateToken()` as
  well, so the suspended user is not left holding a usable session cookie.

---

## 5. Fix 4 — Suspension not enforced mid-session

**Severity: high.**

`is_suspended` was only ever read inside the two login controllers. Suspending
an account therefore did nothing to the sessions it already had: the staff
member kept full access until `SESSION_LIFETIME` (120 minutes) ran out. For a
control whose entire purpose is revoking access from someone right now, that is
the wrong behaviour.

A new middleware, `EnsureStaffNotSuspended` (alias `staff.active`), checks the
flag on every staff request and tears the session down the moment it flips.

It is applied to **all three** staff route groups, not just the role-gated
ones, so routes behind bare `auth:staff` — logout, receipt verification, guest
history, the re-auth endpoints — are covered too.

The middleware content-negotiates its response. Much of the staff console is
`fetch()`-driven, and a 302 to the login page would be parsed as a successful
response body:

| Request type | Response |
|---|---|
| Normal page load | `302` → `staff.login` with an error message |
| `expectsJson()` | `403` + `{"success": false, "message": "..."}` |

---

## 6. Fix 5 — The staff password oracle

**Severity: high.**

Six endpoints implement "re-enter your password to continue" before a
destructive action:

- `staff.rooms.verifyPassword`
- `staff.completedbookings.verify-password`
- `staff.userrecords.verify-password`
- `staff.staffrecords.verify-password`
- `staff.discounts.verify-password`
- `staff.bookings.verify-password`

Each runs `Hash::check()` and reports the boolean result as JSON, and none had
a rate limit. Anyone with a hijacked staff session could brute-force the
staff member's own password to unlock the very actions the prompt exists to
guard.

All six now carry `throttle:staff-password` — 5 attempts per minute, keyed on
the **staff account** rather than the IP, so the limit follows the session it
is actually protecting.

---

## 7. New building blocks

### `EnsureStaffNotSuspended` middleware

`app/Http/Middleware/EnsureStaffNotSuspended.php`, aliased as `staff.active` in
`bootstrap/app.php`. Add it to any future staff route group. It is a no-op for
unauthenticated requests, so ordering after `auth:staff` is what you want:

```php
Route::middleware(['auth:staff', 'staff.active', 'staff.role:admin,master_admin'])
```

### Named rate limiters

Defined in `AppServiceProvider::bootRateLimiters()`. Named limiters keep the
policy in one place instead of scattering magic numbers across route
definitions, and they can key on the authenticated user rather than the IP.

| Name | Policy |
|---|---|
| `staff-password` | 5/minute, keyed on staff id (falls back to IP) |
| `registration` | 5 per 10 minutes, keyed on IP |
| `password-reset` | 5 per 10 minutes, keyed on `email\|ip` |

Use them from a route with `->middleware('throttle:<name>')`. A typo in the name
fails at request time, not boot time — verify with:

```bash
php artisan tinker --execute="var_dump((bool) app(Illuminate\Cache\RateLimiter::class)->limiter('staff-password'));"
```

---

## 8. Rate limit reference

Inline limiters live in the controller and use the `RateLimiter` facade
directly; route limiters are middleware. Login endpoints have both — the inline
one is per-account, the route one is a per-IP backstop that stops a single host
spraying many different accounts.

| Endpoint | Limit | Keyed on | Where |
|---|---|---|---|
| `POST /staff/login` | 5, 15-min decay | `email\|ip` | Controller |
| `POST /staff/login` | 20/min | ip | `throttle:20,1` |
| `POST /login/user` | 5, 15-min decay | `email\|ip` | Controller |
| `POST /login/user` | 20/min | ip | `throttle:20,1` |
| `POST /staff/otp` | 5, 15-min decay | `staff_id` | Controller |
| `POST /staff/otp/resend` | 3 per 10 min | `staff_id` | Controller (pre-existing) |
| `POST /signup` | 5 per 10 min | ip | `throttle:registration` |
| `POST /forgot-password` | 5 per 10 min | `email\|ip` | `throttle:password-reset` |
| `POST /reset-password` | 10/min | ip | `throttle:10,1` |
| 6 × `verify-password` | 5/min | `staff_id` | `throttle:staff-password` |
| `POST /email/verification-notification` | 6/min | ip | `throttle:6,1` (pre-existing) |

### Where the counters live

Rate limiters use the **cache**, not the session. This project runs
`CACHE_STORE=database`, so counters survive a restart and are shared across
processes. Two consequences:

- Clearing the cache (`php artisan cache:clear`) resets every limiter. That is
  the intended escape hatch when a real user locks themselves out.
- If the cache store is ever switched to `array`, **every limit silently stops
  working**. Do not do this outside tests.

### Known limitation

Keying on `email|ip` means an attacker with a pool of IP addresses can still
spread guesses across them. The per-IP backstop caps any single host, but a
distributed attack is not fully stopped by throttling alone. The mitigation is
account lockout or CAPTCHA after N failures across all IPs; neither is
implemented, and both trade off against a lockout-DoS on staff accounts. This
is a deliberate, documented gap rather than an oversight.

---

## 9. Testing

`tests/Feature/StaffAuthSecurityTest.php` — 12 regression tests covering every
fix above:

```bash
php artisan test --filter=StaffAuthSecurityTest
```

| Test | Guards against |
|---|---|
| `master_otp_000000_is_rejected` | the backdoor returning |
| `expired_otp_is_rejected` | expiry check being dropped |
| `already_used_otp_is_rejected` | `used_at` check being dropped |
| `valid_otp_still_logs_staff_in` | over-tightening the query |
| `consuming_one_otp_burns_the_other_outstanding_codes` | OTP replay |
| `login_step_one_issues_an_otp_and_marks_the_session_pending` | the happy path regressing |
| `generated_otp_is_a_six_digit_code` | a bad RNG swap breaking the format |
| `mail_failure_does_not_strand_the_login` | SMTP outage 500ing mid-login |
| `unknown_staff_email_gets_the_same_error_as_a_bad_password` | enumeration |
| `suspended_staff_session_is_terminated_mid_session` | stale sessions |
| `suspended_staff_gets_json_403_on_ajax_routes` | AJAX misparsing a 302 |
| `customer_login_is_rate_limited` | throttle regressions |

The OTP tests set `config(['staff.otp_enabled' => true])` explicitly, so they
pass regardless of how the environment flag is set.

The suspension middleware was additionally verified end-to-end against the real
MySQL dev database with a live session cookie: an active staff session returned
`200`, and after flipping `is_suspended` the *same cookie* returned `302` to the
login page on an HTML route and `403 {"success":false}` on a JSON route.

---

## 10. Supporting change — migration portability

The test suite runs on in-memory SQLite (see `phpunit.xml`), but three older
migrations issue raw MySQL `ALTER TABLE ... MODIFY COLUMN ... ENUM` statements,
which SQLite cannot parse. `RefreshDatabase` failed before a single test ran.

Those three now carry the same `DB::getDriverName() !== 'mysql'` guard that
`2026_07_20_000001_convert_status_columns_to_string` already established:

- `2025_09_13_010449_alter_bookings_status_column.php`
- `2025_09_27_064346_add_expired_status_to_bookings_table.php`
- `2025_10_12_125103_add_master_admin_role_to_staff.php`

All three are historical ENUM widenings that the 2026-07-20 migration later
supersedes by converting those columns to `VARCHAR`. **MySQL behaviour is
completely unchanged** — the guard only affects non-MySQL drivers, where the
statements could never have run anyway.

> ⚠️ ~~One caveat for future test authors: on SQLite, `bookings.status` keeps
> the CHECK constraint from the original `enum('pending','booked','cancelled')`
> definition.~~ **Resolved in batch 2** by
> `2026_07_28_000001_relax_status_check_constraints_on_sqlite.php`, which
> converts those columns to plain strings on non-MySQL drivers. Booking-touching
> tests now run on SQLite.

---

## 11. Operational notes

### What users will notice

- A staff member who fails login 5 times waits 15 minutes. The error states the
  remaining seconds.
- "No account found with this email" is gone. Every failed staff login now says
  *Invalid staff credentials*. **This is intentional** — expect a support
  question about it, and do not "fix" it back.
- Suspending a staff account now logs them out immediately instead of at the
  end of their session.

### Unlocking someone who is locked out

```bash
php artisan cache:clear
```

This resets all limiters globally. There is no per-user unlock command; add one
if it becomes routine.

### OTP is enabled

`STAFF_OTP_ENABLED=true` is set in `.env`. Staff login is two-step: email and
password, then a six-digit code mailed to the staff address.

Verified end to end against the dev server: step 1 redirects to the OTP form,
`000000` is rejected and leaves the session unauthenticated, and the real code
redirects to the dashboard with a working session.

**Measured send latency: 4.6–6.5 seconds**, added to every staff login. This is
the single biggest argument for queueing the notification — see below.

#### ⚠️ A bad staff email address fails silently

SMTP acceptance is not delivery. Gmail accepts the message, returns success,
and only bounces asynchronously to `MAIL_FROM_ADDRESS` minutes later. From the
application's point of view the send *succeeded* — `issueOtp()` returns `true`,
the audit log records `otp_requested`, and nothing indicates a problem.

The staff member simply never receives a code and cannot log in. The
`try/catch` in `issueOtp()` only catches *synchronous* failures (connection
refused, auth rejected, malformed address); it cannot catch a bounce.

Practical consequence: **verify the email address when creating a staff
account.** If someone reports never receiving codes, check the
`MAIL_FROM_ADDRESS` inbox for a bounce before assuming the OTP system is
broken.

#### Turning OTP back off

If the mailer breaks, staff logins stop working. Rollback is two commands:

```bash
php artisan config:clear
```

after setting `STAFF_OTP_ENABLED=false` in `.env`. Login reverts to
single-factor immediately. In `local` the `/__dev-login` route is a further
backstop.

The mailer itself: `MAIL_MAILER=smtp` against `smtp.gmail.com` requires
`MAIL_PASSWORD` to be a Gmail **app password**, not the account password —
Google rejects the latter outright, and that failure *is* synchronous, so it
would be caught and logged rather than silently lost.

#### Recommended next step — queue the notification

`StaffLoginOtpNotification` imports `ShouldQueue` but does not implement it,
and `QUEUE_CONNECTION=sync`, so the mail is sent inline on the login request.
That is where the 4.6–6.5s per-login cost comes from, and it also means an SMTP
stall holds a web worker open.

Implementing `ShouldQueue` and running a real queue connection would drop login
latency to near zero and make retries possible. The trade-off is that a queue
worker must be running, or no OTP is ever delivered — which is a worse failure
mode than slow logins. Do not make this change without also arranging worker
supervision.

---

## 12. Known follow-ups

Carried over from the audit, **not** addressed in this batch:

### Batch 2 — payment routes ✅ done

Fixed and documented in
[`security-payment-hardening.md`](security-payment-hardening.md).

Two claims made here originally were corrected once the running application was
probed rather than only read:

- `POST /sandbox/process/{payment}` did **not** work as a single anonymous
  request — the `web` group's CSRF middleware returned `419`. But an anonymous
  session is issued a valid token, so one extra `GET /` was enough. The
  conclusion stood; the mechanism took two requests.
- `sandbox/webhook/*` was **not** CSRF-exempt. Its `withoutMiddleware()` call
  named `App\Http\Middleware\VerifyCsrfToken`, a Laravel 10 leftover that is not
  the class in the web group, so the exclusion matched nothing and the endpoint
  returned `419` to everyone. It was non-functional rather than exposed — and
  would have become an open endpoint the moment someone corrected the
  exemption.

### Batch 3 — input handling

- Stored XSS: `room_number`, `room_type`, `wing` and `type_name` are
  interpolated raw into `innerHTML` in the manual-booking and walk-in room
  boards. Neither file defines an `esc()` helper, though
  `partials/dashboard/calendar-modal.blade.php` does and uses it correctly.
  Room field validation allows any characters.
- `DiscountController::store()` takes `reservation_id` from an
  attacker-controlled array key without checking it belongs to the booking.
- `MainReportsController` passes `$request->all()` into the report service with
  no validation. Not injectable — filters and columns are whitelisted — but
  missing keys produce 500s.
- `SettingsController::update()` changes the password and *then* falls through
  to profile validation, so a password-only submission can change the password
  and still return a validation error.

### Deployment checklist

- `APP_DEBUG=true` and `APP_ENV=local` are correct for local work. Shipping as
  is renders stack traces containing DB credentials on any 500.
- `SESSION_SECURE_COOKIE` is unset — set it once the site is on HTTPS.
- Remove the `/__dev-login` route (`routes/web.php`). It is environment-guarded
  and safe today, but its own comment says to remove it.
- Password minimum is 6 characters across signup, reset, and staff creation.
  Consider Laravel's `Password::defaults()` with an uncompromised check.

---

## 13. File manifest

### Added

| File | Purpose |
|---|---|
| `app/Http/Middleware/EnsureStaffNotSuspended.php` | Per-request suspension enforcement |
| `tests/Feature/StaffAuthSecurityTest.php` | 9 regression tests |
| `docs/security-auth-hardening.md` | This document |

### Modified

| File | Change |
|---|---|
| `app/Http/Controllers/StaffAuthController.php` | Removed `000000` backdoor; burn outstanding OTPs; reordered throttle; unified error message; dummy-hash timing guard; re-keyed OTP limiter; `random_int()` codes; shared `issueOtp()` helper with delivery-failure handling |
| `app/Http/Controllers/AuthController.php` | Added login rate limiting; `email` validation; session invalidation for suspended users |
| `bootstrap/app.php` | Registered the `staff.active` middleware alias |
| `app/Providers/AppServiceProvider.php` | Added `bootRateLimiters()` with three named limiters |
| `routes/web.php` | Applied `staff.active` to 3 staff groups; throttles on login, signup, password reset, and 6 verify-password endpoints |
| `database/migrations/2025_09_13_010449_*.php` | MySQL driver guard |
| `database/migrations/2025_09_27_064346_*.php` | MySQL driver guard |
| `database/migrations/2025_10_12_125103_*.php` | MySQL driver guard |
