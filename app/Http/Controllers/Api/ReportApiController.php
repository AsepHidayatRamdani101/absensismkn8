<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;

use App\Models\AttendanceDetail;
use App\Models\Student;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    //----------------------------------
    // LAPORAN HARIAN
    //----------------------------------

    public function daily(): JsonResponse
    {
        $today = Carbon::today();

        return response()->json([
            'success' => true,
            'message' => 'Laporan harian berhasil dimuat',
            'data' => [
                'tanggal' => $today->toDateString(),
                'hadir' => AttendanceDetail::whereDate('created_at', $today)->where('status', 'Hadir')->count(),
                'izin' => AttendanceDetail::whereDate('created_at', $today)->where('status', 'Izin')->count(),
                'sakit' => AttendanceDetail::whereDate('created_at', $today)->where('status', 'Sakit')->count(),
                'alpha' => AttendanceDetail::whereDate('created_at', $today)->where('status', 'Alpha')->count(),
            ],
            'meta' => [
                'scope' => 'daily',
            ],

        ]);
    }

    //----------------------------------
    // LAPORAN BULANAN
    //----------------------------------

    public function monthly(): JsonResponse
    {
        $now = now();
        $month = $now->month;
        $year = $now->year;

        return response()->json([
            'success' => true,
            'message' => 'Laporan bulanan berhasil dimuat',
            'data' => [
                'hadir' => AttendanceDetail::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('status', 'Hadir')->count(),
                'izin' => AttendanceDetail::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('status', 'Izin')->count(),
                'sakit' => AttendanceDetail::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('status', 'Sakit')->count(),
                'alpha' => AttendanceDetail::whereYear('created_at', $year)->whereMonth('created_at', $month)->where('status', 'Alpha')->count(),
            ],
            'meta' => [
                'scope' => 'monthly',
                'year' => $year,
                'month' => $month,
            ],

        ]);
    }

    //----------------------------------
    // LAPORAN PER SISWA
    //----------------------------------

    public function student(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('siswa')) {
            $isOwnRecord = $student->nisn === $user->email || $student->nis === $user->email;
            abort_unless($isOwnRecord, 403, 'Anda tidak memiliki akses ke data siswa ini.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan siswa berhasil dimuat',
            'data' => [
                'student' => new StudentResource($student->loadMissing('classroom.major')),
                'hadir' => AttendanceDetail::where('student_id', $student->id)->where('status', 'Hadir')->count(),
                'izin' => AttendanceDetail::where('student_id', $student->id)->where('status', 'Izin')->count(),
                'sakit' => AttendanceDetail::where('student_id', $student->id)->where('status', 'Sakit')->count(),
                'alpha' => AttendanceDetail::where('student_id', $student->id)->where('status', 'Alpha')->count(),
            ],
            'meta' => [
                'scope' => 'student',
                'student_id' => $student->id,
            ],

        ]);
    }
}
