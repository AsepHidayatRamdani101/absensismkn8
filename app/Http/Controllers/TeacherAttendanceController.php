<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AttendanceDetail;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherAttendanceController extends Controller
{
    public function siswaIndex()
    {
        $user = auth()->user();

        $student = Student::with('classroom.major')
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
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

        $schedules = collect();

        if ($todayDayName !== null && !$isWeekendHoliday) {
            $schedules = Schedule::query()
                ->with(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject', function ($query) use ($student) {
                    $query->where('classroom_id', $student->classroom_id);
                })
                ->orderBy('jam_mulai')
                ->get();
        }

        $scheduleIds = $schedules->pluck('id');

        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $attendanceDetails = AttendanceDetail::query()
            ->where('student_id', $student->id)
            ->whereIn('teacher_attendance_id', $teacherAttendances->pluck('id'))
            ->get()
            ->keyBy('teacher_attendance_id');

        $scheduleRows = $schedules->map(function ($schedule) use ($teacherAttendances, $attendanceDetails) {
            $teacherAttendance = $teacherAttendances->get($schedule->id);
            $detail = $teacherAttendance ? $attendanceDetails->get($teacherAttendance->id) : null;

            $selectedAction = null;
            if ($detail) {
                if ($detail->status === 'Hadir') {
                    $selectedAction = 'Hadir';
                } elseif ($detail->status === 'Izin' && strcasecmp((string) $detail->keterangan, 'Tugas') === 0) {
                    $selectedAction = 'Tugas';
                }
            }

            return [
                'schedule' => $schedule,
                'teacher_attendance' => $teacherAttendance,
                'detail' => $detail,
                'selected_action' => $selectedAction,
            ];
        });

        return view('siswa.teacher_attendances.index', [
            'student' => $student,
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'scheduleRows' => $scheduleRows,
        ]);
    }

    public function index()
    {
        $isReadOnly = auth()->user()?->hasRole('siswa') ?? false;

        $teacherAttendances = TeacherAttendance::with([
            'teacher',
            'schedule.teacherSubject.subject',
            'schedule.teacherSubject.teacher',
            'classroom',
            'subject',
            'academicYear',
        ])->latest()->get();

        $filterTahunAjarans = AcademicYear::query()
            ->select('tahun_ajaran')
            ->distinct()
            ->orderByDesc('tahun_ajaran')
            ->pluck('tahun_ajaran');

        $filterTangggals = TeacherAttendance::query()
            ->select('tanggal')
            ->distinct()
            ->orderBy('tanggal')
            ->pluck('tanggal');

        $filterGurus = TeacherAttendance::query()
            ->join('teachers', 'teachers.id', '=', 'teacher_attendances.teacher_id')
            ->select('teachers.nama_lengkap')
            ->distinct()
            ->orderBy('teachers.nama_lengkap')
            ->pluck('teachers.nama_lengkap');

        $filterMapels = TeacherAttendance::query()
            ->join('subjects', 'subjects.id', '=', 'teacher_attendances.subject_id')
            ->select('subjects.nama_mapel')
            ->distinct()
            ->orderBy('subjects.nama_mapel')
            ->pluck('subjects.nama_mapel');

        $filterPertemuans = TeacherAttendance::query()
            ->whereNotNull('pertemuan')
            ->select('pertemuan')
            ->distinct()
            ->orderBy('pertemuan')
            ->pluck('pertemuan');

        $filterKelas = TeacherAttendance::query()
            ->join('classrooms', 'classrooms.id', '=', 'teacher_attendances.classroom_id')
            ->select('classrooms.nama_kelas')
            ->distinct()
            ->orderBy('classrooms.nama_kelas')
            ->pluck('classrooms.nama_kelas');

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $schedules = Schedule::with(['teacherSubject.subject', 'teacherSubject.classroom'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
        $classrooms = Classroom::orderBy('nama_kelas')->get();
        $subjects = Subject::orderBy('nama_mapel')->get();
        $academicYears = AcademicYear::orderByDesc('tahun_ajaran')->get();

        return view('admin.teacher_attendances.index', compact(
            'teacherAttendances',
            'teachers',
            'schedules',
            'classrooms',
            'subjects',
            'academicYears',
            'filterTahunAjarans',
            'filterTangggals',
            'filterGurus',
            'filterMapels',
            'filterPertemuans',
            'filterKelas',
            'isReadOnly'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'schedule_id' => 'required|exists:schedules,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'tanggal' => 'required|date',
            'pertemuan' => 'required|integer|min:1|max:255',
            'materi_pembelajaran' => 'nullable|string',
            'catatan_guru' => 'nullable|string',
            'status' => 'required|in:Draft,Selesai',
        ]);

        TeacherAttendance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data absensi oleh guru berhasil ditambahkan.'
        ]);
    }

    public function edit(TeacherAttendance $teacherAttendance)
    {
        return response()->json($teacherAttendance);
    }

    public function update(Request $request, TeacherAttendance $teacherAttendance)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'schedule_id' => 'required|exists:schedules,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'tanggal' => 'required|date',
            'pertemuan' => 'required|integer|min:1|max:255',
            'materi_pembelajaran' => 'nullable|string',
            'catatan_guru' => 'nullable|string',
            'status' => 'required|in:Draft,Selesai',
        ]);

        $teacherAttendance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data absensi oleh guru berhasil diperbarui.'
        ]);
    }

    public function destroy(TeacherAttendance $teacherAttendance)
    {
        $teacherAttendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data absensi oleh guru berhasil dihapus.'
        ]);
    }

    public function submitForSiswa(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'action' => 'required|in:Hadir,Tugas',
        ]);

        $user = auth()->user();

        $student = Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        $schedule->load(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom', 'teacherSubject.academicYear']);

        if ((int) $schedule->teacherSubject->classroom_id !== (int) $student->classroom_id) {
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

        if ($todayDayName === null || in_array($todayDayName, ['Sabtu', 'Minggu'], true) || $schedule->hari !== $todayDayName) {
            return redirect()->route('siswa.teacher-attendances.index')
                ->with('error', 'Aksi hanya bisa dilakukan untuk jadwal hari ini.');
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

        $status = $validated['action'] === 'Tugas' ? 'Izin' : 'Hadir';
        $keterangan = $validated['action'] === 'Tugas' ? 'Tugas' : null;

        AttendanceDetail::updateOrCreate(
            [
                'teacher_attendance_id' => $teacherAttendance->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'keterangan' => $keterangan,
                'jam_absen' => now()->format('H:i:s'),
            ]
        );

        return redirect()->route('siswa.teacher-attendances.index')
            ->with('success', 'Absensi berhasil disimpan dengan pilihan ' . $validated['action'] . '.');
    }
}
