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
use App\Events\StaffNotification;
use App\Support\Realtime;
use App\Support\RoomHold;
use App\Support\RoomStays;

class RoomController extends Controller
{
    /**
     * Room number, type and wing are typed by staff and end up interpolated
     * into the booking-board markup. Escaping at render time is the real
     * defence (see the room board views), but there is no legitimate reason
     * for a room label to contain markup, so keep it out of the column too.
     *
     * Deliberately permissive about what a room may be *called* — letters,
     * digits, spaces, hyphens, underscores covers "101", "A-12", "chev_re".
     */
    private const SAFE_LABEL = 'regex:/^[A-Za-z0-9][A-Za-z0-9 _\-]*$/';

    private const LABEL_MESSAGES = [
        'room_number.regex' => 'Room number may only contain letters, numbers, spaces, hyphens and underscores.',
        'room_type.regex'   => 'Room type may only contain letters, numbers, spaces, hyphens and underscores.',
        'wing.regex'        => 'Wing may only contain letters, numbers, spaces, hyphens and underscores.',
    ];

    public function index()
    {
        // Business dates are Manila; the app timezone is UTC, so a bare
        // today() is yesterday between midnight and 8 AM Manila.
        $today = Carbon::today(config('hostel.timezone'));

        $rooms = Room::all();

        $stayContext = RoomStays::context($today);

        // The badge answers "can the desk give this room out tonight", so a
        // stay covering today outranks the housekeeping column. Rendering
        // `rooms.status` raw is what let a room a guest had paid for — and was
        // due in tonight — go on reading AVAILABLE until someone checked them in.
        $displayStatuses = RoomStays::displayStatuses($rooms, $stayContext);

        $totalRooms = $rooms->count();
        $counts = collect($displayStatuses)->countBy();
        $occupiedRooms = $counts->get('occupied', 0);
        $availableRooms = $counts->get('available', 0);
        $reservedRooms = $counts->get('reserved', 0);
        $pendingRooms = $counts->get('pending', 0);
        $maintenanceRooms = $counts->get('maintenance', 0);
        $cleaningRooms = $counts->get('cleaning', 0);

        // Bookable today is now exactly what the badge says: 'available' already
        // means housekeeping-ready with no stay spanning today.
        $availableNowByType = $rooms->groupBy(fn ($r) => strtolower($r->room_type))
            ->map(fn ($group) => $group->filter(fn ($r) => $displayStatuses[$r->id] === 'available')->count());

        $roomTypes = RoomType::withCount('rooms')->orderBy('name')->get();

        return view('staff.rooms.index', compact(
            'rooms',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
            'pendingRooms',
            'maintenanceRooms',
            'cleaningRooms',
            'roomTypes',
            'stayContext',
            'displayStatuses',
            'availableNowByType'
        ));
    }

    /**
     * Lightweight JSON snapshot of every room's live state, polled by the
     * Room Management page to keep cards/stats fresh without a reload.
     */
    public function statusFeed()
    {
        $today = Carbon::today(config('hostel.timezone'));
        $stayContext = RoomStays::context($today);

        $rooms = Room::all(['id', 'room_number', 'status']);
        $displayStatuses = RoomStays::displayStatuses($rooms, $stayContext);

        $rooms = $rooms->map(function ($room) use ($stayContext, $displayStatuses) {
            $ctx = $stayContext[trim($room->room_number)] ?? [];
            $current = $ctx['current'] ?? null;
            $next = $ctx['next'] ?? null;

            // Same helper the room card renders from, so a live poll can never
            // word a hold differently from the server-rendered page.
            $line = RoomHold::stayLine($current, $next);

            $stay = [
                'kind'  => $line['kind'],
                'label' => $line['label'],
                'guest' => $line['guest'],
                'title' => $line['title'] ?: null,
            ];

            return [
                'id'      => $room->id,
                // The housekeeping column, kept for the kebab's checkmark; the
                // badge and every count follow display_status.
                'status'  => $room->status,
                'display_status' => $displayStatuses[$room->id],
                'held'    => (bool) $current,
                // Held, but nobody has been paid yet — the front desk can act
                // on this (chase the guest, or let it expire).
                'pending' => RoomHold::isPending($current['status'] ?? $next['status'] ?? null),
                'stay'    => $stay,
            ];
        });

        return response()->json(['success' => true, 'rooms' => $rooms]);
    }

public function occupancyForRoom(Room $room)
{
    $today = now(config('hostel.timezone'))->toDateString();

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
            'room_number' => ['required', 'string', 'max:50', self::SAFE_LABEL, 'unique:rooms,room_number'],
            'room_type'   => ['required', 'string', 'max:100', self::SAFE_LABEL],
            'wing'        => ['required', 'string', 'max:100', self::SAFE_LABEL],
            'price'       => 'required|numeric|min:0',
            'status'      => ['nullable', Rule::in(Room::SETTABLE_STATUSES)],
            'notes'       => 'nullable|string|max:2000',
        ], self::LABEL_MESSAGES);

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
            'room_number' => ['required', 'string', 'max:50', self::SAFE_LABEL, 'unique:rooms,room_number,' . $room->id],
            'room_type'   => ['required', 'string', 'max:100', self::SAFE_LABEL],
            'wing'        => ['required', 'string', 'max:100', self::SAFE_LABEL],
            'price'       => 'required|numeric|min:0',
            'status'      => ['nullable', Rule::in(Room::SETTABLE_STATUSES)],
            'notes'       => 'nullable|string|max:2000',
        ], self::LABEL_MESSAGES);

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

        // A room going out of service is the one status change the rest of the
        // desk needs pushed at them — everything else they discover when they
        // next look at the board.
        if ($validated['status'] === 'maintenance' && ($oldValues['status'] ?? null) !== 'maintenance') {
            Realtime::emit(StaffNotification::roomMaintenance($room->fresh()));
        }

        // Marking a room available does not necessarily make the badge say
        // Available — a booking may still be holding it tonight — so the
        // board is told what to render rather than left to guess.
        $stayContext = RoomStays::context(Carbon::today(config('hostel.timezone')));

        return response()->json([
            'success' => true,
            'room' => $room,
            'display_status' => RoomStays::displayStatuses([$room], $stayContext)[$room->id],
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
