<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $fillable = [
        'psgcCode',
        'psgcOldCode',
        'provDesc',
        'regCode',
        'provCode'
    ];

    public function cities()
    {
        return $this->hasMany(City::class, 'provCode', 'provCode');
    }
}
