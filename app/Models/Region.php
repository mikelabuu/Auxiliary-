<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regions';
    public $timestamps = false;
    protected $primaryKey = 'code'; // PSGC code is primary key
    protected $keyType = 'string';
}
