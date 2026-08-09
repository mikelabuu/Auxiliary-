# Farmers Hostel — Booking & Operations System

Booking and front-desk system for Farmers Hostel, CLSU Auxiliary Services
Program (Science City of Muñoz, Nueva Ecija).

Guests search availability, reserve rooms and upload proof of payment. Staff run
the front desk, room board, payment verification and records behind the same
data. One login form serves both — `LoginController` resolves the identity, then
hands off to the `web` guard for guests or the `staff` guard for staff.

**Laravel 12 · PHP 8.2+ · MySQL · Blade + Alpine · Tailwind via Vite**

---

## Setup

Requires PHP 8.2+, Composer, Node 18+, and MySQL running (XAMPP is what this is
developed against).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create an empty database named `aux_system` (or change `DB_DATABASE` in `.env`),
then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

The app is at **http://127.0.0.1:8000**.

> `npm run build`, never a bare `vite build` — the build script runs
> `php artisan view:cache` first, and Tailwind scans *compiled* Blade. Building
> against a cold view cache silently drops utility classes.

## Test accounts

All seeded with the password `password`.

| Role | Email | Lands on |
|---|---|---|
| Master admin | `master@example.com` | `/staff/dashboard` |
| Admin | `admin@example.com` | `/staff/dashboard` |
| Front desk | `frontdesk@example.com` | `/front-desk/dashboard` |
| Guest | `user@example.com` | `/` |

Staff and guests sign in at the **same** form, `/login`. (`/staff/login`
redirects there; it is not a second form.)

The seeder also creates 6 room types and 12 rooms across three wings, so
availability, the room board and the booking calendar all have something to
show on a fresh database.

## Things that will confuse you otherwise

- **Blade edits appear to do nothing.** Views are cached in this repo. Run
  `php artisan view:clear && php artisan view:cache` after editing a template.
- **The site renders unstyled.** Check for a leftover `public/hot` file and
  delete it — it points every asset at a Vite dev server that is no longer
  listening. This is the first thing to check for any "the site looks broken or
  slow" report.
- **Staff OTP is off by default** (`STAFF_OTP_ENABLED=false`). Turn it on to
  exercise that path, but with `MAIL_MAILER=log` the code is written to
  `storage/logs/laravel.log`, not emailed.
- **No email is actually sent** with the default `MAIL_MAILER=log`. Booking
  confirmations, receipts and OTPs all land in `storage/logs/laravel.log`.
- **Live console updates need Reverb.** Not required — with
  `BROADCAST_CONNECTION=log` the consoles fall back to polling and nothing
  breaks. See `docs/operations.md` to turn it on.
- **Unpaid holds only expire if the scheduler runs.** Without
  `php artisan schedule:run` firing every minute, a `pending_payment` booking
  holds its room forever and the room quietly disappears from availability. Not
  a bug — a missing process. `docs/operations.md` covers this in detail.

## Running the tests

```bash
php artisan test
```

## Where things are

| Path | What |
|---|---|
| `docs/operations.md` | What must be running, and what silently breaks when it isn't. **Read this one.** |
| `docs/security-auth-hardening.md` | Login, guards, OTP, rate limiting |
| `docs/security-input-hardening.md` | Validation rules and why each exists |
| `docs/security-payment-hardening.md` | Payment proof handling and verification |
| `PRODUCT.md` | Who the users are and what the product is for |
| `DESIGN.md` | Design system and visual language |
| `plans/` | Per-change implementation plans |

## Notes on the domain

- Prices are **always** recomputed server-side from the room catalog. Anything
  posted by the client for price or capacity is display continuity only and is
  never trusted.
- `pending_payment` blocks a room exactly as hard as a paid booking does. That
  is deliberate — see the availability logic in `BookingController`.
- Addresses use the Philippine Standard Geographic Code. The full dataset is
  committed at `resources/data/psgc.json` and served by `PsgcController`; the
  form selects post `code|name` pairs, not bare codes.
