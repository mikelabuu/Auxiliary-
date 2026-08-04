<?php

// Feature toggle for the staff/admin login flow.
return [

    // When false, staff log in directly after a correct email/password with
    // no OTP step. Set STAFF_OTP_ENABLED=true in .env to turn it back on —
    // all the OTP routes/views/model/notification are untouched and still work.
    'otp_enabled' => env('STAFF_OTP_ENABLED', false),

    // Desk alerts (App\Mail\StaffBookingAlertMail): a new booking, or a guest
    // uploading a proof of payment.
    'alerts' => [
        'enabled' => env('STAFF_ALERTS_ENABLED', true),

        // Comma-separated override. Leave unset and the alerts go to every
        // active, unsuspended staff account in the roles below — which is what
        // a small front desk wants. Set it to route everything to one shared
        // inbox instead.
        'to' => env('STAFF_ALERT_RECIPIENTS'),

        // Who is on the hook when no explicit recipient list is configured.
        'roles' => ['frontdesk', 'admin', 'master_admin'],

        // Hard cap. Without it, a growing staff table quietly turns one
        // booking into dozens of SMTP round-trips on an inline mailer.
        'max_recipients' => 5,
    ],

    // NOTE: 'checkout_reminder' moved to config/hostel.php. It is a hostel
    // operating rule defined against check-out time, not a staff-account
    // setting, and holding it here meant the reminder and the deadline it is
    // measured from lived in two different files.

];
