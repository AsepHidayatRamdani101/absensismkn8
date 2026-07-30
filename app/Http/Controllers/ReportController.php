<?php

namespace App\Http\Controllers;

use App\Exports\GuruMapelRecapExport;
use App\Exports\GuruWaliKelasRecapExport;
use App\Exports\StudentLeaveReportExport;
use App\Exports\StudentAttendanceReportExport;
use App\Exports\TeacherAgendaReportExport;
use App\Exports\TeacherAttendanceReportExport;
use App\Exports\TeacherLeaveReportExport;
use App\Models\AttendanceDetail;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Student;
use App\Models\StudentLeaveRequest;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherLeaveRequest;
use App\Models\TeacherSubject;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function teacherAttendance(Request $request)
    {
        $rows = $this->buildTeacherAttendanceQuery($request)->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $majors = Major::orderBy('nama_jurusan')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

        return view('admin.reports.teacher-attendance', [
            'rows' => $rows,
            'teachers' => $teachers,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function studentAttendance(Request $request)
    {
        $rows = $this->buildStudentAttendanceQuery($request)->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $students = Student::with('classroom')->orderBy('nama_lengkap')->get();
        $majors = Major::orderBy('nama_jurusan')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

        return view('admin.reports.student-attendance', [
            'rows' => $rows,
            'teachers' => $teachers,
            'students' => $students,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function guruWaliKelasRecap(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::with('waliClassroom.major')
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher || !$teacher->is_wali_kelas || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $payload = $this->buildGuruWaliKelasRecapPayload($teacher, $request);

        return view('guru.rekap_siswa_wali_kelas.index', $payload);
    }

    public function guruWaliKelasRecapPdf(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::with('waliClassroom.major')
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher || !$teacher->is_wali_kelas || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $payload = $this->buildGuruWaliKelasRecapPayload($teacher, $request);

        $pdf = Pdf::loadView('guru.reports.pdf.wali-kelas-recap', $payload)
            ->setPaper('a4', 'landscape');

        return $pdf->download('rekap-siswa-wali-kelas.pdf');
    }

    public function guruWaliKelasRecapExcel(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::with('waliClassroom.major')
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher || !$teacher->is_wali_kelas || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $payload = $this->buildGuruWaliKelasRecapPayload($teacher, $request);

        return Excel::download(
            new GuruWaliKelasRecapExport($payload['rows'], $payload['totals'], $payload['periodLabel'], $teacher, $teacher->waliClassroom),
            'rekap-siswa-wali-kelas.xlsx'
        );
    }

    public function guruWaliKelasRecapDetail(Request $request, Student $student)
    {
        $user = auth()->user();

        $teacher = Teacher::with('waliClassroom.major')
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher || !$teacher->is_wali_kelas || !$teacher->wali_classroom_id) {
            abort(403);
        }

        // Verify student belongs to teacher's wali class
        if ($student->classroom_id !== $teacher->wali_classroom_id) {
            abort(403);
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);

        // Get all attendance details for this student in the period
        $attendanceQuery = AttendanceDetail::query()
            ->with(['teacherAttendance.subject', 'teacherAttendance.teacher'])
            ->where('student_id', $student->id)
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->orderByDesc('teacher_attendances.tanggal')
            ->orderByDesc('attendance_details.id');

        if ($startDate && $endDate) {
            $attendanceQuery->whereBetween('teacher_attendances.tanggal', [$startDate, $endDate]);
        }

        $attendances = $attendanceQuery
            ->select('attendance_details.*')
            ->get();

        return response()->json([
            'success' => true,
            'student' => [
                'nama' => $student->nama_lengkap,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
            ],
            'attendances' => $attendances->map(function ($attendance) {
                return [
                    'tanggal' => $attendance->teacherAttendance->tanggal->format('Y-m-d'),
                    'hari' => $attendance->teacherAttendance->tanggal->isoFormat('DDDD'),
                    'mapel' => $attendance->teacherAttendance->subject->nama_mapel ?? '-',
                    'guru' => $attendance->teacherAttendance->teacher->nama_lengkap ?? '-',
                    'status' => $attendance->status,
                    'keterangan' => $attendance->keterangan ?? '-',
                ];
            }),
            'period' => $this->buildPeriodLabel($request),
        ]);
    }

    private function buildGuruWaliKelasRecapPayload(Teacher $teacher, Request $request): array
    {
        $teacher->loadMissing('waliClassroom.major');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $students = Student::query()
            ->where('classroom_id', $teacher->wali_classroom_id)
            ->orderBy('nama_lengkap')
            ->get();

        // Get all attendance details for the period
        $attendanceQuery = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->select('attendance_details.*', 'teacher_attendances.tanggal')
            ->whereIn('attendance_details.student_id', $students->pluck('id'));

        if ($startDate && $endDate) {
            $attendanceQuery->whereBetween('teacher_attendances.tanggal', [$startDate, $endDate]);
        }

        $attendances = $attendanceQuery->get();

        // Build rows with daily average calculation
        $rows = $students->map(function (Student $student) use ($attendances) {
            $studentAttendances = $attendances->where('student_id', $student->id);

            if ($studentAttendances->isEmpty()) {
                return [
                    'student' => $student,
                    'hadir' => 0,
                    'sakit' => 0,
                    'izin' => 0,
                    'alpa' => 0,
                    'total' => 0,
                    'persen_hadir' => 0,
                ];
            }

            // Group by date to calculate daily averages
            $attendancesByDate = $studentAttendances->groupBy('tanggal');
            $dailyAverages = [];
            $allStatuses = [];

            foreach ($attendancesByDate as $date => $dateAttendances) {
                $totalSubjects = $dateAttendances->count();
                $score = 0;

                foreach ($dateAttendances as $attendance) {
                    // Hadir dan Terlambat = 1, else = 0
                    if (in_array($attendance['status'], ['Hadir', 'Terlambat'])) {
                        $score += 1;
                    }

                    // Accumulate all statuses for counting
                    $status = $attendance['status'];
                    if (!isset($allStatuses[$status])) {
                        $allStatuses[$status] = 0;
                    }
                    $allStatuses[$status] += 1;
                }

                // Daily average for this date
                $dailyAverages[] = $score / $totalSubjects;
            }

            // Calculate totals from daily averages
            $totalAveraged = array_sum($dailyAverages);
            $hadir = isset($allStatuses['Hadir']) ? (int) $allStatuses['Hadir'] : 0;
            $terlambat = isset($allStatuses['Terlambat']) ? (int) $allStatuses['Terlambat'] : 0;
            $sakit = isset($allStatuses['Sakit']) ? (int) $allStatuses['Sakit'] : 0;
            $izin = isset($allStatuses['Izin']) ? (int) $allStatuses['Izin'] : 0;
            $alpa = isset($allStatuses['Alpa']) || isset($allStatuses['Alpha']) ?
                ((int) ($allStatuses['Alpa'] ?? 0) + (int) ($allStatuses['Alpha'] ?? 0)) : 0;

            $totalRecords = (int) $studentAttendances->count();
            $persenHadir = $totalRecords > 0 ? round((($hadir + $terlambat) / $totalRecords) * 100, 2) : 0;

            return [
                'student' => $student,
                'hadir' => round($totalAveraged, 2),
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => round($totalAveraged, 2),
                'persen_hadir' => $persenHadir,
            ];
        });

        $totals = [
            'hadir' => round($rows->sum('hadir'), 2),
            'sakit' => (int) $rows->sum('sakit'),
            'izin' => (int) $rows->sum('izin'),
            'alpa' => (int) $rows->sum('alpa'),
            'total' => round($rows->sum('total'), 2),
        ];

        return [
            'teacher' => $teacher,
            'classroom' => $teacher->waliClassroom,
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ];
    }

    public function guruMapelRecap(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $payload = $this->buildGuruMapelRecapPayload($teacher, $request);

        return view('guru.rekap_guru_mapel.index', $payload);
    }

    public function guruMapelRecapPdf(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            abort(403);
        }

        $payload = $this->buildGuruMapelRecapPayload($teacher, $request);

        $pdf = Pdf::loadView('guru.reports.pdf.mapel-recap', $payload)
            ->setPaper('a4', 'landscape');

        return $pdf->download('rekap-guru-mapel.pdf');
    }

    public function guruMapelRecapExcel(Request $request)
    {
        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            abort(403);
        }

        $payload = $this->buildGuruMapelRecapPayload($teacher, $request);

        return Excel::download(
            new GuruMapelRecapExport($payload['rows'], $payload['totals'], $payload['periodLabel'], $teacher),
            'rekap-guru-mapel.xlsx'
        );
    }

    private function buildGuruMapelRecapPayload(Teacher $teacher, Request $request): array
    {

        $teacherSubjects = TeacherSubject::query()
            ->with(['subject', 'classroom'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $subjectOptions = $teacherSubjects
            ->map(fn($item) => $item->subject)
            ->filter()
            ->unique('id')
            ->sortBy('nama_mapel')
            ->values();

        $classroomOptions = $teacherSubjects
            ->map(fn($item) => $item->classroom)
            ->filter()
            ->unique('id')
            ->sortBy('nama_kelas')
            ->values();

        $allowedSubjectIds = $subjectOptions->pluck('id')->map(fn($id) => (int) $id)->values()->all();
        $allowedClassroomIds = $classroomOptions->pluck('id')->map(fn($id) => (int) $id)->values()->all();

        $selectedSubjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;
        $selectedClassroomId = $request->filled('classroom_id') ? (int) $request->classroom_id : null;

        if ($selectedSubjectId !== null && !in_array($selectedSubjectId, $allowedSubjectIds, true)) {
            $selectedSubjectId = null;
        }

        if ($selectedClassroomId !== null && !in_array($selectedClassroomId, $allowedClassroomIds, true)) {
            $selectedClassroomId = null;
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $rowsQuery = AttendanceDetail::query()
            ->with([
                'student.classroom',
                'teacherAttendance.subject',
                'teacherAttendance.classroom',
            ])
            ->whereHas('teacherAttendance', function ($query) use ($teacher, $startDate, $endDate, $selectedSubjectId, $selectedClassroomId) {
                $query->where('teacher_id', $teacher->id);

                if ($startDate && $endDate) {
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                }

                if ($selectedSubjectId !== null) {
                    $query->where('subject_id', $selectedSubjectId);
                }

                if ($selectedClassroomId !== null) {
                    $query->where('classroom_id', $selectedClassroomId);
                }
            })
            ->orderByDesc('id');

        $rows = $rowsQuery->get();

        $totals = [
            'hadir' => (int) $rows->where('status', 'Hadir')->count(),
            'sakit' => (int) $rows->where('status', 'Sakit')->count(),
            'izin' => (int) $rows->where('status', 'Izin')->count(),
            'alpa' => (int) $rows->filter(function ($item) {
                return in_array($item->status, ['Alpha', 'Alpa'], true);
            })->count(),
            'total' => (int) $rows->count(),
        ];

        return [
            'teacher' => $teacher,
            'rows' => $rows,
            'totals' => $totals,
            'filters' => [
                ...$request->all(),
                'subject_id' => $selectedSubjectId,
                'classroom_id' => $selectedClassroomId,
            ],
            'periodLabel' => $this->buildPeriodLabel($request),
            'subjectOptions' => $subjectOptions,
            'classroomOptions' => $classroomOptions,
            'showSubjectFilter' => $subjectOptions->count() > 1,
            'showClassroomFilter' => $classroomOptions->count() > 1,
        ];
    }

    public function teacherAttendancePdf(Request $request)
    {
        $rows = $this->buildTeacherAttendanceQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-attendance', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-guru.pdf');
    }

    public function teacherAttendanceExcel(Request $request)
    {
        $rows = $this->buildTeacherAttendanceQuery($request)->get();

        return Excel::download(
            new TeacherAttendanceReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-absensi-guru.xlsx'
        );
    }

    public function studentAttendancePdf(Request $request)
    {
        $rows = $this->buildStudentAttendanceQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.student-attendance', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-siswa.pdf');
    }

    public function studentAttendanceExcel(Request $request)
    {
        $rows = $this->buildStudentAttendanceQuery($request)->get();

        return Excel::download(
            new StudentAttendanceReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-absensi-siswa.xlsx'
        );
    }

    public function teacherAgenda(Request $request)
    {
        $rows = $this->buildTeacherAgendaQuery($request)->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $majors = Major::orderBy('nama_jurusan')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

        return view('admin.reports.teacher-agenda', [
            'rows' => $rows,
            'teachers' => $teachers,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherAgendaPdf(Request $request)
    {
        $rows = $this->buildTeacherAgendaQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-agenda', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-agenda-guru.pdf');
    }

    public function teacherAgendaExcel(Request $request)
    {
        $rows = $this->buildTeacherAgendaQuery($request)->get();

        return Excel::download(
            new TeacherAgendaReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-agenda-guru.xlsx'
        );
    }

    public function teacherLeave(Request $request)
    {
        $rows = $this->buildTeacherLeaveQuery($request)->get();
        $teachers = Teacher::orderBy('nama_lengkap')->get();

        return view('admin.reports.teacher-leave', [
            'rows' => $rows,
            'teachers' => $teachers,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherLeavePdf(Request $request)
    {
        $rows = $this->buildTeacherLeaveQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-leave', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-izin-guru.pdf');
    }

    public function teacherLeaveExcel(Request $request)
    {
        $rows = $this->buildTeacherLeaveQuery($request)->get();

        return Excel::download(
            new TeacherLeaveReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-izin-guru.xlsx'
        );
    }

    public function studentLeave(Request $request)
    {
        $rows = $this->buildStudentLeaveQuery($request)->get();
        $students = Student::with('classroom')->orderBy('nama_lengkap')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

        return view('admin.reports.student-leave', [
            'rows' => $rows,
            'students' => $students,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function studentLeavePdf(Request $request)
    {
        $rows = $this->buildStudentLeaveQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.student-leave', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-izin-siswa.pdf');
    }

    public function studentLeaveExcel(Request $request)
    {
        $rows = $this->buildStudentLeaveQuery($request)->get();

        return Excel::download(
            new StudentLeaveReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-izin-siswa.xlsx'
        );
    }

    private function buildTeacherAttendanceQuery(Request $request)
    {
        $query = TeacherAttendance::with([
            'teacher',
            'subject',
            'classroom.major',
            'attendanceDetails',
        ])->orderByDesc('tanggal')->orderByDesc('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('major_id')) {
            $query->whereHas('classroom', function ($classroomQuery) use ($request) {
                $classroomQuery->where('major_id', $request->major_id);
            });
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        return $query;
    }

    private function buildTeacherAgendaQuery(Request $request)
    {
        return $this->buildTeacherAttendanceQuery($request);
    }

    private function buildTeacherLeaveQuery(Request $request)
    {
        $query = TeacherLeaveRequest::with('teacher')->orderByDesc('tanggal_mulai')->orderByDesc('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        if ($startDate && $endDate) {
            $query->whereDate('tanggal_mulai', '<=', $endDate)
                ->whereDate('tanggal_selesai', '>=', $startDate);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('status_pengajuan')) {
            $query->where('status_pengajuan', $request->status_pengajuan);
        }

        return $query;
    }

    private function buildStudentLeaveQuery(Request $request)
    {
        $query = StudentLeaveRequest::with(['student.classroom.major', 'verifier'])
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        if ($startDate && $endDate) {
            $query->whereDate('tanggal_mulai', '<=', $endDate)
                ->whereDate('tanggal_selesai', '>=', $startDate);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function ($studentQuery) use ($request) {
                $studentQuery->where('classroom_id', $request->classroom_id);
            });
        }

        if ($request->filled('status_pengajuan')) {
            $query->where('status_pengajuan', $request->status_pengajuan);
        }

        return $query;
    }

    private function buildStudentAttendanceQuery(Request $request)
    {
        $query = AttendanceDetail::with([
            'student.classroom.major',
            'teacherAttendance.teacher',
            'teacherAttendance.subject',
            'teacherAttendance.classroom.major',
        ])->orderByDesc('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        if ($startDate && $endDate) {
            $query->whereHas('teacherAttendance', function ($teacherAttendanceQuery) use ($startDate, $endDate) {
                $teacherAttendanceQuery->whereBetween('tanggal', [$startDate, $endDate]);
            });
        }

        if ($request->filled('teacher_id')) {
            $query->whereHas('teacherAttendance', function ($teacherAttendanceQuery) use ($request) {
                $teacherAttendanceQuery->where('teacher_id', $request->teacher_id);
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('major_id')) {
            $query->whereHas('student.classroom', function ($classroomQuery) use ($request) {
                $classroomQuery->where('major_id', $request->major_id);
            });
        }

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function ($studentQuery) use ($request) {
                $studentQuery->where('classroom_id', $request->classroom_id);
            });
        }

        return $query;
    }

    private function resolveDateRange(Request $request): array
    {
        $periodType = $request->input('period_type');

        if ($periodType === 'tanggal' && $request->filled('tanggal')) {
            $date = Carbon::parse($request->tanggal)->toDateString();

            return [$date, $date];
        }

        if ($periodType === 'mingguan' && $request->filled('minggu')) {
            [$year, $week] = explode('-W', $request->minggu);
            $startDate = Carbon::now()->setISODate((int) $year, (int) $week)->startOfWeek();
            $endDate = Carbon::now()->setISODate((int) $year, (int) $week)->endOfWeek();

            return [$startDate->toDateString(), $endDate->toDateString()];
        }

        if ($periodType === 'bulanan' && $request->filled('bulan')) {
            $date = Carbon::createFromFormat('Y-m', $request->bulan);

            return [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
        }

        if ($periodType === 'tahunan' && $request->filled('tahun')) {
            $startDate = Carbon::createFromDate((int) $request->tahun, 1, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int) $request->tahun, 12, 31)->endOfDay();

            return [$startDate->toDateString(), $endDate->toDateString()];
        }

        return [null, null];
    }

    private function buildPeriodLabel(Request $request): string
    {
        $periodType = $request->input('period_type');

        if ($periodType === 'tanggal' && $request->filled('tanggal')) {
            return 'Tanggal: ' . $request->tanggal;
        }

        if ($periodType === 'mingguan' && $request->filled('minggu')) {
            return 'Mingguan: ' . $request->minggu;
        }

        if ($periodType === 'bulanan' && $request->filled('bulan')) {
            return 'Bulanan: ' . $request->bulan;
        }

        if ($periodType === 'tahunan' && $request->filled('tahun')) {
            return 'Tahunan: ' . $request->tahun;
        }

        return 'Semua Periode';
    }
}
