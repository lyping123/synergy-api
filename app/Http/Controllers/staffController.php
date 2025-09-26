<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class staffController extends Controller
{
    public function findstaff($staff_id)
    {
        $staff=User::where('staff_id',$staff_id)->first();
        if($staff){
            return response()->json([
                "status"=>200,
                "message"=>"fetch staff",
                "data"=>$staff
            ]);
        }else{
            return response()->json([
                "status"=>404,
                "message"=>"staff not found"
            ]);
        }
    }
}
