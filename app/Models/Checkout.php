<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory;

    /**
     * How a stay ended. 'auto' is the check-out-time sweep, 'manual' the front desk on
     * the day the guest was due out, 'emergency' a stay cut short before its
     * check-out date — the only one that carries a `reason`.
     */
    public const METHODS = ['auto', 'manual', 'emergency'];

    protected $fillable = [
        'booking_id',
        'checked_out_at',
        'method',
        'reason',
        'processed_by',
    ];

    /**
     * A checkout belongs to a booking.
     */
    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'booking_id');
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'processed_by');
    }
}
