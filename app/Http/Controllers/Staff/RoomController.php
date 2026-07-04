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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogger;

class RoomController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $rooms = Room::withCount(['bookings as occupied_bookings_count' => function ($query) use ($today) {
            $query->where('status', 'checked_in')
                  ->whereDate('check_in', '<=', $today)
                  ->whereDate('check_out', '>=', $today);
        }])->get();

        $totalRooms = $rooms->count();
        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $maintenanceRooms = $rooms->where('status', 'maintenance')->count();
        $cleaningRooms = $rooms->where('status', 'cleaning')->count();

        $roomTypes = RoomType::withCount('rooms')->orderBy('name')->get();

        return view('staff.rooms.index', compact(
            'rooms',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms',
            'cleaningRooms',
            'roomTypes'
        ));
    }

public function occupancyForRoom(Room $room)
{
    $today = now()->toDateString();

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
        ->whereDate('bookings.check_in', '<=', $today)
        ->whereDate('bookings.check_out', '>=', $today)
        ->where('bookings.status', 'active')
        ->distinct()
        ->get();

    $bookings = $bookings->map(function ($b) {
        return [
            'id' => $b->booking_id,
            'user_id' => $b->user_id,
            'guest_name' => $b->guest_name,
            'status' => ucfirst($b->status),
            'check_in_formatted' => \Carbon\Carbon::parse($b->check_in)->format('M d, Y'),
            'check_out_formatted' => \Carbon\Carbon::parse($b->check_out)->format('M d, Y'),
        ];
    });

    return response()->json([
        'success' => true,
        'bookings' => $bookings,
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
            'status'      => 'nullable|in:available,occupied,maintenance,cleaning',
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
            'status'      => 'nullable|in:available,occupied,maintenance,cleaning',
            'notes'       => 'nullable|string|max:2000',
        ]);

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

        return response()->json([
            'success' => true,
            'room' => $room
        ]);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,maintenance,cleaning',
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
