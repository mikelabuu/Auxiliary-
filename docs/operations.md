# Operations

What has to be running for this system to behave, and what breaks when it
isn't. Everything here is silent when it fails — nothing on any screen says
"the scheduler stopped three days ago", which is exactly why this page exists.

For getting a server to the point where any of this runs at all — nginx, TLS,
systemd units, the cron entry, and the ordering rules in a deploy — see
`docs/deployment.md`. This page assumes the box already exists.

## The processes

| Process | Command | Required for | Silent failure mode |
|---|---|---|---|
| **Web server** | XAMPP Apache, or `php artisan serve` | Everything | Obvious |
| **MySQL** | XAMPP MySQL | Everything | Obvious |
| **Scheduler** | `php artisan schedule:run`, every minute | Holds expiring, no-shows, auto check-out | **Rooms stay blocked forever** |
| **Reverb** | `php artisan reverb:start` | Live console updates | Boards go stale until refresh |
| **Queue worker** | `php artisan queue:work` | Nothing *yet* — see below | n/a today (nothing implements `ShouldQueue`) |

The commands are the same on a server; only the supervision differs. In
production the last three are a cron entry and two systemd units, and both
long-lived processes hold the code they booted with — so a deploy that does not
restart them keeps serving the previous release. `docs/deployment.md` has the
units and the restart step.

### Scheduler — the one that actually costs money

Three commands hang off it (`app/Console/Kernel.php`):

- `bookings:expire` — every minute. Releases a `pending_payment` booking once
  its window (`BOOKING_EXPIRY_MINUTES`, default **1440** — 24 hours) has run
  out. Read the value through `App\Support\PaymentWindow`, never straight from
  the config: at 1440 the raw integer is no longer something you can print at a
  guest, and every template that used to interpolate it said "60 minutes".
- `bookings:mark-no-show` — 00:05 Asia/Manila. Forfeits paid bookings whose
  check-in day passed without an arrival: no refund, rooms released. It skips
  any booking with a **pending reschedule request**, because that guest did ask
  in time and is waiting on the desk, not the other way round.
- `bookings:autocheckout` — every 30 minutes, no-ops before the configured
  check-out time (12:00 PM Manila). Check-in is 2:00 PM and check-out is noon;
  the two are separate config keys and no longer the same number.

If `schedule:run` is not firing, **unpaid holds never expire.** A guest who
opens checkout and walks away keeps that room off the market permanently. The
availability endpoints all count `pending_payment` as blocking, so the room
disappears from the public calendar, from the room board, and from both staff
booking screens — and no error is raised anywhere, because nothing is wrong.
The room is simply, quietly, unsellable.

An unpaid hold dies on **two** clocks, and `bookings:expire` collects both:

- the 24-hour window running out, and
- the guest's own check-in time arriving with the booking still unpaid.

The second exists because the first cannot cover a same-day booking. Booked at
10 AM for tonight, a 24-hour window would hold the rooms until 10 AM *tomorrow*
— straight through the night they were sold for, then released for a night
already over. Availability (`Booking::applyActiveHold`) reads both clocks
directly, so the room is back on sale at check-in time even if the scheduler is
dead; the command is the bookkeeping that writes it down.

### Booking policy, and where each half is enforced

| Rule | Guest-facing | Enforced by |
| --- | --- | --- |
| 24 hours to pay, or until check-in — whichever is sooner | checkout terms, hold countdown, received mail | `PaymentWindow::deadlineFor`, `bookings:expire`, `Booking::applyActiveHold` |
| Guests book a room *style*; the room number is assigned | checkout room card, booking summary | `BookingController::store` (front desk and admin still pick numbers) |
| A paid booking cannot be cancelled | checkout terms, My Bookings, booking page | `SettingsController::cancelBooking` |
| Move it instead — at least **24 hours** before check-in | reschedule form, booking page, no-show mail | `RescheduleRequest::deadlineFor`, `RescheduleController::rejectIfClosed` |
| Miss that and it is forfeited, no refund | checkout terms, no-show mail | `bookings:mark-no-show` |
| **No refunds, ever** — a shorter reschedule buys no credit | reschedule form, decision mail, staff queue | `RescheduleAdminController::approve` (`max()` on payable) |
| Senior/PWD bookings are settled at the front desk | checkout notice, booking page, received mail | `PaymentController::rejectIfNotPayable` |
| Check-in 2:00 PM, check-out 12:00 PM | every guest-facing page and mail | `config/hostel.php` via `StaySchedule` |
| Early check-in is a request, not a slot we sell | arrival picker, staff booking detail, desk alert | `StaySchedule::arrivalSlots` / `isEarlyArrival` |
| Every online booking names who endorsed the guest | checkout form | `BookingController::store` (`referred_by`, required online, optional at the desk) |

The last one needs a counter action to be settleable at all: front desk →
Bookings → **Settle** (`FrontDeskBookingsController::settle`). Without it a
discounted booking has no route to `paid` and simply expires.

On Windows, in development: Task Scheduler, running every minute. (On a server
this is a cron entry instead — `docs/deployment.md`. The failure mode below is
identical on both, and so is the fix: check the path.) **Check the path before
running this** — it must match where the project actually lives. The command below was
wrong for a year (it pointed at `C:\xampp\htdocs\Auxiliary\aux_system\artisan`,
a directory that does not exist), and because a task whose target is missing
fails silently, following this page produced exactly the outage it warns about.

```bash
schtasks /create /tn "FarmersHostel Scheduler" /sc minute /mo 1 /tr "C:\xampp\php\php.exe C:\xampp\htdocs\Auxiliary-\artisan schedule:run" /ru SYSTEM
```

Confirm it exists and has actually run — `Last Run Time` is the field that
matters, and `Last Result` must be `0`:

```bash
schtasks /query /tn "FarmersHostel Scheduler" /v /fo LIST
```

`php artisan schedule:list` only shows what *would* run. It says nothing about
whether anything is calling it, so it cannot tell you the scheduler is alive.

The honest check is to ask the data instead — this counts holds that should
already have been released, and it must be `0`:

```bash
php artisan tinker --execute='echo App\Models\Booking::where("status","pending_payment")->where("pending_payment_since","<=",now()->subMinutes(config("bookings.expiry_minutes")))->count();'
```

### Reverb

```bash
php artisan reverb:start
```

Live push for the staff consoles. Broadcasts go through `App\Support\Realtime`,
which swallows a dead Reverb, so **an action never fails because Reverb is
down** — the other consoles just don't hear about it until their next poll or
refresh. Degraded, not broken.

### Queue worker

`QUEUE_CONNECTION=database`, but **nothing in the app implements `ShouldQueue`**,
so in practice there is still nothing to work and no worker is required. The
connection setting is where jobs *would* go, not evidence that any exist —
`select count(*) from jobs` is the check that settles it.

Mail therefore still goes out **inside the web request**: a booking submission
and a staff login both wait on Gmail's SMTP. `App\Support\StaffAlert` and
`App\Support\GuestNotice` wrap every send in a try/catch precisely because of
this — a dead mail server must not cost a guest the booking they just made.

`App\Notifications\StaffLoginOtpNotification` imports `ShouldQueue` but
deliberately does **not** implement it, for the reason below. The unused import
is misleading; do not "tidy" it by adding the interface.

**Before you queue anything, supervise a worker first.** The staff OTP travels
this path, and an unworked queue means no OTP, which means nobody can log in at
all. That is a worse failure than a slow login. See
`docs/security-auth-hardening.md` for the full reasoning.

## Deploying a change

Order matters in one place:

```bash
php artisan migrate
npm run build
php artisan config:cache
php artisan route:cache
```

`npm run build` runs `php artisan view:cache` first, and that is load-bearing:
Tailwind scans **compiled** Blade, so building against a cold view cache
silently drops utilities that are only referenced in components. Use
`npm run build`, never a bare `vite build`.

If the site renders unstyled, check `public/hot` before anything else — a
leftover file from a dead `npm run dev` points every asset at a Vite server
that is no longer listening. Delete it.

## First-time setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

## Health

`GET /up` is Laravel's default health endpoint. It confirms the framework
boots; it does **not** check the database, Reverb, or scheduler freshness. Treat
a green `/up` as "PHP is alive", nothing more.
