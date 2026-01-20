<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function verify($number)
    {
        $receipt = Receipt::where('receipt_number', $number)
                    ->with('booking')
                    ->first();

        if (!$receipt) {
            return view('receipts.verify', [
                'valid' => false,
                'reason' => 'Receipt not found.',
                'receipt' => null,
            ]);
        }

        if (!Storage::disk('local')->exists($receipt->file_path)) {
            return view('receipts.verify', [
                'valid' => false,
                'reason' => 'Receipt file missing on server.',
                'receipt' => $receipt,
            ]);
        }

        $file = Storage::disk('local')->get($receipt->file_path);
        $sha = hash('sha256', $file);
        $valid = hash_equals($receipt->sha256_hash, $sha);

        return view('receipts.verify', [
            'valid' => $valid,
            'reason' => $valid ? 'Verified OK' : 'Hash mismatch — file may have been altered.',
            'receipt' => $receipt,
        ]);
    }
}
