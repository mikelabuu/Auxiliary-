<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Support\RoomBoard;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard's two stat rows (hero cards + secondary strip). These were
 * plain controller variables frozen at page load; as a component they poll
 * and follow the same broadcast pushes as the room map, so "Rooms available
 * now" actually means now. All values are computed in render() so a plain
 * $refresh recomputes everything.
 */
class StatCards extends Component
{
    protected $listeners = ['refreshDashboardStats' => '$refresh'];

    public function render()
    {
        $now = Carbon::now(config('hostel.timezone'));

        $totalRooms = Room::count();
        $roomsUnderMaintenance = Room::where('status', 'maintenance')->count();
        $totalBookings = Booking::count();
        $totalUsers = User::count();
        $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Percent changes vs last month
        $bookingsThisMonth = Booking::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count();
        $lastMonth = $now->copy()->subMonth();
        $bookingsLastMonth = Booking::whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month)->count();
        $bookingPercentChange = $bookingsLastMonth > 0
            ? (($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100
            : ($bookingsThisMonth > 0 ? 100 : 0);

        // Secondary strip — availableCount uses the shared RoomBoard state so
        // it always matches the Room Status Map legend.
        $availableCount = RoomBoard::state()->where('display_status', 'available')->count();
        $checkinsThisWeek = DB::table('checkins')->where('checked_in_at', '>=', Carbon::now()->subDays(7))->count();
        $checkoutsThisWeek = DB::table('checkouts')->where('checked_out_at', '>=', Carbon::now()->subDays(7))->count();
        $pendingDiscounts = Discount::where('status', 'pending')->count();

        // ── Share-of-total for each card's meter ─────────────────────────
        // Each bar has a real denominator; none of them are decoration.
        // The rooms meter went with the Total Rooms card when it moved to the
        // secondary strip, which has no meter — so it is no longer computed.
        $bookingsFulfilled = Booking::whereIn('status', ['active', 'completed'])->count();
        $bookingsFulfilledPct = $totalBookings > 0 ? ($bookingsFulfilled / $totalBookings) * 100 : 0;

        $usersWithBooking = Booking::whereNotNull('user_id')->distinct()->count('user_id');
        $usersActivePct = $totalUsers > 0 ? ($usersWithBooking / $totalUsers) * 100 : 0;

        $sellablePct = $totalRooms > 0 ? ($availableCount / $totalRooms) * 100 : 0;

        // ── 12-week trend glyphs ─────────────────────────────────────────
        // Bookings and signups are cumulative all-time counts, so the headline
        // number never moves much and says nothing about direction. The
        // sparkline shows the weekly intake behind it. One grouped query each;
        // YEARWEEK mode 3 is ISO, matching Carbon's 'oW'.
        $weeksAgo = Carbon::now(config('hostel.timezone'))->subWeeks(11)->startOfWeek();

        $weeklySeries = function ($rows) use ($now) {
            $out = [];
            for ($i = 11; $i >= 0; $i--) {
                $key = (int) $now->copy()->subWeeks($i)->format('oW');
                $out[] = (int) ($rows[$key] ?? 0);
            }
            return $out;
        };

        $bookingSpark = $weeklySeries(
            Booking::where('created_at', '>=', $weeksAgo)
                ->selectRaw('YEARWEEK(created_at, 3) as yw, COUNT(*) as c')
                ->groupBy('yw')->pluck('c', 'yw')
        );

        $userSpark = $weeklySeries(
            User::where('created_at', '>=', $weeksAgo)
                ->selectRaw('YEARWEEK(created_at, 3) as yw, COUNT(*) as c')
                ->groupBy('yw')->pluck('c', 'yw')
        );

        // ── Revenue ──────────────────────────────────────────────────────
        // This used to be the hero card, where an all-time gross total carried
        // a month-on-month delta and a trailing-12-month chart. Three time
        // windows in one card meant a quiet month rendered as "-100%" beside a
        // seven-figure lifetime number, which reads as a collapse. The headline
        // is the month now, so the delta beneath it compares like with like;
        // all-time gross survives as the footnote, where a reference figure
        // belongs.
        $revenueThisMonth = (float) Payment::where('status', 'success')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('amount');

        $revenueLastMonth = (float) Payment::where('status', 'success')
            ->whereYear('created_at', $lastMonth->year)
            ->whereMonth('created_at', $lastMonth->month)
            ->sum('amount');

        $revenuePercentChange = $revenueLastMonth > 0
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
            : ($revenueThisMonth > 0 ? 100 : 0);

        $grossRevenue = (float) Payment::where('status', 'success')->sum('amount');

        // Trailing 12 months in one grouped query rather than twelve.
        $monthWindow = $now->copy()->subMonths(11)->startOfMonth();
        $revenueByMonth = Payment::where('status', 'success')
            ->where('created_at', '>=', $monthWindow)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $revenueSpark = [];
        for ($i = 11; $i >= 0; $i--) {
            $revenueSpark[] = (float) ($revenueByMonth[$now->copy()->subMonths($i)->format('Y-m')] ?? 0);
        }

        return view('livewire.dashboard.stat-cards', compact(
            'totalRooms',
            'roomsUnderMaintenance',
            'totalBookings',
            'bookingPercentChange',
            'totalUsers',
            'newUsersThisWeek',
            'availableCount',
            'checkinsThisWeek',
            'checkoutsThisWeek',
            'pendingDiscounts',
            'bookingsFulfilled',
            'bookingsFulfilledPct',
            'usersWithBooking',
            'usersActivePct',
            'sellablePct',
            'bookingSpark',
            'userSpark',
            'revenueThisMonth',
            'revenuePercentChange',
            'revenueSpark',
            'grossRevenue'
        ));
    }
}
