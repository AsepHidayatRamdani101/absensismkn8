<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\AttendanceDevice;
use App\Models\Classroom;
use App\Models\Holiday;
use App\Models\Major;
use App\Models\OfficerAttendancePermit;
use App\Models\PjjAttendance;
use App\Models\Schedule;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentLeaveRequest;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherSubject;
use App\Support\PklMode;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !method_exists($user, 'hasRole')) {
            abort(403);
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('kurikulum')) {
            return redirect()->route('kurikulum.dashboard');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('kesiswaan')) {
            return redirect()->route('kesiswaan.dashboard');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('guru')) {
            return redirect()->route('guru.dashboard');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('siswa')) {
            return redirect()->route('siswa.dashboard');
        }

        abort(403);
    }

    public function admin(Request $request)
    {
        $mode = $this->resolveDisplayMode($request);
        $today = Carbon::today();
        $weekStart = Carbon::today()->startOfWeek();
        $weekEnd = Carbon::today()->endOfWeek();
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        $cacheKey = 'dashboard:admin:metrics:' . $mode . ':' . now()->format('YmdHi');
        $metrics = Cache::remember($cacheKey, now()->addMinutes(2), function () use (
            $mode,
            $today,
            $weekStart,
            $weekEnd,
            $monthStart,
            $monthEnd
        ) {
            // Mode PKL aktif: siswa kelas XII yang sedang PKL tidak dihitung dalam total.
            $totalStudentsQuery = Student::query();
            if (PklMode::isActive()) {
                $totalStudentsQuery->whereDoesntHave('classroom', fn($q) => $q->where('tingkat', 'XII'));
            }
            $totalStudents = $totalStudentsQuery->count();
            $totalTeachers = Teacher::count();
            $totalClassrooms = Classroom::count();
            $totalMajors = Major::count();
            $totalDevices = AttendanceDevice::count();

            $todayTeacherAttendances = TeacherAttendance::whereDate('tanggal', $today)->count();
            $todayStudentAttendanceByTeacher = AttendanceDetail::whereHas('teacherAttendance', function ($query) use ($today) {
                $query->whereDate('tanggal', $today);
            })->count();
            $todayStudentAttendanceIoT = Attendance::whereDate('tanggal', $today)->count();

            $studentStatusRows = AttendanceDetail::query()
                ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
                ->join('students', 'students.id', '=', 'attendance_details.student_id')
                ->leftJoin('classrooms', 'classrooms.id', '=', 'students.classroom_id')
                ->whereDate('teacher_attendances.tanggal', $today)
                ->whereIn('attendance_details.status', ['Hadir', 'Izin', 'Sakit', 'Dispen', 'Alpha', 'Alpa', 'Bolos', 'Terlambat'])
                ->selectRaw("CASE WHEN attendance_details.status IN ('Alpha', 'Alpa') THEN 'Alpa' ELSE attendance_details.status END as status")
                ->addSelect(
                    'students.id as student_id',
                    'students.nama_lengkap as name',
                    'students.nis',
                    'classrooms.nama_kelas as classroom'
                )
                ->distinct()
                ->orderBy('students.nama_lengkap')
                ->get();

            // Hari PJJ: kartu Hadir/Terlambat diambil dari absensi selfie PJJ
            // agar jumlahnya sama dengan dashboard PJJ.
            if (SchoolCalendar::isPjj($today)) {
                $pjjStatusRows = PjjAttendance::query()
                    ->join('students', 'students.id', '=', 'pjj_attendances.student_id')
                    ->leftJoin('classrooms', 'classrooms.id', '=', 'students.classroom_id')
                    ->whereDate('pjj_attendances.attendance_date', $today)
                    ->selectRaw("CASE WHEN pjj_attendances.status = 'Terlambat' THEN 'Terlambat' ELSE 'Hadir' END as status")
                    ->addSelect(
                        'students.id as student_id',
                        'students.nama_lengkap as name',
                        'students.nis',
                        'classrooms.nama_kelas as classroom'
                    )
                    ->distinct()
                    ->orderBy('students.nama_lengkap')
                    ->get();

                $pjjStudentIds = $pjjStatusRows->pluck('student_id')->flip();
                $studentStatusRows = $studentStatusRows
                    ->reject(fn($row) => in_array($row->status, ['Hadir', 'Terlambat'], true)
                        || isset($pjjStudentIds[$row->student_id]))
                    ->concat($pjjStatusRows);
            }

            $statusDefinitions = [
                'Hadir' => ['label' => 'Hadir', 'color' => 'success'],
                'Izin' => ['label' => 'Izin', 'color' => 'info'],
                'Sakit' => ['label' => 'Sakit', 'color' => 'warning'],
                'Alpa' => ['label' => 'Alpa', 'color' => 'danger'],
                'Bolos' => ['label' => 'Bolos', 'color' => 'dark'],
                'Terlambat' => ['label' => 'Terlambat', 'color' => 'indigo'],
                'Dispen' => ['label' => 'Dispen', 'color' => 'secondary'],
            ];
            $studentStatusCards = collect($statusDefinitions)->map(function ($definition, $status) use ($studentStatusRows) {
                $students = $studentStatusRows
                    ->where('status', $status)
                    ->unique('student_id')
                    ->map(fn($student) => [
                        'name' => (string) $student->name,
                        'nis' => (string) ($student->nis ?? '-'),
                        'classroom' => (string) ($student->classroom ?? '-'),
                    ])
                    ->values()
                    ->all();

                return [
                    'key' => $status,
                    'label' => $definition['label'],
                    'color' => $definition['color'],
                    'count' => count($students),
                    'students' => $students,
                ];
            })->values()->all();

            $teacherPresentToday = $this->countPresentTeachers($today, $today);
            $teacherPresentThisWeek = $this->countPresentTeachers($weekStart, $weekEnd);
            $teacherPresentThisMonth = $this->countPresentTeachers($monthStart, $monthEnd);

            $studentPresentToday = $this->countPresentStudents($today, $today);
            $studentPresentThisWeek = $this->countPresentStudents($weekStart, $weekEnd);
            $studentPresentThisMonth = $this->countPresentStudents($monthStart, $monthEnd);

            $teacherPresencePercent = $totalTeachers > 0
                ? round(($teacherPresentToday / $totalTeachers) * 100, 2)
                : 0;

            $studentPresencePercent = $totalStudents > 0
                ? round(($studentPresentToday / $totalStudents) * 100, 2)
                : 0;

            $teacherPresencePercentWeek = $totalTeachers > 0
                ? round(($teacherPresentThisWeek / $totalTeachers) * 100, 2)
                : 0;

            $teacherPresencePercentMonth = $totalTeachers > 0
                ? round(($teacherPresentThisMonth / $totalTeachers) * 100, 2)
                : 0;

            $studentPresencePercentWeek = $totalStudents > 0
                ? round(($studentPresentThisWeek / $totalStudents) * 100, 2)
                : 0;

            $studentPresencePercentMonth = $totalStudents > 0
                ? round(($studentPresentThisMonth / $totalStudents) * 100, 2)
                : 0;

            $metrics = compact(
                'totalStudents',
                'totalTeachers',
                'totalClassrooms',
                'totalMajors',
                'totalDevices',
                'todayTeacherAttendances',
                'todayStudentAttendanceByTeacher',
                'todayStudentAttendanceIoT',
                'studentStatusCards',
                'teacherPresentToday',
                'teacherPresentThisWeek',
                'teacherPresentThisMonth',
                'studentPresentToday',
                'studentPresentThisWeek',
                'studentPresentThisMonth',
                'teacherPresencePercent',
                'studentPresencePercent',
                'teacherPresencePercentWeek',
                'teacherPresencePercentMonth',
                'studentPresencePercentWeek',
                'studentPresencePercentMonth'
            );

            if ($mode === 'detail') {
                $dailyTeacherCounts = TeacherAttendance::query()
                    ->selectRaw('DATE(tanggal) as tanggal')
                    ->selectRaw('COUNT(DISTINCT teacher_id) as total_guru_hadir')
                    ->where('kehadiran_guru', 'Hadir')
                    ->whereBetween('tanggal', [Carbon::today()->subDays(6)->toDateString(), $today->toDateString()])
                    ->groupBy('tanggal')
                    ->orderBy('tanggal')
                    ->get();

                $dailySchoolAttendance = Attendance::query()
                    ->selectRaw('DATE(tanggal) as tanggal, student_id')
                    ->whereIn('status', ['Hadir', 'Terlambat', 'Dispen'])
                    ->whereBetween('tanggal', [Carbon::today()->subDays(6)->toDateString(), $today->toDateString()]);

                $dailyClassAttendance = AttendanceDetail::query()
                    ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
                    ->selectRaw('DATE(teacher_attendances.tanggal) as tanggal, attendance_details.student_id')
                    ->whereIn('attendance_details.status', ['Hadir', 'Terlambat', 'Dispen'])
                    ->whereBetween('teacher_attendances.tanggal', [Carbon::today()->subDays(6)->toDateString(), $today->toDateString()]);

                $dailyPjjAttendance = PjjAttendance::query()
                    ->selectRaw('DATE(attendance_date) as tanggal, student_id')
                    ->whereBetween('attendance_date', [Carbon::today()->subDays(6)->toDateString(), $today->toDateString()]);

                $dailyStudentCounts = DB::query()
                    ->fromSub($dailySchoolAttendance->union($dailyClassAttendance)->union($dailyPjjAttendance), 'present_students')
                    ->select('tanggal')
                    ->selectRaw('COUNT(DISTINCT student_id) as total_siswa_hadir')
                    ->groupBy('tanggal')
                    ->get()
                    ->pluck('total_siswa_hadir', 'tanggal');

                $teacherCountsByDate = $dailyTeacherCounts->pluck('total_guru_hadir', 'tanggal');
                $dailyDates = $teacherCountsByDate->keys()
                    ->merge($dailyStudentCounts->keys())
                    ->unique()
                    ->sort()
                    ->values();

                $dailyLabels = $dailyDates->map(fn($date) => Carbon::parse($date)->format('d M'));
                $dailyTeacherCounts = $dailyDates->map(fn($date) => (int) ($teacherCountsByDate[$date] ?? 0))->values();
                $dailyStudentPresentCounts = $dailyDates->map(fn($date) => (int) ($dailyStudentCounts[$date] ?? 0))->values();

                $metrics['dailyLabels'] = $dailyLabels;
                $metrics['dailyTeacherCounts'] = $dailyTeacherCounts;
                $metrics['dailyStudentPresentCounts'] = $dailyStudentPresentCounts;
                $metrics['statusLabels'] = collect($studentStatusCards)->pluck('label');
                $metrics['statusData'] = collect($studentStatusCards)->pluck('count');
            }

            return $metrics;
        });

        $effectiveDayCounts = SchoolCalendar::effectiveDayCounts($monthStart, $monthEnd);

        return view('admin.dashboard', array_merge($metrics, $effectiveDayCounts, [
            'mode' => $mode,
            'dashboardMonthLabel' => $monthStart->copy()->locale('id')->translatedFormat('F Y'),
        ]));
    }

    private function countPresentTeachers(Carbon $startDate, Carbon $endDate): int
    {
        return (int) TeacherAttendance::query()
            ->where('kehadiran_guru', 'Hadir')
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->distinct()
            ->count('teacher_id');
    }

    private function countPresentStudents(Carbon $startDate, Carbon $endDate): int
    {
        // Hari PJJ: kehadiran diambil dari absensi selfie PJJ agar angkanya sama dengan dashboard PJJ.
        if ($startDate->isSameDay($endDate) && SchoolCalendar::isPjj($startDate)) {
            return (int) PjjAttendance::query()
                ->whereDate('attendance_date', $startDate->toDateString())
                ->distinct()
                ->count('student_id');
        }

        $schoolAttendanceIds = Attendance::query()
            ->select('student_id')
            ->whereIn('status', ['Hadir', 'Terlambat', 'Dispen'])
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        $classAttendanceIds = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->select('attendance_details.student_id')
            ->whereIn('attendance_details.status', ['Hadir', 'Terlambat', 'Dispen'])
            ->whereBetween('teacher_attendances.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        // Sertakan absensi PJJ (baris hanya ada pada tanggal PJJ) agar rekap mingguan/bulanan ikut menghitungnya.
        $pjjAttendanceIds = PjjAttendance::query()
            ->select('student_id')
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);

        return (int) DB::query()
            ->fromSub($schoolAttendanceIds->union($classAttendanceIds)->union($pjjAttendanceIds), 'present_students')
            ->distinct()
            ->count('student_id');
    }

    public function siswa(Request $request)
    {
        $mode = $this->resolveDisplayMode($request);
        $user = Auth::user();
        $today = Carbon::today();
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        $student = Student::with('classroom.major')
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if ($student) {
            $student->ensureQrToken();
        }

        $statusCountsMonth = [
            'Hadir' => 0,
            'Sakit' => 0,
            'Izin' => 0,
            'Alpa' => 0,
        ];

        $statusCountsTotal = [
            'Hadir' => 0,
            'Sakit' => 0,
            'Izin' => 0,
            'Alpa' => 0,
        ];

        $statusPercentsMonth = [
            'Hadir' => 0,
            'Sakit' => 0,
            'Izin' => 0,
            'Alpa' => 0,
        ];

        $totalRecordsMonth = 0;
        $totalRecordsAll = 0;
        $attendanceDaysMonth = 0;
        $latestAttendance = null;

        if ($student) {
            $baseQuery = AttendanceDetail::query()
                ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
                ->where('attendance_details.student_id', $student->id);

            $totalRecordsAll = (clone $baseQuery)->count();

            $monthQuery = (clone $baseQuery)->whereBetween('teacher_attendances.tanggal', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ]);

            $totalRecordsMonth = (clone $monthQuery)->count();

            $statusCountsMonth = [
                'Hadir' => (int) (clone $monthQuery)->where('attendance_details.status', 'Hadir')->count(),
                'Sakit' => (int) (clone $monthQuery)->where('attendance_details.status', 'Sakit')->count(),
                'Izin' => (int) (clone $monthQuery)->where('attendance_details.status', 'Izin')->count(),
                'Alpa' => (int) (clone $monthQuery)->whereIn('attendance_details.status', ['Alpha', 'Alpa'])->count(),
            ];

            $statusCountsTotal = [
                'Hadir' => (int) (clone $baseQuery)->where('attendance_details.status', 'Hadir')->count(),
                'Sakit' => (int) (clone $baseQuery)->where('attendance_details.status', 'Sakit')->count(),
                'Izin' => (int) (clone $baseQuery)->where('attendance_details.status', 'Izin')->count(),
                'Alpa' => (int) (clone $baseQuery)->whereIn('attendance_details.status', ['Alpha', 'Alpa'])->count(),
            ];

            $statusPercentsMonth = [
                'Hadir' => $this->percentage($statusCountsMonth['Hadir'], $totalRecordsMonth),
                'Sakit' => $this->percentage($statusCountsMonth['Sakit'], $totalRecordsMonth),
                'Izin' => $this->percentage($statusCountsMonth['Izin'], $totalRecordsMonth),
                'Alpa' => $this->percentage($statusCountsMonth['Alpa'], $totalRecordsMonth),
            ];

            $attendanceDaysMonth = (int) (clone $monthQuery)
                ->distinct('teacher_attendances.tanggal')
                ->count('teacher_attendances.tanggal');

            $latestAttendance = AttendanceDetail::query()
                ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
                ->where('attendance_details.student_id', $student->id)
                ->orderByDesc('teacher_attendances.tanggal')
                ->orderByDesc('attendance_details.id')
                ->select('attendance_details.*')
                ->with(['teacherAttendance.subject', 'teacherAttendance.teacher'])
                ->first();
        }

        $schoolSetting = Cache::remember('school-settings:first', now()->addMinutes(30), function () {
            return SchoolSetting::query()->first();
        });

        return view('siswa.dashboard', compact(
            'student',
            'today',
            'monthStart',
            'monthEnd',
            'statusCountsMonth',
            'statusCountsTotal',
            'statusPercentsMonth',
            'totalRecordsMonth',
            'totalRecordsAll',
            'attendanceDaysMonth',
            'latestAttendance',
            'schoolSetting',
            'mode'
        ));
    }

    public function guru(Request $request)
    {
        $mode = $this->resolveDisplayMode($request);
        $user = Auth::user();
        $today = Carbon::today();
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        $statusCountsMonth = [
            'Hadir' => 0,
            'Selesai' => 0,
            'Draft' => 0,
            'Belum Absen' => 0,
        ];

        $statusCountsTotal = [
            'Hadir' => 0,
            'Selesai' => 0,
            'Draft' => 0,
            'Belum Absen' => 0,
        ];

        $statusPercentsMonth = [
            'Hadir' => 0,
            'Selesai' => 0,
            'Draft' => 0,
            'Belum Absen' => 0,
        ];

        $totalRecordsMonth = 0;
        $totalRecordsAll = 0;
        $attendanceDaysMonth = 0;
        $latestTeacherAttendance = null;
        $targetTeachingMonth = 0;
        $teachingClassCount = 0;
        $teachingSubjectCount = 0;
        $todayScheduleCount = 0;
        $todayAttendanceCount = 0;
        $pendingStudentLeaveRequests = 0;
        $isWaliKelas = false;

        if ($teacher) {
            $baseQuery = TeacherAttendance::query()->where('teacher_id', $teacher->id);
            $monthQuery = (clone $baseQuery)->whereBetween('tanggal', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ]);

            $totalRecordsAll = (clone $baseQuery)->count();
            $totalRecordsMonth = (clone $monthQuery)->count();

            $targetTeachingMonth = $this->countExpectedTeacherSessions($teacher->id, $monthStart->copy(), $monthEnd->copy());
            $belumAbsenMonth = max($targetTeachingMonth - $totalRecordsMonth, 0);

            $statusCountsMonth = [
                'Hadir' => $totalRecordsMonth,
                'Selesai' => (int) (clone $monthQuery)->where('status', 'Selesai')->count(),
                'Draft' => (int) (clone $monthQuery)->where('status', 'Draft')->count(),
                'Belum Absen' => $belumAbsenMonth,
            ];

            $statusCountsTotal = [
                'Hadir' => $totalRecordsAll,
                'Selesai' => (int) (clone $baseQuery)->where('status', 'Selesai')->count(),
                'Draft' => (int) (clone $baseQuery)->where('status', 'Draft')->count(),
                'Belum Absen' => $belumAbsenMonth,
            ];

            $statusPercentsMonth = [
                'Hadir' => $this->percentage($statusCountsMonth['Hadir'], $targetTeachingMonth),
                'Selesai' => $this->percentage($statusCountsMonth['Selesai'], max($totalRecordsMonth, 1)),
                'Draft' => $this->percentage($statusCountsMonth['Draft'], max($totalRecordsMonth, 1)),
                'Belum Absen' => $this->percentage($statusCountsMonth['Belum Absen'], $targetTeachingMonth),
            ];

            $attendanceDaysMonth = (int) (clone $monthQuery)
                ->distinct('tanggal')
                ->count('tanggal');

            $latestTeacherAttendance = TeacherAttendance::query()
                ->with(['subject', 'classroom', 'academicYear'])
                ->where('teacher_id', $teacher->id)
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->first();

            $teachingClassCount = (int) TeacherSubject::query()
                ->where('teacher_id', $teacher->id)
                ->distinct('classroom_id')
                ->count('classroom_id');

            $teachingSubjectCount = (int) TeacherSubject::query()
                ->where('teacher_id', $teacher->id)
                ->distinct('subject_id')
                ->count('subject_id');

            $dayMap = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ];

            $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;

            $todayScheduleCount = $todayDayName
                ? (int) PklMode::applyToScheduleQuery(Schedule::query()
                    ->where('hari', $todayDayName)
                    ->whereHas('teacherSubject', function ($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    })
                    )->count()
                : 0;

            $todayAttendanceCount = (int) TeacherAttendance::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $today->toDateString())
                ->count();

            $isWaliKelas = (bool) ($teacher->is_wali_kelas && $teacher->wali_classroom_id);

            if ($isWaliKelas) {
                $pendingStudentLeaveRequests = (int) StudentLeaveRequest::query()
                    ->where('status_pengajuan', 'Menunggu')
                    ->whereHas('student', function ($query) use ($teacher) {
                        $query->where('classroom_id', $teacher->wali_classroom_id);
                    })
                    ->count();
            }
        }

        $schoolSetting = Cache::remember('school-settings:first', now()->addMinutes(30), function () {
            return SchoolSetting::query()->first();
        });
        $isPklModeActive = PklMode::isActive();

        return view('guru.dashboard', compact(
            'teacher',
            'today',
            'monthStart',
            'monthEnd',
            'statusCountsMonth',
            'statusCountsTotal',
            'statusPercentsMonth',
            'totalRecordsMonth',
            'totalRecordsAll',
            'attendanceDaysMonth',
            'latestTeacherAttendance',
            'targetTeachingMonth',
            'teachingClassCount',
            'teachingSubjectCount',
            'todayScheduleCount',
            'todayAttendanceCount',
            'pendingStudentLeaveRequests',
            'isWaliKelas',
            'schoolSetting',
            'isPklModeActive',
            'mode'
        ));
    }

    public function kurikulum(Request $request)
    {
        $mode = $this->resolveDisplayMode($request);
        $pendingGuruLeaveRequests = class_exists(\App\Models\TeacherLeaveRequest::class)
            ? (int) \App\Models\TeacherLeaveRequest::query()->where('status_pengajuan', 'Menunggu')->count()
            : 0;

        $pendingOfficerAttendancePermits = class_exists(OfficerAttendancePermit::class)
            ? (int) OfficerAttendancePermit::query()->where('status_pengajuan', 'Menunggu')->count()
            : 0;

        $pendingStudentLeaveRequests = class_exists(\App\Models\StudentLeaveRequest::class)
            ? (int) \App\Models\StudentLeaveRequest::query()->where('status_pengajuan', 'Menunggu')->count()
            : 0;

        $totalTeacherAttendancesToday = (int) TeacherAttendance::query()
            ->whereDate('tanggal', Carbon::today()->toDateString())
            ->count();

        $isPklModeActive = PklMode::isActive();

        return view('kurikulum.dashboard', compact(
            'pendingGuruLeaveRequests',
            'pendingOfficerAttendancePermits',
            'pendingStudentLeaveRequests',
            'totalTeacherAttendancesToday',
            'isPklModeActive',
            'mode'
        ));
    }

    private function resolveDisplayMode(Request $request): string
    {
        return $request->query('mode') === 'detail' ? 'detail' : 'ringkas';
    }

    private function percentage(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }

    private function countExpectedTeacherSessions(int $teacherId, Carbon $startDate, Carbon $endDate): int
    {
        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $schedulePerDayQuery = Schedule::query()
            ->join('teacher_subjects', 'teacher_subjects.id', '=', 'schedules.teacher_subject_id')
            ->where('teacher_subjects.teacher_id', $teacherId)
            ->select('schedules.hari', DB::raw('COUNT(*) as total_jadwal'))
            ->groupBy('schedules.hari');

        $schedulePerDay = PklMode::applyToScheduleQuery($schedulePerDayQuery)
            ->pluck('total_jadwal', 'schedules.hari');
        $holidayDates = Holiday::query()
            ->whereDate('tanggal', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query->whereNull('tanggal_akhir')
                    ->whereDate('tanggal', '>=', $startDate->toDateString())
                    ->orWhereDate('tanggal_akhir', '>=', $startDate->toDateString());
            })
            ->where('is_pjj', false)
            ->get(['tanggal', 'tanggal_akhir', 'is_pjj'])
            ->flatMap(function (Holiday $holiday) use ($startDate, $endDate) {
                if ($holiday->is_pjj) {
                    return [];
                }

                $rangeStart = Carbon::parse($holiday->tanggal)->max($startDate);
                $rangeEnd = Carbon::parse($holiday->tanggal_akhir ?? $holiday->tanggal)->min($endDate);
                $dates = [];

                for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
                    $dates[] = $date->toDateString();
                }

                return $dates;
            })
            ->flip();

        $expected = 0;
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $dayName = $dayMap[$cursor->dayOfWeekIso] ?? null;
            if (
                !$cursor->isWeekend()
                && $dayName !== null
                && !$holidayDates->has($cursor->toDateString())
            ) {
                $expected += (int) ($schedulePerDay[$dayName] ?? 0);
            }

            $cursor->addDay();
        }

        return $expected;
    }
}
