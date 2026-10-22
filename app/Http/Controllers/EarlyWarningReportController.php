<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AttendanceDetail;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Support\PklMode;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EarlyWarningReportController extends Controller
{
    private const STUDENT_STATUSES = [
        'Sakit' => 'Sakit',
        'Izin' => 'Izin',
        'Alpha' => 'Alpa',
        'Bolos' => 'Bolos',
        'Dispen' => 'Dispen',
    ];

    private const TEACHER_ISSUES = [
        'student_attendance' => 'Tidak mengisi absensi siswa',
        'agenda' => 'Tidak mengisi agenda',
        'no_activity' => 'Tidak melakukan aktivitas',
    ];

    public function index(Request $request)
    {
        $academicYear = $this->activeAcademicYear();
        [$semesterStart, $semesterEnd] = $this->semesterRange($academicYear);
        $period = $request->input('period', 'semester');
        $selectedMonth = $request->input('month', $this->defaultMonth($semesterStart, $semesterEnd));
        $teacherId = $request->input('teacher_id', '');
        $hasFilters = $request->boolean('apply_filters');
        $reportFilters = $hasFilters ? $this->reportFilters($request) : null;
        $reportUrl = $request->routeIs('kesiswaan.*') ? route('kesiswaan.reports.early-warning') : route('admin.reports.early-warning');
        $studentDataUrl = $request->routeIs('kesiswaan.*')
            ? route('kesiswaan.reports.early-warning.students')
            : route('admin.reports.early-warning.students');
        $teacherDataUrl = $request->routeIs('kesiswaan.*')
            ? route('kesiswaan.reports.early-warning.teachers')
            : route('admin.reports.early-warning.teachers');

        return view('admin.reports.early-warning', [
            'academicYear' => $academicYear,
            'filters' => [
                'period' => $period,
                'month' => $selectedMonth,
                'teacher_id' => $teacherId,
                'report_url' => $reportUrl,
                'student_data_url' => $studentDataUrl,
                'teacher_data_url' => $teacherDataUrl,
            ],
            'hasFilters' => $hasFilters,
            'periodLabel' => $hasFilters
                ? $reportFilters['start']->format('d-m-Y') . ' s.d. ' . $reportFilters['end']->format('d-m-Y')
                : null,
            'teachers' => Teacher::query()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']),
            'semesterRange' => [
                'start' => $semesterStart->format('Y-m'),
                'end' => $semesterEnd->format('Y-m'),
            ],
        ]);
    }

    public function studentData(Request $request)
    {
        $filters = $this->reportFilters($request);
        $rows = $this->studentWarnings(
            $filters['academic_year']->id,
            $filters['start'],
            $filters['end'],
            $filters['effective_days'],
            $filters['include_semester_threshold'],
            $filters['teacher_id']
        );

        return $this->dataTableResponse($request, $rows, [
            null, 'nis', 'name', 'classroom', 'status', 'total_days', 'consecutive_days', 'peak_month_days', 'warning',
        ]);
    }

    public function teacherData(Request $request)
    {
        $filters = $this->reportFilters($request);
        $rows = $this->teacherWarnings(
            $filters['academic_year']->id,
            $filters['start'],
            $filters['end'],
            $filters['include_semester_threshold'],
            $filters['teacher_id']
        );

        return $this->dataTableResponse($request, $rows, [
            null, 'name', 'classroom', 'subject', 'issue', 'total_days', 'consecutive_days', 'peak_month_days', 'warning',
        ]);
    }

    private function activeAcademicYear(): AcademicYear
    {
        $academicYear = AcademicYear::query()->where('is_active', true)->first();
        abort_unless($academicYear, 422, 'Tahun ajaran aktif belum ditentukan.');

        return $academicYear;
    }

    private function defaultMonth(Carbon $semesterStart, Carbon $semesterEnd): string
    {
        $month = today()->startOfMonth();
        if ($month->lt($semesterStart)) {
            $month = $semesterStart->copy();
        } elseif ($semesterEnd->gte($semesterStart) && $month->gt($semesterEnd)) {
            $month = $semesterEnd->copy();
        }

        return $month->format('Y-m');
    }

    private function reportFilters(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['required', 'in:monthly,semester'],
            'month' => ['required_if:period,monthly', 'nullable', 'date_format:Y-m'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);
        $academicYear = $this->activeAcademicYear();
        [$semesterStart, $semesterEnd] = $this->semesterRange($academicYear);
        $period = $validated['period'];

        if ($period === 'monthly') {
            $monthDate = Carbon::createFromFormat('!Y-m', $validated['month']);
            $startDate = $monthDate->copy()->startOfMonth()->max($semesterStart);
            $endDate = $monthDate->copy()->endOfMonth()->min($semesterEnd);
            abort_if($startDate->gt($endDate), 422, 'Bulan yang dipilih berada di luar semester aktif atau belum berlangsung.');
        } else {
            $startDate = $semesterStart;
            $endDate = $semesterEnd;
        }

        return [
            'academic_year' => $academicYear,
            'start' => $startDate,
            'end' => $endDate,
            'effective_days' => $this->effectiveSchoolDays($startDate, $endDate),
            'include_semester_threshold' => $period === 'semester',
            'teacher_id' => !empty($validated['teacher_id']) ? (int) $validated['teacher_id'] : null,
        ];
    }

    private function dataTableResponse(Request $request, Collection $rows, array $columns)
    {
        $recordsTotal = $rows->count();
        $search = mb_strtolower(trim((string) $request->input('search.value', '')));
        if ($search !== '') {
            $rows = $rows->filter(function (array $row) use ($search): bool {
                return str_contains(mb_strtolower(implode(' ', array_map(
                    fn($value) => is_scalar($value) ? (string) $value : '',
                    $row
                ))), $search);
            })->values();
        }

        $recordsFiltered = $rows->count();
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderIndex] ?? null;
        if (is_string($orderColumn) && in_array($orderColumn, $columns, true)) {
            $rows = $rows->sortBy(
                fn(array $row) => mb_strtolower((string) ($row[$orderColumn] ?? '')),
                SORT_NATURAL,
                $request->input('order.0.dir') === 'desc'
            )->values();
        }

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $pagedRows = $length === -1 ? $rows->slice($start)->values() : $rows->slice($start, max($length, 0))->values();
        $pagedRows = $pagedRows->values()->map(fn(array $row, int $index) => ['row_number' => $start + $index + 1] + $row);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $pagedRows,
        ]);
    }

    private function semesterRange(AcademicYear $academicYear): array
    {
        if (!preg_match('/^(20\d{2})\s*\/\s*(20\d{2})$/', (string) $academicYear->tahun_ajaran, $matches)) {
            abort(422, 'Format tahun ajaran aktif tidak valid; gunakan format seperti 2026/2027.');
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        $firstDay = $academicYear->semester === 'Genap'
            ? Carbon::create($endYear, 1, 1)->startOfDay()
            : Carbon::create($startYear, 7, 1)->startOfDay();
        $lastDay = $academicYear->semester === 'Genap'
            ? Carbon::create($endYear, 6, 30)->startOfDay()
            : Carbon::create($startYear, 12, 31)->startOfDay();
        $today = today()->startOfDay();

        if ($firstDay->gt($today)) {
            return [$firstDay, $firstDay->copy()->subDay()];
        }

        return [$firstDay, $lastDay->min($today)];
    }

    private function effectiveSchoolDays(Carbon $startDate, Carbon $endDate): array
    {
        $days = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isSunday() || SchoolCalendar::isHoliday($date)) {
                continue;
            }

            $days[] = $date->toDateString();
        }

        return $days;
    }

    private function studentWarnings(
        int $academicYearId,
        Carbon $startDate,
        Carbon $endDate,
        array $effectiveDays,
        bool $includeSemesterThreshold,
        ?int $teacherId
    ): Collection {
        $records = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('students', 'students.id', '=', 'attendance_details.student_id')
            ->leftJoin('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->where('teacher_attendances.academic_year_id', $academicYearId)
            ->when($teacherId, fn($query) => $query->where('teacher_attendances.teacher_id', $teacherId))
            ->whereBetween('teacher_attendances.tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('attendance_details.status', array_keys(self::STUDENT_STATUSES))
            ->orderBy('students.nama_lengkap')
            ->get([
                'attendance_details.student_id',
                'attendance_details.status',
                'teacher_attendances.tanggal',
                'students.nis',
                'students.nama_lengkap',
                'classrooms.nama_kelas',
            ]);

        $warnings = collect();
        foreach ($records->groupBy(fn($row) => $row->student_id . ':' . self::STUDENT_STATUSES[$row->status]) as $studentStatusRecords) {
            $first = $studentStatusRecords->first();
            $dates = $studentStatusRecords->pluck('tanggal')
                ->map(fn($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->sort()
                ->values()
                ->all();
            $metrics = $this->warningMetrics($dates, $effectiveDays, $includeSemesterThreshold);

            if (!$metrics['triggered']) {
                continue;
            }

            $warnings->push([
                'nis' => $first->nis,
                'name' => $first->nama_lengkap,
                'classroom' => $first->nama_kelas ?? '-',
                'status' => self::STUDENT_STATUSES[$first->status],
                'total_days' => count($dates),
                'consecutive_days' => $metrics['consecutive'],
                'peak_month_days' => $metrics['peak_month'],
                'warning' => $metrics['warning'],
            ]);
        }

        return $warnings->sortBy(fn(array $row) => mb_strtolower(
            $row['classroom'] . '|' . $row['name'] . '|' . $row['status']
        ))->values();
    }

    private function teacherWarnings(
        int $academicYearId,
        Carbon $startDate,
        Carbon $endDate,
        bool $includeSemesterThreshold,
        ?int $teacherId
    ): Collection
    {
        $scheduleQuery = Schedule::query()
            ->with([
                'teacherSubject.teacher:id,nama_lengkap',
                'teacherSubject.classroom:id,nama_kelas',
                'teacherSubject.subject:id,nama_mapel',
                'teacherSubject.academicYear:id,is_active',
            ])
            ->whereHas('teacherSubject', fn($query) => $query
                ->where('academic_year_id', $academicYearId)
                ->when($teacherId, fn($teacherQuery) => $teacherQuery->where('teacher_id', $teacherId))
                ->whereHas('teacher'));
        $schedules = PklMode::applyToScheduleQuery($scheduleQuery)->get();
        $scheduleIds = $schedules->pluck('id')->map(fn($id) => (int) $id)->all();
        $activities = TeacherAttendance::query()
            ->where('academic_year_id', $academicYearId)
            ->whereIn('schedule_id', $scheduleIds)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->withCount('attendanceDetails')
            ->get([
                'id',
                'schedule_id',
                'teacher_id',
                'tanggal',
                'status',
                'foto_guru_path',
                'tugas_file_path',
                'tugas_deskripsi',
                'materi_pembelajaran',
                'catatan_guru',
            ])
            ->keyBy(fn(TeacherAttendance $row) => $row->schedule_id . ':' . Carbon::parse($row->tanggal)->toDateString());

        $subjectDays = collect();
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isSunday()) {
                continue;
            }

            foreach ($schedules as $schedule) {
                if ($schedule->hari !== $dayNames[$date->dayOfWeekIso] || SchoolCalendar::isHolidayAt($date, $schedule->jam_mulai)) {
                    continue;
                }

                $teacher = $schedule->teacherSubject->teacher;
                $classroom = $schedule->teacherSubject->classroom;
                $subject = $schedule->teacherSubject->subject;
                if (!$teacher || !$classroom || !$subject) {
                    continue;
                }

                $dateKey = $date->toDateString();
                $groupKey = implode(':', [$teacher->id, $classroom->id, $subject->id]);
                $attendance = $activities->get($schedule->id . ':' . $dateKey);
                $hasStudentAttendance = (int) ($attendance?->attendance_details_count ?? 0) > 0;
                $hasAgenda = $attendance?->status === 'Selesai';
                $hasActivity = $hasStudentAttendance
                    || $hasAgenda
                    || !empty($attendance?->foto_guru_path)
                    || !empty($attendance?->tugas_file_path)
                    || !empty($attendance?->tugas_deskripsi)
                    || !empty($attendance?->materi_pembelajaran)
                    || !empty($attendance?->catatan_guru);
                $group = $subjectDays->get($groupKey, [
                    'teacher_id' => (int) $teacher->id,
                    'name' => $teacher->nama_lengkap,
                    'classroom' => $classroom->nama_kelas,
                    'subject' => $subject->nama_mapel,
                    'scheduled_dates' => [],
                    'issue_dates' => [],
                ]);
                $group['scheduled_dates'][$dateKey] = true;

                if (!$hasStudentAttendance) {
                    $group['issue_dates']['student_attendance'][$dateKey] = true;
                }
                if (!$hasAgenda) {
                    $group['issue_dates']['agenda'][$dateKey] = true;
                }
                if (!$hasActivity) {
                    $group['issue_dates']['no_activity'][$dateKey] = true;
                }

                $subjectDays->put($groupKey, $group);
            }
        }

        $warnings = collect();
        foreach ($subjectDays as $subjectDay) {
            $scheduledDates = array_keys($subjectDay['scheduled_dates']);
            sort($scheduledDates);
            foreach (self::TEACHER_ISSUES as $issue => $label) {
                $issueDates = array_keys($subjectDay['issue_dates'][$issue] ?? []);
                sort($issueDates);
                $metrics = $this->warningMetrics($issueDates, $scheduledDates, $includeSemesterThreshold);

                if (!$metrics['triggered']) {
                    continue;
                }

                $warnings->push([
                    'name' => $subjectDay['name'],
                    'classroom' => $subjectDay['classroom'],
                    'subject' => $subjectDay['subject'],
                    'issue' => $label,
                    'total_days' => count($issueDates),
                    'consecutive_days' => $metrics['consecutive'],
                    'peak_month_days' => $metrics['peak_month'],
                    'warning' => $metrics['warning'],
                ]);
            }
        }

        return $warnings->sortBy(fn(array $row) => mb_strtolower(
            $row['name'] . '|' . $row['classroom'] . '|' . $row['subject'] . '|' . $row['issue']
        ))->values();
    }

    private function warningMetrics(array $dates, array $eligibleDays, bool $includeSemesterThreshold): array
    {
        $dateSet = array_fill_keys($dates, true);
        $monthCounts = [];
        foreach ($dates as $date) {
            $month = substr($date, 0, 7);
            $monthCounts[$month] = ($monthCounts[$month] ?? 0) + 1;
        }

        $currentStreak = 0;
        $maxStreak = 0;
        foreach ($eligibleDays as $eligibleDate) {
            if (isset($dateSet[$eligibleDate])) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        $peakMonth = $monthCounts ? max($monthCounts) : 0;
        $peakMonthName = '';
        if ($peakMonth > 0) {
            $peakMonthKey = array_search($peakMonth, $monthCounts, true);
            $peakMonthName = Carbon::createFromFormat('!Y-m', $peakMonthKey)->format('m/Y');
        }
        $reasons = [];
        if ($maxStreak >= 5) {
            $reasons[] = "berturut-turut {$maxStreak} hari (ambang: 5)";
        }
        if ($peakMonth >= 10) {
            $reasons[] = "{$peakMonth} hari pada {$peakMonthName} (ambang bulanan: 10)";
        }
        if ($includeSemesterThreshold && count($dates) >= 30) {
            $reasons[] = count($dates) . ' hari selama semester (ambang semester: 30)';
        }

        return [
            'triggered' => $reasons !== [],
            'consecutive' => $maxStreak,
            'peak_month' => $peakMonth,
            'peak_month_name' => $peakMonthName,
            'warning' => implode(', ', $reasons),
        ];
    }
}
