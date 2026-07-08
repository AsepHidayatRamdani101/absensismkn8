<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = Attendance::with([
            'student.classroom',
            'schedule.teacherSubject.subject',
            'attendanceDevice',
        ])->latest()->get();

        $students = Student::with('classroom')->orderBy('nama_lengkap')->get();

        $schedules = Schedule::with([
            'teacherSubject.subject',
            'teacherSubject.classroom',
        ])->orderBy('hari')->orderBy('jam_mulai')->get();

        $attendanceDevices = AttendanceDevice::orderBy('nama_device')->get();

        return view('admin.attendances.index', compact(
            'attendances',
            'students',
            'schedules',
            'attendanceDevices'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'attendance_device_id' => 'nullable|exists:attendance_devices,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_keluar' => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
            'status' => 'required|in:Hadir,Izin,Sakit,Alpha,Terlambat',
            'metode' => 'required|in:Manual,RFID,Face,QR',
        ]);

        if ($this->isWeekendHoliday($validated['tanggal'])) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.'
            ], 422);
        }

        Attendance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil ditambahkan.'
        ]);
    }

    public function edit(Attendance $attendance)
    {
        return response()->json($attendance);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'attendance_device_id' => 'nullable|exists:attendance_devices,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_keluar' => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
            'status' => 'required|in:Hadir,Izin,Sakit,Alpha,Terlambat',
            'metode' => 'required|in:Manual,RFID,Face,QR',
        ]);

        if ($this->isWeekendHoliday($validated['tanggal'])) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.'
            ], 422);
        }

        $attendance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil diperbarui.'
        ]);
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil dihapus.'
        ]);
    }

    private function isWeekendHoliday(string $date): bool
    {
        $dayName = Carbon::parse($date)->locale('id')->dayName;

        return in_array($dayName, ['Sabtu', 'Minggu'], true);
    }
}
