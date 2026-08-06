<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\staffController;
use App\Http\Controllers\studentController;
use App\Http\Controllers\attendanceController;

//prefix
Route::prefix('v1')->group(function () {
    //pretest api
    Route::get("/pretest/student",[studentController::class,'pretest_student']);
    Route::get("/pretest/student/{id}",[studentController::class,'pretest_student_by_id']);
    Route::post("/pretest/student/add",[studentController::class,'add_pretest_student']);
    Route::post("/pretest/student/{id}/update",[studentController::class,'update_pretest_student']);
    Route::post("/pretest/student/{id}/delete",[studentController::class,'delete_pretest_student']);



    // Route::get("/staff/{id}",[staffController::class,'findstaff']);
    Route::get("/staff/all",[staffController::class,'fetchAllStaff']);
    Route::post("/staff/add",[staffController::class,'addstaff']);
    

    Route::get("/attendance/{date}/count",[attendanceController::class,'countattendance']);
    Route::get("/attendance/{date}/all",[attendanceController::class,'fetchattendance']);
    Route::get("/attendance/{date}/staff",[attendanceController::class,'check_staff_attendance']);
    Route::get("attendance/last_checkin/{staff_id}/{date}",[attendanceController::class,'last_checkin']);
    Route::post("/attendance/add",[attendanceController::class,'addattendance']);
    Route::put("/attendance/{id}/update",[attendanceController::class,'updateattendance']);
    Route::delete("/attendance/{id}/delete",[attendanceController::class,'deleteattendance']);
    Route::get("/attendance/today",[attendanceController::class,'todayattendance']);

    Route::get("/reminder/apply_leave",[attendanceController::class,'reminder_apply_leave']);

    Route::get("/reminder/tuition_fee",[studentController::class,'remind_tuition_fee']);
    Route::get("/reminder/tuition_fee_ptpk",[studentController::class,'remind_tuition_ptpk']);
    Route::get("/reminder/hostel_fee",[studentController::class,'remind_hostel_fee']);

    

});

