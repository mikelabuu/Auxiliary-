<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Staff extends Authenticatable
{
    use Notifiable;

    /**
     * Every role a staff row may hold. Single source of truth now that `role`
     * is a plain VARCHAR rather than a MySQL enum.
     *
     * `housekeeping` used to be listed here and in ASSIGNABLE_ROLES, but the
     * system never grew a console for it: IssuesStaffOtp::redirectForRole()
     * knows master_admin, admin and frontdesk, and sends anything else back to
     * the login with "your staff account has no valid role assigned" — after
     * the account has already cleared its password and burned an emailed OTP.
     * So the role could be handed out but never signed into, which reads to
     * whoever holds one as a broken login rather than as unfinished work.
     * It is not planned, so it is gone rather than left as a trap.
     *
     * No staff row held it when it was removed. The MySQL enum the column was
     * originally declared as still lists the value and is harmless; dropping
     * it is a schema change nobody needs.
     */
    public const ROLES = ['master_admin', 'admin', 'frontdesk'];

    /**
     * Roles selectable through the staff-management UI. `master_admin` is
     * provisioned out of band and must never be assignable from a form.
     */
    public const ASSIGNABLE_ROLES = ['admin', 'frontdesk'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_suspended',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        
        'password' => 'hashed',
    ];
}
