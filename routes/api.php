<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\TeacherApiController;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\DeviceApiController;
use App\Http\Controllers\Api\ReportApiController;

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-auth');

    Route::middleware(['auth:sanctum', 'throttle:api-default'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('audit');
        Route::get('/profile', [AuthController::class, 'profile']);

        //-----------------------------------
        // MASTER DATA
        //-----------------------------------

        Route::get('/students', [StudentApiController::class, 'index'])
            ->middleware('role:admin|kurikulum|kesiswaan|bk|tu|guru|siswa');
        Route::get('/teachers', [TeacherApiController::class, 'index'])
            ->middleware('role:admin|kurikulum|kesiswaan|bk|tu|guru');

        //-----------------------------------
        // ATTENDANCE
        //-----------------------------------

        Route::post(
            '/attendance/manual',
            [AttendanceApiController::class, 'manual']
        )->middleware(['role:admin|kurikulum|guru', 'audit']);

        Route::get(
            '/attendance/history',
            [AttendanceApiController::class, 'history']
        )->middleware('role:admin|kurikulum|guru');

        //-----------------------------------
        // REPORT
        //-----------------------------------

        Route::get(
            '/reports/daily',
            [ReportApiController::class, 'daily']
        )->middleware('role:admin|kurikulum|kesiswaan|bk');

        Route::get(
            '/reports/monthly',
            [ReportApiController::class, 'monthly']
        )->middleware('role:admin|kurikulum|kesiswaan|bk');

        Route::get(
            '/reports/student/{student}',
            [ReportApiController::class, 'student']
        )->middleware('role:admin|kurikulum|kesiswaan|bk|guru|siswa');

        //-----------------------------------
        // DEVICE
        //-----------------------------------

        Route::post(
            '/device/rfid',
            [DeviceApiController::class, 'rfid']
        )->middleware(['role:admin', 'audit']);

        Route::post(
            '/device/face',
            [DeviceApiController::class, 'face']
        )->middleware(['role:admin', 'audit']);
    });
});
