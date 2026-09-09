<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    /** Issue one immutable receipt per booking; email and downloads reuse it. */
    public function issue(Booking $booking, Payment $payment): Receipt
    {
        return DB::transaction(function () use ($booking, $payment) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->firstOrFail();

            if ($payment->booking_id !== $booking->id || $payment->status !== 'success') {
                throw new \RuntimeException('A receipt requires a successful payment for this booking.');
            }

            $existing = Receipt::where('booking_id', $booking->id)->first();
            if ($existing) {
                return $existing;
            }

            $booking->load('reservations');
            $number = 'R-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
            $bytes = Pdf::loadView('pdf.receipt', [
                'booking' => $booking,
                'payment' => $payment,
                'receipt_number' => $number,
            ])->setPaper('a4')->setWarnings(false)->output();

            $path = "receipts/Receipt_{$booking->id}.pdf";
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new \RuntimeException('The receipt could not be saved.');
            }

            return Receipt::create([
                'booking_id' => $booking->id,
                'receipt_number' => $number,
                'generated_by' => 'system',
                'file_path' => $path,
                'sha256_hash' => hash('sha256', $bytes),
            ]);
        });
    }
}
