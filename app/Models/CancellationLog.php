<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationLog extends Model
{
    use HasFactory;

    protected $table = 'cancellation_logs';

    protected $fillable = [
        'booking_id',
        'cancelled_at',
        'cancelled_by', //either by staff or user
        'reason',
    ];

    protected $dates = [
        'cancelled_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the booking associated with this cancellation.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
