<?php

namespace App\Http\Controllers\Staff;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\DiscountChanged;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountFile;
use App\Models\Balance;
use App\Services\DiscountService;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditLogger;

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
            'booking.reservations.discountFiles.reviewer' // eager load per-reservation files + reviewer
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
        Realtime::emit(new DiscountChanged());

        return back()->with('success', 'File approved for reservation #' . ($file->reservation->room_number ?? 'N/A'));
    }

    public function rejectFile(Discount $discount, DiscountFile $file)
    {
        $this->authorizeFile($discount, $file);

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

        Realtime::emit(new DiscountChanged());

        return back()->with('success', 'File rejected for reservation #' . ($file->reservation->room_number ?? 'N/A'));
    }

    public function approve(Discount $discount)
    {   
        if ($discount->status !== 'pending') {
            return back()->with('error', 'This discount request has already been reviewed.');
        }

        if ($discount->files()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Please review all files before finalizing.');
        }

        DB::transaction(function () use ($discount) {

            $staff = Auth::guard('staff')->user();
            $discount->lockForUpdate();

            // Recheck inside transaction (safety)
            if ($discount->status !== 'pending') {
                throw new \Exception('Concurrent update detected.');
            }

            $amount = $this->discountService->calculate($discount);

            $discount->update([
                'amount' => $amount,
                'status' => 'approved',
                'reviewed_by' => auth('staff')->id(),
                'reviewed_at' => now(),
            ]);

            $booking = $discount->booking;
            $booking->update([
                'discount' => $amount,
                'payable_amount' => $booking->total_price - $amount,
                'status' => 'pending_payment',
            ]);

            // Delete all reviewed files
            foreach ($discount->files as $file) {
                Storage::delete($file->file_path);
            }

            AuditLogger::log(
                'discount_request_applied',
                $discount,
                ['status' => 'pending'],
                ['status' => 'approved'],
                "Staff {$staff->name} Approved a discount #{$discount->id} for booking #{$discount->booking->id} (₱{$amount})."
            );
        });

        // This is the decision the guest is sitting on their booking page
        // waiting for: the payable amount just changed and payment is now
        // unblocked. Emitted post-commit so subscribers read the new figures.
        $this->announceDecision($discount);

        return redirect()->route('staff.discounts.index')->with('success', 'Discount approved successfully.');
    }


    public function reject(Discount $discount)
    {

        if ($discount->status !== 'pending') {
            return back()->with('error', 'This discount request has already been reviewed.');
        }

        DB::transaction(function () use ($discount) {
            $staff = Auth::guard('staff')->user();
            $discount->lockForUpdate();

            if ($discount->status !== 'pending') {
                throw new \Exception('Concurrent update detected.');
            }

            $discount->update([
                'amount' => 0, // make sure discount resets
                'status' => 'rejected',
                'reviewed_by' => auth('staff')->id(),
                'reviewed_at' => now(),
            ]);

            $booking = $discount->booking;
            $booking->update([
                'discount' => 0,
                'payable_amount' => $booking->total_price,
                'status' => 'pending_payment',
            ]);

            // Delete all reviewed files
            foreach ($discount->files as $file) {
                Storage::delete($file->file_path);
            }

            AuditLogger::log(
                'discount_request_rejected',
                $discount,
                ['status' => 'pending'],
                ['status' => 'approved'],
                "Staff {$staff->name} Rejected a discount #{$discount->id} for booking #{$discount->booking->id}."
            );
        });

        $this->announceDecision($discount);

        return redirect()->route('staff.discounts.index')->with('success', 'Discount request rejected.');
    }

    /**
     * Push an approve/reject outcome to everyone watching: the staff queue, the
     * booking consoles, and the guest's own booking page.
     */
    private function announceDecision(Discount $discount): void
    {
        Realtime::emit(new DiscountChanged());
        Realtime::emit(new BookingChanged());

        $booking = $discount->booking()->first();
        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking));
        }
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

    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $staff = Auth::guard('staff')->user();

        if (! $staff || ! Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Incorrect password'], 422);
        }

        return response()->json(['success' => true]);
    }
}
