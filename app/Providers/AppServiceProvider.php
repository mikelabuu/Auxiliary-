<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootRateLimiters();

        // Real data for the admin topbar notification dropdown.
        //
        // These are derived on each request rather than stored, so "read"
        // cannot live in a database column. Every entry therefore carries a
        // stable `id` — type + subject + the timestamp that made it appear —
        // and the topbar remembers which ids have been opened. The id must
        // change when the underlying thing changes (a re-uploaded proof, a
        // room list that grew) so a genuinely new alert is never pre-read.
        //
        // The array shape here is deliberately identical to
        // App\Events\StaffNotification::broadcastWith(). The dropdown renders
        // one list from two sources — this backfill on page load, and alerts
        // arriving live over Reverb — and it can only treat them as one list
        // if they are the same object. `at` is a unix timestamp rather than a
        // formatted string for the same reason: the row's "2 minutes ago" is
        // computed in the browser, so it stays true on a console left open all
        // shift instead of freezing at whatever it said when the page rendered.
        View::composer('components.admin.layout.topbar', function ($view) {
            $notifications = collect();

            Discount::with('booking')
                ->where('status', 'pending')
                ->latest('submitted_at')
                ->take(5)
                ->get()
                ->each(function ($d) use ($notifications) {
                    $time = $d->submitted_at ?? $d->created_at;

                    $notifications->push([
                        'id'    => 'discount:' . $d->id . ':' . ($time?->timestamp ?? 0),
                        'type'  => 'discount',
                        'title' => 'Discount request',
                        'text'  => 'Booking #' . $d->booking_id
                            . ($d->booking?->guest_name ? ' · ' . $d->booking->guest_name : '')
                            . ' awaits review',
                        'url'   => route('staff.discounts.show', $d, absolute: false),
                        'level' => 'info',
                        'at'    => $time?->timestamp ?? 0,
                    ]);
                });

            // Guests who have paid over GCash or a bank transfer and are
            // waiting on a human. The most time-critical item in the list.
            Payment::with('booking')
                ->whereNotNull('proof_path')
                ->awaitingVerification()
                ->latest('proof_submitted_at')
                ->take(5)
                ->get()
                ->each(function ($p) use ($notifications) {
                    $notifications->push([
                        'id'    => 'payment:' . $p->id . ':' . ($p->proof_submitted_at?->timestamp ?? 0),
                        'type'  => 'payment',
                        'title' => 'Proof of payment',
                        'text'  => 'Booking #' . $p->booking_id
                            . ($p->booking?->guest_name ? ' · ' . $p->booking->guest_name : '')
                            . ' awaits verification',
                        'url'   => route('staff.paymentverification.index', [], absolute: false),
                        'level' => 'warning',
                        'at'    => ($p->proof_submitted_at ?? $p->created_at)?->timestamp ?? 0,
                    ]);
                });

            Booking::where('created_at', '>=', now()->subDays(2))
                ->latest()
                ->take(5)
                ->get()
                ->each(function ($b) use ($notifications) {
                    $notifications->push([
                        'id'    => 'booking:' . $b->id . ':' . ($b->created_at?->timestamp ?? 0),
                        'type'  => 'booking',
                        'title' => 'New booking',
                        'text'  => '#' . $b->id . ' · ' . $b->guest_name
                            . ' (' . $b->check_in->format('M d') . ' – ' . $b->check_out->format('M d') . ')',
                        'url'   => route('staff.bookings.index', ['search' => $b->id], absolute: false),
                        'level' => 'success',
                        'at'    => $b->created_at?->timestamp ?? 0,
                    ]);
                });

            $maintenance = Room::where('status', 'maintenance')
                ->latest('updated_at')
                ->get();

            if ($maintenance->isNotEmpty()) {
                $latest = $maintenance->first()->updated_at;

                $notifications->push([
                    // Count is part of the id: an eighth room going down is
                    // news even if the previous seven were already dismissed.
                    'id'    => 'maintenance:' . $maintenance->count() . ':' . ($latest?->timestamp ?? 0),
                    'type'  => 'maintenance',
                    'title' => 'Rooms out of service',
                    'text'  => $maintenance->count() . ' ' . str('room')->plural($maintenance->count())
                        . ' under maintenance (' . $maintenance->pluck('room_number')->take(4)->implode(', ')
                        . ($maintenance->count() > 4 ? ', …' : '') . ')',
                    'url'   => route('staff.rooms', [], absolute: false),
                    'level' => 'error',
                    'at'    => $latest?->timestamp ?? 0,
                ]);
            }

            $view->with('notifications', $notifications->sortByDesc('at')->take(8)->values());
        });
    }

    /**
     * Named limiters used by routes/web.php via the `throttle:<name>` alias.
     */
    protected function bootRateLimiters(): void
    {
        // Guards the six "re-enter your password to continue" endpoints in the
        // staff console. They each Hash::check and report the result, so
        // unthrottled they are a password oracle for a hijacked session.
        // Keyed on the staff account, not the IP, so the limit follows the
        // session it is actually protecting.
        RateLimiter::for('staff-password', function (Request $request) {
            return Limit::perMinute(5)
                ->by('staff-password:' . (auth('staff')->id() ?: $request->ip()));
        });

        // Account creation and password-reset mail. Both send or write on an
        // unauthenticated request, so they are per-IP capped.
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinutes(10, 5)->by(
                strtolower((string) $request->input('email')) . '|' . $request->ip()
            );
        });
    }
}
