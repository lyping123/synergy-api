<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class studentController extends Controller
{
    public function remind_tuition_fee()
    {
        $connection = DB::connection('student_registration');
        $tuitionFees = $connection->table('student')
        ->where('s_status', 'ACTIVE')
        ->selectRaw('id,s_name,if(CURDATE()<DATE(t_end),TIMESTAMPDIFF(MONTH,DATE(t_start),CURDATE()),TIMESTAMPDIFF(MONTH,DATE(t_start),DATE(t_end))) as month_difference,month_pay')
        ->get();
        
        $reminders = [];
        $tuitionFees->map(function($fee) use (&$reminders,$connection) {
            $totalfee=$fee->month_pay*$fee->month_difference;
            $f_receipt=$connection->table('f_receipt as f')
            ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
            ->where('f.s_id', $fee->id)
            ->where('f.r_status','ACTIVE')
            ->whereIn('f.cash_bill_option',['Tuition Fee','Tuition PTPK Self Pay'])
            ->selectRaw('SUM(fd.rp_amount) as total_amount')
            ->groupBy('f.s_id')
            ->get();

            $ptpk_receipt=$connection->table('f_receipt as f')
            ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
            ->where('f.s_id', $fee->id)
            ->where('f.r_status','ACTIVE')
            ->where('f.cash_bill_option','Tuition PTPK')
            ->whereBetween('f.r_date',[date('2024-07-01'),date('2024-07-31')])         
            ->selectRaw('SUM(fd.rp_amount) as total_amount')
            ->groupBy('f.s_id')
            ->get();

            $totalfee-=$f_receipt->first()->total_amount??0;
            $totalfee-=$ptpk_receipt->first()->total_amount??0;
            if($totalfee>0){
                $reminders[]=[
                    "s_name"=>$fee->s_name,
                    "total_fee_left"=>$totalfee,
                ];
            }
        });
        return response()->json([
            "status" => 200,
            "message" => "fetch tuition fee reminder",
            "data" => $reminders
        ]);
    }
    public function remind_hostel_fee()
    {
        $connection = DB::connection('student_registration');
        $hostelFees = $connection->table('student as s')
        ->join('f_receipt as f','f.s_id','=','s.id')
        ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
        ->where('s.h_status', 'YES')
        ->where('s.s_status', 'ACTIVE')
        ->selectRaw('MIN(f.id) as receipt_id, MIN(f.r_date) as first_payment_date,s.s_name,fd.rp_amount')
        ->groupBy('s.s_name','fd.rp_amount')
        ->get();

        $reminders = [];
        $hostelFees->map(function($fee) use (&$reminders,$connection) {
            $firstPaymentDate = new \DateTime($fee->first_payment_date);
            $currentDate = new \DateTime();
            $interval = $firstPaymentDate->diff($currentDate);
            $monthsDifference = ($interval->y * 12) + $interval->m;


            $periodFee = $fee->rp_amount;

            // Count the number of 6-month periods started (includes current period)
            $periodsElapsed = intdiv($monthsDifference, 6) + 1;

            $totalHostelFee = $periodsElapsed * (int)$periodFee;

            $f_receipt=$connection->table('f_receipt as f')
            ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
            ->where('f.s_id', $fee->receipt_id)
            ->where('f.r_status','ACTIVE')
            ->whereIn('f.cash_bill_option',['Hostel Fee'])
            ->selectRaw('SUM(fd.rp_amount) as total_amount')
            ->groupBy('f.s_id')
            ->get();

            $totalPaid = $f_receipt->first()->total_amount ?? 0;
            $totalFeeLeft = $totalHostelFee - $totalPaid;

            if ($totalFeeLeft > 0) {
                $reminders[] = [
                    "s_name" => $fee->s_name,
                    "total_hostel_fee_left" => $totalFeeLeft,
                ];
            }
        });

        return response()->json([
            "status" => 200,
            "message" => "fetch hostel fee reminder",
            "data" => $hostelFees
        ]);
    }
}
