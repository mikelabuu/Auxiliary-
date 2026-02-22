<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $prices = Room::select('room_type', \DB::raw('MAX(price) as price')) ->groupBy('room_type') ->pluck('price', 'room_type');

        return view('staff.rooms', compact(
            'rooms',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms',
            'cleaningRooms',
            'prices'
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

        $request->validate([
            'room_number' => 'required|unique:rooms,room_number',
            'room_type'   => 'required|string|max:255',
            'wing'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
        ]);

        $room = Room::create([
            'room_number' => $request->room_number,
            'room_type'   => $request->room_type,
            'wing'        => $request->wing,
            'price'       => $request->price,
            'status'      => 'available',
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
        ]);

        $oldValues = $room->getOriginal();

        $room->update(array_merge($validated, [
            'last_edited_by' => $staff->id,
        ]));

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
    public function edit(Room $room)
    {
        return response()->json([
            'success' => true,
            'room' => $room
        ]);
    }
}
