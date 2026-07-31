<?php

namespace App\Console\Commands;

use App\Events\StaffNotification;
use App\Support\Realtime;
use Illuminate\Console\Command;

/**
 * Fires a StaffNotification down the live channel without needing a guest to
 * actually book, pay or request anything.
 *
 * Two jobs. It is how you demonstrate the alert system from a terminal with
 * the console open on a projector; and it is how you tell, in ten seconds,
 * whether a broken alert is the broadcast side or the browser side — if this
 * pops a card, the server, Reverb and the subscription are all fine and the
 * problem is in whatever was supposed to emit.
 */
class SendDemoAlert extends Command
{
    protected $signature = 'alert:demo
                            {type=booking : booking, payment, discount or maintenance}';

    protected $description = 'Push a sample alert to every open staff console (demo / smoke test).';

    /** @var array<string, array{title: string, text: string, level: string, route: string}> */
    private const SAMPLES = [
        'booking' => [
            'title' => 'New booking',
            'text' => '#0000 · Demo Guest (Aug 01 – Aug 03)',
            'level' => 'success',
            'route' => 'staff.bookings.index',
        ],
        'payment' => [
            'title' => 'Proof of payment',
            'text' => 'Booking #0000 · Demo Guest awaits verification',
            'level' => 'warning',
            'route' => 'staff.paymentverification.index',
        ],
        'discount' => [
            'title' => 'Discount request',
            'text' => 'Booking #0000 · Demo Guest awaits review',
            'level' => 'info',
            'route' => 'staff.discounts.index',
        ],
        'maintenance' => [
            'title' => 'Room out of service',
            'text' => 'Room 000 moved to maintenance',
            'level' => 'error',
            'route' => 'staff.rooms',
        ],
    ];

    public function handle(): int
    {
        $type = strtolower((string) $this->argument('type'));

        if (! isset(self::SAMPLES[$type])) {
            $this->error("Unknown type '{$type}'. Use one of: " . implode(', ', array_keys(self::SAMPLES)) . '.');

            return self::FAILURE;
        }

        $sample = self::SAMPLES[$type];

        // A fresh id every run, so repeated demos are not de-duplicated by the
        // client (which drops ids it has already shown, by design).
        Realtime::emit(new StaffNotification(
            id: 'demo:' . $type . ':' . now()->timestamp,
            type: $type,
            title: $sample['title'],
            text: $sample['text'],
            // Relative on purpose. This command runs from a terminal, where
            // route() would fall back to APP_URL — and APP_URL is frequently
            // not where the app is actually being served in development, so an
            // absolute link here 404s on somebody else's vhost.
            url: route($sample['route'], [], absolute: false),
            level: $sample['level'],
        ));

        $this->info("Sent a '{$type}' alert to staff.alerts.");
        $this->line('Nothing on screen? Check that `php artisan reverb:start` is running and a staff console is open.');

        return self::SUCCESS;
    }
}
