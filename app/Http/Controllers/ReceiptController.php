<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    /**
     * Confirm that a receipt PDF is the one this system issued.
     *
     * The check is a hash comparison, not a lookup: the stored SHA-256 is taken
     * at the moment the PDF is generated, so an altered copy — a changed total,
     * a different name — produces a different digest and fails here even though
     * the receipt number is real.
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
        if (! $request->hasValidSignature() && ! auth('staff')->check()) {
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
