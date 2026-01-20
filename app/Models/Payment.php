<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'amount',
        'payment_type',
        'status',
        'reference_no',
        'gateway',
        'gateway_response',
        'landbank_transaction_id',
        'webhook_verified',
        'paid_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'webhook_verified' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
