<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\PjjAssignment;
use App\Models\PjjAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\PjjAttendanceSyncService;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PjjWaliMonitoringController extends Controller
{
    public function __construct(private readonly PjjAttendanceSyncService $attendanceSync)
    {
    }

    public function index(Request $request)
    {
        abort_unless(SchoolCalendar::isPjj(Carbon::today()), 403, 'Monitoring PJJ hanya tersedia saat mode PJJ aktif.');

        $teacher = $this->waliTeacherForCurrentUser();
        $classroom = $teacher->waliClassroom;
        abort_unless($classroom, 403, 'Anda tidak terdaftar sebagai wali kelas.');

        $pjjDates = $this->configuredPjjDates();
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
        ]);

        $today = Carbon::today()->toDateString();
        $date = Carbon::parse($request->input('date') ?: (in_array($today, $pjjDates, true) ? $today : (collect($pjjDates)->last() ?? $today)));
        $isToday = $date->isToday();

        $students = Student::query()
            ->where('classroom_id', $classroom->id)
            ->orderBy('nama_lengkap')
            ->get();

        $attendances = PjjAttendance::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        // Tugas PJJ yang ditujukan ke kelas wali pada tanggal terpilih, beserta pengumpulan siswa kelas ini.
        $assignments = PjjAssignment::query()
            ->whereHas('teacherSubject', fn($query) => $query->where('classroom_id', $classroom->id))
            ->whereDate('created_at', $date->toDateString())
            ->with([
                'teacher:id,nama_lengkap',
                'teacherSubject.subject:id,nama_mapel',
                'submissions' => fn($query) => $query->whereIn('student_id', $students->pluck('id')),
            ])
            ->orderBy('created_at')
            ->get();

        $rows = $students->map(function (Student $student) use ($attendances, $assignments, $teacher, $classroom, $isToday) {
            $attendance = $attendances->get($student->id);

            $pendingAssignments = $assignments->filter(
                fn(PjjAssignment $assignment) => !$assignment->submissions->contains('student_id', $student->id)
            )->values();

            $needsReminder = !$attendance || $pendingAssignments->isNotEmpty();

            return [
                'student' => $student,
                'attendance' => $attendance,
                'pending_assignments' => $pendingAssignments,
                'whatsapp_url' => $needsReminder && $isToday
                    ? $this->buildWhatsappUrl($student, $attendance, $pendingAssignments, $teacher, $classroom->nama_kelas)
                    : null,
            ];
        });

        $summary = [
            'total' => $students->count(),
            'attended' => $rows->filter(fn(array $row) => $row['attendance'] !== null)->count(),
            'late' => $rows->filter(fn(array $row) => $row['attendance']?->status === 'Terlambat')->count(),
            'not_attended' => $rows->filter(fn(array $row) => $row['attendance'] === null)->count(),
            'assignments' => $assignments->count(),
            'pending_submissions' => $rows->sum(fn(array $row) => $row['pending_assignments']->count()),
        ];

        return view('guru.wali_kelas.pjj.monitoring', [
            'teacher' => $teacher,
            'classroom' => $classroom,
            'rows' => $rows,
            'assignments' => $assignments,
            'summary' => $summary,
            'pjjDates' => $pjjDates,
            'date' => $date,
            'isToday' => $isToday,
        ]);
    }

    /**
     * Force absen datang: wali kelas mencatatkan kehadiran PJJ siswa yang
     * terkendala perangkat/jaringan, lalu disinkronkan ke absensi reguler.
     */
    public function forceCheckIn(Request $request)
    {
        abort_unless(SchoolCalendar::isPjj(Carbon::today()), 403, 'Force absen hanya tersedia saat mode PJJ aktif.');

        $teacher = $this->waliTeacherForCurrentUser();

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'status' => ['required', 'in:Hadir,Terlambat'],
            'note' => ['required', 'string', 'max:255'],
        ], [
            'note.required' => 'Isi alasan/kendala siswa sebagai catatan force absen.',
        ]);

        $student = $this->studentOfWaliClassroom($teacher, (int) $data['student_id']);

        $attendance = null;

        DB::transaction(function () use ($student, $teacher, $data, &$attendance) {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            $exists = PjjAttendance::query()
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', Carbon::today())
                ->exists();

            if ($exists) {
                return;
            }

            $attendance = PjjAttendance::query()->create([
                'student_id' => $student->id,
                'attendance_date' => Carbon::today()->toDateString(),
                'selfie_path' => null,
                'check_in_at' => now(),
                'status' => $data['status'],
                'forced_at' => now(),
                'forced_note' => 'Force absen datang oleh wali kelas ' . $teacher->nama_lengkap . ': ' . $data['note'],
            ]);
        });

        if (!$attendance) {
            return response()->json(['message' => 'Siswa sudah memiliki absensi PJJ hari ini. Muat ulang halaman.'], 422);
        }

        // Sinkron ke absensi reguler seperti alur selfie siswa biasa.
        $this->attendanceSync->syncStudentAttendance($attendance);

        return response()->json([
            'message' => $student->nama_lengkap . ' berhasil diabsenkan datang (status ' . $data['status'] . ') dan tersinkron ke absensi reguler.',
        ]);
    }

    /**
     * Force absen pulang: lengkapi check-out siswa yang sudah absen datang
     * namun terkendala saat absen pulang.
     */
    public function forceCheckOut(Request $request)
    {
        abort_unless(SchoolCalendar::isPjj(Carbon::today()), 403, 'Force absen hanya tersedia saat mode PJJ aktif.');

        $teacher = $this->waliTeacherForCurrentUser();

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'note' => ['required', 'string', 'max:255'],
        ], [
            'note.required' => 'Isi alasan/kendala siswa sebagai catatan force absen.',
        ]);

        $student = $this->studentOfWaliClassroom($teacher, (int) $data['student_id']);

        // Mengikuti aturan absen pulang siswa: baru dapat dilakukan mulai pukul 15.00.
        if (now()->lt(Carbon::today()->setTime(15, 0))) {
            return response()->json(['message' => 'Force absen pulang baru dapat dilakukan mulai pukul 15.00. Saat ini pukul ' . now()->format('H:i') . '.'], 422);
        }

        $attendance = PjjAttendance::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Siswa belum absen datang. Lakukan force absen datang terlebih dahulu.'], 422);
        }

        if ($attendance->check_out_at) {
            return response()->json(['message' => 'Absen pulang siswa sudah tercatat. Muat ulang halaman.'], 422);
        }

        $forcedNote = trim((string) $attendance->forced_note);
        $checkOutNote = 'Force absen pulang oleh wali kelas ' . $teacher->nama_lengkap . ': ' . $data['note'];

        $attendance->update([
            'check_out_at' => now(),
            'forced_at' => $attendance->forced_at ?? now(),
            'forced_note' => $forcedNote !== '' ? $forcedNote . ' | ' . $checkOutNote : $checkOutNote,
        ]);

        return response()->json([
            'message' => 'Absen pulang ' . $student->nama_lengkap . ' berhasil dicatat pukul ' . now()->format('H:i') . '.',
        ]);
    }

    private function studentOfWaliClassroom(Teacher $teacher, int $studentId): Student
    {
        $student = Student::query()
            ->whereKey($studentId)
            ->where('classroom_id', $teacher->wali_classroom_id)
            ->first();

        abort_unless($student, 403, 'Siswa ini bukan anggota kelas Anda.');

        return $student;
    }

    /**
     * Susun tautan wa.me berisi pesan pengingat absen dan/atau tugas yang belum dikumpulkan.
     */
    private function buildWhatsappUrl(Student $student, ?PjjAttendance $attendance, $pendingAssignments, Teacher $teacher, string $className): ?string
    {
        $phone = $this->normalizePhone($student->no_hp ?: $student->no_hp_orang_tua);
        if ($phone === null) {
            return null;
        }

        $lines = [];
        $lines[] = "Assalamu'alaikum {$student->nama_lengkap},";
        $lines[] = "Saya {$teacher->nama_lengkap}, wali kelas {$className}.";

        if (!$attendance) {
            $lines[] = 'Mohon segera lakukan ABSENSI SELFIE PJJ hari ini melalui aplikasi SiAbsensi (absen datang & pulang).';
        }

        if ($pendingAssignments->isNotEmpty()) {
            $lines[] = 'Tugas PJJ berikut belum dikumpulkan:';
            foreach ($pendingAssignments as $assignment) {
                $deadline = $assignment->due_at
                    ? 'deadline ' . $assignment->due_at->format('d-m-Y H:i')
                    : 'tanpa deadline';
                $subject = $assignment->teacherSubject?->subject?->nama_mapel ?? '-';
                $lines[] = "- {$assignment->title} ({$subject}, {$deadline})";
            }
            $lines[] = 'Mohon segera kerjakan dan kumpulkan sebelum batas waktu.';
        }

        $lines[] = 'Terima kasih.';

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode(implode("\n", $lines));
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return '62' . $digits;
    }

    private function waliTeacherForCurrentUser(): Teacher
    {
        $user = Auth::user();

        // Prioritaskan identitas unik (NIP/NUPTK); pencocokan nama hanya fallback
        // agar guru dengan nama kembar tidak ter-resolve ke data guru yang salah.
        $baseQuery = fn() => Teacher::query()
            ->where('is_wali_kelas', true)
            ->whereNotNull('wali_classroom_id')
            ->with('waliClassroom');

        $teacher = $baseQuery()
            ->where(function ($query) use ($user) {
                $query->where('nip', $user->email)
                    ->orWhere('nuptk', $user->email);
            })
            ->first()
            ?? $baseQuery()->where('nama_lengkap', $user->name)->first();

        abort_unless($teacher, 403, 'Anda tidak terdaftar sebagai wali kelas.');

        return $teacher;
    }

    private function configuredPjjDates(): array
    {
        return Holiday::query()
            ->where('is_pjj', true)
            ->get(['tanggal', 'tanggal_akhir'])
            ->flatMap(function (Holiday $holiday): array {
                $start = Carbon::parse($holiday->tanggal)->startOfDay();
                $end = Carbon::parse($holiday->tanggal_akhir ?? $holiday->tanggal)->startOfDay();

                return collect(CarbonPeriod::create($start, $end))
                    ->map(fn(Carbon $date) => $date->toDateString())
                    ->all();
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
