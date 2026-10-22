<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\TeacherAttendance;
use App\Support\PklMode;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScheduleWhatsappReminderService
{
    public function sendDueReminders(): array
    {
        if (!(bool) config('services.fonnte.enabled', false)
            || !(bool) config('services.fonnte.reminder_enabled', false)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Pengingat otomatis tidak aktif.'];
        }

        $token = trim((string) config('services.fonnte.token', ''));
        if ($token === '') {
            Log::error('Pengingat WA jadwal tidak dapat berjalan karena token Fonnte kosong.');
            return ['sent' => 0, 'failed' => 1, 'skipped' => 0, 'message' => 'Token Fonnte kosong.'];
        }

        $today = Carbon::today();
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $dayName = $dayNames[$today->dayOfWeekIso] ?? null;

        if ($dayName === null || $dayName === 'Minggu' || SchoolCalendar::isHoliday($today)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Hari ini bukan hari belajar efektif.'];
        }

        $scheduleQuery = Schedule::query()
            ->with([
                'teacherSubject.teacher',
                'teacherSubject.subject',
                'teacherSubject.classroom.students',
            ])
            ->where('hari', $dayName)
            ->whereHas('teacherSubject.academicYear', fn($query) => $query->where('is_active', true))
            ->orderBy('jam_selesai');
        $schedules = PklMode::applyToScheduleQuery($scheduleQuery)->get()
            ->reject(fn(Schedule $schedule) => SchoolCalendar::isHolidayAt($today, $schedule->jam_selesai));

        if ($schedules->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Tidak ada jadwal aktif hari ini.'];
        }

        $attendances = TeacherAttendance::query()
            ->whereDate('tanggal', $today->toDateString())
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->with('attendanceDetails:id,teacher_attendance_id,student_id,status')
            ->get()
            ->keyBy('schedule_id');

        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        $schoolName = (string) config('services.fonnte.school_name', 'SMKN 8 GARUT');

        foreach ($schedules as $schedule) {
            $attendance = $attendances->get($schedule->id);
            $teacher = $schedule->teacherSubject?->teacher;
            $subjectName = (string) ($schedule->teacherSubject?->subject?->nama_mapel ?? '-');
            $classroomName = (string) ($schedule->teacherSubject?->classroom?->nama_kelas ?? '-');

            if ($this->isDue($today, $schedule->jam_selesai, 5)) {
                if ($attendance && $attendance->kehadiran_guru !== 'Hadir') {
                    $stats['skipped']++;
                } else {
                    $missingAttendance = !$attendance || $attendance->attendanceDetails->isEmpty();
                    $missingAgenda = !$attendance || $attendance->status !== 'Selesai';

                    if ($missingAttendance || $missingAgenda) {
                        $messageParts = [];
                        if ($missingAttendance) {
                            $messageParts[] = 'absensi siswa';
                        }
                        if ($missingAgenda) {
                            $messageParts[] = 'agenda pembelajaran';
                        }
                        $message = "Assalamu'alaikum Bapak/Ibu {$teacher?->nama_lengkap},\n"
                            . "Pengingat dari {$schoolName}: jadwal {$subjectName} kelas {$classroomName} "
                            . "pukul " . substr((string) $schedule->jam_mulai, 0, 5) . '-'
                            . substr((string) $schedule->jam_selesai, 0, 5)
                            . " telah selesai. Mohon lengkapi " . implode(' dan ', $messageParts)
                            . " pada aplikasi.\n\nTerima kasih.\nAdmin SiAbsensi";

                        if (!$teacher) {
                            $stats['skipped']++;
                        } elseif ($this->hasSent('teacher', $schedule->id, $today, $teacher->id)) {
                            $stats['skipped']++;
                        } else {
                            $this->sendMessage(
                                $teacher->no_hp ?? null,
                                $message,
                                'teacher',
                                $schedule->id,
                                $today,
                                $teacher->id,
                                $stats
                            );
                        }
                    }
                }
            }

            if (SchoolCalendar::isPjj($today) || !$this->isDue($today, $schedule->jam_selesai, -15)) {
                continue;
            }

            $attendanceByStudent = $attendance?->attendanceDetails->keyBy('student_id') ?? collect();
            foreach ($schedule->teacherSubject?->classroom?->students ?? collect() as $student) {
                $status = $attendanceByStudent->get($student->id)?->status;
                if ($status !== null && !in_array($status, ['Alpha', 'Alpa', 'Bolos'], true)) {
                    continue;
                }

                if ($this->hasSent('parent', $schedule->id, $today, $student->id)) {
                    $stats['skipped']++;
                    continue;
                }

                $message = "Assalamu'alaikum Bapak/Ibu orang tua/wali dari {$student->nama_lengkap},\n"
                    . "Menurut jadwal {$subjectName} kelas {$classroomName} hari ini, hingga 15 menit sebelum "
                    . "pelajaran berakhir siswa belum tercatat hadir"
                    . ($status ? " (status: {$status})" : '')
                    . ". Mohon konfirmasi kepada pihak sekolah bila informasi ini tidak sesuai.\n\n"
                    . $schoolName;

                $this->sendMessage(
                    $student->no_hp_orang_tua,
                    $message,
                    'parent',
                    $schedule->id,
                    $today,
                    $student->id,
                    $stats
                );
            }
        }

        $message = "Pengingat WA jadwal selesai. Terkirim: {$stats['sent']}, gagal: {$stats['failed']}, dilewati: {$stats['skipped']}.";
        return [...$stats, 'message' => $message];
    }

    private function isDue(Carbon $date, string $endTime, int $offsetMinutes): bool
    {
        $triggerAt = Carbon::parse($date->toDateString() . ' ' . $endTime)
            ->addMinutes($offsetMinutes);
        $now = now();

        return $now->greaterThanOrEqualTo($triggerAt)
            && $now->lessThan($triggerAt->copy()->addMinutes(2));
    }

    private function hasSent(string $recipientType, int $scheduleId, Carbon $date, int $recipientId): bool
    {
        return DB::table('schedule_whatsapp_reminders')
            ->where('schedule_id', $scheduleId)
            ->whereDate('tanggal', $date->toDateString())
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->exists();
    }

    private function sendMessage(
        ?string $phone,
        string $message,
        string $recipientType,
        int $scheduleId,
        Carbon $date,
        int $recipientId,
        array &$stats
    ): bool {
        $target = $this->normalizePhone($phone);
        if (!$target) {
            $stats['skipped']++;
            return false;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => (string) config('services.fonnte.token')])
                ->asForm()
                ->post((string) config('services.fonnte.api_url', 'https://api.fonnte.com/send'), [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => (string) config('services.fonnte.default_country_code', '62'),
                ]);

            if (!$response->successful() || $response->json('status') === false) {
                Log::channel('security')->warning('Fonnte menolak pengingat WA sesuai jadwal.', [
                    'schedule_id' => $scheduleId,
                    'recipient_type' => $recipientType,
                    'recipient_id' => $recipientId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                $stats['failed']++;
                return false;
            }

            DB::table('schedule_whatsapp_reminders')->insertOrIgnore([
                'schedule_id' => $scheduleId,
                'tanggal' => $date->toDateString(),
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $stats['sent']++;
            return true;
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            Log::channel('security')->error('Koneksi pengiriman pengingat WA sesuai jadwal gagal.', [
                'schedule_id' => $scheduleId,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'message' => $exception->getMessage(),
            ]);
            $stats['failed']++;
            return false;
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        $countryCode = (string) config('services.fonnte.default_country_code', '62');
        if (str_starts_with($digits, '0')) {
            return $countryCode . substr($digits, 1);
        }

        return str_starts_with($digits, $countryCode) || str_starts_with($digits, '62')
            ? $digits
            : $countryCode . $digits;
    }
}
