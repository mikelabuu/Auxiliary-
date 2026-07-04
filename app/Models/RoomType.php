<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'base_price',
        'capacity',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'capacity'   => 'integer',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_type', 'slug');
    }
}
