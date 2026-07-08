<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDetail;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceDetailController extends Controller
{
    public function guruIndex(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $today = Carbon::today();
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        $todaySchedules = collect();

        if ($todayDayName !== null && !$isWeekendHoliday) {
            $todaySchedules = Schedule::query()
                ->with(['teacherSubject.classroom', 'teacherSubject.subject'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->orderBy('jam_mulai')
                ->get();
        }

        $classOptions = $todaySchedules
            ->map(function ($schedule) {
                return $schedule->teacherSubject->classroom;
            })
            ->filter()
            ->unique('id')
            ->sortBy('nama_kelas')
            ->values();

        $selectedClassroomId = (int) $request->query('classroom_id', 0);
        $allowedClassroomIds = $classOptions->pluck('id')->map(fn($id) => (int) $id)->values()->all();

        if ($selectedClassroomId !== 0 && !in_array($selectedClassroomId, $allowedClassroomIds, true)) {
            $selectedClassroomId = 0;
        }

        $studentsQuery = Student::query()
            ->with('classroom')
            ->whereIn('classroom_id', $allowedClassroomIds)
            ->orderBy('nama_lengkap');

        if ($selectedClassroomId !== 0) {
            $studentsQuery->where('classroom_id', $selectedClassroomId);
        }

        $students = $studentsQuery->get();

        $primaryScheduleByClass = [];
        foreach ($todaySchedules as $schedule) {
            $classroomId = (int) ($schedule->teacherSubject->classroom_id ?? 0);

            if ($classroomId === 0) {
                continue;
            }

            if (!isset($primaryScheduleByClass[$classroomId])) {
                $primaryScheduleByClass[$classroomId] = $schedule;
                continue;
            }

            if ($schedule->jam_mulai < $primaryScheduleByClass[$classroomId]->jam_mulai) {
                $primaryScheduleByClass[$classroomId] = $schedule;
            }
        }

        $scheduleIds = collect($primaryScheduleByClass)->pluck('id')->values();

        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $attendanceDetailRows = AttendanceDetail::query()
            ->whereIn('teacher_attendance_id', $teacherAttendances->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $statusByStudentId = [];
        foreach ($attendanceDetailRows as $row) {
            $statusByStudentId[$row->student_id] = $row->status;
        }

        return view('guru.attendance_details.index', [
            'teacher' => $teacher,
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'classOptions' => $classOptions,
            'selectedClassroomId' => $selectedClassroomId,
            'students' => $students,
            'statusByStudentId' => $statusByStudentId,
        ]);
    }

    public function index()
    {
        $attendanceDetails = AttendanceDetail::with([
            'teacherAttendance.teacher',
            'teacherAttendance.classroom',
            'teacherAttendance.subject',
            'teacherAttendance.academicYear',
            'student.classroom',
        ])->latest()->get();

        $teacherAttendances = TeacherAttendance::with([
            'teacher',
            'classroom',
            'subject',
            'academicYear',
        ])->orderByDesc('tanggal')->orderByDesc('id')->get();

        $students = Student::with('classroom')
            ->orderBy('nama_lengkap')
            ->get();

        $filterTahunAjarans = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('academic_years', 'academic_years.id', '=', 'teacher_attendances.academic_year_id')
            ->select('academic_years.tahun_ajaran')
            ->distinct()
            ->orderByDesc('academic_years.tahun_ajaran')
            ->pluck('academic_years.tahun_ajaran');

        $filterTangggals = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->select('teacher_attendances.tanggal')
            ->distinct()
            ->orderBy('teacher_attendances.tanggal')
            ->pluck('teacher_attendances.tanggal');

        $filterGurus = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('teachers', 'teachers.id', '=', 'teacher_attendances.teacher_id')
            ->select('teachers.nama_lengkap')
            ->distinct()
            ->orderBy('teachers.nama_lengkap')
            ->pluck('teachers.nama_lengkap');

        $filterMapels = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('subjects', 'subjects.id', '=', 'teacher_attendances.subject_id')
            ->select('subjects.nama_mapel')
            ->distinct()
            ->orderBy('subjects.nama_mapel')
            ->pluck('subjects.nama_mapel');

        $filterKelas = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('classrooms', 'classrooms.id', '=', 'teacher_attendances.classroom_id')
            ->select('classrooms.nama_kelas')
            ->distinct()
            ->orderBy('classrooms.nama_kelas')
            ->pluck('classrooms.nama_kelas');

        $filterStatuses = AttendanceDetail::query()
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('admin.attendance_details.index', compact(
            'attendanceDetails',
            'teacherAttendances',
            'students',
            'filterTahunAjarans',
            'filterTangggals',
            'filterGurus',
            'filterMapels',
            'filterKelas',
            'filterStatuses'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_attendance_id' => 'required|exists:teacher_attendances,id',
            'student_id' => [
                'required',
                'exists:students,id',
                Rule::unique('attendance_details')->where(function ($query) use ($request) {
                    return $query->where('teacher_attendance_id', $request->teacher_attendance_id);
                }),
            ],
            'status' => 'required|in:Hadir,Izin,Sakit,Alpha,Terlambat',
            'keterangan' => 'nullable|string|max:255',
            'jam_absen' => 'nullable|date_format:H:i',
        ]);

        $teacherAttendanceDate = TeacherAttendance::query()
            ->whereKey($validated['teacher_attendance_id'])
            ->value('tanggal');

        if ($teacherAttendanceDate && $this->isWeekendHoliday($teacherAttendanceDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.'
            ], 422);
        }

        AttendanceDetail::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Absensi siswa oleh guru berhasil ditambahkan.'
        ]);
    }

    public function edit(AttendanceDetail $attendanceDetail)
    {
        return response()->json($attendanceDetail);
    }

    public function update(Request $request, AttendanceDetail $attendanceDetail)
    {
        $validated = $request->validate([
            'teacher_attendance_id' => 'required|exists:teacher_attendances,id',
            'student_id' => [
                'required',
                'exists:students,id',
                Rule::unique('attendance_details')->where(function ($query) use ($request) {
                    return $query->where('teacher_attendance_id', $request->teacher_attendance_id);
                })->ignore($attendanceDetail->id),
            ],
            'status' => 'required|in:Hadir,Izin,Sakit,Alpha,Terlambat',
            'keterangan' => 'nullable|string|max:255',
            'jam_absen' => 'nullable|date_format:H:i',
        ]);

        $teacherAttendanceDate = TeacherAttendance::query()
            ->whereKey($validated['teacher_attendance_id'])
            ->value('tanggal');

        if ($teacherAttendanceDate && $this->isWeekendHoliday($teacherAttendanceDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.'
            ], 422);
        }

        $attendanceDetail->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Absensi siswa oleh guru berhasil diperbarui.'
        ]);
    }

    public function destroy(AttendanceDetail $attendanceDetail)
    {
        $attendanceDetail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Absensi siswa oleh guru berhasil dihapus.'
        ]);
    }

    public function submitForGuru(Request $request, Student $student)
    {
        $validated = $request->validate([
            'status' => 'required|in:Hadir,Sakit,Izin,Alpa',
            'classroom_id' => 'required|integer',
        ]);

        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $today = Carbon::today();
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;

        if ($todayDayName === null || in_array($todayDayName, ['Sabtu', 'Minggu'], true)) {
            return redirect()->route('guru.attendance-details.index', ['classroom_id' => $validated['classroom_id']])
                ->with('error', 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.');
        }

        $classroomId = (int) $validated['classroom_id'];

        if ((int) $student->classroom_id !== $classroomId) {
            abort(403);
        }

        $schedule = Schedule::query()
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($teacher, $classroomId) {
                $query->where('teacher_id', $teacher->id)
                    ->where('classroom_id', $classroomId);
            })
            ->orderBy('jam_mulai')
            ->with('teacherSubject')
            ->first();

        if (!$schedule || !$schedule->teacherSubject) {
            return redirect()->route('guru.attendance-details.index', ['classroom_id' => $classroomId])
                ->with('error', 'Jadwal hari ini untuk kelas tersebut tidak ditemukan.');
        }

        $teacherAttendance = TeacherAttendance::query()
            ->where('schedule_id', $schedule->id)
            ->whereDate('tanggal', $today->toDateString())
            ->first();

        if (!$teacherAttendance) {
            $lastPertemuan = (int) TeacherAttendance::query()
                ->where('schedule_id', $schedule->id)
                ->max('pertemuan');

            $teacherAttendance = TeacherAttendance::create([
                'teacher_id' => $schedule->teacherSubject->teacher_id,
                'schedule_id' => $schedule->id,
                'classroom_id' => $schedule->teacherSubject->classroom_id,
                'subject_id' => $schedule->teacherSubject->subject_id,
                'academic_year_id' => $schedule->teacherSubject->academic_year_id,
                'tanggal' => $today->toDateString(),
                'pertemuan' => max($lastPertemuan + 1, 1),
                'materi_pembelajaran' => null,
                'catatan_guru' => null,
                'status' => 'Draft',
            ]);
        }

        $status = $validated['status'] === 'Alpa' ? 'Alpha' : $validated['status'];

        AttendanceDetail::updateOrCreate(
            [
                'teacher_attendance_id' => $teacherAttendance->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'keterangan' => null,
                'jam_absen' => now()->format('H:i:s'),
            ]
        );

        return redirect()->route('guru.attendance-details.index', ['classroom_id' => $classroomId])
            ->with('success', 'Status absensi siswa berhasil disimpan.');
    }

    public function submitBulkForGuru(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'nullable|integer',
            'bulk_status' => 'required|in:Hadir,Sakit,Izin,Alpa',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $today = Carbon::today();
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;

        if ($todayDayName === null || in_array($todayDayName, ['Sabtu', 'Minggu'], true)) {
            return redirect()->route('guru.attendance-details.index', ['classroom_id' => (int) ($validated['classroom_id'] ?? 0)])
                ->with('error', 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.');
        }

        $selectedClassroomId = (int) ($validated['classroom_id'] ?? 0);

        $todaySchedulesQuery = Schedule::query()
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->with('teacherSubject')
            ->orderBy('jam_mulai');

        if ($selectedClassroomId !== 0) {
            $todaySchedulesQuery->whereHas('teacherSubject', function ($query) use ($selectedClassroomId) {
                $query->where('classroom_id', $selectedClassroomId);
            });
        }

        $todaySchedules = $todaySchedulesQuery->get();

        if ($todaySchedules->isEmpty()) {
            return redirect()->route('guru.attendance-details.index', ['classroom_id' => $selectedClassroomId])
                ->with('error', 'Jadwal hari ini tidak ditemukan.');
        }

        $primaryScheduleByClass = [];
        foreach ($todaySchedules as $schedule) {
            $classroomId = (int) ($schedule->teacherSubject->classroom_id ?? 0);

            if ($classroomId === 0) {
                continue;
            }

            if (!isset($primaryScheduleByClass[$classroomId]) || $schedule->jam_mulai < $primaryScheduleByClass[$classroomId]->jam_mulai) {
                $primaryScheduleByClass[$classroomId] = $schedule;
            }
        }

        $studentIds = collect($validated['student_ids'] ?? [])->map(fn($id) => (int) $id)->values();

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $teacherAttendanceBySchedule = [];
        $savedCount = 0;
        $status = $validated['bulk_status'] === 'Alpa' ? 'Alpha' : $validated['bulk_status'];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);

            if (!$student) {
                continue;
            }

            $classroomId = (int) $student->classroom_id;
            $schedule = $primaryScheduleByClass[$classroomId] ?? null;

            if (!$schedule || !$schedule->teacherSubject) {
                continue;
            }

            $scheduleId = (int) $schedule->id;

            if (!isset($teacherAttendanceBySchedule[$scheduleId])) {
                $teacherAttendance = TeacherAttendance::query()
                    ->where('schedule_id', $scheduleId)
                    ->whereDate('tanggal', $today->toDateString())
                    ->first();

                if (!$teacherAttendance) {
                    $lastPertemuan = (int) TeacherAttendance::query()
                        ->where('schedule_id', $scheduleId)
                        ->max('pertemuan');

                    $teacherAttendance = TeacherAttendance::create([
                        'teacher_id' => $schedule->teacherSubject->teacher_id,
                        'schedule_id' => $scheduleId,
                        'classroom_id' => $schedule->teacherSubject->classroom_id,
                        'subject_id' => $schedule->teacherSubject->subject_id,
                        'academic_year_id' => $schedule->teacherSubject->academic_year_id,
                        'tanggal' => $today->toDateString(),
                        'pertemuan' => max($lastPertemuan + 1, 1),
                        'materi_pembelajaran' => null,
                        'catatan_guru' => null,
                        'status' => 'Draft',
                    ]);
                }

                $teacherAttendanceBySchedule[$scheduleId] = $teacherAttendance;
            }

            AttendanceDetail::updateOrCreate(
                [
                    'teacher_attendance_id' => $teacherAttendanceBySchedule[$scheduleId]->id,
                    'student_id' => $studentId,
                ],
                [
                    'status' => $status,
                    'keterangan' => null,
                    'jam_absen' => now()->format('H:i:s'),
                ]
            );

            $savedCount++;
        }

        if ($savedCount === 0) {
            return redirect()->route('guru.attendance-details.index', ['classroom_id' => $selectedClassroomId])
                ->with('error', 'Tidak ada data yang tersimpan. Pastikan siswa berada pada kelas sesuai jadwal Anda hari ini.');
        }

        return redirect()->route('guru.attendance-details.index', ['classroom_id' => $selectedClassroomId])
            ->with('success', "Berhasil menyimpan absensi {$savedCount} siswa.");
    }

    private function isWeekendHoliday(string $date): bool
    {
        $dayName = Carbon::parse($date)->locale('id')->dayName;

        return in_array($dayName, ['Sabtu', 'Minggu'], true);
    }
}
