<?php

namespace App\Http\Controllers;

use App\Exports\SiswaAttendanceHistoryExport;
use App\Models\AttendanceDetail;
use App\Models\OfficerAttendancePermit;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentLeaveRequest;
use App\Models\TeacherAttendance;
use App\Models\TeacherLeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                                ->where('status_pengajuan', 'Disetujui')
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
        $officerPermit = null;
        $canOfficerFillAttendance = false;
        $statusByStudentId = [];

        if ($selectedSchedule && $selectedSchedule->teacherSubject) {
            $activeLeaveRequest = TeacherLeaveRequest::query()
                ->where('teacher_id', $selectedSchedule->teacherSubject->teacher_id)
                ->where('status_pengajuan', 'Disetujui')
                ->whereDate('tanggal_mulai', '<=', $today->toDateString())
                ->whereDate('tanggal_selesai', '>=', $today->toDateString())
                ->latest('id')
                ->first();

            $officerPermit = OfficerAttendancePermit::query()
                ->where('officer_student_id', $officer->id)
                ->where('schedule_id', $selectedSchedule->id)
                ->whereDate('request_date', $today->toDateString())
                ->latest('id')
                ->first();

            $canOfficerFillAttendance = (bool) $activeLeaveRequest
                && (bool) $officerPermit
                && $officerPermit->status_pengajuan === 'Disetujui';

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
            'officerPermit' => $officerPermit,
            'canOfficerFillAttendance' => $canOfficerFillAttendance,
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

        // Use provided tanggal or default to today; only load data when filter is explicitly submitted
        $tanggalInput = $request->query('tanggal', '');
        $hasFilter    = $tanggalInput !== '';
        $today        = $hasFilter ? Carbon::parse($tanggalInput) : Carbon::today();
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        $todaySchedules = collect();

        if ($hasFilter && $todayDayName !== null && !$isWeekendHoliday) {
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

        $studentsCount = 0;

        if ($hasFilter && !empty($allowedClassroomIds)) {
            $studentsQuery = Student::query()->whereIn('classroom_id', $allowedClassroomIds);

            if ($selectedClassroomId !== 0) {
                $studentsQuery->where('classroom_id', $selectedClassroomId);
            }

            $studentsCount = (clone $studentsQuery)->count();
        }

        // Get ALL schedules for the selected date (for teacher attendance summary cards)
        $summaryPayload = [
            'allTeacherAttendances' => collect(),
            'todaySchedules' => collect(),
            'teachersWithAttendance' => collect(),
            'teachersWithoutAttendance' => collect(),
        ];

        if ($hasFilter && $todayDayName !== null && !$isWeekendHoliday) {
            $summaryPayload = Cache::remember(
                $this->guruAttendanceSummaryCacheKey($today->toDateString()),
                now()->addMinutes(2),
                function () use ($today, $todayDayName) {
                    $allTodaySchedules = Schedule::query()
                        ->with(['teacherSubject.teacher', 'teacherSubject.classroom', 'teacherSubject.subject'])
                        ->where('hari', $todayDayName)
                        ->whereHas('teacherSubject.teacher')
                        ->orderBy('jam_mulai')
                        ->get();

                    $allScheduleIds = $allTodaySchedules->pluck('id')->values();
                    $allTeacherAttendances = TeacherAttendance::query()
                        ->whereDate('tanggal', $today->toDateString())
                        ->whereIn('schedule_id', $allScheduleIds)
                        ->get()
                        ->keyBy('schedule_id');

                    $teachersFromSchedules = $allTodaySchedules
                        ->map(function ($schedule) {
                            $schedTeacher = $schedule->teacherSubject->teacher;
                            return [
                                'schedule' => $schedule,
                                'teacher' => $schedTeacher,
                                'teacher_id' => $schedTeacher?->id,
                            ];
                        })
                        ->groupBy('teacher_id')
                        ->map(function ($items) use ($allTeacherAttendances) {
                            $schedules = $items->pluck('schedule');
                            $teacher = $items[0]['teacher'];

                            $hasAttendance = false;
                            $attendanceSchedule = null;

                            foreach ($schedules as $schedule) {
                                if ($allTeacherAttendances->has($schedule->id)) {
                                    $hasAttendance = true;
                                    $attendanceSchedule = $schedule;
                                    break;
                                }
                            }

                            return [
                                'teacher' => $teacher,
                                'schedules' => $schedules,
                                'has_attendance' => $hasAttendance,
                                'attendance_schedule' => $attendanceSchedule,
                            ];
                        })
                        ->values();

                    return [
                        'allTeacherAttendances' => $allTeacherAttendances,
                        'todaySchedules' => $allTodaySchedules,
                        'teachersWithAttendance' => $teachersFromSchedules->filter(fn($item) => $item['has_attendance'])->values(),
                        'teachersWithoutAttendance' => $teachersFromSchedules->filter(fn($item) => !$item['has_attendance'])->values(),
                    ];
                }
            );
        }

        return view('guru.attendance_details.index', [
            'teacher' => $teacher,
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'classOptions' => $classOptions,
            'selectedClassroomId' => $selectedClassroomId,
            'tanggalFilter' => $today->toDateString(),
            'hasFilter' => $hasFilter,
            'studentsCount' => $studentsCount,
            'disableAttendanceActions' => $isWeekendHoliday || !$hasFilter || $studentsCount === 0,
            'teachersWithAttendance' => $summaryPayload['teachersWithAttendance'],
            'teachersWithoutAttendance' => $summaryPayload['teachersWithoutAttendance'],
            'allTeacherAttendances' => $summaryPayload['allTeacherAttendances'],
            'todaySchedules' => $summaryPayload['todaySchedules'],
        ]);
    }

    public function guruDatatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);

        $user = auth()->user();

        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        if (!$teacher) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
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

        $tanggalInput = (string) $request->query('tanggal', '');
        $hasFilter = $tanggalInput !== '';

        if (!$hasFilter) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $today = Carbon::parse($tanggalInput);
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        if ($todayDayName === null || $isWeekendHoliday) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $todaySchedules = Schedule::query()
            ->with(['teacherSubject.classroom', 'teacherSubject.subject'])
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderBy('jam_mulai')
            ->get();

        $classOptions = $todaySchedules
            ->map(function ($schedule) {
                return $schedule->teacherSubject->classroom;
            })
            ->filter()
            ->unique('id')
            ->values();

        $selectedClassroomId = (int) $request->query('classroom_id', 0);
        $allowedClassroomIds = $classOptions->pluck('id')->map(fn($id) => (int) $id)->values()->all();

        if ($selectedClassroomId !== 0 && !in_array($selectedClassroomId, $allowedClassroomIds, true)) {
            $selectedClassroomId = 0;
        }

        if (empty($allowedClassroomIds)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $studentsQuery = Student::query()
            ->with('classroom')
            ->whereIn('classroom_id', $allowedClassroomIds);

        if ($selectedClassroomId !== 0) {
            $studentsQuery->where('classroom_id', $selectedClassroomId);
        }

        $recordsTotal = (clone $studentsQuery)->count();

        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($searchValue !== '') {
            $studentsQuery->where(function ($query) use ($searchValue) {
                $query->where('nama_lengkap', 'like', "%{$searchValue}%")
                    ->orWhere('nis', 'like', "%{$searchValue}%")
                    ->orWhereHas('classroom', function ($classroomQuery) use ($searchValue) {
                        $classroomQuery->where('nama_kelas', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $studentsQuery)->count();

        $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 2);
        $orderDirection = strtolower((string) data_get($request->input('order', []), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $columnOrderMap = [
            2 => 'students.nama_lengkap',
            3 => 'students.classroom_id',
        ];
        $orderColumn = $columnOrderMap[$orderColumnIndex] ?? 'students.nama_lengkap';

        $students = $studentsQuery
            ->reorder()
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('students.id')
            ->skip($start)
            ->take($length)
            ->get();

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

        $scheduleIds = collect($primaryScheduleByClass)->pluck('id')->map(fn($id) => (int) $id)->values();

        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $attendanceStatusByStudent = AttendanceDetail::query()
            ->whereIn('teacher_attendance_id', $teacherAttendances->pluck('id')->values())
            ->whereIn('student_id', $students->pluck('id')->values())
            ->pluck('status', 'student_id')
            ->all();

        $csrfToken = csrf_token();

        $data = $students->values()->map(function (Student $student, int $index) use (
            $start,
            $attendanceStatusByStudent,
            $csrfToken
        ) {
            $rawStatus = $attendanceStatusByStudent[$student->id] ?? null;
            $displayStatus = $rawStatus === 'Alpha' ? 'Alpa' : ($rawStatus ?? 'Belum Absen');

            if ($displayStatus === 'Hadir') {
                $statusBadge = '<span class="badge badge-success status-badge">Hadir</span>';
            } elseif ($displayStatus === 'Sakit') {
                $statusBadge = '<span class="badge badge-warning status-badge">Sakit</span>';
            } elseif ($displayStatus === 'Izin') {
                $statusBadge = '<span class="badge badge-info status-badge">Izin</span>';
            } elseif ($displayStatus === 'Alpa') {
                $statusBadge = '<span class="badge badge-danger status-badge">Alpa</span>';
            } elseif ($displayStatus === 'Terlambat') {
                $statusBadge = '<span class="badge badge-warning status-badge">Terlambat</span>';
            } else {
                $statusBadge = '<span class="badge badge-secondary status-badge">Belum Absen</span>';
            }

            $buildActionForm = function (string $status, string $buttonClass, string $label) use ($student, $csrfToken) {
                return '<form method="POST" action="' . route('guru.attendance-details.submit', $student->id) . '" class="d-inline">'
                    . '<input type="hidden" name="_token" value="' . $csrfToken . '">'
                    . '<input type="hidden" name="classroom_id" value="' . (int) $student->classroom_id . '">'
                    . '<input type="hidden" name="status" value="' . e($status) . '">'
                    . '<button type="submit" class="btn ' . $buttonClass . ' btn-xs">' . e($label) . '</button>'
                    . '</form>';
            };

            $aksi = '<div class="attendance-actions">'
                . $buildActionForm('Hadir', 'btn-success', 'Hadir')
                . '<button type="button" class="btn btn-warning btn-xs" disabled title="Nonaktif, gunakan approval izin/sakit wali kelas">Sakit</button>'
                . '<button type="button" class="btn btn-info btn-xs" disabled title="Nonaktif, gunakan approval izin/sakit wali kelas">Izin</button>'
                . $buildActionForm('Alpa', 'btn-danger', 'Alpa')
                . $buildActionForm('Terlambat', 'btn-warning', 'Terlambat')
                . '</div>';

            return [
                'checkbox' => '<input type="checkbox" class="check-student" name="student_ids[]" value="' . $student->id . '" form="bulkAttendanceForm">',
                'no' => $start + $index + 1,
                'nama' => e($student->nama_lengkap),
                'kelas' => e($student->classroom->nama_kelas ?? '-'),
                'status' => $statusBadge,
                'aksi' => $aksi,
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function guruTeacherAttendanceDetail(Request $request)
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
                ->with(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject.teacher')
                ->orderBy('jam_mulai')
                ->get();
        }

        // Get all teacher attendance for today's schedules
        $scheduleIds = $todaySchedules->pluck('id')->values();
        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        // Get all unique teachers from today's schedules
        $teachersData = $todaySchedules
            ->map(function ($schedule) use ($teacherAttendances) {
                $schedTeacher = $schedule->teacherSubject->teacher;
                $hasAttendance = $teacherAttendances->has($schedule->id);
                $teacherAttendanceRecord = $teacherAttendances->get($schedule->id);

                return [
                    'schedule' => $schedule,
                    'teacher' => $schedTeacher,
                    'teacher_id' => $schedTeacher?->id,
                    'subject' => $schedule->teacherSubject->subject,
                    'classroom' => $schedule->teacherSubject->classroom,
                    'jam_mulai' => $schedule->jam_mulai,
                    'jam_selesai' => $schedule->jam_selesai,
                    'has_attendance' => $hasAttendance,
                    'status' => $hasAttendance ? ($teacherAttendanceRecord?->kehadiran_guru ?? 'Hadir') : 'Belum Absen',
                    'teacher_attendance_id' => $teacherAttendanceRecord?->id,
                ];
            })
            ->groupBy('teacher_id')
            ->map(function ($items) {
                $firstItem = $items[0];
                $hasAnyAttendance = $items->contains('has_attendance', true);

                return [
                    'teacher' => $firstItem['teacher'],
                    'schedules' => $items->values(),
                    'has_attendance' => $hasAnyAttendance,
                    'status' => $hasAnyAttendance ? 'Sudah Absen' : 'Belum Absen',
                ];
            })
            ->sortBy(fn($item) => $item['teacher']?->nama_lengkap ?? '')
            ->values();

        $teachersWithAttendance = $teachersData->filter(fn($item) => $item['has_attendance'])->values();
        $teachersWithoutAttendance = $teachersData->filter(fn($item) => !$item['has_attendance'])->values();

        $filter = $request->query('filter', 'all'); // 'all', 'sudah', 'belum'
        if (!in_array($filter, ['all', 'sudah', 'belum'])) {
            $filter = 'all';
        }

        return view('guru.attendance_details.teacher_attendance_detail', [
            'teacher' => $teacher,
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'todaySchedules' => $todaySchedules,
            'teachersData' => $teachersData,
            'teachersWithAttendance' => $teachersWithAttendance,
            'teachersWithoutAttendance' => $teachersWithoutAttendance,
            'filter' => $filter,
        ]);
    }

    public function adminTeacherAttendanceDetail(Request $request)
    {
        $today = Carbon::today();
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
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        $todaySchedules = collect();
        if ($todayDayName !== null && !$isWeekendHoliday) {
            $todaySchedules = Schedule::query()
                ->with(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject.teacher')
                ->orderBy('jam_mulai')
                ->get();
        }

        $scheduleIds = $todaySchedules->pluck('id')->values();
        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $teachersData = $todaySchedules
            ->map(function ($schedule) use ($teacherAttendances) {
                $schedTeacher = $schedule->teacherSubject->teacher;
                $hasAttendance = $teacherAttendances->has($schedule->id);
                $record = $teacherAttendances->get($schedule->id);
                return [
                    'schedule' => $schedule,
                    'teacher' => $schedTeacher,
                    'teacher_id' => $schedTeacher?->id,
                    'subject' => $schedule->teacherSubject->subject,
                    'classroom' => $schedule->teacherSubject->classroom,
                    'jam_mulai' => $schedule->jam_mulai,
                    'jam_selesai' => $schedule->jam_selesai,
                    'has_attendance' => $hasAttendance,
                    'status' => $hasAttendance ? ($record?->kehadiran_guru ?? 'Hadir') : 'Belum Absen',
                    'teacher_attendance_id' => $record?->id,
                ];
            })
            ->groupBy('teacher_id')
            ->map(function ($items) {
                $firstItem = $items[0];
                $hasAny = $items->contains('has_attendance', true);
                return [
                    'teacher' => $firstItem['teacher'],
                    'schedules' => $items->values(),
                    'has_attendance' => $hasAny,
                    'status' => $hasAny ? 'Sudah Absen' : 'Belum Absen',
                ];
            })
            ->sortBy(fn($item) => $item['teacher']?->nama_lengkap ?? '')
            ->values();

        $teachersWithAttendance = $teachersData->filter(fn($i) => $i['has_attendance'])->values();
        $teachersWithoutAttendance = $teachersData->filter(fn($i) => !$i['has_attendance'])->values();

        $filter = $request->query('filter', 'all');
        if (!in_array($filter, ['all', 'sudah', 'belum'])) {
            $filter = 'all';
        }

        return view('admin.attendance_details.teacher_attendance_detail', [
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'todaySchedules' => $todaySchedules,
            'teachersData' => $teachersData,
            'teachersWithAttendance' => $teachersWithAttendance,
            'teachersWithoutAttendance' => $teachersWithoutAttendance,
            'filter' => $filter,
        ]);
    }

    public function index(Request $request)
    {
        $today = Carbon::today();
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
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        $allTodaySchedules = collect();
        if ($todayDayName !== null && !$isWeekendHoliday) {
            $allTodaySchedules = Schedule::query()
                ->with(['teacherSubject.teacher', 'teacherSubject.classroom', 'teacherSubject.subject'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject.teacher')
                ->orderBy('jam_mulai')
                ->get();
        }

        $allScheduleIds = $allTodaySchedules->pluck('id')->values();
        $todayTeacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $allScheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $teachersFromSchedules = $allTodaySchedules
            ->map(fn($s) => ['schedule' => $s, 'teacher' => $s->teacherSubject->teacher, 'teacher_id' => $s->teacherSubject->teacher?->id])
            ->groupBy('teacher_id')
            ->map(function ($items) use ($todayTeacherAttendances) {
                $teacher = $items[0]['teacher'];
                $hasAttendance = $items->pluck('schedule')->some(fn($s) => $todayTeacherAttendances->has($s->id));
                return ['teacher' => $teacher, 'has_attendance' => $hasAttendance];
            })
            ->values();

        $teachersWithAttendance    = $teachersFromSchedules->filter(fn($i) => $i['has_attendance'])->values();
        $teachersWithoutAttendance = $teachersFromSchedules->filter(fn($i) => !$i['has_attendance'])->values();

        // Filter options always loaded from all records
        $filterTahunAjarans = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('academic_years', 'academic_years.id', '=', 'teacher_attendances.academic_year_id')
            ->select('academic_years.tahun_ajaran')->distinct()
            ->orderByDesc('academic_years.tahun_ajaran')->pluck('academic_years.tahun_ajaran');

        $filterGurus = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('teachers', 'teachers.id', '=', 'teacher_attendances.teacher_id')
            ->select('teachers.nama_lengkap')->distinct()
            ->orderBy('teachers.nama_lengkap')->pluck('teachers.nama_lengkap');

        $filterMapels = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('subjects', 'subjects.id', '=', 'teacher_attendances.subject_id')
            ->select('subjects.nama_mapel')->distinct()
            ->orderBy('subjects.nama_mapel')->pluck('subjects.nama_mapel');

        $filterKelas = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->join('classrooms', 'classrooms.id', '=', 'teacher_attendances.classroom_id')
            ->select('classrooms.nama_kelas')->distinct()
            ->orderBy('classrooms.nama_kelas')->pluck('classrooms.nama_kelas');

        $filterStatuses = AttendanceDetail::query()
            ->select('status')->distinct()->orderBy('status')->pluck('status');

        $filters    = $request->only(['tahun_ajaran', 'tanggal', 'guru', 'mapel', 'kelas', 'status']);
        $hasFilter  = (bool) array_filter($filters);

        $attendanceDetails = collect();

        $teacherAttendances = \App\Models\TeacherAttendance::with(['teacher', 'classroom', 'subject', 'academicYear'])
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        $students = Student::with('classroom')->orderBy('nama_lengkap')->get();

        return view('admin.attendance_details.index', compact(
            'attendanceDetails',
            'hasFilter',
            'filters',
            'teacherAttendances',
            'students',
            'filterTahunAjarans',
            'filterGurus',
            'filterMapels',
            'filterKelas',
            'filterStatuses',
            'teachersWithAttendance',
            'teachersWithoutAttendance',
            'isWeekendHoliday',
            'todayDayName'
        ));
    }

    public function adminDatatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 25), 100), 10);

        $filters = [
            'tahun_ajaran' => (string) $request->input('tahun_ajaran', ''),
            'tanggal' => (string) $request->input('tanggal', ''),
            'guru' => (string) $request->input('guru', ''),
            'mapel' => (string) $request->input('mapel', ''),
            'kelas' => (string) $request->input('kelas', ''),
            'status' => (string) $request->input('status', ''),
        ];

        $hasFilter = (bool) array_filter($filters);
        if (!$hasFilter) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = AttendanceDetail::query()
            ->with([
                'teacherAttendance.teacher',
                'teacherAttendance.classroom',
                'teacherAttendance.subject',
                'teacherAttendance.academicYear',
                'student.classroom',
            ])
            ->select('attendance_details.*');

        if ($filters['tahun_ajaran'] !== '') {
            $query->whereHas('teacherAttendance.academicYear', fn($q) => $q->where('tahun_ajaran', $filters['tahun_ajaran']));
        }
        if ($filters['tanggal'] !== '') {
            $query->whereHas('teacherAttendance', fn($q) => $q->whereDate('tanggal', $filters['tanggal']));
        }
        if ($filters['guru'] !== '') {
            $query->whereHas('teacherAttendance.teacher', fn($q) => $q->where('nama_lengkap', $filters['guru']));
        }
        if ($filters['mapel'] !== '') {
            $query->whereHas('teacherAttendance.subject', fn($q) => $q->where('nama_mapel', $filters['mapel']));
        }
        if ($filters['kelas'] !== '') {
            $query->whereHas('teacherAttendance.classroom', fn($q) => $q->where('nama_kelas', $filters['kelas']));
        }
        if ($filters['status'] !== '') {
            $statusFilter = $filters['status'] === 'Alpa' ? 'Alpha' : $filters['status'];
            $query->where('attendance_details.status', $statusFilter);
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($searchValue !== '') {
            $query->where(function ($searchQuery) use ($searchValue) {
                $searchQuery->where('attendance_details.status', 'like', "%{$searchValue}%")
                    ->orWhere('attendance_details.keterangan', 'like', "%{$searchValue}%")
                    ->orWhere('attendance_details.jam_absen', 'like', "%{$searchValue}%")
                    ->orWhereHas('student', function ($studentQuery) use ($searchValue) {
                        $studentQuery->where('nama_lengkap', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('teacherAttendance.teacher', function ($teacherQuery) use ($searchValue) {
                        $teacherQuery->where('nama_lengkap', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('teacherAttendance.subject', function ($subjectQuery) use ($searchValue) {
                        $subjectQuery->where('nama_mapel', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('teacherAttendance.classroom', function ($classroomQuery) use ($searchValue) {
                        $classroomQuery->where('nama_kelas', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $rows = $query
            ->latest('attendance_details.id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->values()->map(function (AttendanceDetail $item, int $index) use ($start) {
            $rawStatus = (string) $item->status;
            $displayStatus = $rawStatus === 'Alpha' ? 'Alpa' : $rawStatus;

            return [
                'checkbox' => '<input type="checkbox" class="check-attendance-detail" value="' . $item->id . '">',
                'no' => $start + $index + 1,
                'tanggal' => e((string) ($item->teacherAttendance->tanggal ?? '-')),
                'guru' => e((string) ($item->teacherAttendance->teacher->nama_lengkap ?? '-')),
                'mapel' => e((string) ($item->teacherAttendance->subject->nama_mapel ?? '-')),
                'kelas' => e((string) ($item->student->classroom->nama_kelas ?? '-')),
                'siswa' => e((string) ($item->student->nama_lengkap ?? '-')),
                'status' => e($displayStatus),
                'jam_absen' => e((string) ($item->jam_absen ?? '-')),
                'keterangan' => e((string) ($item->keterangan ?? '-')),
                'aksi' => '<button class="btn btn-warning btn-xs btn-edit" data-id="' . $item->id . '"><i class="fas fa-edit"></i></button> '
                    . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $item->id . '"><i class="fas fa-trash"></i></button>',
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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
            'status' => 'required|in:Hadir,Alpa,Terlambat',
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

        $this->forgetGuruAttendanceSummaryCache($today->toDateString());

        return redirect()->route('guru.attendance-details.index', [
            'tanggal' => $today->toDateString(),
            'classroom_id' => $classroomId,
        ])
            ->with('success', 'Status absensi siswa berhasil disimpan.')
            ->with('offer_agenda_redirect', true)
            ->with('agenda_redirect_url', route('guru.agenda.index'));
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
            ->where('status_pengajuan', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        if (!$leaveRequest) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Absensi siswa oleh pengurus kelas hanya aktif saat guru izin sudah disetujui kurikulum.');
        }

        $approvedPermit = OfficerAttendancePermit::query()
            ->where('officer_student_id', $officer->id)
            ->where('schedule_id', $schedule->id)
            ->whereDate('request_date', $today->toDateString())
            ->where('status_pengajuan', 'Disetujui')
            ->exists();

        if (!$approvedPermit) {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
                ->with('error', 'Ajukan izin absen kelas ke kurikulum terlebih dahulu untuk jadwal ini.');
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
            'bulk_status' => 'required|in:Hadir,Alpa,Terlambat',
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

        // Pre-load siswa yang sudah diverifikasi izin/sakit oleh wali kelas untuk hari ini
        $approvedLeaveStudentIds = StudentLeaveRequest::query()
            ->whereIn('student_id', $studentIds->all())
            ->where('status_pengajuan', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->pluck('student_id')
            ->map(fn($id) => (int) $id)
            ->flip()
            ->all();

        $teacherAttendanceBySchedule = [];
        $savedCount = 0;
        $skippedCount = 0;
        $status = $validated['bulk_status'] === 'Alpa' ? 'Alpha' : $validated['bulk_status'];

        $validPairs = [];
        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);

            if (!$student) {
                continue;
            }

            if (isset($approvedLeaveStudentIds[$studentId])) {
                $skippedCount++;
                continue;
            }

            $classroomId = (int) $student->classroom_id;
            $schedule = $primaryScheduleByClass[$classroomId] ?? null;

            if (!$schedule || !$schedule->teacherSubject) {
                continue;
            }

            $validPairs[] = [
                'student_id' => $studentId,
                'schedule' => $schedule,
            ];
        }

        $neededScheduleIds = collect($validPairs)
            ->map(fn($pair) => (int) $pair['schedule']->id)
            ->unique()
            ->values();

        if ($neededScheduleIds->isNotEmpty()) {
            $teacherAttendanceBySchedule = TeacherAttendance::query()
                ->whereDate('tanggal', $today->toDateString())
                ->whereIn('schedule_id', $neededScheduleIds)
                ->get()
                ->keyBy('schedule_id')
                ->all();

            $missingScheduleIds = $neededScheduleIds
                ->reject(fn($scheduleId) => isset($teacherAttendanceBySchedule[$scheduleId]))
                ->values();

            if ($missingScheduleIds->isNotEmpty()) {
                $lastPertemuanBySchedule = TeacherAttendance::query()
                    ->selectRaw('schedule_id, MAX(pertemuan) as max_pertemuan')
                    ->whereIn('schedule_id', $missingScheduleIds)
                    ->groupBy('schedule_id')
                    ->pluck('max_pertemuan', 'schedule_id');

                foreach ($missingScheduleIds as $scheduleId) {
                    $schedule = $todaySchedules->firstWhere('id', (int) $scheduleId);

                    if (!$schedule || !$schedule->teacherSubject) {
                        continue;
                    }

                    $teacherAttendance = TeacherAttendance::create([
                        'teacher_id' => $schedule->teacherSubject->teacher_id,
                        'schedule_id' => (int) $scheduleId,
                        'classroom_id' => $schedule->teacherSubject->classroom_id,
                        'subject_id' => $schedule->teacherSubject->subject_id,
                        'academic_year_id' => $schedule->teacherSubject->academic_year_id,
                        'tanggal' => $today->toDateString(),
                        'pertemuan' => max(((int) ($lastPertemuanBySchedule[(int) $scheduleId] ?? 0)) + 1, 1),
                        'materi_pembelajaran' => null,
                        'catatan_guru' => null,
                        'status' => 'Draft',
                    ]);

                    $teacherAttendanceBySchedule[(int) $scheduleId] = $teacherAttendance;
                }
            }
        }

        $nowTime = now()->format('H:i:s');
        $nowStamp = now();
        $upsertRows = [];

        foreach ($validPairs as $pair) {
            $scheduleId = (int) $pair['schedule']->id;
            $teacherAttendance = $teacherAttendanceBySchedule[$scheduleId] ?? null;

            if (!$teacherAttendance) {
                continue;
            }

            $upsertRows[] = [
                'teacher_attendance_id' => $teacherAttendance->id,
                'student_id' => $pair['student_id'],
                'status' => $status,
                'keterangan' => null,
                'jam_absen' => $nowTime,
                'created_at' => $nowStamp,
                'updated_at' => $nowStamp,
            ];
        }

        if (!empty($upsertRows)) {
            AttendanceDetail::query()->upsert(
                $upsertRows,
                ['teacher_attendance_id', 'student_id'],
                ['status', 'keterangan', 'jam_absen', 'updated_at']
            );

            $savedCount = count($upsertRows);
        }

        if ($savedCount === 0) {
            return redirect()->route('guru.attendance-details.index', ['classroom_id' => $selectedClassroomId])
                ->with('error', 'Tidak ada data yang tersimpan. Pastikan siswa berada pada kelas sesuai jadwal Anda hari ini.');
        }

        $message = "Berhasil menyimpan absensi {$savedCount} siswa.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} siswa dilewati karena memiliki izin/sakit yang sudah diverifikasi wali kelas.";
        }

        $this->forgetGuruAttendanceSummaryCache($today->toDateString());

        return redirect()->route('guru.attendance-details.index', [
            'tanggal' => $today->toDateString(),
            'classroom_id' => $selectedClassroomId,
        ])
            ->with('success', $message)
            ->with('offer_agenda_redirect', true)
            ->with('agenda_redirect_url', route('guru.agenda.index'));
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
            ->where('status_pengajuan', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        if (!$leaveRequest) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Absensi siswa oleh pengurus kelas hanya aktif saat guru izin sudah disetujui kurikulum.');
        }

        $approvedPermit = OfficerAttendancePermit::query()
            ->where('officer_student_id', $officer->id)
            ->where('schedule_id', $schedule->id)
            ->whereDate('request_date', $today->toDateString())
            ->where('status_pengajuan', 'Disetujui')
            ->exists();

        if (!$approvedPermit) {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
                ->with('error', 'Ajukan izin absen kelas ke kurikulum terlebih dahulu untuk jadwal ini.');
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

        $nowTime = now()->format('H:i:s');
        $nowStamp = now();

        $upsertRows = $students->map(function ($student) use ($teacherAttendance, $status, $nowTime, $nowStamp) {
            return [
                'teacher_attendance_id' => $teacherAttendance->id,
                'student_id' => $student->id,
                'status' => $status,
                'keterangan' => null,
                'jam_absen' => $nowTime,
                'created_at' => $nowStamp,
                'updated_at' => $nowStamp,
            ];
        })->all();

        AttendanceDetail::query()->upsert(
            $upsertRows,
            ['teacher_attendance_id', 'student_id'],
            ['status', 'keterangan', 'jam_absen', 'updated_at']
        );

        $this->forgetGuruAttendanceSummaryCache($today->toDateString());

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

    private function guruAttendanceSummaryCacheKey(string $date): string
    {
        return 'guru-attendance-summary:' . $date;
    }

    private function forgetGuruAttendanceSummaryCache(string $date): void
    {
        Cache::forget($this->guruAttendanceSummaryCacheKey($date));
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
