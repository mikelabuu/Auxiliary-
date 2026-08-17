<?php

// Feature toggle for the staff/admin login flow.
return [

    // The whole-feature switch. When false, every staff account logs in
    // directly after a correct email/password and `otp_roles` below is not
    // consulted at all. Set STAFF_OTP_ENABLED=true in .env to turn the step
    // back on — all the OTP routes/views/model/notification are untouched and
    // still work either way.
    'otp_enabled' => env('STAFF_OTP_ENABLED', false),

    // Which roles the second factor actually applies to, once it is enabled
    // above. Anyone whose role is not listed signs in on password alone.
    //
    // Front desk is deliberately absent. Theirs is a counter machine signed in
    // and out of all day by whoever is on shift, and a code mailed to an
    // address the whole desk shares is a delay in front of a guest rather than
    // a factor only one person holds. master_admin is the account that can
    // create, suspend and delete other staff, so it is the one that keeps it.
    //
    // Comma-separated in .env, e.g. STAFF_OTP_ROLES=master_admin,admin.
    //
    // Values must be spelled exactly as they appear in Staff::ROLES. A typo
    // does not error — it just never matches, and that role logs in WITHOUT a
    // code. This list fails open, so check it after editing.
    'otp_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('STAFF_OTP_ROLES', 'master_admin'))
    ))),

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
