<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Checkin;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Support\RoomCatalog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stands up the four booking states a walkthrough needs and that the live
 * database happens to have none of: one awaiting payment, one with a receipt
 * already in the verification queue, one paid and arriving today, one already
 * in house and leaving today.
 *
 * Every row it creates is tagged DEMO in the guest name, and --reset removes
 * exactly those rows and nothing else.
 */
class SeedDemoBookings extends Command
{
    protected $signature = 'demo:bookings
                            {--reset : Delete every DEMO booking and its payments, reservations and proofs}
                            {--user= : Use an existing guest account instead of the dedicated demo one}';

    protected $description = 'Seed (or remove) labelled demo bookings for a presentation walkthrough';

    /** Guest names are prefixed with this so cleanup can find them again. */
    private const TAG = '[DEMO]';

    /**
     * A throwaway guest so the walkthrough never has to sign in as, or reset
     * the password of, a real account. Removed again by --reset.
     */
    private const DEMO_EMAIL = 'demo.guest@farmershostel.test';

    private const DEMO_PASSWORD = 'demo-guest-2026';

    public function handle(): int
    {
        if ($this->option('reset')) {
            return $this->reset();
        }

        $user = $this->resolveUser();

        if (! $user) {
            return self::FAILURE;
        }

        $staff = Staff::whereIn('role', ['frontdesk', 'master_admin'])->first() ?? Staff::first();

        if (! $staff) {
            $this->error('No staff account exists to attribute the check-in to.');

            return self::FAILURE;
        }

        // Four free rooms that nothing else is sitting on for the demo window.
        $rooms = $this->availableRooms(4);

        if ($rooms->count() < 4) {
            $this->error("Only {$rooms->count()} available room(s) found; 4 are needed.");
            $this->line('Free some rooms in Room Management (status → available) and run again.');

            return self::FAILURE;
        }

        $today = Carbon::today(config('hostel.timezone'));
        $catalog = RoomCatalog::all();

        $this->line('');
        $this->info('Seeding demo bookings…');

        $created = [];

        // 1 — Awaiting payment. The live walkthrough starts here.
        $created[] = $this->makeBooking(
            $user, $rooms[0], $catalog,
            'Dela Cruz, Juan, Santos',
            $today->copy()->addDays(2),
            $today->copy()->addDays(4),
            'pending_payment'
        );

        // 2 — Receipt already uploaded, sitting in the verification queue.
        $awaiting = $this->makeBooking(
            $user, $rooms[1], $catalog,
            'Reyes, Maria, Lopez',
            $today->copy()->addDays(3),
            $today->copy()->addDays(5),
            'pending_payment'
        );
        $this->attachPendingProof($awaiting);
        $created[] = $awaiting;

        // 3 — Paid and arriving today, so front desk can check it in.
        $paid = $this->makeBooking(
            $user, $rooms[2], $catalog,
            'Bautista, Andres, Cruz',
            $today->copy(),
            $today->copy()->addDays(2),
            'paid'
        );
        $this->attachSuccessfulPayment($paid, $staff);
        $created[] = $paid;

        // 4 — Already in house and leaving today, so check-out has a subject.
        $active = $this->makeBooking(
            $user, $rooms[3], $catalog,
            'Mercado, Rosa, Villanueva',
            $today->copy()->subDays(2),
            $today->copy(),
            'active'
        );
        $this->attachSuccessfulPayment($active, $staff);
        Checkin::create([
            'booking_id' => $active->id,
            'checked_in_at' => $today->copy()->subDays(2)->setTime(14, 0),
            'processed_by' => $staff->id,
        ]);
        $active->reservations->each(fn ($r) => $r->room?->update(['status' => 'occupied']));
        $created[] = $active;

        $this->line('');
        $this->table(
            ['ID', 'Guest', 'Status', 'Check in', 'Check out', 'Room', 'Payable'],
            collect($created)->map(fn (Booking $b) => [
                $b->id,
                Str::limit($b->guest_name, 34),
                $b->status . ($b->payments?->isAwaitingVerification() ? ' (proof queued)' : ''),
                $b->check_in->format('M d'),
                $b->check_out->format('M d'),
                $b->reservations->pluck('room_number')->implode(', '),
                '₱' . number_format($b->payable_amount ?? $b->total_price, 2),
            ])->all()
        );

        $this->line('');
        $this->info('Guest sign-in for the walkthrough');
        $this->line('  email:    ' . $user->email);

        if ($user->email === self::DEMO_EMAIL) {
            $this->line('  password: ' . self::DEMO_PASSWORD);
        } else {
            $this->line('  password: (unchanged — this is an existing account)');
        }

        $this->line('');
        $this->line('Remove all of it later with:  php artisan demo:bookings --reset');
        $this->line('');

        return self::SUCCESS;
    }

    private function reset(): int
    {
        $bookings = Booking::where('guest_name', 'like', self::TAG . '%')->get();

        if ($bookings->isEmpty()) {
            $this->info('No demo bookings found — nothing to remove.');

            return self::SUCCESS;
        }

        $this->warn("About to delete {$bookings->count()} demo booking(s): #" . $bookings->pluck('id')->implode(', #'));

        if (! $this->confirm('Delete them?', true)) {
            $this->line('Cancelled — nothing was deleted.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($bookings) {
            foreach ($bookings as $booking) {
                // Free the rooms this booking was holding before it disappears.
                $booking->reservations->each(fn ($r) => $r->room?->update(['status' => 'available']));

                foreach (Payment::where('booking_id', $booking->id)->get() as $payment) {
                    if ($payment->proof_path) {
                        Storage::disk('local')->delete($payment->proof_path);
                    }
                    $payment->delete();
                }

                Checkin::where('booking_id', $booking->id)->delete();
                $booking->rooms()->detach();
                $booking->reservations()->delete();
                $booking->delete();
            }
        });

        // The throwaway guest goes too, but only if nothing real is hanging
        // off it — never delete an account that still owns other bookings.
        $demoGuest = User::where('email', self::DEMO_EMAIL)->first();

        if ($demoGuest && ! Booking::where('user_id', $demoGuest->id)->exists()) {
            $demoGuest->delete();
            $this->line('Demo guest account removed.');
        }

        $this->info('Demo bookings removed and their rooms released.');

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        if ($email = $this->option('user')) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error("No user found with email {$email}.");
            }

            return $user;
        }

        $user = User::firstOrNew(['email' => self::DEMO_EMAIL]);

        // Reset the password every run so the printed credentials are always
        // the ones that work, whatever happened to the account in between.
        $user->username = 'demoguest';
        $user->password = Hash::make(self::DEMO_PASSWORD);
        $user->is_suspended = false;
        $user->save();

        // The booking journey sits behind `verified` middleware, and
        // email_verified_at is not fillable.
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    /**
     * Rooms that are free now and unclaimed for the whole demo window
     * (2 days back to 5 days forward).
     */
    private function availableRooms(int $count)
    {
        $windowStart = Carbon::today(config('hostel.timezone'))->subDays(3);
        $windowEnd = Carbon::today(config('hostel.timezone'))->addDays(6);

        return Room::where('status', 'available')
            ->whereDoesntHave('bookings', function ($q) use ($windowStart, $windowEnd) {
                $q->whereIn('status', Booking::BLOCKING_STATUSES)
                    ->where('check_in', '<', $windowEnd->toDateString())
                    ->where('check_out', '>', $windowStart->toDateString());
            })
            ->orderBy('room_number')
            ->take($count)
            ->get();
    }

    private function makeBooking(User $user, Room $room, array $catalog, string $name, Carbon $in, Carbon $out, string $status): Booking
    {
        $type = $catalog[$room->room_type] ?? null;
        $price = (float) ($type['price'] ?? 1500);
        $beds = (int) ($type['beds'] ?? 2);
        $nights = max(1, $in->diffInDays($out));
        $total = $price * $nights;

        $booking = Booking::create([
            'user_id' => $user->id,
            'guest_name' => self::TAG . ' ' . $name,
            'guest_address' => 'Science City of Muñoz, Nueva Ecija',
            'guest_phone' => '09' . random_int(100000000, 999999999),
            'check_in' => $in->toDateString(),
            'check_out' => $out->toDateString(),
            'discount' => 0,
            'total_price' => $total,
            'payable_amount' => $total,
            'num_seniors' => 0,
            'expected_guests' => min(2, $beds),
            'wants_discount' => false,
            'status' => $status,
            'payment_mode' => 'system',
        ]);

        Reservation::create([
            'booking_id' => $booking->id,
            'room_number' => $room->room_number,
            'room_type' => $room->room_type,
            'capacity' => $beds,
            'price' => $price,
            'num_seniors' => 0,
            'num_guests' => min(2, $beds),
            'meal' => null,
        ]);

        // The pivot is what RoomBoard and the double-booking guard read.
        $booking->rooms()->attach($room->id);

        return $booking->fresh(['reservations.room', 'payments']);
    }

    /** A receipt already uploaded and waiting on a human. */
    private function attachPendingProof(Booking $booking): void
    {
        $reference = (string) random_int(1000000000, 9999999999);
        $path = 'payment_proofs/demo_' . $booking->id . '.png';

        Storage::disk('local')->put(
            $path,
            $this->fakeReceiptPng($booking, $reference)
        );

        Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->payable_amount ?? $booking->total_price,
            'status' => Payment::STATUS_AWAITING_VERIFICATION,
            'payment_type' => 'manual',
            'reference_no' => strtoupper(Str::random(10)),
            'gateway' => 'gcash',
            'proof_path' => $path,
            'proof_method' => 'gcash',
            'proof_reference' => $reference,
            'proof_submitted_at' => now()->subMinutes(random_int(5, 90)),
        ]);

        $booking->load('payments');
    }

    private function attachSuccessfulPayment(Booking $booking, Staff $staff): void
    {
        Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->payable_amount ?? $booking->total_price,
            'status' => 'success',
            'payment_type' => 'manual',
            'reference_no' => strtoupper(Str::random(10)),
            'gateway' => 'gcash',
            'paid_at' => now()->subDay(),
            'proof_method' => 'gcash',
            'proof_reference' => (string) random_int(1000000000, 9999999999),
            'verified_by' => $staff->id,
            'verified_at' => now()->subDay(),
        ]);

        $booking->load('payments');
    }

    /**
     * A stand-in for the screenshot a guest would upload. Drawn rather than
     * shipped as a binary so the repo carries no fake receipt image.
     */
    private function fakeReceiptPng(Booking $booking, string $reference): string
    {
        $w = 520;
        $h = 760;
        $img = imagecreatetruecolor($w, $h);

        $white = imagecolorallocate($img, 255, 255, 255);
        $ink = imagecolorallocate($img, 28, 32, 38);
        $muted = imagecolorallocate($img, 122, 130, 140);
        $brand = imagecolorallocate($img, 0, 118, 255);
        $rule = imagecolorallocate($img, 226, 230, 235);

        imagefilledrectangle($img, 0, 0, $w, $h, $white);
        imagefilledrectangle($img, 0, 0, $w, 96, $brand);

        $center = function (string $text, int $y, int $font, int $color) use ($img, $w) {
            $x = (int) (($w - imagefontwidth($font) * strlen($text)) / 2);
            imagestring($img, $font, $x, $y, $text, $color);
        };

        $center('G C a s h', 38, 5, $white);
        $center('DEMO RECEIPT - NOT A REAL TRANSACTION', 140, 3, $muted);
        $center('Amount Sent', 200, 3, $muted);
        $center('PHP ' . number_format($booking->payable_amount ?? $booking->total_price, 2), 232, 5, $ink);

        imageline($img, 48, 292, $w - 48, 292, $rule);

        $rows = [
            ['Reference No.', $reference],
            ['Sent to', 'CLSU Farmers Hostel'],
            ['Account No.', '0917 000 0000'],
            ['Date', now()->format('M d, Y')],
            ['Time', now()->format('g:i A')],
            ['Booking', '#' . $booking->id],
            ['Guest', Str::limit(str_replace(self::TAG . ' ', '', $booking->guest_name), 26, '')],
        ];

        $y = 330;
        foreach ($rows as [$label, $value]) {
            imagestring($img, 3, 48, $y, $label, $muted);
            imagestring($img, 4, 240, $y - 2, $value, $ink);
            $y += 48;
        }

        imageline($img, 48, $y + 8, $w - 48, $y + 8, $rule);
        $center('Seeded by php artisan demo:bookings', $y + 40, 2, $muted);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }
}
