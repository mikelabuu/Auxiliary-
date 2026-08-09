<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Models\Room;
use App\Support\RoomBoard;
use Carbon\Carbon;
use Livewire\Component;

/**
 * The welcome panel: today's operational counts plus a plain-language summary
 * of what needs a decision.
 *
 * Split from StatCards because it answers a different question — StatCards is
 * "how big is the business", this is "what needs attention right now" — and
 * because the ops counts have to stay honest to the minute. It follows the same
 * broadcast pushes as the room map so the strip never lags the board.
 *
 * Revenue used to share this row. It lived here as an all-time gross total
 * carrying a month-on-month delta and a trailing-12-month chart — three
 * different time windows in one card, which made a quiet month read as a
 * collapse. It is now a single honest month figure in the KPI row
 * (App\Livewire\Dashboard\StatCards), and the freed slot holds the booking
 * calendar, which front desk actually reads hour to hour.
 */
class Hero extends Component
{
    protected $listeners = ['refreshDashboardStats' => '$refresh'];

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
            'summary'
        ));
    }
}
