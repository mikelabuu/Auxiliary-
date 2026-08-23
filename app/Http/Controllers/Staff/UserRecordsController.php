<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\RefCode;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
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
            'new_this_month' => User::where('created_at', '>=', now(config('hostel.timezone'))->startOfMonth())->count(),
        ];

        $users = User::query()
            ->when($search, function($q) use ($search) {
                // GS-0004 is what the booking detail modal calls a guest, so
                // it is what gets pasted in here — and it matched none of the
                // three text columns below.
                $refId = RefCode::toId($search);

                $q->where(function($q) use ($search, $refId) {
                    $q->where('username', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%");

                    if ($refId !== null) {
                        $q->orWhere('id', $refId);
                    }
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_suspended', false))
            ->when($status === 'suspended', fn($q) => $q->where('is_suspended', true))
            ->withCount(['bookings as completed_bookings_count' => function($q) {
                $q->whereIn('status', ['paid', 'completed']);
            }])
            ->when($sort === 'stays',
                fn($q) => $q->orderByDesc('completed_bookings_count')->orderByDesc('created_at'),
                fn($q) => $q->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            )
            ->paginate($perPage)
            ->withQueryString();

        return view('staff.userrecords.index', compact('users', 'search', 'sort', 'status', 'stats'));
    }

    public function show(User $user)
    {
        $user->loadCount([
            'bookings as total_bookings',
            'bookings as completed_bookings' => fn($q) => $q->whereIn('status', ['paid', 'completed']),
            'bookings as cancelled_bookings' => fn($q) => $q->where('status', 'cancelled'),
        ]);

        $lifetimeSpend = Payment::where('status', 'success')
            ->whereIn('booking_id', $user->bookings()->select('id'))
            ->sum('amount');

        $recentBookings = $user->bookings()
            ->with('reservations')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($b) => [
                'id'     => $b->id,
                'rooms'  => implode(', ', array_filter($b->room_numbers)) ?: '—',
                'dates'  => $b->check_in->format('M d') . ' – ' . $b->check_out->format('M d, Y'),
                'status' => $b->status,
                'amount' => number_format($b->total_price ?? 0, 2),
            ]);

        return response()->json([
            'id'             => $user->id,
            'username'       => $user->username,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'is_suspended'   => (bool) $user->is_suspended,
            'verified'       => (bool) $user->email_verified_at,
            'verified_at'    => $user->email_verified_at?->timezone(config('hostel.timezone'))->format('M d, Y'),
            'joined'         => $user->created_at->timezone(config('hostel.timezone'))->format('M d, Y'),
            'last_login'     => $user->last_login_at ? Carbon::parse($user->last_login_at)->timezone(config('hostel.timezone'))->format('M d, Y · h:i A') : null,
            'last_cancelled' => $user->last_cancelled_at ? Carbon::parse($user->last_cancelled_at)->timezone(config('hostel.timezone'))->format('M d, Y') : null,
            'stats' => [
                'total'     => $user->total_bookings,
                'completed' => $user->completed_bookings,
                'cancelled' => $user->cancelled_bookings,
                'spend'     => number_format($lifetimeSpend, 2),
            ],
            'recent_bookings' => $recentBookings,
        ]);
    }

    public function verifyEmail(User $user)
    {
        $staff = Auth::guard('staff')->user();

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email is already verified.']);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        AuditLogger::log(
            'user_email_verified',
            $user,
            ['email_verified_at' => null],
            ['email_verified_at' => $user->email_verified_at->toDateTimeString()],
            "Staff {$staff->name} manually verified the email of user {$user->username} (ID: {$user->id})."
        );

        return response()->json(['success' => true, 'message' => 'Email marked as verified']);
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
