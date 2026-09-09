<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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

        $receipt = app(\App\Services\ReceiptService::class)->issue($booking, $this->payment);

        // Send email with PDF attached
        return $this->subject('Booking Confirmation & Official Receipt')
                    ->markdown('emails.booking.paid', [
                        'booking' => $booking,
                        'receipt' => $receipt,
                        'payment' => $this->payment,
                    ])
                    ->attachFromStorageDisk('local', $receipt->file_path, $receipt->receipt_number . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
