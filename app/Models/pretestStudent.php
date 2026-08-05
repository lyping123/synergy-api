<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pretestStudent extends Model
{
    protected $fillable = [
        'name',
        'ic_number',
        'phone_number',
        'course',
        'status'
    ];
}
