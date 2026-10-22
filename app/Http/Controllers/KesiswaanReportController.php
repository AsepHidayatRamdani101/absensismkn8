<?php

namespace App\Http\Controllers;

use App\Exports\ClassEffectivenessReportExport;
use App\Models\AttendanceDetail;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\TeacherAttendance;
use App\Support\PklMode;
use App\Support\SchoolCalendar;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class KesiswaanReportController extends Controller
{
    private const WEEKDAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    private const STATUS_LABELS = [
        'Hadir' => 'Hadir',
        'Sakit' => 'Sakit',
        'Izin' => 'Izin',
        'Alpha' => 'Alpa',
        'Bolos' => 'Bolos',
        'Terlambat' => 'Terlambat',
        'Dispen' => 'Dispen',
    ];

    public function index(Request $request)
    {
        $defaultFilters = [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => today()->toDateString(),
            'classroom_id' => '',
        ];
        $hasFilters = $request->boolean('apply_filters');
        $filters = $hasFilters ? $this->validatedFilters($request) : $defaultFilters;

        return view('kesiswaan.reports.class-effectiveness', [
            'filters' => $filters,
            'classrooms' => Classroom::query()->orderBy('nama_kelas')->get(['id', 'nama_kelas']),
            'hasFilters' => $hasFilters,
            'periodLabel' => $hasFilters
                ? Carbon::parse($filters['date_from'])->format('d-m-Y') . ' s.d. ' . Carbon::parse($filters['date_to'])->format('d-m-Y')
                : null,
        ]);
    }

    public function studentData(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $rows = $this->buildReport($filters)->map(function (array $class): array {
            return [
                'classroom' => $class['classroom'],
                'major' => $class['major'],
                'effectiveness' => $class['effectiveness'],
                'student_records' => $class['student_records'],
                'student_present_records' => $class['student_present_records'],
                'status_summary' => collect($class['status_counts'])
                ->map(fn(array $status) => $status['label'] . ': ' . $status['count'] . ' (' . number_format($status['percentage'], 2) . '%)')
                    ->implode(' | '),
            ];
        });

        return $this->dataTableResponse($request, $rows, [
            'classroom', 'major', 'effectiveness', 'student_records', 'student_present_records', 'status_summary',
        ]);
    }

    public function teacherData(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $rows = $this->buildReport($filters)
            ->flatMap(fn(array $class) => $class['teachers']->map(fn(array $teacher) => [
                'classroom' => $class['classroom'],
                'teacher' => $teacher['name'],
                'present' => $teacher['present'],
                'expected' => $teacher['expected'],
                'percentage' => $teacher['percentage'],
            ]))
            ->values();

        return $this->dataTableResponse($request, $rows, [
            'classroom', 'teacher', 'present', 'expected', 'percentage',
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $report = $this->buildReport($filters);

        return Pdf::loadView('kesiswaan.reports.export.class-effectiveness', [
            'report' => $report,
            'periodLabel' => Carbon::parse($filters['date_from'])->format('d-m-Y')
                . ' s.d. ' . Carbon::parse($filters['date_to'])->format('d-m-Y'),
        ])->setPaper('a4', 'landscape')->download('laporan-efektivitas-belajar-kelas.pdf');
    }

    public function excel(Request $request)
    {
        $filters = $this->validatedFilters($request);

        return Excel::download(
            new ClassEffectivenessReportExport(
                $this->buildReport($filters),
                Carbon::parse($filters['date_from'])->format('d-m-Y')
                    . ' s.d. ' . Carbon::parse($filters['date_to'])->format('d-m-Y')
            ),
            'laporan-efektivitas-belajar-kelas.xlsx'
        );
    }

    private function validatedFilters(Request $request): array
    {
        $defaultFrom = now()->startOfMonth()->toDateString();
        $defaultTo = today()->toDateString();
        $request->merge([
            'date_from' => $request->input('date_from', $defaultFrom),
            'date_to' => $request->input('date_to', $defaultTo),
        ]);

        return $request->validate([
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ]);
    }

    private function dataTableResponse(Request $request, Collection $rows, array $orderColumns)
    {
        $recordsTotal = $rows->count();
        $search = mb_strtolower(trim((string) $request->input('search.value', '')));
        if ($search !== '') {
            $rows = $rows->filter(fn(array $row) => str_contains(
                mb_strtolower((string) json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                $search
            ))->values();
        }

        $recordsFiltered = $rows->count();
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $orderColumns[$orderIndex] ?? null;
        if ($orderColumn !== null) {
            $rows = $rows->sortBy(
                fn(array $row) => $row[$orderColumn],
                SORT_NATURAL,
                $request->input('order.0.dir') === 'desc'
            )->values();
        }

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $length === -1 ? $rows->slice($start)->values() : $rows->slice($start, max($length, 0))->values(),
        ]);
    }

    private function buildReport(array $filters): Collection
    {
        $startDate = Carbon::parse($filters['date_from'])->startOfDay();
        $endDate = Carbon::parse($filters['date_to'])->startOfDay();
        $classroomQuery = Classroom::query()
            ->with('major:id,nama_jurusan')
            ->when(
                !empty($filters['classroom_id']),
                fn($query) => $query->whereKey((int) $filters['classroom_id'])
            )
            ->orderBy('nama_kelas');
        $classrooms = $classroomQuery->get();
        $classroomIds = $classrooms->pluck('id')->map(fn($id) => (int) $id)->all();

        $scheduleQuery = Schedule::query()
            ->with([
                'teacherSubject.teacher:id,nama_lengkap',
                'teacherSubject.classroom:id,nama_kelas',
                'teacherSubject.academicYear:id,is_active',
            ])
            ->whereHas('teacherSubject', function ($query) use ($classroomIds) {
                $query->whereIn('classroom_id', $classroomIds)
                    ->whereHas('academicYear', fn($academicYear) => $academicYear->where('is_active', true));
            });
        $schedules = PklMode::applyToScheduleQuery($scheduleQuery)->get();
        $scheduleIds = $schedules->pluck('id')->map(fn($id) => (int) $id)->all();
        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->copy();
        }

        $attendanceByScheduleDate = TeacherAttendance::query()
            ->whereIn('schedule_id', $scheduleIds)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('id')
            ->get(['id', 'schedule_id', 'tanggal', 'teacher_id', 'kehadiran_guru'])
            ->unique(fn(TeacherAttendance $attendance) => $attendance->schedule_id . ':' . $attendance->tanggal->toDateString())
            ->keyBy(fn(TeacherAttendance $attendance) => $attendance->schedule_id . ':' . $attendance->tanggal->toDateString());

        $teacherStats = [];
        foreach ($schedules as $schedule) {
            $teacherSubject = $schedule->teacherSubject;
            $classroomId = (int) $teacherSubject->classroom_id;
            $teacherId = (int) $teacherSubject->teacher_id;
            $teacherKey = $classroomId . ':' . $teacherId;
            $teacherStats[$teacherKey] ??= [
                'teacher' => $teacherSubject->teacher?->nama_lengkap ?? 'Guru tidak diketahui',
                'expected' => 0,
                'present' => 0,
            ];

            foreach ($dates as $date) {
                if (($date->dayOfWeekIso > 6)
                    || self::WEEKDAY_NAMES[$date->dayOfWeekIso] !== $schedule->hari
                    || SchoolCalendar::isHolidayAt($date, (string) $schedule->jam_mulai)
                ) {
                    continue;
                }

                $teacherStats[$teacherKey]['expected']++;
                $attendanceKey = $schedule->id . ':' . $date->toDateString();
                $teacherAttendance = $attendanceByScheduleDate->get($attendanceKey);
                if ($teacherAttendance && in_array($teacherAttendance->kehadiran_guru ?? 'Hadir', ['Hadir'], true)) {
                    $teacherStats[$teacherKey]['present']++;
                }
            }
        }

        $studentRows = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->whereBetween('teacher_attendances.tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('teacher_attendances.classroom_id', $classroomIds)
            ->selectRaw('teacher_attendances.classroom_id, attendance_details.status, COUNT(*) as total')
            ->groupBy('teacher_attendances.classroom_id', 'attendance_details.status')
            ->get();
        $studentCountsByClass = $studentRows->groupBy('classroom_id');

        return $classrooms->map(function (Classroom $classroom) use ($studentCountsByClass, $teacherStats): array {
            $statusCounts = array_fill_keys(array_keys(self::STATUS_LABELS), 0);
            foreach ($studentCountsByClass->get($classroom->id, collect()) as $row) {
                $status = in_array($row->status, ['Alpha', 'Alpa'], true) ? 'Alpha' : $row->status;
                if (array_key_exists($status, $statusCounts)) {
                    $statusCounts[$status] += (int) $row->total;
                }
            }

            $totalStudentRecords = array_sum($statusCounts);
            $presentStudentRecords = $statusCounts['Hadir'] + $statusCounts['Terlambat'] + $statusCounts['Dispen'];
            $teachers = collect($teacherStats)
                ->filter(fn(array $stats, string $key) => str_starts_with($key, $classroom->id . ':'))
                ->map(function (array $stats): array {
                    return [
                        'name' => $stats['teacher'],
                        'expected' => $stats['expected'],
                        'present' => $stats['present'],
                        'percentage' => $stats['expected'] > 0
                            ? round(($stats['present'] / $stats['expected']) * 100, 2)
                            : 0,
                    ];
                })
                ->sortBy('name')
                ->values();

            foreach ($statusCounts as $status => $count) {
                $statusCounts[$status] = [
                    'label' => self::STATUS_LABELS[$status],
                    'count' => $count,
                    'percentage' => $totalStudentRecords > 0
                        ? round(($count / $totalStudentRecords) * 100, 2)
                        : 0,
                ];
            }

            return [
                'classroom' => $classroom->nama_kelas,
                'major' => $classroom->major?->nama_jurusan ?? '-',
                'effectiveness' => $totalStudentRecords > 0
                    ? round(($presentStudentRecords / $totalStudentRecords) * 100, 2)
                    : 0,
                'student_records' => $totalStudentRecords,
                'student_present_records' => $presentStudentRecords,
                'status_counts' => $statusCounts,
                'teachers' => $teachers,
            ];
        });
    }
}
