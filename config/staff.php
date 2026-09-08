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
    // Front desk and cashier are deliberately absent. Both are operational
    // workstations that need to move through a live guest/payment queue; their
    // dedicated accounts still require a password, but not an emailed code.
    // Admin and master-admin accounts retain the second factor because they can
    // change system configuration, staff access and oversight records.
    //
    // Comma-separated in .env, e.g. STAFF_OTP_ROLES=admin,master_admin.
    //
    // Values must be spelled exactly as they appear in Staff::ROLES. A typo
    // does not error — it just never matches, and that role logs in WITHOUT a
    // code. This list fails open, so check it after editing.
    'otp_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('STAFF_OTP_ROLES', 'admin,master_admin'))
    ))),

    // Operational alerts sent through App\Mail\StaffBookingAlertMail.
    'alerts' => [
        'enabled' => env('STAFF_ALERTS_ENABLED', true),

        // Comma-separated override. Leave unset and the alerts go to every
        // active, unsuspended staff account in the roles below — which is what
        // a small front desk wants. Set it to route everything to one shared
        // inbox instead.
        'to' => env('STAFF_ALERT_RECIPIENTS'),

        // A proof goes to the cashier inbox/account, not the broad desk list.
        // The legacy general override remains a fallback so existing
        // deployments keep delivering while they adopt the specific key.
        'cashier_to' => env('STAFF_CASHIER_RECIPIENTS', env('STAFF_ALERT_RECIPIENTS')),
        'admin_to' => env('STAFF_ADMIN_RECIPIENTS'),

        // General desk work when no explicit recipient list is configured.
        'roles' => ['frontdesk', 'admin', 'master_admin'],

        // Financial decisions and post-decision oversight stay separate.
        'cashier_roles' => ['cashier'],
        'admin_roles' => ['admin', 'master_admin'],

        // Hard cap. Without it, a growing staff table quietly turns one
        // booking into dozens of SMTP round-trips on an inline mailer.
        'max_recipients' => 5,
    ],

    // NOTE: 'checkout_reminder' moved to config/hostel.php. It is a hostel
    // operating rule defined against check-out time, not a staff-account
    // setting, and holding it here meant the reminder and the deadline it is
    // measured from lived in two different files.

];
