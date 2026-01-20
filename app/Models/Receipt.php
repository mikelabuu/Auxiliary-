<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'receipt_number',
        'file_path',
        'sha256_hash',
        'generated_by',
    ];

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }
}
