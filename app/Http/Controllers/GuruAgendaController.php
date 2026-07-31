<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDetail;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GuruAgendaController extends Controller
{
    public function index()
    {
        if (!$this->hasAgendaColumns()) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Fitur agenda guru belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        [$today, $todayDayName, $isWeekendHoliday] = $this->resolveTodayContext();

        $todaySchedules = collect();

        if (!$isWeekendHoliday && $todayDayName !== null) {
            $todaySchedules = Schedule::query()
                ->with(['teacherSubject.subject', 'teacherSubject.classroom', 'teacherSubject.academicYear'])
                ->where('hari', $todayDayName)
                ->whereHas('teacherSubject', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->orderBy('jam_mulai')
                ->get();
        }

        // Load semua jadwal dari kelas yang diajar guru hari ini untuk menghitung jam ke yang akurat
        $classroomIds = $todaySchedules
            ->map(fn($s) => (int) ($s->teacherSubject?->classroom_id ?? 0))
            ->filter()->unique()->values()->all();

        $allClassroomSchedules = collect();
        if (!empty($classroomIds) && $todayDayName !== null) {
            $allClassroomSchedules = Schedule::query()
                ->with('teacherSubject')
                ->whereHas('teacherSubject', fn($q) => $q->whereIn('classroom_id', $classroomIds))
                ->where('hari', $todayDayName)
                ->orderBy('jam_mulai')
                ->get()
                ->groupBy(fn($s) => (int) ($s->teacherSubject?->classroom_id ?? 0));
        }

        // Helper: hitung jam ke berdasarkan posisi kumulatif + durasi per kelas
        $calcJamKe = function (Schedule $schedule) use ($allClassroomSchedules): string {
            $classroomId = (int) ($schedule->teacherSubject?->classroom_id ?? 0);
            $classSchedules = ($allClassroomSchedules->get($classroomId) ?? collect())
                ->sortBy('jam_mulai')
                ->values();

            if ($classSchedules->isEmpty()) {
                return '-';
            }

            // Tentukan durasi dasar 1 jam pelajaran (durasi terpendek, min 30 menit)
            $durations = $classSchedules->map(
                fn($s) => Carbon::parse($s->jam_mulai)->diffInMinutes(Carbon::parse($s->jam_selesai))
            )->filter()->sort()->values();

            $baseDuration = max(30, $durations->first() ?? 45);

            // Bangun peta jam mulai → [jam_ke_mulai, jam_ke_selesai] secara kumulatif
            $currentPeriod = 1;
            $periodMap     = [];
            foreach ($classSchedules as $cs) {
                $dur     = Carbon::parse($cs->jam_mulai)->diffInMinutes(Carbon::parse($cs->jam_selesai));
                $periods = max(1, (int) round($dur / $baseDuration));
                $periodMap[$cs->jam_mulai] = [$currentPeriod, $currentPeriod + $periods - 1];
                $currentPeriod += $periods;
            }

            if (isset($periodMap[$schedule->jam_mulai])) {
                [$start, $end] = $periodMap[$schedule->jam_mulai];
                return $start === $end ? (string) $start : "{$start}-{$end}";
            }

            return '-';
        };

        $scheduleIds = $todaySchedules->pluck('id')->values();

        $teacherAttendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy('schedule_id');

        $presentCountByAttendance = AttendanceDetail::query()
            ->selectRaw('teacher_attendance_id, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_hadir', ['Hadir'])
            ->whereIn('teacher_attendance_id', $teacherAttendances->pluck('id'))
            ->groupBy('teacher_attendance_id')
            ->pluck('total_hadir', 'teacher_attendance_id');

        $rows = $todaySchedules->values()->map(function ($schedule, $index) use ($teacherAttendances, $presentCountByAttendance, $teacher, $calcJamKe) {
            $teacherAttendance = $teacherAttendances->get($schedule->id);

            return [
                'jam_ke' => $calcJamKe($schedule),
                'schedule' => $schedule,
                'teacher' => $teacher,
                'teacher_attendance' => $teacherAttendance,
                'jml_siswa_hadir' => (int) ($teacherAttendance ? ($presentCountByAttendance[$teacherAttendance->id] ?? 0) : 0),
                'existing_tugas_file' => $teacherAttendance?->tugas_file_path,
            ];
        });

        return view('guru.agenda.index', [
            'teacher' => $teacher,
            'today' => $today,
            'todayDayName' => $todayDayName,
            'isWeekendHoliday' => $isWeekendHoliday,
            'rows' => $rows,
        ]);
    }

    public function store(Request $request, Schedule $schedule)
    {
        if (!$this->hasAgendaColumns()) {
            return redirect()->route('guru.agenda.index')
                ->with('error', 'Fitur agenda guru belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $schedule->load(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom', 'teacherSubject.academicYear']);

        if ((int) ($schedule->teacherSubject->teacher_id ?? 0) !== (int) $teacher->id) {
            abort(403);
        }

        [$today, $todayDayName, $isWeekendHoliday] = $this->resolveTodayContext();

        if ($isWeekendHoliday || $todayDayName === null || $schedule->hari !== $todayDayName) {
            return redirect()->route('guru.agenda.index')
                ->with('error', 'Agenda hanya bisa diisi untuk jadwal hari ini.');
        }

        $validated = $request->validate([
            'materi_pembelajaran' => 'nullable|string|max:3000',
            'catatan_guru' => 'nullable|string|max:2000',
            'kehadiran_guru' => ['required', Rule::in(['Hadir', 'Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Home Visit'])],
            'tugas_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'tugas_deskripsi' => 'nullable|string|max:3000',
        ]);

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
                'kehadiran_guru' => 'Hadir',
                'tugas_file_path' => null,
                'tugas_deskripsi' => null,
                'status' => 'Draft',
            ]);
        }

        $tugasDeskripsi = trim((string) ($validated['tugas_deskripsi'] ?? ''));
        $hasNewFile = $request->hasFile('tugas_file');
        $hasExistingFile = !empty($teacherAttendance->tugas_file_path);
        $hasNewDescription = $tugasDeskripsi !== '';
        $hasExistingDescription = !empty($teacherAttendance->tugas_deskripsi);

        if (
            $validated['kehadiran_guru'] !== 'Cuti'
            && !$hasNewFile
            && !$hasExistingFile
            && !$hasNewDescription
            && !$hasExistingDescription
        ) {
            return redirect()->route('guru.agenda.index')
                ->withErrors(['tugas_deskripsi' => 'Isi tugas wajib (file atau deskripsi) untuk semua status selain Cuti.'])
                ->withInput();
        }

        $filePath = $teacherAttendance->tugas_file_path;

        if ($hasNewFile) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $filePath = $request->file('tugas_file')->store('guru-tugas', 'public');
        }

        $savedTugasDeskripsi = $hasNewDescription ? $tugasDeskripsi : $teacherAttendance->tugas_deskripsi;

        $teacherAttendance->update([
            'materi_pembelajaran' => $validated['materi_pembelajaran'] ?? null,
            'catatan_guru' => $validated['catatan_guru'] ?? null,
            'kehadiran_guru' => $validated['kehadiran_guru'],
            'tugas_file_path' => $filePath,
            'tugas_deskripsi' => $savedTugasDeskripsi,
            'status' => 'Selesai',
        ]);

        return redirect()->route('guru.agenda.index')
            ->with('success', 'Agenda guru berhasil disimpan.');
    }

    private function resolveCurrentTeacher(): ?Teacher
    {
        $user = auth()->user();

        return Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();
    }

    private function resolveTodayContext(): array
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

        $today = Carbon::today();
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;
        $isWeekendHoliday = in_array($todayDayName, ['Sabtu', 'Minggu'], true);

        return [$today, $todayDayName, $isWeekendHoliday];
    }

    private function hasAgendaColumns(): bool
    {
        return Schema::hasColumns('teacher_attendances', ['kehadiran_guru', 'tugas_file_path', 'tugas_deskripsi']);
    }
}
