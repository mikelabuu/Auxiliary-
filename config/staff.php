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

    // The desk's heads-up that a stay is leaving today
    // (App\Console\Commands\RemindCheckoutsDue).
    //
    // Everything else about check-out is retrospective: the dashboard's Needs
    // Attention panel and the `overdue` KPI only light up once the date has
    // already passed, and bookings:autocheckout closes the stay after the
    // fact. This is the only part of the flow that speaks up while the guest
    // is still standing in the room.
    'checkout_reminder' => [
        'enabled' => env('CHECKOUT_REMINDER_ENABLED', true),

        // Check-out is 2 PM Manila (the time bookings:autocheckout enforces).
        // Noon leaves two hours to chase a guest, brief housekeeping, or take
        // an extension — while all three are still possible.
        'at' => env('CHECKOUT_REMINDER_AT', '12:00'),
    ],

];
