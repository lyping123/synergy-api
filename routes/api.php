<?php

use Phiki\Grammar\Injections\Prefix;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\staffController;
use App\Http\Controllers\studentController;
use App\Http\Controllers\attendanceController;

//prefix
Route::prefix('v1')->group(function () {
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
    Route::get("/reminder/hostel_fee",[studentController::class,'remind_hostel_fee']);
});

