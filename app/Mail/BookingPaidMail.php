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
            'verificationUrl' => $verificationUrl,
        ])->setPaper('a4')->setWarnings(false);

        // Save PDF to storage
        $filename = "receipts/Receipt_{$booking->id}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        // Compute SHA256 hash
        $sha = hash('sha256', Storage::disk('local')->get($filename));

        // Create receipt record
        $receipt = Receipt::create([
            'booking_id' => $booking->id,
            'receipt_number' => $receiptNumber,
            'generated_by' => 'system',
            'file_path' => $filename,
            'sha256_hash' => $sha,
        ]);

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
