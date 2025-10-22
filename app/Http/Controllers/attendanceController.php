<?php

namespace App\Http\Controllers;

use App\Models\apply_leave_list;
use App\Models\mail_log;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\staff_attendance;

class attendanceController extends Controller
{
    public $phone_number;
    public function __construct()
    {
        $this->phone_number = env('WHATSAPP_PHONE_NUMBER', '+60129253398');
    }

    public function countattendance($date){
        
        $count_staff_attendance = staff_attendance::where('date_checkin', $date)
        ->d
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

    public function check_staff_attendance($date)
    {
        $staff=User::where("staff_id","!=","")->get();
        $attendance=$staff->map(function($user){
            $check_attendance=staff_attendance::where('staff_id',$user->staff_id)->where('date_checkin',date('Y-m-d'))->first();
            if($check_attendance){
                return [
                    'staff_id'=>$user->staff_id,
                    'staff_name'=>$user->u_name,
                    'status'=>true,
                    'time_checkin'=>$check_attendance->time_checkin,
                    'time_section'=>$check_attendance->time_section,
                ];
            }else{
                return [
                    'staff_id'=>$user->staff_id,
                    'staff_name'=>$user->u_name,
                    'status'=>false,
                    'time_checkin'=>null,
                    'time_section'=>null,
                ];
            }
         });
        return response()->json([
            "status"=>200,
            "message"=>"fetch staff attendance",
            "data"=>$attendance
        ]);
    }
    public function last_checkin($staff_id,$date)
    {
        $attendance=staff_attendance::where('staff_id',$staff_id)->where('date_checkin',$date)->orderBy('id','asc')->first();
        
        return response()->json([
            "status"=>200,
            "message"=>"fetch last checkin",
            "data"=>$attendance
        ]);
    }
    public function addattendance(Request $request)
    {
        $check_staff=User::where('staff_id',$request->input('staff_id'))->first();
        if(!$check_staff){
            return response()->json([
                "status"=>401,
                "message"=>"Your user account is not been register yet",
            ]);
        }
        $check_scan_in = staff_attendance::where('staff_id', $request->input('staff_id'))
            ->where('date_checkin', $request->input('date_checkin'))
            ->orderBy('time_checkin', 'desc')
            ->first();

        if ($check_scan_in) {
            $last_checkin_time = strtotime($check_scan_in->time_checkin);
            $current_checkin_time = strtotime($request->input('time_checkin'));

            if (($current_checkin_time - $last_checkin_time) < 300) { // 300 seconds = 5 minutes
                return response()->json([
                    "status" => 401,
                    "message" => "staff already checked in within 5 minutes, please try again later",
                ]);
            }
        }

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
    public function todayattendance()
    {
        $staff=User::where("staff_id","!=","")->get();
        $attendance=$staff->map(function($user){
            $date_today=date('Y-m-d');
            $check_attendance=staff_attendance::where('staff_id',$user->staff_id)->where('date_checkin',$date_today)->first();
            
            if(!$check_attendance){
                $appy_leave = apply_leave_list::where('u_id', $user->id)
                    ->whereRaw("STR_TO_DATE(a_from, '%d-%m-%Y') <= ?", [$date_today])
                    ->whereRaw("STR_TO_DATE(a_to,   '%d-%m-%Y') >= ?", [$date_today])
                    ->count();
                
                $status=$appy_leave>0 ? 'apply leave today' : 'absent today';
                return [
                    'staff_name'=>$user->u_name,
                    'status'=>$status,
                ];
            }
         });
        return response()->json([
            "status"=>200,
            "message"=>"fetch today attendance",
            "phone_number"=>$this->phone_number,
            "data"=>$attendance
        ]);
    }
    public function reminder_apply_leave()
    {
        // Group all dates per user id for ACTIVE status
        // Note: selectRaw expects only the select list, not the leading SELECT keyword
        $mail_log = mail_log::selectRaw(
                "u_id, GROUP_CONCAT(DISTINCT `date` ORDER BY `date` ASC SEPARATOR ', ') as group_date"
            )
            ->where('s_status', 'ACTIVE')
            ->groupBy('u_id')
            ->get();
        
        $mail_log = $mail_log->map(function($log) {
            $user = User::find($log->u_id);
            return [
                'u_id' => $log->u_id,
                'staff_name' => $user ? $user->u_name : null,
                'staff_email' => $user ? $user->u_email : null,
                'group_date' => $log->group_date,
            ];
        });
        return response()->json([
            "status" => 200,
            "message" => "fetch reminder apply leave grouped dates",
            "phone_number" => $this->phone_number,
            "data" => $mail_log,
        ]);
    }
}
