<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\NoShowLog;
use App\Models\ExpiryLog;
use App\Models\CancellationLog;
use Carbon\Carbon;

class BookingLogsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $tab = $request->input('tab', 'checkins'); // default tab
        $perPage = 10;

        switch ($tab) {
            case 'checkouts':
                $logs = Checkout::with(['booking', 'staff'])
                    ->when($search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%$search%")
                          ->orWhere('guest_name', 'like', "%$search%")
                    ))
                    ->orderByDesc('checked_out_at')
                    ->paginate($perPage);

                // Format date as string
                $logs->getCollection()->transform(function ($item) {
                    $item->checked_out_at = Carbon::parse($item->checked_out_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            case 'noshow':
                $logs = NoShowLog::with(['booking', 'staff'])
                    ->when($search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%$search%")
                          ->orWhere('guest_name', 'like', "%$search%")
                    ))
                    ->orderByDesc('marked_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->marked_at = Carbon::parse($item->marked_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;
                        case 'cancellations':
                $logs = CancellationLog::with(['booking'])
                    ->when($search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%$search%")
                          ->orWhere('guest_name', 'like', "%$search%")
                    ))
                    ->orderByDesc('cancelled_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(fn($item) => tap($item, fn($i) => 
                    $i->cancelled_at = Carbon::parse($i->cancelled_at)->format('M d, Y h:i A')
                ));
                break;

            case 'expiry':
                $logs = ExpiryLog::with(['booking', 'staff'])
                    ->when($search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%$search%")
                          ->orWhere('guest_name', 'like', "%$search%")
                    ))
                    ->orderByDesc('expired_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->expired_at = Carbon::parse($item->expired_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            default:
                $logs = Checkin::with(['booking', 'staff'])
                    ->when($search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%$search%")
                          ->orWhere('guest_name', 'like', "%$search%")
                    ))
                    ->orderByDesc('checked_in_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->checked_in_at = Carbon::parse($item->checked_in_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;
        }

        return view('staff.bookinglogs.index', compact('logs', 'tab', 'search'));
    }
}
