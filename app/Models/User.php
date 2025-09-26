<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use HasFactory, Notifiable;
    protected $table ='user';

    public function staff_attendance()
    {
        return $this->hasMany(staff_attendance::class, 'staff_id', 'staff_id');
    }

    
}
