<?php

/**
 * Render deterministic staff-alert previews without reading guest records or
 * sending mail.
 *
 * Usage:
 *   php scripts/preview-email.php proof
 *   php scripts/preview-email.php verified
 *   php scripts/preview-email.php new
 *   php scripts/preview-email.php reschedule
 *   php scripts/preview-email.php all
 *
 * Files are written beneath storage/app/private/email-previews/.
 */

use App\Mail\StaffBookingAlertMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RescheduleRequest;
use App\Models\Reservation;
use App\Models\Staff;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'app.name' => 'Farmers Hostel',
    'app.url' => 'https://booking.example.test',
]);

$requested = strtolower($argv[1] ?? 'proof');
$allowed = ['proof', 'verified', 'new', 'reschedule', 'all'];

if (! in_array($requested, $allowed, true)) {
    fwrite(STDERR, "Unknown preview '{$requested}'. Use proof, verified, new, reschedule, or all.\n");
    exit(1);
}

$variants = $requested === 'all' ? ['proof', 'verified', 'new', 'reschedule'] : [$requested];

foreach ($variants as $variant) {
    $booking = new Booking;
    $booking->forceFill([
        'user_id' => 501,
        'guest_name' => 'Maria Santos',
        'guest_phone' => '0917 123 4567',
        'guest_phone_alt' => '0998 765 4321',
        'referred_by' => 'Dr. Andres Reyes',
        'referred_by_phone' => '0918 456 7788',
        'referred_by_purpose' => 'Rice research symposium',
        'check_in' => now()->addDays(12)->toDateString(),
        'check_out' => now()->addDays(15)->toDateString(),
        'arrival_time' => '11:30',
        'expected_guests' => 3,
        'total_price' => 4280,
        'payable_amount' => 4280,
        'status' => in_array($variant, ['verified', 'reschedule'], true)
            ? Booking::STATUS_PAID
            : Booking::STATUS_PENDING_PAYMENT,
    ]);
    $booking->id = 1842;
    $booking->exists = true;

    $roomA = new Reservation;
    $roomA->room_number = 'F-204';
    $roomB = new Reservation;
    $roomB->room_number = 'F-205';
    $booking->setRelation('reservations', collect([$roomA, $roomB]));

    $mailable = match ($variant) {
        'proof' => (function () use ($booking) {
            $payment = new Payment;
            $payment->forceFill([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'amount' => $booking->payable_amount,
                'status' => Payment::STATUS_AWAITING_VERIFICATION,
                'payment_type' => 'manual',
                'reference_no' => 'PAY-2026-001842',
                'gateway' => 'gcash',
                'proof_method' => 'gcash',
                'proof_reference' => 'GCASH-9081-7726-4519',
                'proof_submitted_at' => now(),
            ]);
            $payment->id = 9081;
            $payment->exists = true;

            return StaffBookingAlertMail::proofSubmitted($booking, $payment);
        })(),
        'verified' => (function () use ($booking) {
            $cashier = new Staff;
            $cashier->forceFill([
                'name' => 'Ana Dela Cruz',
                'email' => 'cashier@example.test',
                'role' => 'cashier',
            ]);
            $cashier->id = 17;
            $cashier->exists = true;

            $payment = new Payment;
            $payment->forceFill([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'amount' => $booking->payable_amount,
                'status' => 'success',
                'payment_type' => 'manual',
                'reference_no' => 'PAY-2026-001842',
                'gateway' => 'gcash',
                'proof_method' => 'gcash',
                'proof_reference' => 'GCASH-9081-7726-4519',
                'proof_submitted_at' => now()->subMinutes(8),
                'verified_by' => $cashier->id,
                'verified_at' => now(),
                'paid_at' => now(),
            ]);
            $payment->id = 9081;
            $payment->exists = true;
            $payment->setRelation('verifier', $cashier);

            return StaffBookingAlertMail::paymentVerified($booking, $payment);
        })(),
        'reschedule' => (function () use ($booking) {
            $request = new RescheduleRequest;
            $request->forceFill([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'status' => RescheduleRequest::STATUS_PENDING,
                'original_check_in' => $booking->check_in,
                'original_check_out' => $booking->check_out,
                'requested_check_in' => now()->addDays(20)->toDateString(),
                'requested_check_out' => now()->addDays(23)->toDateString(),
                'reason' => 'Our university field work moved to the following week.',
                'submitted_at' => now(),
            ]);
            $request->id = 331;
            $request->exists = true;

            return StaffBookingAlertMail::rescheduleRequested($booking, $request);
        })(),
        default => StaffBookingAlertMail::newBooking($booking),
    };

    $path = "email-previews/staff-{$variant}.html";
    $html = $mailable->render();
    $text = app(Markdown::class)->renderText(
        'emails.booking.staff-alert',
        $mailable->buildViewData()
    );

    Storage::disk('local')->put($path, $html);
    Storage::disk('local')->put("email-previews/staff-{$variant}.txt", (string) $text);

    echo Storage::disk('local')->path($path) . PHP_EOL;
}
