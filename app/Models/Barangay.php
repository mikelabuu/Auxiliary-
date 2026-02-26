<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $table = 'barangays';

    protected $fillable = [
        'psgcCode',
        'psgcOldCode',
        'brgyDesc',
        'regCode',
        'provCode',
        'citymunCode',
        'brgyCode'
    ];
}
