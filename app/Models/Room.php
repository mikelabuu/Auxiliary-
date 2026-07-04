<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{   
    protected $table = 'rooms';
    
    protected $fillable = [
        'room_number',
        'room_type',
        'wing',
        'price',
        'status',
        'notes',
        'last_edited_by',
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_room')
                    ->withTimestamps();
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type', 'slug');
    }

    public function lastEditedBy()
    {
        return $this->belongsTo(Staff::class, 'last_edited_by');
    }

    public function activeBookings()
    {
        return $this->bookings()->active();
    }

    public function reservations()
    {
        return $this->hasMany(\App\Models\Reservation::class, 'room_number', 'room_number');
    }

}

