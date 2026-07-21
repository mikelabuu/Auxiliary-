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
     */
    public const ROLES = ['master_admin', 'admin', 'frontdesk', 'housekeeping'];

    /**
     * Roles selectable through the staff-management UI. `master_admin` is
     * provisioned out of band and must never be assignable from a form.
     */
    public const ASSIGNABLE_ROLES = ['admin', 'frontdesk', 'housekeeping'];

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
