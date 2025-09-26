<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\staff_attendance;

class attendanceController extends Controller
{
    public function countattendance($date){
        
        $count_staff_attendance = staff_attendance::where('date_checkin', $date)
        ->distinct('staff_id')
        ->count('staff_id');
        
        return response()->json([
            "status"=>200,
            "message"=>"fetch attendance",
            "data"=>$count_staff_attendance
        ]);
    }

    public function fetchattendance($date){
        $staff_attendance = staff_attendance::where('date_checkin', $date)
            ->orderBy('id', 'desc')
            ->get();
        $json_staff_attendance=$staff_attendance->map(function($attendance){
            $user=User::where('staff_id',$attendance->staff_id)->first();
            return [
                'id'=>$attendance->id,
                'staff_id'=>$attendance->staff_id,
                'time_checkin'=>$attendance->time_checkin,
                'time_section'=>$attendance->time_section,
                'date_checkin'=>$attendance->date_checkin,
                'staff_name'=>$user ? $user->u_name : null,
            ];
        });
        return response()->json([
            "status"=>200,
            "message"=>"fetch attendance",
            "data"=>$json_staff_attendance
        ]);
    }
    public function last_checkin($staff_id,$date)
    {
        $attendance=staff_attendance::where('staff_id',$staff_id)->where('date_checkin',$date)->orderBy('id','desc')->first();
        return response()->json([
            "status"=>200,
            "message"=>"fetch last checkin",
            "data"=>$attendance
        ]);
    }
    public function addattendance(Request $request){
        $staff_attendance=new staff_attendance();
        $staff_attendance->staff_id=$request->input('staff_id');
        $staff_attendance->time_checkin=$request->input('time_checkin');
        $staff_attendance->time_section=$request->input('time_section');
        $staff_attendance->date_checkin=$request->input('date_checkin');
        $staff_attendance->save();
        return response()->json([
            "status"=>200,
            "message"=>"add attendance"
        ]);
    }

    public function updateattendance(Request $request,$id){
        
        $staff_attendance=staff_attendance::find($id);
        if($staff_attendance){
            $staff_attendance->staff_id=$request->input('staff_id');
            $staff_attendance->time_checkin=$request->input('time_checkin');
            $staff_attendance->time_section=$request->input('time_section');
            $staff_attendance->date_checkin=$request->input('date_checkin');
            $staff_attendance->save();
            return response()->json([
                "status"=>200,
                "message"=>"update attendance success",
            ]);
        }
        return response()->json([
            "status"=>401,
            "message"=>"attendance not found",
        ]);
        
    }

    public function deleteattendance($id){
        $deleted=staff_attendance::find($id)->delete();
        if($deleted){
            return response()->json([
                "status"=>200,
                "message"=>"delete attendance",
            ]);

        }
        return response()->json([
            "status"=>401,
            "message"=>"delete attendance",
        ]);
    }
}
