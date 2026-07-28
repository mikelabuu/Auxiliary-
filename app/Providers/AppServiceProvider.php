<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Room;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        $this->configureStrictModels();
        $this->configureRateLimiting();

        // Real data for the admin topbar notification dropdown.
        View::composer('components.admin.layout.topbar', function ($view) {
            $notifications = collect();

            Discount::with('booking')
                ->where('status', 'pending')
                ->latest('submitted_at')
                ->take(5)
                ->get()
                ->each(function ($d) use ($notifications) {
                    $notifications->push([
                        'type' => 'discount',
                        'text' => 'Discount request for booking #' . $d->booking_id
                            . ($d->booking?->guest_name ? ' · ' . $d->booking->guest_name : '')
                            . ' awaits review',
                        'time' => $d->submitted_at ?? $d->created_at,
                        'url'  => route('staff.discounts.show', $d),
                    ]);
                });

            Booking::where('created_at', '>=', now()->subDays(2))
                ->latest()
                ->take(5)
                ->get()
                ->each(function ($b) use ($notifications) {
                    $notifications->push([
                        'type' => 'booking',
                        'text' => 'New booking #' . $b->id . ' · ' . $b->guest_name
                            . ' (' . $b->check_in->format('M d') . ' – ' . $b->check_out->format('M d') . ')',
                        'time' => $b->created_at,
                        'url'  => route('staff.bookings.index', ['search' => $b->id]),
                    ]);
                });

            $maintenance = Room::where('status', 'maintenance')
                ->latest('updated_at')
                ->get();

            if ($maintenance->isNotEmpty()) {
                $notifications->push([
                    'type' => 'maintenance',
                    'text' => $maintenance->count() . ' ' . str('room')->plural($maintenance->count())
                        . ' under maintenance (' . $maintenance->pluck('room_number')->take(4)->implode(', ')
                        . ($maintenance->count() > 4 ? ', …' : '') . ')',
                    'time' => $maintenance->first()->updated_at,
                    'url'  => route('staff.rooms'),
                ]);
            }

            $notifications = $notifications->sortByDesc('time')->take(8)->values();

            $view->with('notifications', $notifications)
                 ->with('notifStamps', $notifications->pluck('time')->map(fn ($t) => $t?->timestamp ?? 0));
        });
    }

    /**
     * Turn silent Eloquent mistakes into exceptions outside production.
     *
     * Catches attributes discarded by mass-assignment guards (an `update()`
     * naming a non-fillable column is otherwise a no-op — that behaviour hid a
     * bug during QA), accesses to attributes that were never loaded, and lazy
     * loads that would otherwise become N+1 queries under real traffic.
     *
     * Left off in production so a warning cannot take a page down.
     */
    private function configureStrictModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * Named rate limiters for the endpoints worth protecting.
     *
     * Before this the application had exactly one throttle, on the
     * verification-resend route. Staff login was brute-forceable and the OTP
     * resend endpoint would send mail as fast as it was asked to.
     *
     * Credential endpoints key on the submitted identifier as well as the IP,
     * so one attacker cannot lock every user out by hammering a shared address,
     * and a botnet cannot spread an attack on one account across many IPs.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login|' . strtolower((string) $request->input('email')) . '|' . $request->ip()),
            Limit::perMinute(20)->by('login-ip|' . $request->ip()),
        ]);

        RateLimiter::for('staff-login', fn (Request $request) => [
            Limit::perMinute(5)->by('staff-login|' . strtolower((string) $request->input('email')) . '|' . $request->ip()),
            Limit::perMinute(20)->by('staff-login-ip|' . $request->ip()),
        ]);

        // OTP is the second factor: a six-digit code is guessable in a few
        // thousand tries, so the attempt rate is what makes it worth having.
        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(6)->by('otp|' . $request->ip()));

        // Resending costs an outbound message every time it is called.
        RateLimiter::for('otp-resend', fn (Request $request) => Limit::perMinute(2)->by('otp-resend|' . $request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by('pwreset|' . strtolower((string) $request->input('email')) . '|' . $request->ip()));

        RateLimiter::for('signup', fn (Request $request) => Limit::perMinute(3)->by('signup|' . $request->ip()));

        // Booking and payment are authenticated, so key on the account and fall
        // back to the address for anything that slips through unauthenticated.
        RateLimiter::for('bookings', fn (Request $request) => Limit::perMinute(10)
            ->by('bookings|' . ($request->user()?->id ?: $request->ip())));

        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(10)
            ->by('payments|' . ($request->user()?->id ?: $request->ip())));
    }
}
