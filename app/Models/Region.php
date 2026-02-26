<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = [
        'psgcCode',
        'psgcOldCode',
        'regDesc',
        'regCode'
    ];

    public function provinces()
    {
        return $this->hasMany(Province::class, 'regCode', 'regCode');
    }
}
