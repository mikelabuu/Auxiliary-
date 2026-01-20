<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'otp_code',
        'otp_expires_at',
        'used_at',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
