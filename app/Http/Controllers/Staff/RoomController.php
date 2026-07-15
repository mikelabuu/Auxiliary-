<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogger;
use App\Events\RoomStatusChanged;
use App\Support\Realtime;

class RoomController extends Controller
{
    public function index()
    {
        // Business dates are Manila; the app timezone is UTC, so a bare
        // today() is yesterday between midnight and 8 AM Manila.
        $today = Carbon::today('Asia/Manila');

        $rooms = Room::all();

        $stayContext = $this->buildStayContext($today);

        $totalRooms = $rooms->count();
        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $maintenanceRooms = $rooms->where('status', 'maintenance')->count();
        $cleaningRooms = $rooms->where('status', 'cleaning')->count();

        // Bookable today = housekeeping-available AND no stay spanning today.
        $availableNowByType = $rooms->groupBy(fn ($r) => strtolower($r->room_type))
            ->map(fn ($group) => $group->filter(fn ($r) => $r->status === 'available'
                && !isset($stayContext[trim($r->room_number)]['current']))->count());

        $roomTypes = RoomType::withCount('rooms')->orderBy('name')->get();

        return view('staff.rooms.index', compact(
            'rooms',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms',
            'cleaningRooms',
            'roomTypes',
            'stayContext',
            'availableNowByType'
        ));
    }

    /**
     * Stay context per room number: the stay spanning today ("current")
     * and the soonest upcoming arrival ("next"), from blocking bookings.
     * Shared by index() and statusFeed().
     */
    private function buildStayContext(Carbon $today): array
    {
        $stays = Reservation::query()
            ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->whereDate('bookings.check_out', '>=', $today)
            ->orderBy('bookings.check_in')
            ->get(['reservations.room_number', 'bookings.guest_name', 'bookings.check_in', 'bookings.check_out']);

        $stayContext = [];
        foreach ($stays as $stay) {
            $roomNumber = trim($stay->room_number);
            $checkIn  = Carbon::parse($stay->check_in);
            $checkOut = Carbon::parse($stay->check_out);

            if ($checkIn->lte($today) && $checkOut->gte($today)) {
                $stayContext[$roomNumber]['current'] ??= [
                    'guest' => $stay->guest_name,
                    'until' => $checkOut->format('M d'),
                ];
            } elseif (!isset($stayContext[$roomNumber]['next']) && $checkIn->gt($today)) {
                $stayContext[$roomNumber]['next'] = [
                    'guest' => $stay->guest_name,
                    'from'  => $checkIn->format('M d'),
                ];
            }
        }

        return $stayContext;
    }

    /**
     * Lightweight JSON snapshot of every room's live state, polled by the
     * Room Management page to keep cards/stats fresh without a reload.
     */
    public function statusFeed()
    {
        $today = Carbon::today('Asia/Manila');
        $stayContext = $this->buildStayContext($today);

        $rooms = Room::all(['id', 'room_number', 'status'])->map(function ($room) use ($stayContext) {
            $ctx = $stayContext[trim($room->room_number)] ?? [];
            $current = $ctx['current'] ?? null;
            $next = $ctx['next'] ?? null;

            if ($current) {
                $stay = ['kind' => 'current', 'label' => 'In use · until ' . $current['until'], 'title' => $current['guest'] . ' · until ' . $current['until']];
            } elseif ($next) {
                $stay = ['kind' => 'next', 'label' => 'Next stay · ' . $next['from'], 'title' => $next['guest'] . ' arrives ' . $next['from']];
            } else {
                $stay = ['kind' => 'none', 'label' => 'No upcoming stays', 'title' => null];
            }

            return [
                'id'     => $room->id,
                'status' => $room->status,
                'held'   => (bool) $current,
                'stay'   => $stay,
            ];
        });

        return response()->json(['success' => true, 'rooms' => $rooms]);
    }

public function occupancyForRoom(Room $room)
{
    $today = now('Asia/Manila')->toDateString();

    $bookings = \App\Models\Booking::query()
        ->select(
            'bookings.id as booking_id',
            'bookings.user_id',
            'bookings.guest_name',
            'bookings.status',
            'bookings.check_in',
            'bookings.check_out'
        )
        ->join('reservations', 'reservations.booking_id', '=', 'bookings.id')
        ->where('reservations.room_number', $room->room_number)
        ->whereIn('bookings.status', \App\Models\Booking::BLOCKING_STATUSES)
        ->whereDate('bookings.check_out', '>=', $today)
        ->orderBy('bookings.check_in')
        ->distinct()
        ->get();

    $bookings = $bookings->map(function ($b) use ($today) {
        $checkIn  = \Carbon\Carbon::parse($b->check_in);
        $checkOut = \Carbon\Carbon::parse($b->check_out);
        $isCurrent = $checkIn->lte($today) && $checkOut->gte($today);

        return [
            'id' => $b->booking_id,
            'user_id' => $b->user_id,
            'guest_name' => $b->guest_name,
            'status' => ucwords(str_replace('_', ' ', $b->status)),
            'timeline' => $isCurrent ? 'current' : 'upcoming',
            'nights' => max(1, $checkIn->diffInDays($checkOut)),
            'check_in_formatted' => $checkIn->format('M d, Y'),
            'check_out_formatted' => $checkOut->format('M d, Y'),
        ];
    });

    return response()->json([
        'success'     => true,
        'room_number' => $room->room_number,
        'room_status' => $room->status,
        'bookings'    => $bookings,
    ]);
}

    public function store(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'room_number' => 'required|unique:rooms,room_number',
            'room_type'   => 'required|string|max:255',
            'wing'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'status'      => ['nullable', Rule::in(Room::SETTABLE_STATUSES)],
            'notes'       => 'nullable|string|max:2000',
        ]);

        $room = Room::create([
            'room_number' => $validated['room_number'],
            'room_type'   => $validated['room_type'],
            'wing'        => $validated['wing'],
            'price'       => $validated['price'],
            'status'      => $validated['status'] ?? 'available',
            'notes'       => $validated['notes'] ?? null,
            'last_edited_by'=> $staff->id,
        ]);

        AuditLogger::log(
            'room_created',          // Action name
            $room,                   // Target model
            null,                    // Old values (none for new record)
            $room->toArray(),        // New values
            "Room {$room->room_number} was added by {$staff->name}" // Optional description
        );

        Realtime::emit(new RoomStatusChanged());

        return redirect()->back()->with('success', 'Room added successfully!');
    }

    public function update(Request $request, Room $room)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number,' . $room->id,
            'room_type'   => 'required|string',
            'wing'        => 'required|string',
            'price'       => 'required|numeric|min:0',
            'status'      => ['nullable', Rule::in(Room::SETTABLE_STATUSES)],
            'notes'       => 'nullable|string|max:2000',
        ]);

        // The edit form omits status for an occupied room. Drop the key rather
        // than let a missing/blank value overwrite a status the booking owns.
        if (!$request->filled('status')) {
            unset($validated['status']);
        }

        $oldValues = $room->getOriginal();
        $oldRoomNumber = $room->room_number;

        DB::transaction(function () use ($room, $validated, $staff, $oldRoomNumber) {
            $room->update(array_merge($validated, [
                'last_edited_by' => $staff->id,
            ]));

            // Reservation history is keyed by room_number (a snapshot string,
            // not room_id) — without this, renaming a room orphans its past
            // and active reservations from Room::reservations()/occupancy
            // lookups, which still match on the room's CURRENT room_number.
            if ($validated['room_number'] !== $oldRoomNumber) {
                Reservation::where('room_number', $oldRoomNumber)
                    ->update(['room_number' => $validated['room_number']]);
            }
        });

        $newValues = $room->fresh()->toArray();

        AuditLogger::log(
        'room_updated',
            $room,
            $oldValues,
            $newValues,
            "Room {$room->room_number} was updated by {$staff->name}"
        );

        Realtime::emit(new RoomStatusChanged());

        return response()->json([
            'success' => true,
            'room' => $room
        ]);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'status' => ['required', Rule::in(Room::SETTABLE_STATUSES)],
        ], [
            'status.in' => 'Occupied is set by checking a guest in, not by hand. Use a booking to occupy a room.',
        ]);

        $oldValues = $room->getOriginal();

        $room->update([
            'status'         => $validated['status'],
            'last_edited_by' => $staff->id,
        ]);

        AuditLogger::log(
            'room_status_updated',
            $room,
            $oldValues,
            $room->fresh()->toArray(),
            "Room {$room->room_number} status set to {$validated['status']} by {$staff->name}"
        );

        Realtime::emit(new RoomStatusChanged());

        return response()->json([
            'success' => true,
            'room' => $room,
        ]);
    }

    public function destroy(Room $room)
    {
        $staff = Auth::guard('staff')->user();

        if ($room->reservations()->exists() || $room->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Room {$room->room_number} has booking history and cannot be deleted.",
            ], 422);
        }

        $oldValues = $room->toArray();
        $roomNumber = $room->room_number;

        AuditLogger::log(
            'room_deleted',
            $room,
            $oldValues,
            null,
            "Room {$roomNumber} was deleted by {$staff->name}"
        );

        $room->delete();

        Realtime::emit(new RoomStatusChanged());

        return response()->json([
            'success' => true,
        ]);
    }

    public function edit(Room $room)
    {
        return response()->json([
            'success' => true,
            'room' => $room
        ]);
    }
}
