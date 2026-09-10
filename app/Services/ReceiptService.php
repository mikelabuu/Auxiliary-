<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\RescheduleRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    /** Reuse the issued PDF unless an approved reschedule changes the stay. */
    public function issue(Booking $booking, Payment $payment): Receipt
    {
        return DB::transaction(function () use ($booking, $payment) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->firstOrFail();

            if ($payment->booking_id !== $booking->id || $payment->status !== 'success') {
                throw new \RuntimeException('A receipt requires a successful payment for this booking.');
            }

            $existing = Receipt::where('booking_id', $booking->id)->first();
            $reschedule = RescheduleRequest::where('booking_id', $booking->id)
                ->approved()
                ->whereDate('requested_check_in', $booking->check_in->toDateString())
                ->whereDate('requested_check_out', $booking->check_out->toDateString())
                ->latest('id')->first();

            // A separate file per approved move preserves the previous copy and
            // lets old receipts repair themselves on download, without a backfill.
            $path = $reschedule
                ? "receipts/Receipt_{$booking->id}_reschedule_{$reschedule->id}.pdf"
                : "receipts/Receipt_{$booking->id}.pdf";

            if ($existing && (! $reschedule || $existing->file_path === $path)) {
                return $existing;
            }

            $booking->load('reservations');
            $number = $existing?->receipt_number ?? 'R-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
            $bytes = Pdf::loadView('pdf.receipt', [
                'booking' => $booking,
                'payment' => $payment,
                'receipt_number' => $number,
                'reschedule' => $reschedule,
            ])->setPaper('a4')->setWarnings(false)->output();

            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new \RuntimeException('The receipt could not be saved.');
            }

            if ($existing) {
                $existing->update([
                    'file_path' => $path,
                    'sha256_hash' => hash('sha256', $bytes),
                ]);

                return $existing;
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
