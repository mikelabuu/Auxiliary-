<?php

namespace App\Http\Controllers;

use App\Support\RefCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Events\BookingChanged;
use App\Events\RoomStatusChanged;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\CancellationLog;
use App\Support\GuestNotice;
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
            // The stay cards label themselves "#4", and the emailed receipt
            // calls the same booking R-000004. Neither spelling matched the
            // bare id, so a guest searching for the booking by the number they
            // were given found nothing.
            $refId = RefCode::toId($search);

            $query->where(function ($q) use ($search, $refId) {
                $q->where('id', 'like', "%{$search}%")
                ->orWhereHas('reservations', fn ($r) => $r->where('room_number', 'like', "%{$search}%"))
                ->orWhere('guest_name', 'like', "%{$search}%");

                if ($refId !== null) {
                    $q->orWhere('id', $refId);
                }
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
            // Covers both halves of a row's identity: PMT-0012 for the payment
            // and BK-0004 for the stay it settles. RefCode does not care which
            // prefix was typed, so one resolved number is tried against both
            // columns — a payment id and a booking id are different numbers,
            // and matching the wrong one only ever costs an extra row.
            $refId = RefCode::toId($search);

            $query->where(function($q) use ($search, $refId) {
                $q->where('payments.id', 'like', "%{$search}%")
                ->orWhere('payments.booking_id', 'like', "%{$search}%")
                ->orWhere('payments.reference_no', 'like', "%{$search}%")
                ->orWhere('payments.gateway', 'like', "%{$search}%");

                if ($refId !== null) {
                    $q->orWhere('payments.id', $refId)
                      ->orWhere('payments.booking_id', $refId);
                }
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

        // Which card on the settings page was submitted.
        //
        // Both post here, and they used to be told apart by "did
        // current_password arrive?". That is why the profile card could never
        // ask for a password: adding the field would have routed every profile
        // save into the password branch, which then fails for want of a new
        // password. The discriminator and the missing re-authentication below
        // were the same bug wearing two hats.
        //
        // `_form` is the explicit answer, and matches what the staff records
        // page already does. The fallback keeps the old reading for any
        // request that predates the hidden field.
        $isPasswordChange = $request->has('_form')
            ? $request->input('_form') === 'password'
            : ($request->filled('current_password') || $request->filled('password'));

        if ($isPasswordChange) {
            $request->validate([
                'current_password' => ['required'],
                // bcrypt ignores anything past the 72nd byte, so a longer
                // password would not be the password on the account.
                'password' => ['required', 'confirmed', 'min:8', 'max:72'],
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

        // Moving the email address is an account-recovery change, not a
        // profile detail: whoever holds the new inbox can drive a password
        // reset back into this account. A session alone should not be enough
        // to do it — the password branch above has always re-authenticated
        // before touching a credential, and this is the other credential.
        //
        // Only asked for when the address actually changes, so editing a
        // username or a phone number stays a one-field edit.
        if ($request->email !== $user->email) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
            ], [
                'current_password.required' => 'Enter your current password to change the email address on this account.',
                'current_password.current_password' => 'That password is incorrect.',
            ]);
        }

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

    /**
     * A guest cancelling their own booking — which they may only do while it is
     * still unpaid.
     *
     * The rule is the house policy, not a technical limitation: paying takes
     * the rooms off sale, and once that has happened the money does not come
     * back. A guest whose plans change after paying has exactly one thing they
     * can do, and it is not this — they ask to move the stay, before check-in
     * time on their arrival day. See App\Http\Controllers\RescheduleController.
     *
     * The check has always been here. What it lacked was a way out: it said
     * "this booking cannot be cancelled" and stopped, which reads as a fault
     * rather than a policy and left the guest with nowhere to go.
     */
    public function cancelBooking(Request $request, Booking $booking)
    {
        $user = Auth::user();

        // Ensure user owns this booking
        if ($booking->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($booking->status !== 'pending_payment') {
            $message = match ($booking->status) {
                'pending_discount' => 'This booking is waiting on your Senior / PWD discount review. Withdraw the discount request first, and you can cancel it after that.',
                'paid' => \App\Models\RescheduleRequest::isOpenFor($booking)
                    ? 'A paid booking cannot be cancelled. If you cannot make these dates, request a reschedule at least 24 hours before your check-in.'
                    : 'A paid booking cannot be cancelled, and the deadline to move it has passed. Please contact our front desk.',
                'active' => 'You are already checked in, so there is nothing to cancel. Please speak to our front desk.',
                default => 'This booking can no longer be cancelled.',
            };

            return back()->with('error', $message);
        }

        // Check cooldown
        if ($user->last_cancelled_at) {
            $lastCancelled = Carbon::parse($user->last_cancelled_at);
            $cooldownMinutes = 30;
            $secondsRemaining = ($cooldownMinutes * 60) - $lastCancelled->diffInSeconds(now());

            if ($secondsRemaining > 0) {
                $remaining = (int) ceil($secondsRemaining / 60);
                $unit = $remaining === 1 ? 'minute' : 'minutes';
                return back()->with('error', "Please wait {$remaining} {$unit} before cancelling another booking.");
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

        // The guest knows they cancelled — this is their written record of it,
        // and the only copy that lives outside our own CancellationLog.
        GuestNotice::bookingCancelled($booking, $request->reason);

        return back()->with('status', 'Booking cancelled successfully.');
    }

}
