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
    | You can override this value via your .env file:
    | BOOKING_EXPIRY_MINUTES=60
    |
    */

    'expiry_minutes' => env('BOOKING_EXPIRY_MINUTES', 60),

];