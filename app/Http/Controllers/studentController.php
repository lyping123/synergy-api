<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class studentController extends Controller
{
    public $phone_number;

    public function __construct()
    {
        $this->phone_number = env('WHATSAPP_PHONE_NUMBER', '+60129253398');
    }
    

    public function remind_tuition_fee()
    {
        $connection = DB::connection('student_registration');
        $tuitionFees = $connection->table('student')
        ->where('s_status', 'ACTIVE')
        ->selectRaw('id,s_name,if(CURDATE()<DATE(t_end),TIMESTAMPDIFF(MONTH,DATE(t_start),CURDATE()),TIMESTAMPDIFF(MONTH,DATE(t_start),DATE(t_end))) as month_difference,month_pay')
        ->get();
        
        $reminders = [];
        $tuitionFees->map(function($fee) use (&$reminders,$connection) {
            $totalfee=$fee->month_pay*($fee->month_difference-1);

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
            "phone_number"=>$this->phone_number,
            "data" => $reminders
        ]);
    }

    public function remind_tuition_ptpk()
    {
        $connection = DB::connection('student_registration');
        $tuitionFees = $connection->table('student as s')
        ->join('f_receipt as f','f.s_id','=','s.id')
        ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
        ->where('s.s_status', 'ACTIVE')
        ->where('s.p_method','semester')
        ->where('cash_bill_option','Tuition PTPK')
        ->orWhere('cash_bill_option','Tuition Fee')
        ->selectRaw('s.id,s.s_name,SUM(fd.rp_amount) as total_amount,MIN(f.r_date) as first_payment_date')
        ->groupBy('s.id','s.s_name')
        ->having("total_amount",">",0)
        ->get();
        
        $reminders = [];
        $tuitionFees->map(function($fee) use (&$reminders,$connection) {

            $ptpk_receipt=$connection->table('f_receipt as f')
            ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
            ->where('f.s_id', $fee->id)
            ->where('f.r_status','ACTIVE')
            ->where('f.cash_bill_option','Tuition PTPK')
            ->selectRaw('fd.rp_amount as total_amount, f.r_date')
            ->orderBy('f.id','ASC')
            ->get();
            for($i=2;$i<=4;$i++){
                $futureDate=clone $ptpk_receipt->r_date;
                $futureDate->modify("+".$i." month");
                if($futureDate->format('Y-m-d')<=date('Y-m-d')){
                    $totalFeeshouldpay=$ptpk_receipt->first()->total_amount * $i;
                }
            }

            $totalfee=$totalFeeshouldpay-$fee->total_amount;
            
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
            "phone_number"=>$this->phone_number,
            "data" => $reminders
        ]);
    }
    public function remind_hostel_fee()
    {
        $connection = DB::connection('student_registration');
        $hostelFees = $connection->table('student as s')
        ->join('f_receipt as f','f.s_id','=','s.id')
        ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
        ->join('student_detail as sd', 'sd.s_id', '=', 's.id')
        ->where('sd.s_status', 'ACTIVE')
        ->where('s.s_status', 'ACTIVE')
        ->selectRaw("
            s.s_name,
            s.id as s_id,
            SUBSTRING_INDEX(GROUP_CONCAT(f.id ORDER BY f.r_date ASC, f.id ASC), ',', 1) AS receipt_id,
            SUBSTRING_INDEX(GROUP_CONCAT(f.r_date ORDER BY f.r_date ASC, f.id ASC), ',', 1) AS first_payment_date,
            SUBSTRING_INDEX(GROUP_CONCAT(fd.rp_amount ORDER BY f.r_date ASC, f.id ASC), ',', 1) AS rp_amount
        ")
        ->groupBy('s.s_name', 's.id')
        ->get();

        $reminders = [];
        $hostelFees->map(function($fee) use (&$reminders,$connection) {
            $firstPaymentDate = new \DateTime($fee->first_payment_date);
            $currentDate = new \DateTime();
            $interval = $firstPaymentDate->diff($currentDate);
            $monthsDifference = ($interval->y * 12) + $interval->m;

            if ($monthsDifference >=33) {
                return;
            }

            $periodFee =$fee->rp_amount;

            // Count the number of 6-month periods started (includes current period)
            $periodsElapsed = intdiv($monthsDifference, 6) + 1;

            $totalHostelFee = $periodsElapsed * (int)$periodFee;
            
            $f_receipt=$connection->table('f_receipt as f')
            ->join('f_receipt_detail as fd','fd.r_id','=','f.id')
            ->where('f.s_id', $fee->s_id)
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
            "phone_number"=>$this->phone_number,
            "data" => $reminders
        ]);
    }
}
