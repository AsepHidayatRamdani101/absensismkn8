<?php

namespace App\Console\Commands;

use App\Models\PjjAssignment;
use App\Models\PjjAttendance;
use App\Services\PjjAttendanceSyncService;
use Illuminate\Console\Command;

class SyncPjjAttendanceCommand extends Command
{
    protected $signature = 'pjj:sync-attendance
        {--date= : Batasi sinkronisasi pada satu tanggal (Y-m-d)}
        {--dry-run : Hanya menampilkan jumlah data tanpa menyimpan}';

    protected $description = 'Sinkronkan data kehadiran PJJ historis (selfie siswa & upload tugas guru) ke tabel absensi pembelajaran reguler';

    public function handle(PjjAttendanceSyncService $sync): int
    {
        $date = $this->option('date');
        $dryRun = (bool) $this->option('dry-run');

        $assignmentQuery = PjjAssignment::query()->orderBy('id');
        $attendanceQuery = PjjAttendance::query()->with('student')->orderBy('id');

        if ($date) {
            $assignmentQuery->whereDate('created_at', $date);
            $attendanceQuery->whereDate('attendance_date', $date);
        }

        $assignmentTotal = (clone $assignmentQuery)->count();
        $attendanceTotal = (clone $attendanceQuery)->count();

        $this->info("Tugas PJJ guru yang akan disinkronkan: {$assignmentTotal}");
        $this->info("Selfie PJJ siswa yang akan disinkronkan: {$attendanceTotal}");

        if ($dryRun) {
            $this->comment('Mode dry-run: tidak ada data yang disimpan.');

            return self::SUCCESS;
        }

        $teacherSessions = 0;
        $assignmentQuery->chunkById(100, function ($assignments) use ($sync, &$teacherSessions) {
            foreach ($assignments as $assignment) {
                $teacherSessions += $sync->syncTeacherAssignment($assignment);
            }
        });
        $this->info("Sesi kehadiran guru tersinkron: {$teacherSessions}");

        $studentRows = 0;
        $attendanceQuery->chunkById(200, function ($attendances) use ($sync, &$studentRows) {
            foreach ($attendances as $attendance) {
                $studentRows += $sync->syncStudentAttendance($attendance);
            }
        });
        $this->info("Baris kehadiran siswa tersinkron: {$studentRows}");

        $this->info('Sinkronisasi PJJ ke absensi reguler selesai.');

        return self::SUCCESS;
    }
}
