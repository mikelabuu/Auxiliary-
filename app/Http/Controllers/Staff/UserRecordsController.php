<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditLogger;

class UserRecordsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest'); // newest first by default
        $status = $request->input('status', 'all');
        $perPage = 10;

        $stats = [
            'total'          => User::count(),
            'active'         => User::where('is_suspended', false)->count(),
            'suspended'      => User::where('is_suspended', true)->count(),
            'verified'       => User::whereNotNull('email_verified_at')->count(),
            'new_this_month' => User::where('created_at', '>=', now('Asia/Manila')->startOfMonth())->count(),
        ];

        $users = User::query()
            ->when($search, function($q) use ($search) {
                $q->where(function($q) use ($search) {
                    $q->where('username', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%");
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_suspended', false))
            ->when($status === 'suspended', fn($q) => $q->where('is_suspended', true))
            ->withCount(['bookings as completed_bookings_count' => function($q) {
                $q->whereIn('status', ['paid', 'completed']);
            }])
            ->orderBy(
                'created_at',
                $sort === 'oldest' ? 'asc' : 'desc'
            )
            ->paginate($perPage)
            ->withQueryString();

        return view('staff.userrecords.index', compact('users', 'search', 'sort', 'status', 'stats'));
    }

    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required']);

        $staff = Auth::guard('staff')->user();

        if (!Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password']);
        }

        return response()->json(['success' => true]);
    }

    public function suspend(User $user)
    {
        $staff = Auth::guard('staff')->user();

        $user->update(['is_suspended' => true]);

        AuditLogger::log(
            'user_suspended',
            $user,
            ['is_suspended' => false],
            ['is_suspended' => true],
            "Staff {$staff->name} suspended user {$user->name} (ID: {$user->id})."
        );

        return response()->json(['success' => true, 'message' => 'User suspended successfully']);
    }

    public function unsuspend(User $user)
    {
        $staff = Auth::guard('staff')->user();

        $user->update(['is_suspended' => false]);

        AuditLogger::log(
            'user_unsuspended',
            $user,
            ['is_suspended' => true],
            ['is_suspended' => false],
            "Staff {$staff->name} unsuspended user {$user->name} (ID: {$user->id})."
        );

        return response()->json(['success' => true, 'message' => 'User unsuspended successfully']);
    }
}
