<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TeacherAttendanceResource;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\AttendanceDetail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceApiController extends Controller
{
    public function manual(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $validated = $request->validate([

            'teacher_id' => 'nullable|integer|exists:teachers,id',

            'schedule_id' => 'required|integer|exists:schedules,id',

            'classroom_id' => 'required|integer|exists:classrooms,id',

            'subject_id' => 'required|integer|exists:subjects,id',

            'academic_year_id' => 'required|integer|exists:academic_years,id',

            'tanggal' => 'required|date',

            'pertemuan' => 'required|string|max:100',

            'details' => 'required|array|min:1',
            'details.*.student_id' => 'required|integer|exists:students,id',
            'details.*.status' => 'required|in:Hadir,Izin,Sakit,Alpha,Terlambat',
            'details.*.keterangan' => 'nullable|string|max:255',

        ]);

        $teacherId = (int) ($validated['teacher_id'] ?? 0);

        if (method_exists($user, 'hasRole') && $user->hasRole('guru')) {
            $teacher = Teacher::query()
                ->where('nip', $user->email)
                ->orWhere('nama_lengkap', $user->name)
                ->first();

            abort_if($teacher === null, 403, 'Data guru tidak ditemukan untuk akun ini.');
            $teacherId = (int) $teacher->id;
        }

        abort_if($teacherId <= 0, 422, 'teacher_id tidak valid.');

        $attendance = DB::transaction(function () use ($validated, $teacherId) {
            $attendance = TeacherAttendance::create([

                'teacher_id' => $teacherId,
                'schedule_id' => $validated['schedule_id'],
                'classroom_id' => $validated['classroom_id'],
                'subject_id' => $validated['subject_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'tanggal' => $validated['tanggal'],
                'pertemuan' => $validated['pertemuan'],
                'status' => 'Selesai',

            ]);

            $detailsPayload = [];
            foreach ($validated['details'] as $item) {
                $detailsPayload[] = [
                    'teacher_attendance_id' => $attendance->id,
                    'student_id' => $item['student_id'],
                    'status' => $item['status'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'jam_absen' => now()->format('H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            AttendanceDetail::insert($detailsPayload);

            return $attendance;
        });

        return response()->json([

            'success' => true,

            'message' => 'Absensi berhasil disimpan',
            'data' => [
                'teacher_attendance_id' => $attendance->id,
            ],

        ]);
    }


    public function history(Request $request)
    {
        $perPage = max(min((int) $request->integer('per_page', 20), 100), 10);
        $items = TeacherAttendance::with([
            'teacher',
            'subject',
            'classroom'
        ])
            ->latest()
            ->paginate($perPage);

        return response()->json([

            'success' => true,

            'data' => TeacherAttendanceResource::collection($items->getCollection()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],

        ]);
    }
}
