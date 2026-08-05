<?php

namespace App\Http\Controllers\Staff\FrontDesk;

use App\Support\RoomCatalog;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FrontDeskDashboardController extends Controller
{
    public function index()
    {
        $year = now()->year;

        $bookingsPerMonth = Payment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
        ->where('status', 'success')
        ->whereYear('created_at', $year)
        ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
        ->orderBy('month')
        ->get()
        ->keyBy('month');

        // Monthly revenue (successful payments) for the same year — the
        // insights combo chart overlays this as a line.
        $revenuePerMonth = Payment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->where('status', 'success')
        ->whereYear('created_at', $year)
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->orderBy('month')
        ->get()
        ->keyBy('month');

        // format for chart
        $labels = [];
        $values = [];
        $revenueValues = [];
        $peakMonthName = 'None';
        $peakMonthCount = 0;
        for ($i = 1; $i <= 12; $i++) {
            $count = $bookingsPerMonth[$i]->total ?? 0;
            $labels[] = date('M', mktime(0, 0, 0, $i, 1)); // short month name
            $values[] = $count;
            $revenueValues[] = (float) ($revenuePerMonth[$i]->total ?? 0);

            if ($count > $peakMonthCount) {
                $peakMonthCount = $count;
                $peakMonthName = date('F', mktime(0, 0, 0, $i, 1));
            }
        }

        // Per-date occupancy for the calendar modal (arrivals, departures,
        // in-house nights + each day's guest list). Mirrors the admin dashboard.
        $calendarStart = Carbon::now()->startOfMonth()->subMonths(2);
        $calendarEnd = Carbon::now()->startOfMonth()->addMonths(10);

        $calendarData = [];
        Booking::with('reservations')
            ->whereIn('status', array_merge(Booking::BLOCKING_STATUSES, ['completed']))
            ->where('check_in', '<', $calendarEnd)
            ->where('check_out', '>', $calendarStart)
            ->get()
            ->each(function ($b) use (&$calendarData, $calendarStart, $calendarEnd) {
                $rooms = $b->reservations->pluck('room_number')->filter()->unique()->implode(', ');
                $type = $b->reservations->first()->room_type ?? '';
                $type = RoomCatalog::label($type);

                $start = $b->check_in->greaterThan($calendarStart) ? $b->check_in->copy() : $calendarStart->copy();
                $end   = $b->check_out->lessThan($calendarEnd) ? $b->check_out->copy() : $calendarEnd->copy();

                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $ds = $d->toDateString();
                    $calendarData[$ds] ??= ['a' => 0, 'd' => 0, 's' => 0, 'guests' => []];

                    $isArrival = $d->isSameDay($b->check_in);
                    $isDeparture = $d->isSameDay($b->check_out);

                    if ($isArrival) $calendarData[$ds]['a']++;
                    if ($isDeparture) $calendarData[$ds]['d']++;
                    if (!$isDeparture) $calendarData[$ds]['s']++;

                    $calendarData[$ds]['guests'][] = [
                        'n'  => $b->guest_name,
                        'r'  => $rooms ?: '—',
                        't'  => $type,
                        'k'  => $isArrival ? 'arrival' : ($isDeparture ? 'departure' : 'stay'),
                        'st' => $b->status,
                    ];
                }
            });

        // Desk-day KPIs: the desk lives in Manila time while the app clock
        // may be UTC, so "today" is pinned to Asia/Manila.
        $manilaToday = now(config('hostel.timezone'))->toDateString();

        $arrivalsToday = Booking::whereDate('check_in', $manilaToday)
            ->whereIn('status', Booking::BLOCKING_STATUSES)
            ->count();

        $departuresToday = Booking::whereDate('check_out', $manilaToday)
            ->where('status', 'active')
            ->count();

        $inHouse = Booking::where('status', 'active')->count();

        $totalRooms = Room::count();
        $availableTonight = Room::where('status', 'available')->count();

        // Payments recorded during Manila's today (created_at is stored in
        // the app timezone, so convert the Manila day bounds before querying)
        $dayStart = now(config('hostel.timezone'))->startOfDay()->setTimezone(config('app.timezone'));
        $dayEnd = now(config('hostel.timezone'))->endOfDay()->setTimezone(config('app.timezone'));
        $collectedToday = Payment::where('status', 'success')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->sum('amount');

        return view('staff.frontdesk.dashboard.index', compact(
            'labels',
            'values',
            'revenueValues',
            'peakMonthName',
            'peakMonthCount',
            'calendarData',
            'arrivalsToday',
            'departuresToday',
            'inHouse',
            'totalRooms',
            'availableTonight',
            'collectedToday',
        ));
    }
}
