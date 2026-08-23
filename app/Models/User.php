<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        // Signup builds this from the name fields it collects. Missing from
        // this list, it was dropped on every registration without a word.
        'full_name',
        'email',
        'password',
        'phone',
        // Reused as the checkout prefill, so a returning guest is not made to
        // rebuild the same four-level address chain on every booking.
        'region_code',
        'province_code',
        'city_code',
        'barangay_code',
        'last_login_at',
        'is_suspended',
        'last_cancelled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // MySQL hands tinyint(1) back as an int, so `$user->is_suspended` was
        // 0/1 rather than false/true. Truthy checks were fine; anything that
        // compared strictly, or serialised it to JSON for the console, was not.
        'is_suspended' => 'boolean',
    ];
    
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

}
