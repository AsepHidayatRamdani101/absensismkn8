<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\AttendanceDetail;
use App\Models\Student;

use Carbon\Carbon;

class ReportApiController extends Controller
{
    //----------------------------------
    // LAPORAN HARIAN
    //----------------------------------

    public function daily()
    {
        $today = Carbon::today();

        return response()->json([

            'tanggal' => $today,

            'hadir' => AttendanceDetail::whereDate(
                'created_at',
                $today
            )->where('status', 'Hadir')->count(),

            'izin' => AttendanceDetail::whereDate(
                'created_at',
                $today
            )->where('status', 'Izin')->count(),

            'sakit' => AttendanceDetail::whereDate(
                'created_at',
                $today
            )->where('status', 'Sakit')->count(),

            'alpha' => AttendanceDetail::whereDate(
                'created_at',
                $today
            )->where('status', 'Alpha')->count(),

        ]);
    }

    //----------------------------------
    // LAPORAN BULANAN
    //----------------------------------

    public function monthly()
    {
        $month = now()->month;

        return response()->json([

            'hadir' => AttendanceDetail::whereMonth(
                'created_at',
                $month
            )->where('status', 'Hadir')->count(),

            'izin' => AttendanceDetail::whereMonth(
                'created_at',
                $month
            )->where('status', 'Izin')->count(),

            'sakit' => AttendanceDetail::whereMonth(
                'created_at',
                $month
            )->where('status', 'Sakit')->count(),

            'alpha' => AttendanceDetail::whereMonth(
                'created_at',
                $month
            )->where('status', 'Alpha')->count(),

        ]);
    }

    //----------------------------------
    // LAPORAN PER SISWA
    //----------------------------------

    public function student(Student $student)
    {
        return response()->json([

            'student' => $student,

            'hadir' => AttendanceDetail::where(
                'student_id',
                $student->id
            )->where('status', 'Hadir')->count(),

            'izin' => AttendanceDetail::where(
                'student_id',
                $student->id
            )->where('status', 'Izin')->count(),

            'sakit' => AttendanceDetail::where(
                'student_id',
                $student->id
            )->where('status', 'Sakit')->count(),

            'alpha' => AttendanceDetail::where(
                'student_id',
                $student->id
            )->where('status', 'Alpha')->count(),

        ]);
    }
}