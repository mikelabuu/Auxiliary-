<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoShowLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'previous_status',
        'new_status',
        'reason',
        'marked_at',
        'processed_by',
    ];

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'booking_id');
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'processed_by');
    }

}
