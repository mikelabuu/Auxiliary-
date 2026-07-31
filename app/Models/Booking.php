<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{

    use HasFactory;

    /**
     * Statuses that make a booking's rooms unavailable to other guests.
     * Single source of truth — used by both the availability endpoints and
     * the double-booking guard inside BookingController::store().
     */
    /**
     * Every status a booking can actually hold is written somewhere in the
     * app: pending_payment, pending_discount, paid, active, completed,
     * cancelled, expired, no_show. Keep this list to statuses that exist —
     * phantom entries ('confirmed', 'checked_in') hid a dead occupancy count
     * for months because queries against them silently matched nothing.
     */
    public const BLOCKING_STATUSES = [
        'pending_payment',
        'pending_discount',
        'paid',
        'active',
    ];

    /**
     * Every status a booking may legitimately hold. This is the single source
     * of truth now that `status` is a plain VARCHAR rather than a MySQL enum —
     * validate against this list instead of relying on the column type. Keep it
     * in sync with the lifecycle; do not add phantom values ('confirmed',
     * 'checked_in') that no code actually sets.
     */
    public const STATUSES = [
        'pending_discount',
        'pending_payment',
        'paid',
        'active',
        'completed',
        'cancelled',
        'expired',
        'no_show',
    ];

    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_address',
        'guest_phone',
        'check_in',
        'check_out',
        'arrival_time',
        'special_requests',
        'accepted_terms_at',
        'discount',
        'num_seniors',
        'total_price',
        'payable_amount',   // 
        'status',
        'wants_discount',
        'expected_guests',    //
        'payment_mode',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        // The guest-facing hold countdown (public/booking/show) needs the time
        // of day, not just the date, to work out how long is left on the
        // payment window — so this is a datetime, unlike check_in/check_out.
        'pending_payment_since' => 'datetime',
        'accepted_terms_at' => 'datetime',
        'num_seniors' => 'integer',
        'expected_guests' => 'integer',   //
        'total_price' => 'float',
        'discount' => 'float',
        'payable_amount' => 'float',    //
    ];


    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;

        // If status is being set to pending_payment, record the timestamp
        if ($value === 'pending_payment' && empty($this->attributes['pending_payment_since'])) {
            $this->attributes['pending_payment_since'] = now();
        }

        // If status changes away from pending_payment, reset the column
        if ($value !== 'pending_payment') {
            $this->attributes['pending_payment_since'] = null;
        }
    }

    // Derived from reservations (the authoritative per-room source) so it can
    // never drift from a stored copy. Eager-load 'reservations' at read sites
    // that loop over many bookings to avoid N+1.
    public function getRoomNumbersAttribute()
    {
        return $this->reservations->pluck('room_number')->filter()->values()->all();
    }

    // Relationships

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rooms(): BelongsToMany
    {
    return $this->belongsToMany(Room::class, 'booking_room')->withTimestamps();
    }
    public function getRoomTypeAttribute()
    {
        return $this->rooms->pluck('room_type')->unique()->implode(', ');
    }

    public function discount()
    {
        return $this->hasOne(Discount::class);
    }

    public function discountRequest()
    {
        return $this->hasOne(Discount::class);
    }

    public function getDiscountStatusAttribute()
    {
        if (! $this->wants_discount) {
            return null; // user didn’t tick request box
        }

        $discount = $this->discountRequest;

        if (! $discount) {
            return 'not_submitted'; // ticked box but no upload yet
        }

        return $discount->status; // 'pending', 'approved', 'rejected'
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending_payment', 'pending_discount']);
    }

    public function checkouts()
    {
        return $this->hasMany(\App\Models\Checkout::class, 'booking_id');
    }

    public function payments()
    {
        return $this->hasOne(\App\Models\Payment::class, 'booking_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['paid', 'active'])
                    ->where('check_out', '>=', now('Asia/Manila')->startOfDay());
    }
    
    public function balance()
    {
        return $this->hasOne(Balance::class);
    }
}   


