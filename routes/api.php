<?php

use App\Http\Controllers\attendanceController;
use App\Http\Controllers\staffController;
use Illuminate\Support\Facades\Route;
use Phiki\Grammar\Injections\Prefix;

//prefix
Route::prefix('v1')->group(function () {
    Route::get("/staff/{id}",[staffController::class,'findstaff']);
    Route::post("/staff/add",[staffController::class,'addstaff']);

    Route::get("/attendance/{date}/count",[attendanceController::class,'countattendance']);
    Route::get("/attendance/{date}/all",[attendanceController::class,'fetchattendance']);
    Route::get("attendance/last_checkin/{staff_id}/{date}",[attendanceController::class,'last_checkin']);
    Route::post("/attendance/add",[attendanceController::class,'addattendance']);
    Route::put("/attendance/{id}/update",[attendanceController::class,'updateattendance']);
    Route::delete("/attendance/{id}/delete",[attendanceController::class,'deleteattendance']);
});

