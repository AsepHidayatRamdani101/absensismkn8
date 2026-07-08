<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\TeacherApiController;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\DeviceApiController;
use App\Http\Controllers\Api\ReportApiController;

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);

        //-----------------------------------
        // MASTER DATA
        //-----------------------------------

        Route::get('/students', [StudentApiController::class, 'index']);
        Route::get('/teachers', [TeacherApiController::class, 'index']);

        //-----------------------------------
        // ATTENDANCE
        //-----------------------------------

        Route::post('/attendance/manual',
            [AttendanceApiController::class, 'manual']);

        Route::get('/attendance/history',
            [AttendanceApiController::class, 'history']);

        //-----------------------------------
        // REPORT
        //-----------------------------------

        Route::get('/reports/daily',
            [ReportApiController::class, 'daily']);

        Route::get('/reports/monthly',
            [ReportApiController::class, 'monthly']);

        Route::get('/reports/student/{student}',
            [ReportApiController::class, 'student']);

        //-----------------------------------
        // DEVICE
        //-----------------------------------

        Route::post('/device/rfid',
            [DeviceApiController::class, 'rfid']);

        Route::post('/device/face',
            [DeviceApiController::class, 'face']);

    });

});