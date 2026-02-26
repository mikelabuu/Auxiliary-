<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'psgcCode',
        'psgcOldCode',
        'citymunDesc',
        'regCode',
        'provCode',
        'citymunCode'
    ];

    public function barangays()
    {
        return $this->hasMany(Barangay::class, 'citymunCode', 'citymunCode');
    }
}
