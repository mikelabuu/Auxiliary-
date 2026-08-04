<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Support\RoomBoard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The dashboard's top row: the welcome panel (with today's operational counts
 * and a plain-language summary) and the gross-revenue hero card.
 *
 * Split from StatCards because it answers a different question — StatCards is
 * "how big is the business", this is "what needs attention right now" — and
 * because the ops counts have to stay honest to the minute. It follows the same
 * broadcast pushes as the room map so the strip never lags the board.
 */
class Hero extends Component
{
    protected $listeners = ['refreshDashboardStats' => '$refresh'];

    /** Points for a 520x120 area chart, as [polyline, closed polygon]. */
    private function chartPoints(array $series): array
    {
        $w = 520.0;
        $h = 120.0;
        $pad = 8.0;                 // keeps the stroke off the top/bottom edge
        $max = max(max($series), 1);
        $n = count($series);

        $pts = [];
        foreach ($series as $i => $v) {
            $x = $n > 1 ? round($i * ($w / ($n - 1)), 1) : 0;
            $y = round($h - $pad - ($v / $max) * ($h - $pad * 2), 1);
            $pts[] = "{$x},{$y}";
        }

        $line = implode(' ', $pts);
        // Close the shape down the right edge, along the floor, and back up.
        $area = $line . ' ' . $w . ',' . $h . ' 0,' . $h;

        return [$line, $area];
    }

    public function render()
    {
        $now = Carbon::now(config('hostel.timezone'));
        $today = $now->copy()->startOfDay()->toDateString();

        // ── Inventory ────────────────────────────────────────────────────
        $totalRooms = Room::count();
        $board = RoomBoard::state();
        $readyToHost = $board->where('display_status', 'available')->count();

        // ── Today's operational counts ───────────────────────────────────
        $arriving = Booking::whereDate('check_in', $today)
            ->where('status', 'paid')
            ->count();

        $departing = Booking::whereDate('check_out', $today)
            ->where('status', 'active')
            ->count();

        $inHouse = Booking::where('status', 'active')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->count();

        // Overdue = still checked in past check-out, plus paid arrivals that
        // never showed. Both are decisions a manager owes today.
        $overdueCheckouts = Booking::where('status', 'active')
            ->whereDate('check_out', '<', $today)
            ->count();
        $missedArrivals = Booking::where('status', 'paid')
            ->whereDate('check_in', '<', $today)
            ->count();
        $overdue = $overdueCheckouts + $missedArrivals;

        // ── Revenue ──────────────────────────────────────────────────────
        $grossRevenue = (float) Payment::where('status', 'success')->sum('amount');

        $lastMonth = $now->copy()->subMonth();
        $revenueThisMonth = (float) Payment::where('status', 'success')
            ->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->sum('amount');
        $revenueLastMonth = (float) Payment::where('status', 'success')
            ->whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month)->sum('amount');
        $revenueChange = $revenueLastMonth > 0
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
            : ($revenueThisMonth > 0 ? 100 : 0);

        // Trailing 12 months, one grouped query rather than twelve.
        $windowStart = $now->copy()->subMonths(11)->startOfMonth();
        $byMonth = Payment::where('status', 'success')
            ->where('created_at', '>=', $windowStart)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $series = [];
        for ($i = 11; $i >= 0; $i--) {
            $series[] = (float) ($byMonth[$now->copy()->subMonths($i)->format('Y-m')] ?? 0);
        }
        [$revenueLine, $revenueArea] = $this->chartPoints($series);

        $bookingsCount = Booking::count();
        $pendingPayments = Payment::where('status', 'pending')->count();

        // ── Plain-language summary ───────────────────────────────────────
        // Reads as a sentence a duty manager would actually say, so the number
        // row underneath is confirmation rather than the only signal.
        $summary = "All {$totalRooms} rooms are accounted for and {$readyToHost} "
            . ($readyToHost === 1 ? 'is' : 'are') . ' ready to host.';
        if ($overdue > 0) {
            $summary .= ' ' . ($overdue === 1 ? 'One reservation is' : "{$overdue} reservations are")
                . ' overdue and ' . ($overdue === 1 ? 'needs' : 'need') . ' a decision today.';
        } elseif ($arriving > 0) {
            $summary .= ' ' . ($arriving === 1 ? 'One guest arrives' : "{$arriving} guests arrive") . ' today.';
        } else {
            $summary .= ' Nothing needs a decision right now.';
        }

        return view('livewire.dashboard.hero', compact(
            'totalRooms',
            'readyToHost',
            'arriving',
            'departing',
            'inHouse',
            'overdue',
            'summary',
            'grossRevenue',
            'revenueChange',
            'revenueLine',
            'revenueArea',
            'bookingsCount',
            'pendingPayments'
        ));
    }
}
