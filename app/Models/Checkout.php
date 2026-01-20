<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'checked_out_at',
        'method',
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
