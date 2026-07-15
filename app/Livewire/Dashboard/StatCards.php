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
        $now = Carbon::now('Asia/Manila');

        $totalRooms = Room::count();
        $roomsUnderMaintenance = Room::where('status', 'maintenance')->count();
        $totalBookings = Booking::count();
        $totalUsers = User::count();
        $totalRevenue = Payment::where('status', 'success')->sum('amount');
        $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Percent changes vs last month
        $bookingsThisMonth = Booking::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count();
        $lastMonth = $now->copy()->subMonth();
        $bookingsLastMonth = Booking::whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month)->count();
        $bookingPercentChange = $bookingsLastMonth > 0
            ? (($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100
            : ($bookingsThisMonth > 0 ? 100 : 0);

        $revenueThisMonth = Payment::where('status', 'success')->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->sum('amount');
        $revenueLastMonth = Payment::where('status', 'success')->whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month)->sum('amount');
        $revenuePercentChange = $revenueLastMonth > 0
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
            : ($revenueThisMonth > 0 ? 100 : 0);

        // Sparkline for the revenue card (Jan → current month, 70x24 viewBox)
        $revenuePerMonth = Payment::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'success')
            ->whereYear('created_at', $now->year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month');

        $spark = collect(range(1, max($now->month, 2)))
            ->map(fn ($m) => (float) ($revenuePerMonth[$m] ?? 0));
        $sparkMax = max($spark->max(), 1);
        $sparkCount = $spark->count();
        $revenueSparkline = $spark->map(function ($v, $i) use ($sparkMax, $sparkCount) {
            $x = $sparkCount > 1 ? round($i * (70 / ($sparkCount - 1)), 1) : 0;
            $y = round(21 - ($v / $sparkMax) * 18, 1);
            return "{$x},{$y}";
        })->implode(' ');

        // Secondary strip — availableCount uses the shared RoomBoard state so
        // it always matches the Room Status Map legend.
        $availableCount = RoomBoard::state()->where('display_status', 'available')->count();
        $checkinsThisWeek = DB::table('checkins')->where('checked_in_at', '>=', Carbon::now()->subDays(7))->count();
        $checkoutsThisWeek = DB::table('checkouts')->where('checked_out_at', '>=', Carbon::now()->subDays(7))->count();
        $pendingDiscounts = Discount::where('status', 'pending')->count();

        return view('livewire.dashboard.stat-cards', compact(
            'totalRooms',
            'roomsUnderMaintenance',
            'totalBookings',
            'bookingPercentChange',
            'totalUsers',
            'newUsersThisWeek',
            'totalRevenue',
            'revenuePercentChange',
            'revenueSparkline',
            'availableCount',
            'checkinsThisWeek',
            'checkoutsThisWeek',
            'pendingDiscounts'
        ));
    }
}
