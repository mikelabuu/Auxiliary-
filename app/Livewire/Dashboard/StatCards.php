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

        // Revenue moved to the hero card (App\Livewire\Dashboard\Hero), which
        // owns the gross total, the month-on-month delta and the 12-month
        // chart — computing any of it here again would just be duplicate SQL.

        // Secondary strip — availableCount uses the shared RoomBoard state so
        // it always matches the Room Status Map legend.
        $availableCount = RoomBoard::state()->where('display_status', 'available')->count();
        $checkinsThisWeek = DB::table('checkins')->where('checked_in_at', '>=', Carbon::now()->subDays(7))->count();
        $checkoutsThisWeek = DB::table('checkouts')->where('checked_out_at', '>=', Carbon::now()->subDays(7))->count();
        $pendingDiscounts = Discount::where('status', 'pending')->count();

        // ── Share-of-total for each card's meter ─────────────────────────
        // Each bar has a real denominator; none of them are decoration.
        $roomsServiceablePct = $totalRooms > 0
            ? (($totalRooms - $roomsUnderMaintenance) / $totalRooms) * 100 : 0;

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

        // Revenue for the current month — the hero card shows all-time gross,
        // so without this the dashboard never states what this month earned.
        $revenueThisMonth = (float) Payment::where('status', 'success')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('amount');

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
            'roomsServiceablePct',
            'bookingsFulfilled',
            'bookingsFulfilledPct',
            'usersWithBooking',
            'usersActivePct',
            'sellablePct',
            'bookingSpark',
            'userSpark',
            'revenueThisMonth'
        ));
    }
}
