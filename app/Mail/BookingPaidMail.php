<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\Storage;

class BookingPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $payment;

    public function __construct(Booking $booking, Payment $payment)
    {
        $this->booking = $booking;
        $this->payment = $payment;
    }

    public function build()
    {
        $booking = $this->booking;

        // room_numbers is derived from reservations; ensure they're present
        // (this mailable may be queued/serialized without the relation loaded).
        $booking->loadMissing('reservations');

        // Generate receipt number and verification URL
        $receiptNumber = 'R-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
        $verificationUrl = url("/verify-receipt/{$receiptNumber}");

        // Generate QR code as raw PNG
        $qr = Builder::create()
            ->data($verificationUrl)
            ->size(150)
            ->margin(0)
            ->build();

        $qrBase64 = trim(base64_encode($qr->getString()));

        // Generate PDF with clean Blade
        $pdf = Pdf::loadView('pdf.receipt', [
            'booking' => $booking,
            'payment' => $this->payment,
            'qrBase64' => $qrBase64,
            'receipt_number' => $receiptNumber,
            'verificationUrl' => $verificationUrl
        ])->setPaper('a4')->setWarnings(false);

        // Save PDF to storage
        $filename = "receipts/Receipt_{$booking->id}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        // Compute SHA256 hash
        $sha = hash('sha256', Storage::disk('local')->get($filename));

        // One official receipt per booking, re-issuable.
        //
        // The number is a pure function of the booking id and receipt_number is
        // UNIQUE, so a plain create() threw an integrity violation the second
        // time this ran for a booking — a re-sent confirmation, a queued
        // mailable retried after a transient failure, or a second settled
        // payment. The send site catches \Exception and only logs, so the guest
        // silently received no receipt.
        //
        // updateOrCreate keeps the number stable while refreshing the digest to
        // match the PDF actually on disk; a stale hash would fail verification.
        $receipt = Receipt::updateOrCreate(
            ['receipt_number' => $receiptNumber],
            [
                'booking_id'   => $booking->id,
                'generated_by' => 'system',
                'file_path'    => $filename,
                'sha256_hash'  => $sha,
            ]
        );

        // Send email with PDF attached
        return $this->subject('Booking Confirmation & Official Receipt')
                    ->markdown('emails.booking.paid', [
                        'booking' => $booking,
                        'receipt' => $receipt,
                        'payment' => $this->payment,
                    ])
                    ->attachData($pdf->output(), "Receipt_{$booking->id}.pdf", [
                        'mime' => 'application/pdf',
                    ]);
    }
}
