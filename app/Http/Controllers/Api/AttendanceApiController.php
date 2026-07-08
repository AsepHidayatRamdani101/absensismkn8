<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherAttendance;
use App\Models\AttendanceDetail;

use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    public function manual(Request $request)
    {
        $validated = $request->validate([

            'teacher_id' => 'required',

            'schedule_id' => 'required',

            'classroom_id' => 'required',

            'subject_id' => 'required',

            'academic_year_id' => 'required',

            'tanggal' => 'required|date',

            'pertemuan' => 'required',

            'details' => 'required|array'

        ]);

        $attendance = TeacherAttendance::create([

            'teacher_id' => $validated['teacher_id'],
            'schedule_id' => $validated['schedule_id'],
            'classroom_id' => $validated['classroom_id'],
            'subject_id' => $validated['subject_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'tanggal' => $validated['tanggal'],
            'pertemuan' => $validated['pertemuan'],
            'status' => 'Selesai',

        ]);

        foreach ($validated['details'] as $item) {

            AttendanceDetail::create([

                'teacher_attendance_id' => $attendance->id,

                'student_id' => $item['student_id'],

                'status' => $item['status'],

                'keterangan' => $item['keterangan'] ?? null,

                'jam_absen' => now()->format('H:i:s')

            ]);
        }

        return response()->json([

            'success' => true,

            'message' => 'Absensi berhasil disimpan'

        ]);
    }


    public function history()
    {
        return response()->json([

            'success' => true,

            'data' => TeacherAttendance::with([
                'teacher',
                'subject',
                'classroom'
            ])
            ->latest()
            ->paginate(20)

        ]);
    }
}