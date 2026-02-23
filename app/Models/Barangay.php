<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $table = 'barangays';
    public $timestamps = false;
    protected $primaryKey = 'code';
    protected $keyType = 'string';
}
