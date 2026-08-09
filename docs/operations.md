# Operations

What has to be running for this system to behave, and what breaks when it
isn't. Everything here is silent when it fails — nothing on any screen says
"the scheduler stopped three days ago", which is exactly why this page exists.

## The processes

| Process | Command | Required for | Silent failure mode |
|---|---|---|---|
| **Web server** | XAMPP Apache, or `php artisan serve` | Everything | Obvious |
| **MySQL** | XAMPP MySQL | Everything | Obvious |
| **Scheduler** | `php artisan schedule:run`, every minute | Holds expiring, no-shows, auto check-out | **Rooms stay blocked forever** |
| **Reverb** | `php artisan reverb:start` | Live console updates | Boards go stale until refresh |
| **Queue worker** | `php artisan queue:work` | Nothing *yet* — see below | n/a today (nothing implements `ShouldQueue`) |

### Scheduler — the one that actually costs money

Three commands hang off it (`app/Console/Kernel.php`):

- `bookings:expire` — every minute. Releases a `pending_payment` booking once
  its window (`BOOKING_EXPIRY_MINUTES`, default 60) has run out.
- `bookings:mark-no-show` — 00:05 Asia/Manila. Flags paid bookings whose
  check-in day passed without an arrival.
- `bookings:autocheckout` — every 30 minutes, no-ops before 2 PM Manila.

If `schedule:run` is not firing, **unpaid holds never expire.** A guest who
opens checkout and walks away keeps that room off the market permanently. The
availability endpoints all count `pending_payment` as blocking, so the room
disappears from the public calendar, from the room board, and from both staff
booking screens — and no error is raised anywhere, because nothing is wrong.
The room is simply, quietly, unsellable.

Windows Task Scheduler, running every minute. **Check the path before running
this** — it must match where the project actually lives. The command below was
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
