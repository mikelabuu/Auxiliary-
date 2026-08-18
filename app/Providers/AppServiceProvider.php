<?php

namespace App\Providers;

use App\Support\StaffAlerts;
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

        // The check-in time, as guest-facing copy says it: "2:00 PM".
        //
        // Shared rather than called inline because it is *copy*, appearing in
        // ten sentences across six pages and the confirmation email. Written
        // out, moving check-in meant finding all ten and hoping. Written as a
        // 45-character static call, the sentences become hard to read, and
        // these are the pages guests actually read.
        View::share('checkinTime', \App\Support\StaySchedule::checkinLabel());

        // Check-out, likewise — and this one was not merely repeated, it was
        // wrong. Five guest-facing places stated "12:00 NN" while
        // bookings:autocheckout enforces config(hostel.checkout_time), so the site
        // promised guests two hours it was never going to give them. Reading
        // it from the same key the command enforces is what stops the promise
        // and the behaviour from disagreeing again.
        View::share('checkoutTime', \App\Support\StaySchedule::checkoutLabel());

        // Real data for the admin topbar notification dropdown. The list, and
        // the reasoning behind its stable ids and exact payload shape, lives in
        // App\Support\StaffAlerts — which Staff\NotificationFeedController also
        // serves as JSON, so a console left open picks up new alerts without
        // anyone reloading the page.
        View::composer('components.admin.layout.topbar', function ($view) {
            $view->with('notifications', StaffAlerts::current());
        });
    }

    /**
     * Named limiters used by routes/web.php via the `throttle:<name>` alias.
     */
    protected function bootRateLimiters(): void
    {
        // NOTE: the 'staff-password' limiter that used to live here is gone
        // with the endpoints it guarded. The six "re-enter your password to
        // continue" routes were orphaned — the console had already dropped the
        // modals that called them ("Password re-auth dropped", see the staff
        // records and user records pages) — so they were removed rather than
        // left implying a control that no longer ran. If the confirmation step
        // is ever brought back, it needs this limiter back with it: those
        // endpoints Hash::check and report the result, which is a password
        // oracle for a hijacked session if left uncapped.

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

        // The unauthenticated read endpoints the booking UI calls while a
        // guest picks dates: room availability, the sold-out calendar, and the
        // PSGC address lists. None of them expose anything about a guest, so
        // this is not an authorisation control — it is a cost control. Each
        // one runs real queries, and without a cap a single host can drive
        // them as fast as the database will answer.
        //
        // Deliberately generous. A guest changing dates fires several of these
        // in quick succession, and a hostel's guests can easily share one
        // public address behind NAT, so the limit has to sit well above what
        // ordinary use looks like while still being far below what a script
        // does. 120/minute is roughly two requests a second from one address.
        RateLimiter::for('public-lookup', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
