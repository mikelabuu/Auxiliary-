<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking Expiry (in minutes)
    |--------------------------------------------------------------------------
    |
    | Defines how long a booking can stay in "pending_payment" status before
    | it is automatically marked as "expired".
    |
    | House policy is 24 hours to settle. It was an hour for a long time, which
    | only worked because paying meant a GCash transfer from wherever the guest
    | was sitting; a Senior/PWD booking now has to be settled at the front desk
    | in person, and an hour is not a journey.
    |
    | Read it through App\Support\PaymentWindow rather than from here. At 1440
    | the raw integer is no longer something you can print at a guest — every
    | template that interpolated it used to say "60 minutes" and would now say
    | "1440 minutes".
    |
    | You can override this value via your .env file:
    | BOOKING_EXPIRY_MINUTES=1440
    |
    */

    'expiry_minutes' => env('BOOKING_EXPIRY_MINUTES', 1440),

];