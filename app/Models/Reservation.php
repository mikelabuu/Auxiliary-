<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{

    protected $fillable = [
        'booking_id',
        'room_id',
        'room_number',
        'room_type',
        'capacity',
        'price',
        'check_in',
        'check_out',
        'num_seniors',
        'num_guests',
        'meal',
    ];

    protected $casts = [
        'meal' => 'array',
        'status' => 'string',
    ];

    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_OCCUPIED    = 'occupied';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_CLEANING    = 'cleaning';

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function discountFiles()
    {
        return $this->hasMany(DiscountFile::class);
    }
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_number', 'room_number');
    }
}


