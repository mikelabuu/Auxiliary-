<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Topbar global search. Returns grouped JSON results for the
     * admin quick-search dropdown (bookings, guests, rooms).
     */
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['bookings' => [], 'users' => [], 'rooms' => []]);
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

        $bookings = Booking::query()
            ->where(function ($w) use ($q, $like) {
                $w->where('guest_name', 'like', $like);
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            })
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($b) => [
                'id'     => $b->id,
                'guest'  => $b->guest_name,
                'status' => str_replace('_', ' ', $b->status),
                'dates'  => $b->check_in->format('M d') . ' – ' . $b->check_out->format('M d'),
                // Completed bookings live on their own page; everything else is in the hub.
                'url'    => $b->status === 'completed'
                    ? route('staff.completedbookings.index', ['search' => $b->id])
                    : route('staff.bookings.index', ['search' => $b->id]),
            ]);

        $users = User::query()
            ->where(function ($w) use ($like) {
                $w->where('username', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like);
            })
            ->take(4)
            ->get()
            ->map(fn ($u) => [
                'name'  => $u->username,
                'email' => $u->email,
                'url'   => route('staff.userrecords.index', ['search' => $u->email]),
            ]);

        $rooms = Room::query()
            ->where(function ($w) use ($like) {
                $w->where('room_number', 'like', $like)
                  ->orWhere('room_type', 'like', $like);
            })
            ->orderBy('room_number')
            ->take(4)
            ->get()
            ->map(fn ($r) => [
                'number' => $r->room_number,
                'type'   => ucfirst($r->room_type),
                'status' => $r->status,
                'url'    => route('staff.rooms'),
            ]);

        return response()->json(compact('bookings', 'users', 'rooms'));
    }
}
