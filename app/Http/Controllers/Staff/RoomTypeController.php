<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoomTypeController extends Controller
{
    public function store(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:room_types,name',
            'base_price' => 'required|numeric|min:0',
            'capacity'   => 'required|integer|min:1|max:255',
        ]);

        $slug = Str::slug($validated['name'], '');
        if ($slug === '') {
            $slug = 'type' . time();
        }

        $baseSlug = $slug;
        $suffix = 1;
        while (RoomType::where('slug', $slug)->exists()) {
            $slug = $baseSlug . (++$suffix);
        }

        // The name/slug uniqueness checks above are check-then-act and can
        // race under concurrent submissions of the same new type name; both
        // `slug` and `name` have DB-level unique constraints as the real
        // guard, so turn a collision into a clean error instead of a 500.
        try {
            $roomType = RoomType::create([
                'slug'       => $slug,
                'name'       => $validated['name'],
                'base_price' => $validated['base_price'],
                'capacity'   => $validated['capacity'],
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That room type was just created by someone else. Please try again.',
            ], 409);
        }

        AuditLogger::log(
            'room_type_created',
            $roomType,
            null,
            $roomType->toArray(),
            "Room type {$roomType->name} was added by {$staff->name}"
        );

        return response()->json([
            'success' => true,
            'roomType' => $roomType,
        ]);
    }

    public function update(Request $request, RoomType $roomType)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:room_types,name,' . $roomType->id,
            'base_price' => 'required|numeric|min:0',
            'capacity'   => 'required|integer|min:1|max:255',
        ]);

        $oldValues = $roomType->getOriginal();

        try {
            $roomType->update($validated);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Another room type already uses that name.',
            ], 409);
        }

        AuditLogger::log(
            'room_type_updated',
            $roomType,
            $oldValues,
            $roomType->fresh()->toArray(),
            "Room type {$roomType->name} was updated by {$staff->name}"
        );

        return response()->json([
            'success' => true,
            'roomType' => $roomType->fresh()->loadCount('rooms'),
        ]);
    }
}
