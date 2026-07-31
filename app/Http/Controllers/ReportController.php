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
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
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

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $majors = Major::orderBy('nama_jurusan')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

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

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-attendance-recognition-missing-teacher-sessions', $payload)
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-sesi-guru-belum-' . $type . '-' . $teacher->id . '.pdf');
    }

    public function teacherAttendanceRecognitionMissingTeacherSessionsExcel(Request $request, string $type, Teacher $teacher)
    {
        $payload = $this->buildTeacherAttendanceRecognitionMissingTeacherSessionsPayload($request, $type, $teacher);

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

    private function buildGuruWaliKelasRecapPayload(Teacher $teacher, Request $request): array
    {
        $teacher->loadMissing('waliClassroom.major');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $students = Student::query()
            ->where('classroom_id', $teacher->wali_classroom_id)
            ->orderBy('nama_lengkap')
            ->get();

        $summaryQuery = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->select('attendance_details.student_id')
            ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN attendance_details.status IN ('Alpha','Alpa') THEN 1 ELSE 0 END) as alpa")
            ->selectRaw('COUNT(*) as total')
            ->whereIn('attendance_details.student_id', $students->pluck('id'));

        if ($startDate && $endDate) {
            $summaryQuery->whereBetween('teacher_attendances.tanggal', [$startDate, $endDate]);
        }

        $summaries = $summaryQuery
            ->groupBy('attendance_details.student_id')
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $student) use ($summaries) {
            $summary = $summaries->get($student->id);
            $total = (int) ($summary->total ?? 0);
            $hadir = (int) ($summary->hadir ?? 0);

            return [
                'student' => $student,
                'hadir' => $hadir,
                'sakit' => (int) ($summary->sakit ?? 0),
                'izin' => (int) ($summary->izin ?? 0),
                'alpa' => (int) ($summary->alpa ?? 0),
                'total' => $total,
                'persen_hadir' => $total > 0 ? round(($hadir / $total) * 100, 2) : 0,
            ];
        });

        $totals = [
            'hadir' => (int) $rows->sum('hadir'),
            'sakit' => (int) $rows->sum('sakit'),
            'izin' => (int) $rows->sum('izin'),
            'alpa' => (int) $rows->sum('alpa'),
            'total' => (int) $rows->sum('total'),
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

    private function buildTeacherAttendanceRecognitionQuery(Request $request)
    {
        $query = TeacherAttendance::with([
            'teacher',
            'subject',
            'classroom.major',
        ])->withCount('attendanceDetails')
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

        $baseRows = $this->buildTeacherAttendanceRecognitionQuery($request)->get();

        $rows = $baseRows->map(function (TeacherAttendance $item) {
            return [
                'item' => $item,
                ...$this->buildTeacherAttendanceRecognitionRow($item),
            ];
        });

        $sessionRows = $rows
            ->filter($typeConfig[$type]['predicate'])
            ->filter(fn($row) => (int) ($row['item']->teacher_id ?? 0) === (int) $teacher->id)
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
