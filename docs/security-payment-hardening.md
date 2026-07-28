# Payment Route Lockdown — Security Documentation

Batch 2 of the security audit: the **payment flow**. Batch 1 (authentication
and sessions) is documented in
[`security-auth-hardening.md`](security-auth-hardening.md).

The gateway is still a simulation — no real money moves. What this batch fixes
is the **trust boundary around it**, which is gateway-independent: who is
allowed to start a payment, who may read or drive one, and how a server-to-server
callback proves it is genuine. Swapping the sandbox for a real provider should
not require redesigning any of it.

---

## Table of Contents

1. [What was actually wrong](#1-what-was-actually-wrong)
2. [Fix 1 — Authentication and ownership](#2-fix-1--authentication-and-ownership)
3. [Fix 2 — View-name injection](#3-fix-2--view-name-injection)
4. [Fix 3 — The webhook](#4-fix-3--the-webhook)
5. [Fix 4 — State guards](#5-fix-4--state-guards)
6. [Supporting change — SQLite schema parity](#6-supporting-change--sqlite-schema-parity)
7. [Testing](#7-testing)
8. [Swapping in a real gateway](#8-swapping-in-a-real-gateway)
9. [Follow-ups](#9-follow-ups)
10. [File manifest](#10-file-manifest)

---

## 1. What was actually wrong

Every payment route carried **no middleware at all**:

```
GET   booking/{booking}/pay        PaymentController@pay
GET   sandbox/pay/{payment}        showPaymentPage
POST  sandbox/process/{payment}    processPayment
GET   sandbox/result/{status}/{payment}   result
GET   sandbox/status/{payment}     status
POST  sandbox/webhook/{payment}    webhook
```

No `auth`, and no ownership check anywhere in the controllers. Payment ids are
sequential integers.

### Two corrections to the original audit

The first audit pass was read-only. Probing the running application refined two
claims, both worth recording because they changed the severity picture.

**The process endpoint needed a CSRF token.** The audit said an anonymous
`POST /sandbox/process/{payment}` marked a booking paid. In fact it returned
`419` — the `web` group's CSRF middleware applied. But Laravel issues a CSRF
token to *anonymous* sessions too, so one `GET /` supplied a valid token and
the second request went straight through to the controller. The conclusion held
— an unauthenticated attacker could drive someone else's payment — but it took
two requests, not one.

**The webhook was broken, not exposed.** The audit called it "CSRF-exempt with
no signature verification". It was not actually exempt. The route said:

```php
->withoutMiddleware([VerifyCsrfToken::class])
```

referencing `App\Http\Middleware\VerifyCsrfToken` — a leftover from the Laravel
10 skeleton that is *not* the class in the web group (that is
`Illuminate\Foundation\Http\Middleware\ValidateCsrfToken`). The exclusion
silently matched nothing, so the webhook returned `419` to every caller and had
never worked. Its `$except = ['sandbox/webhook/*']` property was equally inert.

So the webhook was a latent problem rather than an active hole: the moment
someone "fixed" the exemption the right way, it would have become an
unauthenticated endpoint that marks any payment successful.

### Verified before the fix

| Request | Result |
|---|---|
| `GET /sandbox/status/21` anonymous | `200 {"status":"success"}` — payment state leaked |
| `GET /sandbox/pay/21` anonymous | `200` — another user's payment page rendered |
| `POST /sandbox/process/21` anonymous + token | `302` into the controller |
| `POST /sandbox/webhook/21` | `419` — non-functional |

---

## 2. Fix 1 — Authentication and ownership

The five guest-facing routes now sit behind `auth` + `verified`, matching the
rest of the booking journey:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/booking/{booking}/pay', [PaymentController::class, 'pay'])->name('bookings.pay');

    Route::prefix('sandbox')->name('sandbox.')->group(function () {
        Route::get('/pay/{payment}', ...)->name('pay');
        Route::post('/process/{payment}', ...)->name('process');
        Route::get('/result/{status}/{payment}', ...)->where('status', 'success|failed')->name('result');
        Route::get('/status/{payment}', ...)->name('status');
    });
});
```

Middleware only establishes *who is asking*. Ownership is a separate check,
because being logged in says nothing about whose payment this is:

```php
private function authorizePayment(Payment $payment): void
{
    $booking = $payment->booking;

    abort_if($booking === null, 404);
    abort_unless($booking->user_id === Auth::id(), 403);
}
```

Called at the top of `showPaymentPage`, `processPayment`, `result` and
`status`. `PaymentController::pay` does the equivalent against the booking.

> **Why the booking, not `payments.user_id`?** The payments table has its own
> `user_id`, but it is denormalised — written once at creation from
> `$booking->user_id`. The booking is the authority; checking the copy would
> trust a value that can drift.

---

## 3. Fix 2 — View-name injection

`result()` built a view path straight from a URL segment:

```php
public function result($status, Payment $payment)
{
    return view("sandbox.$status", compact('payment'));   // BEFORE
}
```

Now constrained in two places — at the route with
`->where('status', 'success|failed')`, and again in the controller:

```php
abort_unless(in_array($status, ['success', 'failed'], true), 404);
```

The route constraint alone would do it, but a route constraint is easy to drop
during a refactor and the consequence is not obvious from the controller. Both.

---

## 4. Fix 3 — The webhook

A callback has no session, so ownership cannot be checked. The signature is the
entire trust boundary.

The CSRF exemption is now declared where it actually takes effect —
`bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'sandbox/webhook/*',
]);
```

and the endpoint authenticates with HMAC-SHA256 over the raw request body:

```php
private function webhookSignatureIsValid(Request $request): bool
{
    $secret = config('services.sandbox.webhook_secret');

    // Fail closed. An unset secret must never mean "accept anything".
    if (blank($secret)) {
        Log::error('[SANDBOX-WEBHOOK] SANDBOX_WEBHOOK_SECRET is not set; rejecting.');
        return false;
    }

    $provided = (string) $request->header('X-Sandbox-Signature');

    if ($provided === '') {
        return false;
    }

    return hash_equals(
        hash_hmac('sha256', $request->getContent(), $secret),
        $provided
    );
}
```

Three properties worth keeping when the real gateway lands:

- **Signed over the body**, so a captured signature cannot be reused with
  different content.
- **`hash_equals`**, not `===` — constant-time, no timing oracle on the digest.
- **Fails closed.** A missing secret rejects everything. The tempting
  `if (!$secret) return true;` shortcut turns a config mistake into an open
  endpoint.

The handler is also idempotent now — gateways retry, and applying a
confirmation twice must be harmless:

```php
if ($payment->status === 'success' && $payment->webhook_verified) {
    return response()->json(['message' => 'Already processed', 'status' => 'success']);
}
```

### Calling it

`SANDBOX_WEBHOOK_SECRET` lives in `.env` (present in `.env.example`, unset by
default). Generate one with:

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

To call the webhook by hand, sign the exact body you send:

```bash
BODY='{"event":"payment.success"}'; SIG=$(php -r "echo hash_hmac('sha256', '$BODY', getenv('SANDBOX_WEBHOOK_SECRET'));"); curl -X POST http://127.0.0.1:8000/sandbox/webhook/1 -H "Content-Type: application/json" -H "X-Sandbox-Signature: $SIG" --data-binary "$BODY"
```

---

## 5. Fix 4 — State guards

Two transitions were unguarded.

**`PaymentController::pay` minted a payment for any booking**, whatever its
status — including already-paid and cancelled ones. It now refuses anything not
in `pending_payment`.

**`processPayment` only rejected already-successful payments**
(`if ($payment->status == 'success')`), so a `failed` payment could be driven
again. It now requires `pending`.

Request input is validated rather than read raw:

```php
$validated = $request->validate([
    'payment_type' => ['nullable', Rule::in(['full', 'reservation_fee'])],
    'simulate'     => ['nullable', Rule::in(['success', 'fail'])],
]);
```

Amounts were never taken from the request and still are not — they come from
`$booking->payable_amount ?? $booking->total_price` server-side.

---

## 6. Supporting change — SQLite schema parity

`2026_07_28_000001_relax_status_check_constraints_on_sqlite.php`.

Batch 1 noted that on SQLite, `bookings.status` still carried the CHECK
constraint generated from the *original* `enum('pending','booked','cancelled')`,
because every migration that widened it is MySQL-only. Any test creating a
booking with a modern status failed on a constraint violation — which blocked
this batch's tests entirely.

The new migration converts `bookings.status`, `rooms.status` and `staff.role`
to plain strings on **non-MySQL drivers only**. MySQL returns early: it reached
this state back at `2026_07_20_000001`. The caveat recorded in batch 1 §10 is
now resolved.

---

## 7. Testing

`tests/Feature/PaymentSecurityTest.php` — 10 tests. Full suite: **24 passing**.

```bash
php artisan test --filter=PaymentSecurityTest
```

| Test | Guards against |
|---|---|
| `anonymous_visitors_cannot_reach_any_payment_route` | middleware being dropped |
| `anonymous_visitor_cannot_mark_a_booking_paid` | the headline exploit |
| `another_user_cannot_touch_someone_elses_payment` | IDOR across all four endpoints |
| `owner_can_complete_a_payment` | over-tightening breaking checkout |
| `payment_cannot_be_started_for_a_booking_not_awaiting_payment` | state guard |
| `an_already_processed_payment_cannot_be_re_driven` | state guard |
| `result_status_cannot_address_an_arbitrary_view` | view-name injection |
| `webhook_rejects_missing_and_wrong_signatures` | unsigned callbacks |
| `webhook_accepts_a_correct_signature` | over-tightening breaking the gateway |
| `webhook_fails_closed_when_no_secret_is_configured` | the "no secret = allow" shortcut |

### Verified against the running application

| Check | Before | After |
|---|---|---|
| Anonymous → any payment route | `200` / reached controller | `302 → /login` |
| Other user → `pay`, `status`, `result`, `process` | reachable | `403` |
| `{status}` = `gateway`, `layouts.app`, `../welcome` | rendered / errored | `404` |
| Webhook, no signature | `419` | `401` |
| Webhook, wrong signature | `419` | `401` |
| Webhook, correct signature | `419` | `200`, payment confirmed |
| Webhook, valid signature + tampered body | `419` | `401` |
| Owner full checkout | worked | works — booking `paid` |

---

## 8. Swapping in a real gateway

What should survive the swap:

- **The route group.** Guest-facing endpoints behind `auth` + `verified`; the
  callback outside it, authenticated by signature.
- **`authorizePayment()`.** Ownership resolves through the booking. Unchanged
  regardless of provider.
- **Signature verification shape.** Real providers sign the raw body with a
  shared secret; only the header name and digest algorithm change. Keep
  `hash_equals` and keep failing closed.
- **Idempotency.** Every real gateway retries.

What must change:

- `processPayment` currently *decides* the outcome from a `simulate` field.
  A real gateway decides, and the app records. That field disappears.
- `sleep(1)` is a simulation artefact.
- Real providers require the amount and currency to be echoed back and checked
  against the payment record before marking anything paid.
- `landbank_transaction_id` is populated with `'SBX-' . uniqid()`. Real
  transaction references come from the provider.

---

## 9. Follow-ups

Still outstanding from the audit — **batch 3**:

- **Stored XSS in the room boards.** `room_number`, `room_type`, `wing` and
  `type_name` are interpolated raw into `innerHTML` in
  `staff/manualbooking/index.blade.php` and `staff/frontdesk/walkin/create.blade.php`.
  Neither defines an `esc()` helper, though
  `partials/dashboard/calendar-modal.blade.php` does and uses it correctly.
  Room field validation permits any characters.
- **`DiscountController::store()`** takes `reservation_id` from an
  attacker-controlled array key without checking it belongs to the booking.
- **`MainReportsController`** passes `$request->all()` into the report service
  unvalidated. Not injectable — filters and columns are whitelisted — but
  missing keys produce 500s.
- **`SettingsController::update()`** changes the password and then falls
  through to profile validation, so a password-only submission can change the
  password and still return a validation error.

Deployment checklist items (`APP_DEBUG`, `SESSION_SECURE_COOKIE`,
`/__dev-login`, the 6-character password minimum) are listed in
[`security-auth-hardening.md`](security-auth-hardening.md) §12.

### Dead code worth removing

`app/Http/Middleware/VerifyCsrfToken.php` is a Laravel 10 leftover that nothing
references now. Its `$except` array reads like active configuration and is not —
exactly the confusion that hid the broken webhook exemption. Deleting it is safe
but out of scope here.

---

## 10. File manifest

### Added

| File | Purpose |
|---|---|
| `tests/Feature/PaymentSecurityTest.php` | 10 regression tests |
| `database/migrations/2026_07_28_000001_relax_status_check_constraints_on_sqlite.php` | SQLite schema parity |
| `docs/security-payment-hardening.md` | This document |

### Modified

| File | Change |
|---|---|
| `routes/web.php` | Payment routes moved behind `auth`+`verified`; `{status}` constrained; dead `VerifyCsrfToken` import removed |
| `app/Http/Controllers/PaymentController.php` | Ownership check; `pending_payment` state guard |
| `app/Http/Controllers/Payments/SandboxGatewayController.php` | `authorizePayment()` on four endpoints; view-name validation; HMAC webhook verification; idempotency; input validation; stricter state guard |
| `bootstrap/app.php` | CSRF exemption declared where it takes effect |
| `config/services.php` | `sandbox.webhook_secret` |
| `.env.example` | `SANDBOX_WEBHOOK_SECRET` documented |
