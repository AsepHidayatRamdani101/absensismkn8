<?php

namespace App\Services;

use App\Models\AttendanceDetail;
use App\Models\PjjAssignment;
use App\Models\PjjAttendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\TeacherAttendance;
use App\Support\PklMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Menyinkronkan data kehadiran PJJ (selfie siswa & upload tugas guru)
 * ke tabel absensi pembelajaran reguler (teacher_attendances + attendance_details)
 * agar rekap kehadiran menyatu di menu Absensi Siswa, Absensi Guru, dan Absensi Guru Full.
 */
class PjjAttendanceSyncService
{
    private const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /**
     * Upload tugas PJJ oleh guru dihitung sebagai kehadiran guru:
     * sesi KBM reguler dibuat/dilengkapi dengan kehadiran "Hadir" dan agenda
     * terisi otomatis ("Selesai") mengikuti struktur isian agenda guru
     * (materi, deskripsi tugas, catatan).
     *
     * @return int Jumlah sesi guru yang tersinkron.
     */
    public function syncTeacherAssignment(PjjAssignment $assignment): int
    {
        $date = Carbon::parse($assignment->created_at ?? now())->startOfDay();
        $schedules = $this->schedulesForTeacherSubject((int) $assignment->teacher_subject_id, $date);

        $synced = 0;

        foreach ($schedules as $schedule) {
            $session = $this->resolveSession($schedule, $date);

            $updates = [];
            if ($session->status !== 'Selesai') {
                $updates['status'] = 'Selesai';
            }
            if ($session->kehadiran_guru !== 'Hadir') {
                $updates['kehadiran_guru'] = 'Hadir';
            }
            if (blank($session->materi_pembelajaran)) {
                $updates['materi_pembelajaran'] = Str::limit('PJJ: ' . $assignment->title, 3000, '');
            }
            if (blank($session->tugas_deskripsi)) {
                $updates['tugas_deskripsi'] = Str::limit((string) $assignment->instructions, 3000, '');
            }
            if (blank($session->catatan_guru)) {
                $updates['catatan_guru'] = 'Sesi PJJ — agenda terisi otomatis dari upload tugas PJJ.';
            }

            if ($updates !== []) {
                $session->update($updates);
            }

            $synced++;
        }

        return $synced;
    }

    /**
     * Satu selfie PJJ siswa dihitung "Hadir" (keterangan "PJJ") untuk
     * seluruh jadwal pelajaran kelas siswa pada hari tersebut.
     *
     * @return int Jumlah baris absensi siswa yang tersinkron.
     */
    public function syncStudentAttendance(PjjAttendance $attendance): int
    {
        $attendance->loadMissing('student');
        $student = $attendance->student;

        if (!$student || !$student->classroom_id) {
            return 0;
        }

        $date = Carbon::parse($attendance->attendance_date)->startOfDay();
        $dayName = self::DAY_NAMES[$date->dayOfWeekIso] ?? null;

        if ($dayName === null) {
            return 0;
        }

        $schedules = PklMode::applyToScheduleQuery(
            Schedule::query()
                ->where('hari', $dayName)
                ->whereHas('teacherSubject', fn($query) => $query->where('classroom_id', $student->classroom_id))
                ->with('teacherSubject')
                ->orderBy('jam_mulai')
        )->get();

        $jamAbsen = Carbon::parse($attendance->check_in_at ?? $attendance->created_at ?? $date)->format('H:i:s');
        // Status mengikuti pencatatan PJJ: Terlambat jika absen datang melewati batas waktu.
        $status = $attendance->status === 'Terlambat' ? 'Terlambat' : 'Hadir';
        $synced = 0;

        foreach ($schedules as $schedule) {
            $session = $this->resolveSession($schedule, $date);

            // firstOrCreate agar koreksi manual admin/guru tidak tertimpa saat sinkron ulang.
            AttendanceDetail::query()->firstOrCreate(
                [
                    'teacher_attendance_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => $status,
                    'keterangan' => 'PJJ',
                    'jam_absen' => $jamAbsen,
                ]
            );

            $synced++;
        }

        return $synced;
    }

    /**
     * Tandai siswa yang sama sekali tidak absen selfie PJJ pada tanggal tersebut
     * sebagai "Alpha" di seluruh jadwal pelajaran kelasnya hari itu.
     * Dijalankan setelah hari berakhir (scheduler) atau manual via artisan.
     *
     * @return int Jumlah baris absensi Alpha yang dibuat.
     */
    public function markAbsentStudents(Carbon $date): int
    {
        $date = $date->copy()->startOfDay();
        $dayName = self::DAY_NAMES[$date->dayOfWeekIso] ?? null;

        if ($dayName === null) {
            return 0;
        }

        $attendedStudentIds = PjjAttendance::query()
            ->whereDate('attendance_date', $date->toDateString())
            ->pluck('student_id')
            ->all();

        $marked = 0;

        Student::query()
            ->whereNotNull('classroom_id')
            ->whereNotIn('id', $attendedStudentIds)
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($date, $dayName, &$marked) {
                // Kelompokkan per kelas agar jadwal cukup diambil sekali per kelas.
                foreach ($students->groupBy('classroom_id') as $classroomId => $classroomStudents) {
                    $schedules = PklMode::applyToScheduleQuery(
                        Schedule::query()
                            ->where('hari', $dayName)
                            ->whereHas('teacherSubject', fn($query) => $query->where('classroom_id', $classroomId))
                            ->with('teacherSubject')
                            ->orderBy('jam_mulai')
                    )->get();

                    foreach ($schedules as $schedule) {
                        $session = $this->resolveSession($schedule, $date);

                        foreach ($classroomStudents as $student) {
                            // firstOrCreate agar status manual (Izin/Sakit/dll) tidak tertimpa.
                            $detail = AttendanceDetail::query()->firstOrCreate(
                                [
                                    'teacher_attendance_id' => $session->id,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'status' => 'Alpha',
                                    'keterangan' => 'PJJ — tidak melakukan absensi selfie',
                                    'jam_absen' => null,
                                ]
                            );

                            if ($detail->wasRecentlyCreated) {
                                $marked++;
                            }
                        }
                    }
                }
            });

        return $marked;
    }

    /**
     * Ambil jadwal mapel pada hari tersebut; jika mapel tidak terjadwal hari itu,
     * fallback ke jadwal pertamanya agar kehadiran guru PJJ tetap tercatat.
     *
     * @return Collection<int, Schedule>
     */
    private function schedulesForTeacherSubject(int $teacherSubjectId, Carbon $date): Collection
    {
        $dayName = self::DAY_NAMES[$date->dayOfWeekIso] ?? null;

        $baseQuery = PklMode::applyToScheduleQuery(
            Schedule::query()
                ->where('teacher_subject_id', $teacherSubjectId)
                ->with('teacherSubject')
                ->orderBy('jam_mulai')
        );

        if ($dayName !== null) {
            $onDay = (clone $baseQuery)->where('hari', $dayName)->get();

            if ($onDay->isNotEmpty()) {
                return $onDay;
            }
        }

        $fallback = $baseQuery->first();

        return $fallback ? collect([$fallback]) : collect();
    }

    /**
     * Cari atau buat sesi KBM (teacher_attendances) untuk jadwal + tanggal tertentu.
     */
    private function resolveSession(Schedule $schedule, Carbon $date): TeacherAttendance
    {
        return DB::transaction(function () use ($schedule, $date) {
            // Lock baris jadwal agar sesi tidak dibuat ganda saat banyak siswa submit bersamaan.
            Schedule::query()->whereKey($schedule->id)->lockForUpdate()->first();

            $existing = TeacherAttendance::query()
                ->where('schedule_id', $schedule->id)
                ->whereDate('tanggal', $date->toDateString())
                ->first();

            if ($existing) {
                return $existing;
            }

            $teacherSubject = $schedule->teacherSubject;
            $lastPertemuan = (int) TeacherAttendance::query()
                ->where('schedule_id', $schedule->id)
                ->max('pertemuan');

            return TeacherAttendance::create([
                'teacher_id' => $teacherSubject->teacher_id,
                'schedule_id' => $schedule->id,
                'classroom_id' => $teacherSubject->classroom_id,
                'subject_id' => $teacherSubject->subject_id,
                'academic_year_id' => $teacherSubject->academic_year_id,
                'tanggal' => $date->toDateString(),
                'pertemuan' => max($lastPertemuan + 1, 1),
                'materi_pembelajaran' => null,
                'catatan_guru' => 'Sesi PJJ — dibuat otomatis dari absensi PJJ.',
                'kehadiran_guru' => 'Hadir',
                'status' => 'Draft',
            ]);
        });
    }
}
