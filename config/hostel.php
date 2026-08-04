<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operating Timezone
    |--------------------------------------------------------------------------
    |
    | Where the hostel actually is. Distinct from config('app.timezone'), which
    | stays UTC so timestamps are stored unambiguously — this is the wall clock
    | staff read, and the one "today" means on a dashboard.
    |
    | It was previously written as the literal 'Asia/Manila' in 86 places across
    | 43 files. Every one of them was a correct copy, which is exactly why it
    | was worth collecting: a business that only ever has one timezone never
    | discovers that the fact was scattered.
    |
    | Do NOT fix a timezone problem by changing app.timezone instead. That
    | changes how every existing timestamp is written and read, and would
    | silently reinterpret data already in the database.
    |
    */

    'timezone' => env('HOSTEL_TIMEZONE', 'Asia/Manila'),

    /*
    |--------------------------------------------------------------------------
    | Check-out Time
    |--------------------------------------------------------------------------
    |
    | The daily deadline bookings:autocheckout enforces, in the operating
    | timezone above. Anything that has to agree with check-out — the reminder
    | that fires ahead of it, a future grace period, the wording on a receipt —
    | should read it from here rather than restating 14:00.
    |
    */

    'checkout_time' => env('HOSTEL_CHECKOUT_TIME', '14:00'),

    /*
    |--------------------------------------------------------------------------
    | Check-out Reminder
    |--------------------------------------------------------------------------
    |
    | The desk's heads-up that a stay is leaving today, sent by
    | bookings:checkout-reminder.
    |
    | Expressed as hours *before* checkout_time rather than as its own clock
    | time, which is what it actually is — "two hours before check-out", not
    | "noon". Stored as an absolute 12:00 it was a second number that had to be
    | kept in agreement with the first by hand: moving check-out to 4 PM would
    | have left the reminder firing four hours early, correctly, quietly, and
    | wrongly. Read it through App\Support\CheckoutSchedule, never directly.
    |
    */

    'checkout_reminder' => [
        'enabled'    => env('CHECKOUT_REMINDER_ENABLED', true),
        'lead_hours' => env('CHECKOUT_REMINDER_LEAD_HOURS', 2),
    ],

];
