<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountFile extends Model
{
    protected $fillable = [
        'discount_id',
        'reservation_id',
        'file_path',
        'uploaded_at',
        'status',        
        'reviewed_by',   
        'reviewed_at',   
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',  
    ];

    //  Relationships
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

        public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
