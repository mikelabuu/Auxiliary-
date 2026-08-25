<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Support\StaffAlerts;
use Illuminate\Http\JsonResponse;

/**
 * The bell's contents, as JSON.
 *
 * The desk consoles are long-lived pages — the front desk leaves one open for a
 * whole shift — but the notification list was only ever built at page load.
 * Live push over Reverb was the intended answer and the client half of it is
 * written, but it needs a daemon and a WebSocket proxy this host cannot
 * currently keep running, so in practice nothing updated the bell and staff had
 * to refresh to see that anything had happened.
 *
 * This is the same list from the same source as the page-load backfill, so an
 * alert that arrives by poll and the same alert re-rendered on the next page
 * load are one row, read state and all. If Reverb is ever running, alerts
 * simply arrive sooner; the topbar de-dupes on the stable id either way, so
 * both paths can be live at once without showing anything twice.
 *
 * It also carries the sidebar's four queue counts. Those used to be inline
 * queries in the sidebar view, true only at render time; returning them on the
 * poll the console is already making keeps the badges live for free rather
 * than adding a second endpoint and a second timer.
 */
class NotificationFeedController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'items'  => StaffAlerts::current(),
            'counts' => StaffAlerts::pendingCounts(),
        ]);
    }
}
