<?php

namespace App\Http\Controllers;

use App\Exports\SiswaAttendanceHistoryExport;
use App\Models\AttendanceDetail;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherLeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceDetailController extends Controller
{
    public function siswaHistory(Request $request)
    {
        $student = $this->resolveCurrentStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        $payload = $this->buildSiswaHistoryPayload($request, $student);

        return view('siswa.attendance_history.index', [
            'student' => $student,
            ...$payload,
        ]);
    }

    public function siswaHistoryPdf(Request $request)
    {
        $student = $this->resolveCurrentStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        $payload = $this->buildSiswaHistoryPayload($request, $student);

        $pdf = Pdf::loadView('siswa.attendance_history.pdf', [
            'student' => $student,
            'histories' => $payload['histories'],
            'statusSummary' => $payload['statusSummary'],
            'filters' => [
                'tanggal_dari' => $payload['tanggalDari'],
                'tanggal_sampai' => $payload['tanggalSampai'],
                'status' => $payload['statusFilter'],
                'guru_mapel' => $payload['guruMapelFilter'],
            ],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('riwayat-absen-siswa.pdf');
    }

    public function siswaHistoryExcel(Request $request)
    {
        $student = $this->resolveCurrentStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        $payload = $this->buildSiswaHistoryPayload($request, $student);

        return Excel::download(
            new SiswaAttendanceHistoryExport($payload['histories']),
            'riwayat-absen-siswa.xlsx'
        );
    }

    public function siswaIndex(Request $request)
    {
        $officer = $this->resolveOfficerStudent();

        if (!$officer || !$officer->canSubmitTeacherAttendance()) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengisi absensi siswa kelas.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum mengakses menu ini. Riwayat Absen tetap bisa diakses.');
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

        $today = Carbon::today(config('app.timezone'));
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        $leaveSchedules = collect();

        if ($todayDayName !== null && !$isWeekendHoliday) {
            $leaveSchedules = Schedule::query()
                ->with(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject', function ($query) use ($officer, $today) {
                    $query->where('classroom_id', $officer->classroom_id)
                        ->whereHas('teacher.leaveRequests', function ($leaveQuery) use ($today) {
                            $leaveQuery
                                ->whereIn('status_pengajuan', ['Menunggu', 'Disetujui'])
                                ->whereDate('tanggal_mulai', '<=', $today->toDateString())
                                ->whereDate('tanggal_selesai', '>=', $today->toDateString());
                        });
                })
                ->orderBy('jam_mulai')
                ->get();
        }

        $selectedScheduleId = (int) $request->query('schedule_id', 0);
        $allowedScheduleIds = $leaveSchedules->pluck('id')->map(fn($id) => (int) $id)->values()->all();

        if ($selectedScheduleId !== 0 && !in_array($selectedScheduleId, $allowedScheduleIds, true)) {
            $selectedScheduleId = 0;
        }

        if ($selectedScheduleId === 0 && !empty($allowedScheduleIds)) {
            $selectedScheduleId = (int) $allowedScheduleIds[0];
        }

        $selectedSchedule = $leaveSchedules->firstWhere('id', $selectedScheduleId);

        $activeLeaveRequest = null;
        $statusByStudentId = [];

        if ($selectedSchedule && $selectedSchedule->teacherSubject) {
            $activeLeaveRequest = TeacherLeaveRequest::query()
                ->where('teacher_id', $selectedSchedule->teacherSubject->teacher_id)
                ->whereIn('status_pengajuan', ['Menunggu', 'Disetujui'])
                ->whereDate('tanggal_mulai', '<=', $today->toDateString())
                ->whereDate('tanggal_selesai', '>=', $today->toDateString())
                ->latest('id')
                ->first();

            $teacherAttendance = TeacherAttendance::query()
                ->where('schedule_id', $selectedSchedule->id)
                ->whereDate('tanggal', $today->toDateString())
                ->first();

            if ($teacherAttendance) {
                $rows = AttendanceDetail::query()
                    ->where('teacher_attendance_id', $teacherAttendance->id)
                    ->get();

                foreach ($rows as $row) {
                    $statusByStudentId[$row->student_id] = $row->status;
                }
            }
        }

        $students = Student::query()
            ->with('classroom')
            ->where('classroom_id', $officer->classroom_id)
            ->orderBy('nama_lengkap')
            ->get();

        return view('siswa.attendance_details.index', [
            'officer' => $officer,
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'leaveSchedules' => $leaveSchedules,
            'selectedScheduleId' => $selectedScheduleId,
            'selectedSchedule' => $selectedSchedule,
            'activeLeaveRequest' => $activeLeaveRequest,
            'students' => $students,
            'statusByStudentId' => $statusByStudentId,
        ]);
    }

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

    public function destroyMultiple(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:attendance_details,id',
        ]);

        AttendanceDetail::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => count($validated['ids']) . ' data absensi siswa oleh guru berhasil dihapus',
        ]);
    }

    public function submitForGuru(Request $request, Student $student)
    {
        $validated = $request->validate([
            'status' => 'required|in:Hadir,Sakit,Izin,Alpa,Terlambat',
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

    public function submitForOfficer(Request $request, Student $student)
    {
        $validated = $request->validate([
            'status' => 'required|in:Hadir,Sakit,Izin,Alpa',
            'classroom_id' => 'required|integer',
            'schedule_id' => 'required|integer|exists:schedules,id',
        ]);

        $officer = $this->resolveOfficerStudent();

        if (!$officer || !$officer->canSubmitTeacherAttendance()) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengisi absensi siswa kelas.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum melakukan absensi. Riwayat Absen tetap bisa diakses.');
        }

        $classroomId = (int) $validated['classroom_id'];

        if ((int) $officer->classroom_id !== $classroomId || (int) $student->classroom_id !== $classroomId) {
            abort(403);
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
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $validated['schedule_id']])
                ->with('error', 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.');
        }

        $schedule = Schedule::query()
            ->with('teacherSubject')
            ->where('id', (int) $validated['schedule_id'])
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->first();

        if (!$schedule || !$schedule->teacherSubject) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Jadwal tidak valid untuk kelas Anda hari ini.');
        }

        $leaveRequest = TeacherLeaveRequest::query()
            ->where('teacher_id', $schedule->teacherSubject->teacher_id)
            ->whereIn('status_pengajuan', ['Menunggu', 'Disetujui'])
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        if (!$leaveRequest) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Absensi siswa oleh pengurus kelas hanya aktif saat guru mengajukan izin.');
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
                'kehadiran_guru' => $leaveRequest->jenis_pengajuan,
                'tugas_file_path' => $leaveRequest->lampiran_tugas_path,
                'tugas_deskripsi' => $leaveRequest->deskripsi_tugas,
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

        return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
            ->with('success', 'Status absensi siswa berhasil disimpan.');
    }

    public function submitBulkForGuru(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'nullable|integer',
            'bulk_status' => 'required|in:Hadir,Sakit,Izin,Alpa,Terlambat',
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

    public function submitBulkForOfficer(Request $request)
    {
        $validated = $request->validate([
            'bulk_status' => 'required|in:Hadir,Sakit,Izin,Alpa',
            'classroom_id' => 'required|integer',
            'schedule_id' => 'required|integer|exists:schedules,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        $officer = $this->resolveOfficerStudent();

        if (!$officer || !$officer->canSubmitTeacherAttendance()) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengisi absensi siswa kelas.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum melakukan absensi massal. Riwayat Absen tetap bisa diakses.');
        }

        $classroomId = (int) $validated['classroom_id'];

        if ((int) $officer->classroom_id !== $classroomId) {
            abort(403);
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
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $validated['schedule_id']])
                ->with('error', 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.');
        }

        $schedule = Schedule::query()
            ->with('teacherSubject')
            ->where('id', (int) $validated['schedule_id'])
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->first();

        if (!$schedule || !$schedule->teacherSubject) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Jadwal tidak valid untuk kelas Anda hari ini.');
        }

        $leaveRequest = TeacherLeaveRequest::query()
            ->where('teacher_id', $schedule->teacherSubject->teacher_id)
            ->whereIn('status_pengajuan', ['Menunggu', 'Disetujui'])
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        if (!$leaveRequest) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Absensi siswa oleh pengurus kelas hanya aktif saat guru mengajukan izin.');
        }

        $studentIds = collect($validated['student_ids'])->map(fn($id) => (int) $id)->values();

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->where('classroom_id', $classroomId)
            ->get();

        if ($students->isEmpty()) {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
                ->with('error', 'Tidak ada data siswa valid untuk disimpan.');
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
                'kehadiran_guru' => $leaveRequest->jenis_pengajuan,
                'tugas_file_path' => $leaveRequest->lampiran_tugas_path,
                'tugas_deskripsi' => $leaveRequest->deskripsi_tugas,
                'status' => 'Draft',
            ]);
        }

        $status = $validated['bulk_status'] === 'Alpa' ? 'Alpha' : $validated['bulk_status'];

        foreach ($students as $student) {
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
        }

        return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
            ->with('success', 'Absensi siswa berhasil disimpan secara massal.');
    }

    private function isWeekendHoliday(string $date): bool
    {
        $dayName = Carbon::parse($date)->locale('id')->dayName;

        return in_array($dayName, ['Sabtu', 'Minggu'], true);
    }

    private function resolveOfficerStudent(): ?Student
    {
        $student = $this->resolveCurrentStudent();

        return $student;
    }

    private function buildSiswaHistoryPayload(Request $request, Student $student): array
    {
        $query = AttendanceDetail::query()
            ->with([
                'teacherAttendance.teacher',
                'teacherAttendance.subject',
                'teacherAttendance.classroom',
            ])
            ->where('student_id', $student->id);

        $tanggalDari = (string) $request->query('tanggal_dari', '');
        $tanggalSampai = (string) $request->query('tanggal_sampai', '');
        $statusFilter = (string) $request->query('status', '');
        $guruMapelFilter = (string) $request->query('guru_mapel', '');

        if ($tanggalDari !== '') {
            $query->whereHas('teacherAttendance', function ($attendanceQuery) use ($tanggalDari) {
                $attendanceQuery->whereDate('tanggal', '>=', $tanggalDari);
            });
        }

        if ($tanggalSampai !== '') {
            $query->whereHas('teacherAttendance', function ($attendanceQuery) use ($tanggalSampai) {
                $attendanceQuery->whereDate('tanggal', '<=', $tanggalSampai);
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($guruMapelFilter !== '') {
            [$teacherId, $subjectId] = array_pad(explode(':', $guruMapelFilter), 2, null);

            if (is_numeric($teacherId) && is_numeric($subjectId)) {
                $query->whereHas('teacherAttendance', function ($attendanceQuery) use ($teacherId, $subjectId) {
                    $attendanceQuery->where('teacher_id', (int) $teacherId)
                        ->where('subject_id', (int) $subjectId);
                });
            }
        }

        $histories = $query
            ->latest('id')
            ->get();

        $guruMapelOptions = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('teachers', 'teachers.id', '=', 'teacher_attendances.teacher_id')
            ->join('subjects', 'subjects.id', '=', 'teacher_attendances.subject_id')
            ->where('attendance_details.student_id', $student->id)
            ->selectRaw('teacher_attendances.teacher_id as teacher_id, teacher_attendances.subject_id as subject_id, subjects.nama_mapel as nama_mapel, teachers.nama_lengkap as nama_guru')
            ->distinct()
            ->orderBy('subjects.nama_mapel')
            ->orderBy('teachers.nama_lengkap')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->teacher_id . ':' . $item->subject_id,
                    'label' => $item->nama_mapel . ' - ' . $item->nama_guru,
                ];
            })
            ->values();

        $statusSummary = [
            'total' => (int) $histories->count(),
            'hadir' => (int) $histories->where('status', 'Hadir')->count(),
            'sakit' => (int) $histories->where('status', 'Sakit')->count(),
            'izin' => (int) $histories->where('status', 'Izin')->count(),
            'alpa' => (int) $histories->filter(fn($item) => in_array($item->status, ['Alpha', 'Alpa'], true))->count(),
            'terlambat' => (int) $histories->where('status', 'Terlambat')->count(),
        ];

        $statusSummary['persentase_hadir'] = $statusSummary['total'] > 0
            ? round(($statusSummary['hadir'] / $statusSummary['total']) * 100, 2)
            : 0;

        return [
            'histories' => $histories,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'statusFilter' => $statusFilter,
            'guruMapelFilter' => $guruMapelFilter,
            'guruMapelOptions' => $guruMapelOptions,
            'statusSummary' => $statusSummary,
        ];
    }

    private function resolveCurrentStudent(): ?Student
    {
        $user = auth()->user();

        return Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();
    }
}
