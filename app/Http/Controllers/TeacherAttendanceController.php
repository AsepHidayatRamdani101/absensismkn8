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
use App\Models\TeacherLeaveRequest;
use App\Support\PklMode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeacherAttendanceController extends Controller
{
    public function siswaIndex(Request $request)
    {
        $user = Auth::user();

        $student = Student::with('classroom.major')
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        if (!$student->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum mengakses menu ini. Riwayat Absen tetap bisa diakses.');
        }

        $canSubmitTeacherAttendance = $student->canSubmitTeacherAttendance();

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
        $selectedDateInput = (string) $request->query('tanggal', $today->toDateString());

        try {
            $selectedDate = Carbon::parse($selectedDateInput)->startOfDay();
        } catch (\Throwable $e) {
            $selectedDate = $today->copy();
        }

        $selectedDayName = $dayMap[$selectedDate->dayOfWeekIso] ?? null;
        $isWeekendHoliday = in_array($selectedDayName, ['Sabtu', 'Minggu'], true);
        $isSelectedDateToday = $selectedDate->isSameDay($today);

        $schedules = collect();

        if ($selectedDayName !== null && !$isWeekendHoliday) {
            $schedulesQuery = Schedule::query()
                ->with(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom'])
                ->where('hari', $selectedDayName)
                ->whereHas('teacherSubject', function ($query) use ($student) {
                    $query->where('classroom_id', $student->classroom_id);
                })
                ->orderBy('jam_mulai');

            $schedules = PklMode::applyToScheduleQuery($schedulesQuery)->get();
        }

        $guruMapelOptions = $schedules
            ->map(function ($schedule) {
                $teacherSubject = $schedule->teacherSubject;

                return [
                    'id' => (int) ($teacherSubject->id ?? 0),
                    'label' => trim((string) ($teacherSubject->subject->nama_mapel ?? '-'))
                        . ' - ' . trim((string) ($teacherSubject->teacher->nama_lengkap ?? '-')),
                ];
            })
            ->filter(fn($option) => $option['id'] > 0)
            ->unique('id')
            ->sortBy('label')
            ->values();

        $selectedGuruMapelId = (int) $request->query('guru_mapel', 0);
        $allowedGuruMapelIds = $guruMapelOptions->pluck('id')->all();

        if ($selectedGuruMapelId !== 0 && !in_array($selectedGuruMapelId, $allowedGuruMapelIds, true)) {
            $selectedGuruMapelId = 0;
        }

        if ($selectedGuruMapelId !== 0) {
            $schedules = $schedules->filter(function ($schedule) use ($selectedGuruMapelId) {
                return (int) ($schedule->teacherSubject->id ?? 0) === $selectedGuruMapelId;
            })->values();
        }

        $teacherIds = $schedules
            ->pluck('teacherSubject.teacher_id')
            ->filter()
            ->unique()
            ->values();

        $todayLeaveRequestsByTeacher = collect();
        $approvedLeavesByTeacher = collect();

        if ($teacherIds->isNotEmpty()) {
            $todayLeaveRequestsByTeacher = TeacherLeaveRequest::query()
                ->whereIn('teacher_id', $teacherIds)
                ->whereDate('tanggal_mulai', '<=', $selectedDate->toDateString())
                ->whereDate('tanggal_selesai', '>=', $selectedDate->toDateString())
                ->latest('id')
                ->get()
                ->unique('teacher_id')
                ->keyBy('teacher_id');

            $approvedLeavesByTeacher = TeacherLeaveRequest::query()
                ->whereIn('teacher_id', $teacherIds)
                ->where('status_pengajuan', 'Disetujui')
                ->whereDate('tanggal_mulai', '<=', $selectedDate->toDateString())
                ->whereDate('tanggal_selesai', '>=', $selectedDate->toDateString())
                ->latest('id')
                ->get()
                ->keyBy('teacher_id');
        }

        $scheduleIds = $schedules->pluck('id');

        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $selectedDate->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $attendanceDetails = AttendanceDetail::query()
            ->where('student_id', $student->id)
            ->whereIn('teacher_attendance_id', $teacherAttendances->pluck('id'))
            ->get()
            ->keyBy('teacher_attendance_id');

        $scheduleRows = $schedules->map(function ($schedule) use ($teacherAttendances, $attendanceDetails, $todayLeaveRequestsByTeacher, $approvedLeavesByTeacher, $canSubmitTeacherAttendance, $isSelectedDateToday) {
            $teacherAttendance = $teacherAttendances->get($schedule->id);
            $detail = $teacherAttendance ? $attendanceDetails->get($teacherAttendance->id) : null;
            $todayLeaveRequest = $todayLeaveRequestsByTeacher->get((int) ($schedule->teacherSubject->teacher_id ?? 0));
            $approvedLeave = $approvedLeavesByTeacher->get((int) ($schedule->teacherSubject->teacher_id ?? 0));

            $selectedAction = null;
            if ($detail) {
                if ($detail->status === 'Hadir') {
                    $selectedAction = 'Hadir';
                } elseif ($detail->status === 'Izin' && strcasecmp((string) $detail->keterangan, 'Tugas') === 0) {
                    $selectedAction = 'Tugas';
                } elseif ($detail->status === 'Alpha') {
                    $selectedAction = 'Tanpa Keterangan';
                }
            }

            $guruStatus = $approvedLeave
                ? $approvedLeave->jenis_pengajuan
                : ($teacherAttendance?->kehadiran_guru ?? 'Hadir');

            $requiresApproval = (bool) $todayLeaveRequest;
            $isApprovedIzin = (bool) $approvedLeave && strcasecmp((string) $guruStatus, 'Izin') === 0;
            $canOfficerSubmitForRow = $canSubmitTeacherAttendance
                && $isSelectedDateToday
                && (!$requiresApproval || (bool) $approvedLeave)
                && !$isApprovedIzin;

            $submissionBlockReason = null;

            if (!$isSelectedDateToday) {
                $submissionBlockReason = 'Aksi hanya tersedia untuk tanggal hari ini';
            }

            if ($canSubmitTeacherAttendance && $isSelectedDateToday && $requiresApproval && !$approvedLeave) {
                $submissionBlockReason = 'Menunggu approve kurikulum';
            }

            if ($canSubmitTeacherAttendance && $isSelectedDateToday && $isApprovedIzin) {
                $submissionBlockReason = 'Guru izin sudah disetujui kurikulum';
            }

            return [
                'schedule' => $schedule,
                'teacher_attendance' => $teacherAttendance,
                'detail' => $detail,
                'selected_action' => $selectedAction,
                'today_leave_request' => $todayLeaveRequest,
                'approved_leave' => $approvedLeave,
                'guru_status' => $guruStatus,
                'can_officer_submit' => $canOfficerSubmitForRow,
                'submission_block_reason' => $submissionBlockReason,
                'is_approved_izin' => $isApprovedIzin,
            ];
        });

        return view('siswa.teacher_attendances.index', [
            'student' => $student,
            'today' => $selectedDate,
            'todayDayName' => $selectedDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'scheduleRows' => $scheduleRows,
            'canSubmitTeacherAttendance' => $canSubmitTeacherAttendance,
            'selectedDate' => $selectedDate->toDateString(),
            'selectedGuruMapelId' => $selectedGuruMapelId,
            'guruMapelOptions' => $guruMapelOptions,
            'isSelectedDateToday' => $isSelectedDateToday,
        ]);
    }

    public function index()
    {
        $authUser = Auth::user();
        $isReadOnly = $authUser && method_exists($authUser, 'hasRole')
            ? $authUser->hasRole('siswa')
            : false;

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
            'action' => 'required|in:Hadir,Tugas,Tanpa Keterangan',
            'foto_guru' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto_guru_kamera' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = Auth::user();

        $student = Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        if (!$student->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum melakukan absensi. Riwayat Absen tetap bisa diakses.');
        }

        if (!$student->canSubmitTeacherAttendance()) {
            return redirect()->route('siswa.teacher-attendances.index')
                ->with('error', 'Hanya siswa dengan jabatan KM, Sekretaris, atau Bendahara yang dapat mengabsen guru.');
        }

        $schedule->load(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom', 'teacherSubject.academicYear']);

        if ((int) $schedule->teacherSubject->classroom_id !== (int) $student->classroom_id) {
            abort(403);
        }

        if (PklMode::excludesClassroomLevel($schedule->teacherSubject->classroom->tingkat ?? null)) {
            return redirect()->route('siswa.teacher-attendances.index')
                ->with('error', 'Mode PKL aktif. Absensi guru untuk kelas XII dinonaktifkan sementara.');
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

        $todayLeaveRequest = TeacherLeaveRequest::query()
            ->where('teacher_id', $schedule->teacherSubject->teacher_id)
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        $approvedLeave = TeacherLeaveRequest::query()
            ->where('teacher_id', $schedule->teacherSubject->teacher_id)
            ->where('status_pengajuan', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        if ($todayLeaveRequest && !$approvedLeave) {
            return redirect()->route('siswa.teacher-attendances.index')
                ->with('error', 'Guru sudah mengajukan izin, tetapi belum disetujui kurikulum. Absensi belum bisa dilakukan.');
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
                'kehadiran_guru' => $approvedLeave?->jenis_pengajuan ?? 'Hadir',
                'tugas_file_path' => $approvedLeave?->lampiran_tugas_path,
                'tugas_deskripsi' => $approvedLeave?->deskripsi_tugas,
                'foto_guru_path' => null,
                'status' => 'Draft',
            ]);
        } elseif ($approvedLeave) {
            $teacherAttendance->update([
                'kehadiran_guru' => $approvedLeave->jenis_pengajuan,
                'tugas_file_path' => $approvedLeave->lampiran_tugas_path,
                'tugas_deskripsi' => $approvedLeave->deskripsi_tugas,
            ]);
        }

        $uploadedPhoto = $request->file('foto_guru') ?? $request->file('foto_guru_kamera');

        if ($uploadedPhoto) {
            if (!empty($teacherAttendance->foto_guru_path)) {
                Storage::disk('public')->delete($teacherAttendance->foto_guru_path);
            }

            $teacherAttendance->foto_guru_path = $uploadedPhoto->store('siswa-foto-guru', 'public');
            $teacherAttendance->save();
        }

        $status = match ($validated['action']) {
            'Tugas' => 'Izin',
            'Tanpa Keterangan' => 'Alpha',
            default => 'Hadir',
        };

        $keterangan = match ($validated['action']) {
            'Tugas' => 'Tugas',
            'Tanpa Keterangan' => 'Tanpa Keterangan',
            default => null,
        };

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
