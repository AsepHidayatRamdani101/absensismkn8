<?php

namespace App\Http\Controllers;

use App\Exports\GuruMapelRecapExport;
use App\Exports\GuruWaliKelasRecapExport;
use App\Exports\StudentLeaveReportExport;
use App\Exports\StudentAttendanceReportExport;
use App\Exports\TeacherAgendaReportExport;
use App\Exports\TeacherAttendanceReportExport;
use App\Exports\TeacherAttendanceRecognitionMissingSessionsExport;
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
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const PDF_EXPORT_MAX_ROWS = 3000;
    private const EXCEL_EXPORT_MAX_ROWS = 20000;

    public function teacherAttendanceRecognition(Request $request)
    {
        $baseRows = $this->buildTeacherAttendanceRecognitionQuery($request)->get();

        $rows = $baseRows->map(function (TeacherAttendance $item) {
            return [
                'item' => $item,
                ...$this->buildTeacherAttendanceRecognitionRow($item),
            ];
        });

        $summary = [
            'total_sesi' => (int) $rows->count(),
            'total_diakui' => (int) $rows->sum('recognized_point'),
        ];
        $summary['persentase_diakui'] = $summary['total_sesi'] > 0
            ? round(($summary['total_diakui'] / $summary['total_sesi']) * 100, 2)
            : 0;

        $countUniqueTeachers = static function (Collection $collection): int {
            return $collection
                ->map(fn($row) => (int) ($row['item']->teacher_id ?? 0))
                ->filter(fn($teacherId) => $teacherId > 0)
                ->unique()
                ->count();
        };

        $missingTeacherCards = [
            'absen_siswa_belum' => $countUniqueTeachers($rows->filter(fn($row) => !$row['has_absensi_siswa_oleh_guru'])),
            'agenda_belum' => $countUniqueTeachers($rows->filter(fn($row) => !$row['has_agenda_guru'])),
            'foto_belum' => $countUniqueTeachers($rows->filter(fn($row) => !$row['has_absensi_guru_siswa_kamera'])),
        ];

        $teachers = $this->getReportTeachers();
        $majors = $this->getReportMajors();
        $classrooms = $this->getReportClassrooms();

        return view('admin.reports.teacher-attendance-recognition', [
            'rows' => $rows,
            'summary' => $summary,
            'missingTeacherCards' => $missingTeacherCards,
            'teachers' => $teachers,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherAttendanceRecognitionMissingTeachers(Request $request, string $type)
    {
        $typeConfig = $this->teacherAttendanceRecognitionTypeConfig();

        if (!isset($typeConfig[$type])) {
            abort(404);
        }

        $baseRows = $this->buildTeacherAttendanceRecognitionQuery($request)->get();

        $rows = $baseRows->map(function (TeacherAttendance $item) {
            return [
                'item' => $item,
                ...$this->buildTeacherAttendanceRecognitionRow($item),
            ];
        });

        $filteredRows = $rows->filter($typeConfig[$type]['predicate'])->values();

        $teacherRows = $filteredRows
            ->groupBy(fn($row) => (int) ($row['item']->teacher_id ?? 0))
            ->map(function (Collection $group) {
                $first = $group->first();
                $teacher = $first['item']->teacher;

                return [
                    'teacher_id' => (int) ($first['item']->teacher_id ?? 0),
                    'teacher_name' => $teacher->nama_lengkap ?? '-',
                    'total_sesi_belum' => (int) $group->count(),
                    'mapel' => $group->map(fn($row) => $row['item']->subject->nama_mapel ?? '-')
                        ->unique()
                        ->values()
                        ->implode(', '),
                    'kelas' => $group->map(fn($row) => $row['item']->classroom->nama_kelas ?? '-')
                        ->unique()
                        ->values()
                        ->implode(', '),
                    'tanggal_terakhir' => $group->map(fn($row) => (string) $row['item']->tanggal)
                        ->filter()
                        ->max(),
                ];
            })
            ->sortByDesc('total_sesi_belum')
            ->values();

        return view('admin.reports.teacher-attendance-recognition-missing-teachers', [
            'title' => $typeConfig[$type]['title'],
            'type' => $type,
            'rows' => $teacherRows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherAttendanceRecognitionMissingTeacherSessions(Request $request, string $type, Teacher $teacher)
    {
        $payload = $this->buildTeacherAttendanceRecognitionMissingTeacherSessionsPayload($request, $type, $teacher);

        return view('admin.reports.teacher-attendance-recognition-missing-teacher-sessions', [
            ...$payload,
            'filters' => $request->all(),
        ]);
    }

    public function teacherAttendanceRecognitionMissingTeacherSessionsPdf(Request $request, string $type, Teacher $teacher)
    {
        $payload = $this->buildTeacherAttendanceRecognitionMissingTeacherSessionsPayload($request, $type, $teacher);

        if ($response = $this->guardCollectionExportLimit($request, $payload['rows'], 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-attendance-recognition-missing-teacher-sessions', $payload)
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-sesi-guru-belum-' . $type . '-' . $teacher->id . '.pdf');
    }

    public function teacherAttendanceRecognitionMissingTeacherSessionsExcel(Request $request, string $type, Teacher $teacher)
    {
        $payload = $this->buildTeacherAttendanceRecognitionMissingTeacherSessionsPayload($request, $type, $teacher);

        if ($response = $this->guardCollectionExportLimit($request, $payload['rows'], 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

        return Excel::download(
            new TeacherAttendanceRecognitionMissingSessionsExport(
                $payload['rows'],
                $payload['title'],
                $payload['periodLabel'],
                $teacher->nama_lengkap ?? 'Guru'
            ),
            'laporan-sesi-guru-belum-' . $type . '-' . $teacher->id . '.xlsx'
        );
    }

    public function teacherAttendance(Request $request)
    {
        $hasFilter = $this->hasTeacherAttendanceFilter($request);

        $teachers = $this->getReportTeachers();
        $majors = $this->getReportMajors();
        $classrooms = $this->getReportClassrooms();

        return view('admin.reports.teacher-attendance', [
            'hasFilter' => (bool) $hasFilter,
            'teachers' => $teachers,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherAttendanceDatatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);

        if (!$this->hasTeacherAttendanceFilter($request)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = $this->buildTeacherAttendanceQuery($request);
        $recordsTotal = (clone $query)->count();

        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('teacher_attendances.status', 'like', "%{$searchValue}%")
                    ->orWhere('teacher_attendances.pertemuan', 'like', "%{$searchValue}%")
                    ->orWhereDate('teacher_attendances.tanggal', $searchValue)
                    ->orWhereHas('teacher', function ($teacherQuery) use ($searchValue) {
                        $teacherQuery->where('nama_lengkap', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('subject', function ($subjectQuery) use ($searchValue) {
                        $subjectQuery->where('nama_mapel', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('classroom', function ($classroomQuery) use ($searchValue) {
                        $classroomQuery->where('nama_kelas', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 1);
        $orderDirection = strtolower((string) data_get($request->input('order', []), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnOrderMap = [
            1 => 'teacher_attendances.tanggal',
            7 => 'teacher_attendances.pertemuan',
            8 => 'teacher_attendances.status',
        ];
        $orderColumn = $columnOrderMap[$orderColumnIndex] ?? 'teacher_attendances.id';

        $rows = $query
            ->reorder()
            ->orderBy($orderColumn, $orderDirection)
            ->orderByDesc('teacher_attendances.id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->values()->map(function (TeacherAttendance $item, int $index) use ($start) {
            return [
                'no' => $start + $index + 1,
                'tanggal' => (string) ($item->tanggal ?? '-'),
                'guru' => (string) ($item->teacher->nama_lengkap ?? '-'),
                'mapel' => (string) ($item->subject->nama_mapel ?? '-'),
                'jurusan' => (string) ($item->classroom->major->nama_jurusan ?? '-'),
                'kelas' => (string) ($item->classroom->nama_kelas ?? '-'),
                'pertemuan' => (string) ($item->pertemuan ?? '-'),
                'status' => (string) ($item->status ?? '-'),
                'jumlah_siswa_diabsen' => (int) ($item->attendance_details_count ?? 0),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function studentAttendance(Request $request)
    {
        $hasFilter = $this->hasStudentAttendanceFilter($request);

        $teachers = $this->getReportTeachers();
        $students = $this->getReportStudents();
        $majors = $this->getReportMajors();
        $classrooms = $this->getReportClassrooms();

        return view('admin.reports.student-attendance', [
            'hasFilter' => (bool) $hasFilter,
            'teachers' => $teachers,
            'students' => $students,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function studentAttendanceDatatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);

        if (!$this->hasStudentAttendanceFilter($request)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = $this->buildStudentAttendanceQuery($request);
        $recordsTotal = (clone $query)->count();

        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('attendance_details.status', 'like', "%{$searchValue}%")
                    ->orWhere('attendance_details.jam_absen', 'like', "%{$searchValue}%")
                    ->orWhere('attendance_details.keterangan', 'like', "%{$searchValue}%")
                    ->orWhereHas('student', function ($studentQuery) use ($searchValue) {
                        $studentQuery->where('nama_lengkap', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('teacherAttendance.teacher', function ($teacherQuery) use ($searchValue) {
                        $teacherQuery->where('nama_lengkap', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('teacherAttendance.subject', function ($subjectQuery) use ($searchValue) {
                        $subjectQuery->where('nama_mapel', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('student.classroom', function ($classroomQuery) use ($searchValue) {
                        $classroomQuery->where('nama_kelas', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $rows = $query
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->values()->map(function (AttendanceDetail $item, int $index) use ($start) {
            return [
                'no' => $start + $index + 1,
                'tanggal' => (string) ($item->teacherAttendance->tanggal ?? '-'),
                'guru' => (string) ($item->teacherAttendance->teacher->nama_lengkap ?? '-'),
                'siswa' => (string) ($item->student->nama_lengkap ?? '-'),
                'mapel' => (string) ($item->teacherAttendance->subject->nama_mapel ?? '-'),
                'jurusan' => (string) ($item->student->classroom->major->nama_jurusan ?? '-'),
                'kelas' => (string) ($item->student->classroom->nama_kelas ?? '-'),
                'status' => (string) ($item->status ?? '-'),
                'jam_absen' => (string) ($item->jam_absen ?? '-'),
                'keterangan' => (string) ($item->keterangan ?? '-'),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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

        if ($response = $this->guardCollectionExportLimit($request, $payload['rows'], 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

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

        if ($response = $this->guardCollectionExportLimit($request, $payload['rows'], 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

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
            ->select('attendance_details.student_id', 'attendance_details.status', 'teacher_attendances.tanggal')
            ->whereIn('attendance_details.student_id', $students->pluck('id'));

        if ($startDate && $endDate) {
            $attendanceQuery->whereBetween('teacher_attendances.tanggal', [$startDate, $endDate]);
        }

        $attendances = $attendanceQuery->get();
        $attendancesByStudent = $attendances->groupBy('student_id');

        // Build rows with daily average calculation
        $rows = $students->map(function (Student $student) use ($attendancesByStudent) {
            $studentAttendances = $attendancesByStudent->get($student->id, collect());

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

        if ($response = $this->guardCollectionExportLimit($request, $payload['rows'], 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

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

        if ($response = $this->guardCollectionExportLimit($request, $payload['rows'], 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

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
        $query = $this->buildTeacherAttendanceQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-attendance', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-guru.pdf');
    }

    public function teacherAttendanceExcel(Request $request)
    {
        $query = $this->buildTeacherAttendanceQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        return Excel::download(
            new TeacherAttendanceReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-absensi-guru.xlsx'
        );
    }

    public function studentAttendancePdf(Request $request)
    {
        $query = $this->buildStudentAttendanceQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        $pdf = Pdf::loadView('admin.reports.pdf.student-attendance', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-siswa.pdf');
    }

    public function studentAttendanceExcel(Request $request)
    {
        $query = $this->buildStudentAttendanceQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        return Excel::download(
            new StudentAttendanceReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-absensi-siswa.xlsx'
        );
    }

    public function teacherAgenda(Request $request)
    {
        $teachers = $this->getReportTeachers();
        $majors = $this->getReportMajors();
        $classrooms = $this->getReportClassrooms();

        return view('admin.reports.teacher-agenda', [
            'teachers' => $teachers,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherAgendaDatatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);

        $query = $this->buildTeacherAgendaQuery($request);
        $recordsTotal = (clone $query)->count();

        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('teacher_attendances.status', 'like', "%{$searchValue}%")
                    ->orWhere('teacher_attendances.kehadiran_guru', 'like', "%{$searchValue}%")
                    ->orWhere('teacher_attendances.materi_pembelajaran', 'like', "%{$searchValue}%")
                    ->orWhere('teacher_attendances.tugas_deskripsi', 'like', "%{$searchValue}%")
                    ->orWhereDate('teacher_attendances.tanggal', $searchValue)
                    ->orWhereHas('teacher', function ($teacherQuery) use ($searchValue) {
                        $teacherQuery->where('nama_lengkap', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('subject', function ($subjectQuery) use ($searchValue) {
                        $subjectQuery->where('nama_mapel', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('classroom', function ($classroomQuery) use ($searchValue) {
                        $classroomQuery->where('nama_kelas', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 1);
        $orderDirection = strtolower((string) data_get($request->input('order', []), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnOrderMap = [
            1 => 'teacher_attendances.tanggal',
            7 => 'teacher_attendances.kehadiran_guru',
            9 => 'teacher_attendances.status',
        ];
        $orderColumn = $columnOrderMap[$orderColumnIndex] ?? 'teacher_attendances.id';

        $rows = $query
            ->reorder()
            ->orderBy($orderColumn, $orderDirection)
            ->orderByDesc('teacher_attendances.id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->values()->map(function (TeacherAttendance $item, int $index) use ($start) {
            $tugas = '-';

            if (!empty($item->tugas_file_path)) {
                $tugas = 'Ada File';
            } elseif (!empty($item->tugas_deskripsi)) {
                $tugas = \Illuminate\Support\Str::limit($item->tugas_deskripsi, 40);
            }

            return [
                'no' => $start + $index + 1,
                'tanggal' => (string) ($item->tanggal ?? '-'),
                'guru' => (string) ($item->teacher->nama_lengkap ?? '-'),
                'mapel' => (string) ($item->subject->nama_mapel ?? '-'),
                'kelas' => (string) ($item->classroom->nama_kelas ?? '-'),
                'materi' => (string) ($item->materi_pembelajaran ?? '-'),
                'kehadiran_guru' => (string) ($item->kehadiran_guru ?? 'Hadir'),
                'tugas' => $tugas,
                'status' => (string) ($item->status ?? '-'),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function teacherAgendaPdf(Request $request)
    {
        $query = $this->buildTeacherAgendaQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-agenda', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-agenda-guru.pdf');
    }

    public function teacherAgendaExcel(Request $request)
    {
        $query = $this->buildTeacherAgendaQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        return Excel::download(
            new TeacherAgendaReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-agenda-guru.xlsx'
        );
    }

    public function teacherLeave(Request $request)
    {
        $rows = $this->buildTeacherLeaveQuery($request)->get();
        $teachers = $this->getReportTeachers();

        return view('admin.reports.teacher-leave', [
            'rows' => $rows,
            'teachers' => $teachers,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherLeavePdf(Request $request)
    {
        $query = $this->buildTeacherLeaveQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-leave', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-izin-guru.pdf');
    }

    public function teacherLeaveExcel(Request $request)
    {
        $query = $this->buildTeacherLeaveQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        return Excel::download(
            new TeacherLeaveReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-izin-guru.xlsx'
        );
    }

    public function studentLeave(Request $request)
    {
        $rows = $this->buildStudentLeaveQuery($request)->get();
        $students = $this->getReportStudents();
        $classrooms = $this->getReportClassrooms();

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
        $query = $this->buildStudentLeaveQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'PDF', self::PDF_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        $pdf = Pdf::loadView('admin.reports.pdf.student-leave', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-izin-siswa.pdf');
    }

    public function studentLeaveExcel(Request $request)
    {
        $query = $this->buildStudentLeaveQuery($request);

        if ($response = $this->guardQueryExportLimit($request, $query, 'Excel', self::EXCEL_EXPORT_MAX_ROWS)) {
            return $response;
        }

        $rows = $query->get();

        return Excel::download(
            new StudentLeaveReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-izin-siswa.xlsx'
        );
    }

    private function buildTeacherAttendanceQuery(Request $request)
    {
        $query = TeacherAttendance::query()
            ->select([
                'id',
                'tanggal',
                'teacher_id',
                'subject_id',
                'classroom_id',
                'pertemuan',
                'status',
                'materi_pembelajaran',
                'kehadiran_guru',
                'tugas_file_path',
                'tugas_deskripsi',
            ])
            ->with([
                'teacher:id,nama_lengkap',
                'subject:id,nama_mapel',
                'classroom:id,nama_kelas,major_id',
                'classroom.major:id,nama_jurusan',
            ])
            ->withCount('attendanceDetails')
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

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

    private function buildTeacherAttendanceRecognitionQuery(Request $request)
    {
        $query = TeacherAttendance::query()
            ->select([
                'id',
                'tanggal',
                'teacher_id',
                'subject_id',
                'classroom_id',
                'status',
                'kehadiran_guru',
                'foto_guru_path',
            ])
            ->with([
                'teacher:id,nama_lengkap',
                'subject:id,nama_mapel',
                'classroom:id,nama_kelas,major_id',
                'classroom.major:id,nama_jurusan',
            ])
            ->withCount('attendanceDetails')
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

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

    private function buildTeacherAttendanceRecognitionRow(TeacherAttendance $item): array
    {
        $hasAbsensiGuruSiswaKamera = !empty($item->foto_guru_path);
        $hasAgendaGuru = $item->status === 'Selesai';
        $hasAbsensiSiswaOlehGuru = $item->kehadiran_guru === 'Hadir' && (int) $item->attendance_details_count > 0;
        $recognizedPoint = ($hasAbsensiGuruSiswaKamera && $hasAgendaGuru && $hasAbsensiSiswaOlehGuru) ? 1 : 0;

        return [
            'has_absensi_guru_siswa_kamera' => $hasAbsensiGuruSiswaKamera,
            'has_agenda_guru' => $hasAgendaGuru,
            'has_absensi_siswa_oleh_guru' => $hasAbsensiSiswaOlehGuru,
            'recognized_point' => $recognizedPoint,
        ];
    }

    private function teacherAttendanceRecognitionTypeConfig(): array
    {
        return [
            'absen-siswa' => [
                'title' => 'Guru Belum Melakukan Absen Siswa',
                'predicate' => fn(array $row) => !$row['has_absensi_siswa_oleh_guru'],
            ],
            'agenda' => [
                'title' => 'Guru Belum Mengisi Agenda',
                'predicate' => fn(array $row) => !$row['has_agenda_guru'],
            ],
            'foto' => [
                'title' => 'Guru Tidak Difoto Oleh Siswa',
                'predicate' => fn(array $row) => !$row['has_absensi_guru_siswa_kamera'],
            ],
        ];
    }

    private function buildTeacherAttendanceRecognitionMissingTeacherSessionsPayload(Request $request, string $type, Teacher $teacher): array
    {
        $typeConfig = $this->teacherAttendanceRecognitionTypeConfig();

        if (!isset($typeConfig[$type])) {
            abort(404);
        }

        $baseRows = $this->buildTeacherAttendanceRecognitionQuery($request)
            ->where('teacher_id', $teacher->id)
            ->get();

        $rows = $baseRows->map(function (TeacherAttendance $item) {
            return [
                'item' => $item,
                ...$this->buildTeacherAttendanceRecognitionRow($item),
            ];
        });

        $sessionRows = $rows
            ->filter($typeConfig[$type]['predicate'])
            ->values()
            ->map(function ($row) {
                return [
                    'tanggal' => (string) ($row['item']->tanggal ?? '-'),
                    'mapel' => $row['item']->subject->nama_mapel ?? '-',
                    'jurusan' => $row['item']->classroom->major->nama_jurusan ?? '-',
                    'kelas' => $row['item']->classroom->nama_kelas ?? '-',
                    'absensi_guru_siswa_kamera' => $row['has_absensi_guru_siswa_kamera'],
                    'agenda_guru' => $row['has_agenda_guru'],
                    'absensi_siswa_oleh_guru' => $row['has_absensi_siswa_oleh_guru'],
                ];
            });

        return [
            'title' => $typeConfig[$type]['title'] . ' - ' . ($teacher->nama_lengkap ?? 'Guru'),
            'type' => $type,
            'teacher' => $teacher,
            'rows' => $sessionRows,
            'periodLabel' => $this->buildPeriodLabel($request),
        ];
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    private function hasTeacherAttendanceFilter(Request $request): bool
    {
        return $request->hasAny(['period_type', 'tanggal', 'minggu', 'bulan', 'tahun', 'teacher_id', 'major_id', 'classroom_id'])
            && (bool) array_filter($request->only(['period_type', 'tanggal', 'minggu', 'bulan', 'tahun', 'teacher_id', 'major_id', 'classroom_id']));
    }

    private function hasStudentAttendanceFilter(Request $request): bool
    {
        return $request->hasAny(['period_type', 'tanggal', 'minggu', 'bulan', 'tahun', 'teacher_id', 'student_id', 'major_id', 'classroom_id', 'status'])
            && (bool) array_filter($request->only(['period_type', 'tanggal', 'minggu', 'bulan', 'tahun', 'teacher_id', 'student_id', 'major_id', 'classroom_id', 'status']));
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

    private function getReportTeachers(): Collection
    {
        return Cache::remember('reports:filters:teachers', now()->addMinutes(15), function () {
            return Teacher::query()
                ->select('id', 'nama_lengkap')
                ->orderBy('nama_lengkap')
                ->get();
        });
    }

    private function getReportMajors(): Collection
    {
        return Cache::remember('reports:filters:majors', now()->addMinutes(15), function () {
            return Major::query()
                ->select('id', 'nama_jurusan')
                ->orderBy('nama_jurusan')
                ->get();
        });
    }

    private function getReportClassrooms(): Collection
    {
        return Cache::remember('reports:filters:classrooms', now()->addMinutes(15), function () {
            return Classroom::query()
                ->select('id', 'nama_kelas', 'major_id')
                ->with(['major:id,nama_jurusan'])
                ->orderBy('nama_kelas')
                ->get();
        });
    }

    private function getReportStudents(): Collection
    {
        return Cache::remember('reports:filters:students', now()->addMinutes(15), function () {
            return Student::query()
                ->select('id', 'nama_lengkap', 'classroom_id')
                ->with(['classroom:id,nama_kelas'])
                ->orderBy('nama_lengkap')
                ->get();
        });
    }

    private function guardQueryExportLimit(Request $request, $query, string $format, int $maxRows)
    {
        $totalRows = (clone $query)->count();

        if ($totalRows <= $maxRows) {
            return null;
        }

        return redirect()->back()->withInput($request->query())->with(
            'error',
            sprintf(
                'Export %s dibatasi maksimal %d baris per file. Silakan persempit filter periode/guru/kelas terlebih dahulu.',
                $format,
                $maxRows
            )
        );
    }

    private function guardCollectionExportLimit(Request $request, Collection $rows, string $format, int $maxRows)
    {
        if ($rows->count() <= $maxRows) {
            return null;
        }

        return redirect()->back()->withInput($request->query())->with(
            'error',
            sprintf(
                'Export %s dibatasi maksimal %d baris per file. Silakan persempit filter periode/guru/kelas terlebih dahulu.',
                $format,
                $maxRows
            )
        );
    }
}
