<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Events\BookingChanged;
use App\Events\RoomStatusChanged;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\CancellationLog;
use App\Support\Realtime;
use Carbon\Carbon;

class SettingsController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        $username = $user->username;
        return view('public.account.profile', compact('username'));
    }
    
    public function bookings(Request $request)
    {
        $user = Auth::user();

        // Get query params
        $search   = $request->input('search');
        $status   = $request->input('status');
        $sortBy   = $request->input('sort_by', 'created_at');
        $sortDir  = $request->input('sort_dir', 'desc');

        // Build base query
        $query = $user->bookings()
            ->with('reservations', 'rooms', 'discountRequest');

        // Search: match ID, room number, or guest name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                ->orWhereHas('reservations', fn ($r) => $r->where('room_number', 'like', "%{$search}%"))
                ->orWhere('guest_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Sort
        $allowedSorts = ['id', 'check_in', 'check_out', 'total_price', 'payable_amount', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) $sortBy = 'created_at';
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

        $bookings = $query->orderBy($sortBy, $sortDir)->paginate(8)->withQueryString();

        // Cooldown logic
        $onCooldown = $user->bookings()
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subMinutes(30))
            ->exists();

        return view('public.account.bookings', [
            'username'   => $user->username,
            'bookings'   => $bookings,
            'onCooldown' => $onCooldown,
            'search'     => $search,
            'status'     => $status,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ]);
    }


    public function transactions(Request $request)
    {
        $user = Auth::user();

        $search   = $request->input('search');
        $status   = $request->input('status');
        $sortBy   = $request->input('sort_by', 'created_at');
        $sortDir  = $request->input('sort_dir', 'desc');

        // Get payments only for this user's bookings
        $query = Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->where('bookings.user_id', $user->id)
            ->select('payments.*'); // make sure we only select payments columns

        // Search: match ID, booking_id, reference_no, gateway
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('payments.id', 'like', "%{$search}%")
                ->orWhere('payments.booking_id', 'like', "%{$search}%")
                ->orWhere('payments.reference_no', 'like', "%{$search}%")
                ->orWhere('payments.gateway', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('payments.status', $status);
        }

        // Sort
        $allowedSorts = ['id','booking_id','amount','status','reference_no','gateway','landbank_transaction_id','created_at'];
        if (!in_array($sortBy, $allowedSorts)) $sortBy = 'created_at';
        $sortDir = in_array($sortDir, ['asc','desc']) ? $sortDir : 'desc';

        $payments = $query->orderBy($sortBy, $sortDir)
                        ->paginate(10)
                        ->withQueryString();

        return view('public.account.transactions', [
            'username' => $user->username,
            'payments' => $payments,
            'search'   => $search,
            'status'   => $status,
            'sortBy'   => $sortBy,
            'sortDir'  => $sortDir,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // If this request is for password update
        if ($request->filled('current_password') || $request->filled('password')) {
            $request->validate([
                'current_password' => ['required'],
                'password' => ['required', 'confirmed', 'min:6'],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Return here. This used to fall through to the profile block
            // below, which requires username and email — so a password-only
            // submission changed the password and *then* failed validation,
            // reporting an error for a change that had already happened.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Password updated. Please sign in again.');
        }

        // Otherwise: profile update
        $request->validate([
            'username' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $logout = false;

        $user->username = $request->username;
        $user->phone = $request->phone;

        if ($request->email !== $user->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            $logout = true;
        }

        $user->save();

        // Changing the email clears verification, so the session has to go
        // with it — and logout() alone leaves the session record usable.
        if ($logout) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Email updated. Please sign in again to verify it.');
        }

        return back()->with('status', 'Profile updated successfully.');
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        $user = Auth::user();

        // Ensure user owns this booking
        if ($booking->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // Only cancel if pending_payment
        if ($booking->status !== 'pending_payment') {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        // Check cooldown
        if ($user->last_cancelled_at) {
            $lastCancelled = Carbon::parse($user->last_cancelled_at);

            if ($lastCancelled->diffInMinutes(now()) < 30) {
                $remaining = 30 - $lastCancelled->diffInMinutes(now());
                return back()->with('error', "Please wait {$remaining} minutes before cancelling another booking.");
            }
        }

        // Validate reason
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        // Cancel booking
        $booking->status = 'cancelled';
        $booking->save();

        // Log cancellation
        CancellationLog::create([
            'booking_id' => $booking->id,
            'cancelled_at' => now(),
            'cancelled_by' => 'user',
            'reason' => $request->reason,
        ]);

        // Update last cancel timestamp
        $user->last_cancelled_at = now();
        $user->save();

        // A guest cancelling drops the booking out of BLOCKING_STATUSES, which
        // frees its rooms for everyone else — but no console was being told,
        // so the freed rooms stayed spoken-for until the next poll.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        return back()->with('status', 'Booking cancelled successfully.');
    }

}
