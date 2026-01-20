<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_address',
        'guest_phone',
        'check_in',
        'check_out',
        'discount',
        'num_seniors',
        'total_price',
        'payable_amount',   // 
        'status',
        'wants_discount',
        'room_numbers',       // Stored as CSV
        'expected_guests',    // 
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
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

    // Accessor for room_numbers (CSV -> Array)
    public function getRoomNumbersAttribute($value)
    {
        return explode(',', $value);
    }

    // Mutator for room_numbers (Array -> CSV)
    public function setRoomNumbersAttribute($value)
    {
        $this->attributes['room_numbers'] = is_array($value) ? implode(',', $value) : $value;
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
        return $query->whereIn('status', ['paid', 'active', 'checked_in'])
                    ->where('check_out', '>=', now()->startOfDay());
    }
    
    public function balance()
    {
        return $this->hasOne(Balance::class);
    }
}   


