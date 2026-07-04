<?php

// Feature toggle for the staff/admin login flow.
return [

    // When false, staff log in directly after a correct email/password with
    // no OTP step. Set STAFF_OTP_ENABLED=true in .env to turn it back on —
    // all the OTP routes/views/model/notification are untouched and still work.
    'otp_enabled' => env('STAFF_OTP_ENABLED', false),

];
