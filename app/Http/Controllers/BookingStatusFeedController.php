<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The current status of the signed-in guest's own bookings.
 *
 * A guest sitting on their booking page is waiting on a decision they cannot
 * make themselves: a receipt being verified, a Senior/PWD discount being
 * reviewed. Both guest pages were written to learn the outcome live over
 * Reverb — but `window.Echo` has never existed on a guest page. The public
 * bundle (resources/js/app.js) does not import it; only resources/js/admin.js
 * does. So every one of those `.listen()` calls sat behind an
 * `if (!window.Echo) return;` that always returned, and the only way to find
 * out anything had changed was to refresh by hand.
 *
 * Ownership is structural here rather than a check that can be forgotten: the
 * where clause is the only way rows enter the result, so there is no path that
 * returns someone else's booking. Status is all it returns — no names, amounts
 * or contact details — matching exactly what App\Events\BookingStatusChanged
 * was permitted to put on the wire for the same audience.
 */
class BookingStatusFeedController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            // Keyed by id so a caller can look up just the booking it is
            // showing. Capped because this is polled: a guest watching one
            // booking has no use for a hundred rows of history.
            //
            // The line comes from the model rather than being assembled in the
            // browser, so the wording lives in one place next to the statuses
            // it describes. See Booking::statusLine().
            'bookings' => Booking::where('user_id', $request->user()->id)
                ->latest('id')
                ->take(50)
                ->get(['id', 'status'])
                ->mapWithKeys(fn (Booking $b) => [$b->id => [
                    'status'  => $b->status,
                    'message' => Booking::statusLine($b->id, (string) $b->status),
                ]]),
        ]);
    }
}
