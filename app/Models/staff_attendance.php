<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff_attendance extends Model
{
    protected $table ='staff_attendance';
    protected $fillable = ['staff_id','time_checkin','time_section','date_checkin'];
    public $timestamps = false;

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'staff_id');
    }

}
