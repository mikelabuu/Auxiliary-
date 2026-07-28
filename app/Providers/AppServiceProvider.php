<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Discount;
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
