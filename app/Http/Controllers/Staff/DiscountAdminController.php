<?php

namespace App\Http\Controllers\Staff;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\DiscountChanged;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\DiscountFile;
use App\Services\AuditLogger;
use App\Services\DiscountService;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DiscountAdminController extends Controller
{
    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function index()
    {
        return view('staff.discounts.index');
    }

    public function show($id)
    {
        $discount = Discount::with([
            'files.reviewer',   // to show who reviewed
            'booking.reservations.discountFiles.reviewer', // eager load per-reservation files + reviewer
        ])->findOrFail($id);

        $booking = $discount->booking;

        $staff = Auth::guard('staff')->user();

        AuditLogger::log(
            'view_discount_requests',
            $discount,
            null,
            null,
            "Staff {$staff->name} viewed discount request #{$discount->id} for booking #{$booking->id}"
        );

        return view('staff.discounts.show', compact('discount', 'booking'));
    }

    public function approveFile(Discount $discount, DiscountFile $file)
    {
        $this->authorizeFile($discount, $file);

        if (! $this->isOpenForInPersonReview($discount)) {
            return back()->with('error', 'This discount request is no longer an active booking hold.');
        }

        // Check if already reviewed
        if ($file->status !== 'pending') {
            return back()->with('error', 'This file has already been reviewed by another staff.');
        }

        $staff = Auth::guard('staff')->user();
        $oldStatus = $file->status;

        $file->update([
            'status'      => 'approved',
            'reviewed_by' => auth('staff')->id(),
            'reviewed_at' => now(),
        ]);

        AuditLogger::log(
            'discount_file_approved',
            $file,
            ['status' => $oldStatus],
            ['status' => 'approved'],
            "Staff {$staff->name} approved a discount ID file (ID: {$file->id}) for booking #{$discount->booking->id}"
        );

        // Per-file reviews move the request's progress bar in the queue.
        Realtime::emit(new DiscountChanged);

        return back()->with('success', 'File approved for reservation #' . ($file->reservation->room_number ?? 'N/A'));
    }

    public function rejectFile(Discount $discount, DiscountFile $file)
    {
        $this->authorizeFile($discount, $file);

        if (! $this->isOpenForInPersonReview($discount)) {
            return back()->with('error', 'This discount request is no longer an active booking hold.');
        }

        if ($file->status !== 'pending') {
            return back()->with('error', 'This file has already been reviewed by another staff.');
        }

        $staff = Auth::guard('staff')->user();
        $oldStatus = $file->status;

        $file->update([
            'status'      => 'rejected',
            'reviewed_by' => auth('staff')->id(),
            'reviewed_at' => now(),
        ]);

        AuditLogger::log(
            'discount_file_rejected',
            $file,
            ['status' => $oldStatus],
            ['status' => 'rejected'],
            "Staff {$staff->name} rejected a discount ID file (ID: {$file->id}) for booking #{$discount->booking->id}"
        );

        Realtime::emit(new DiscountChanged);

        return back()->with('success', 'File rejected for reservation #' . ($file->reservation->room_number ?? 'N/A'));
    }

    public function approve(Request $request, Discount $discount)
    {
        if ($discount->status !== 'pending') {
            return back()->with('error', 'This discount request has already been reviewed.');
        }

        if (! $this->isOpenForInPersonReview($discount)) {
            return back()->with('error', 'This booking is no longer awaiting an in-person discount decision.');
        }

        if ($discount->files()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Please review all files before finalizing.');
        }

        if (! $discount->files()->where('status', 'approved')->exists()) {
            return back()->with('error', 'No ID was approved. Reject the request so the booking can continue at the regular rate.');
        }

        $request->validate([
            'original_ids_verified' => ['accepted'],
        ], [
            'original_ids_verified.accepted' => 'Confirm that every approved original ID was checked in person.',
        ]);

        $outcome = DB::transaction(function () use ($discount) {
            $booking = Booking::whereKey($discount->booking_id)->lockForUpdate()->first();
            $locked = Discount::whereKey($discount->id)->lockForUpdate()->first();

            $staff = Auth::guard('staff')->user();

            // Both the request and its booking are re-read under locks. A
            // second reviewer, an expiry or a guest withdrawal must not revive
            // a stale hold or apply the same discount twice.
            if (! $locked || $locked->status !== 'pending') {
                return 'already_reviewed';
            }

            if (! $booking || $booking->status !== Booking::STATUS_PENDING_DISCOUNT) {
                return 'booking_closed';
            }

            if ($locked->files()->where('status', 'pending')->exists()) {
                return 'files_pending';
            }

            if (! $locked->files()->where('status', 'approved')->exists()) {
                return 'none_approved';
            }

            $locked->load(['booking.reservations', 'files']);
            $amount = $this->discountService->calculate($locked);

            $locked->update([
                'amount' => $amount,
                'status' => 'approved',
                'reviewed_by' => auth('staff')->id(),
                'reviewed_at' => now(),
            ]);

            $booking->update([
                'discount' => $amount,
                'payable_amount' => $booking->total_price - $amount,
                'status' => Booking::STATUS_PENDING_PAYMENT,
            ]);

            // Delete all reviewed files
            foreach ($locked->files as $file) {
                Storage::delete($file->file_path);
            }

            AuditLogger::log(
                'discount_request_applied',
                $locked,
                ['status' => 'pending'],
                ['status' => 'approved'],
                "Staff {$staff->name} approved discount #{$locked->id} for booking #{$booking->id} after checking the original IDs in person (₱{$amount})."
            );

            return 'approved';
        });

        if ($outcome !== 'approved') {
            $message = match ($outcome) {
                'files_pending' => 'Please review all files before finalizing.',
                'none_approved' => 'No ID was approved. Reject the request so the booking can continue at the regular rate.',
                'booking_closed' => 'This booking is no longer awaiting an in-person discount decision.',
                default => 'This discount request has already been reviewed by another staff member.',
            };

            return back()->with('error', $message);
        }

        // This is the decision the guest is sitting on their booking page
        // waiting for: the payable amount just changed and payment is now
        // unblocked. Emitted post-commit so subscribers read the new figures.
        $this->announceDecision($discount->refresh());

        return redirect()->route('staff.discounts.index')->with('success', 'Discount approved after the original IDs were verified in person.');
    }

    public function reject(Discount $discount)
    {

        if ($discount->status !== 'pending') {
            return back()->with('error', 'This discount request has already been reviewed.');
        }

        if (! $this->isOpenForInPersonReview($discount)) {
            return back()->with('error', 'This booking is no longer awaiting an in-person discount decision.');
        }

        $rejected = DB::transaction(function () use ($discount) {
            $booking = Booking::whereKey($discount->booking_id)->lockForUpdate()->first();
            $locked = Discount::whereKey($discount->id)->lockForUpdate()->first();
            $staff = Auth::guard('staff')->user();

            if (! $locked || $locked->status !== 'pending'
                || ! $booking || $booking->status !== Booking::STATUS_PENDING_DISCOUNT) {
                return false;
            }

            $locked->load('files');

            $locked->update([
                'amount' => 0, // make sure discount resets
                'status' => 'rejected',
                'reviewed_by' => auth('staff')->id(),
                'reviewed_at' => now(),
            ]);

            // wants_discount is cleared alongside the amount. There is no
            // discount to prove at the desk any more, so this booking pays the
            // ordinary rate online like any other — leaving the flag set would
            // have PaymentController turn the guest away with an instruction
            // to bring IDs that have just been rejected.
            $booking->update([
                'discount' => 0,
                'payable_amount' => $booking->total_price,
                'wants_discount' => false,
                'status' => Booking::STATUS_PENDING_PAYMENT,
            ]);

            // Delete all reviewed files
            foreach ($locked->files as $file) {
                Storage::delete($file->file_path);
            }

            AuditLogger::log(
                'discount_request_rejected',
                $locked,
                ['status' => 'pending'],
                ['status' => 'rejected'],
                "Staff {$staff->name} rejected discount #{$locked->id} for booking #{$booking->id}."
            );

            return true;
        });

        if (! $rejected) {
            return back()->with('error', 'This discount request was already handled or its booking is no longer on hold.');
        }

        $this->announceDecision($discount->refresh());

        return redirect()->route('staff.discounts.index')->with('success', 'Discount request rejected.');
    }

    /**
     * Push an approve/reject outcome to everyone watching: the staff queue, the
     * booking consoles, and the guest's own booking page.
     */
    private function announceDecision(Discount $discount): void
    {
        Realtime::emit(new DiscountChanged);
        Realtime::emit(new BookingChanged);

        $booking = $discount->booking()->first();
        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking));
        }

        // The broadcast above only reaches a browser that happens to be open on
        // the booking page. Everyone else needs telling, because this decision
        // is what put the booking on the payment clock: either it goes to the
        // desk with an approved discount, or it can be paid online at the full
        // rate — and in both cases the window is now running against them.
        \App\Support\GuestNotice::discountDecided($booking);
    }

    public function previewFile(DiscountFile $file)
    {
        return response()->file(Storage::path($file->file_path));
    }

    protected function authorizeFile(Discount $discount, DiscountFile $file)
    {
        if ($file->discount_id !== $discount->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    /** Only a live pending-discount hold may be decided at the counter. */
    private function isOpenForInPersonReview(Discount $discount): bool
    {
        return $discount->status === 'pending'
            && $discount->booking()
                ->where('status', Booking::STATUS_PENDING_DISCOUNT)
                ->exists();
    }

}
