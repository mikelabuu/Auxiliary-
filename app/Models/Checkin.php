<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkin extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'checked_in_at',
        'processed_by',
    ];

    /**
     * A check-in belongs to a booking.
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
