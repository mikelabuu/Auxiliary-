<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function download(\App\Models\Booking $booking)
    {
        $staff = auth('staff')->user();
        $guest = auth('web')->user();
        $allowedStaff = $staff && ! $staff->is_suspended
            && in_array($staff->role, ['master_admin', 'admin', 'cashier', 'frontdesk'], true);
        $owner = $guest && ! $guest->is_suspended && $guest->hasVerifiedEmail()
            && (int) $booking->user_id === (int) $guest->id;
        abort_unless($allowedStaff || $owner, 403);

        $payment = $booking->paymentAttempts()->where('status', 'success')->orderBy('id')->first();
        abort_unless($payment, 404);
        $receipt = app(\App\Services\ReceiptService::class)->issue($booking, $payment);
        abort_unless(Storage::disk('local')->exists($receipt->file_path), 404, 'The receipt file is unavailable. Please contact the front desk.');
        abort_unless(hash_equals($receipt->sha256_hash, hash('sha256', Storage::disk('local')->get($receipt->file_path))), 409, 'This receipt does not match the issued copy. Please contact the front desk.');

        return Storage::disk('local')->download($receipt->file_path, $receipt->receipt_number . '.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Confirm that the receipt is on record and its stored PDF is intact.
     * Scanning a QR alone cannot authenticate changes to a printed copy.
     */
    public function verify(Request $request, string $number)
    {
        // Two ways in. A signed link is how a guest arrives: the QR on their
        // receipt carries the signature, so holding the receipt is the proof of
        // entitlement. A staff session is the other, which keeps receipts issued
        // before signing was introduced verifiable from the console, and lets
        // the desk check a number a guest reads out over the phone.
        //
        // Without one of the two this is refused rather than answered, because
        // receipt numbers run in sequence from the booking id and an open
        // endpoint would be trivially enumerable.
        $staff = auth('staff')->user();
        $allowedStaff = $staff && ! $staff->is_suspended
            && in_array($staff->role, ['master_admin', 'admin', 'cashier', 'frontdesk'], true);
        if (! $request->hasValidSignature() && ! $allowedStaff) {
            abort(403);
        }

        $receipt = Receipt::where('receipt_number', $number)
                    ->with('booking')
                    ->first();

        if (!$receipt) {
            return view('receipts.verify', [
                'valid' => false,
                'reason' => 'No receipt exists with this number.',
                'receipt' => null,
            ]);
        }

        if (!Storage::disk('local')->exists($receipt->file_path)) {
            return view('receipts.verify', [
                'valid' => false,
                'reason' => 'This receipt is on record, but the stored copy is missing, so it cannot be checked here.',
                'receipt' => $receipt,
            ]);
        }

        $file = Storage::disk('local')->get($receipt->file_path);
        $sha = hash('sha256', $file);
        // hash_equals, not ===: comparison time must not depend on how much of
        // the digest matched.
        $valid = hash_equals($receipt->sha256_hash, $sha);

        return view('receipts.verify', [
            'valid' => $valid,
            'reason' => $valid
                ? 'This receipt matches our records.'
                : 'This copy does not match the receipt we issued under this number.',
            'receipt' => $receipt,
        ]);
    }
}
