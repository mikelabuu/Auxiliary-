<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Fire-and-forget wrapper for real-time broadcast events.
 *
 * Broadcasts are a "nice to have" — the app must keep working even if the
 * Reverb WebSocket server isn't running. Because our events are
 * ShouldBroadcastNow (sent synchronously), a broken/absent Reverb would
 * otherwise throw a BroadcastException mid-request and fail the underlying
 * action (a room status change, a check-in, etc.). Routing every broadcast
 * through here downgrades that failure to a logged warning so the action
 * still succeeds; the pages fall back to their polling.
 */
class Realtime
{
    public static function emit(object $event): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            Log::warning('Realtime broadcast skipped (' . class_basename($event) . '): ' . $e->getMessage());
        }
    }
}
