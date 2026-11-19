<?php

use App\Models\Student;
use App\Models\Schedule as ScheduleModel;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\User;
use App\Support\PklMode;
use App\Support\SchoolCalendar;
use App\Services\Dashboard\DashboardCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('accounts:generate-guru-siswa {--reset-password : Reset juga password akun yang sudah ada ke password default}', function () {
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $resetPasswords = (bool) $this->option('reset-password');

    $guruCreated = 0;
    $guruUpdated = 0;
    $guruSkipped = 0;

    Teacher::query()->orderBy('id')->chunk(200, function ($teachers) use (&$guruCreated, &$guruUpdated, &$guruSkipped, $resetPasswords) {
        foreach ($teachers as $teacher) {
            $username = trim((string) $teacher->nip);

            if ($username === '') {
                $guruSkipped++;
                continue;
            }

            $user = User::firstOrNew(['email' => $username]);
            $isNew = !$user->exists;
            $user->name = $teacher->nama_lengkap;

            if ($isNew || $resetPasswords) {
                $user->password = Hash::make('guru12345');
            }

            $user->save();
            $isNew ? $guruCreated++ : $guruUpdated++;

            if (!$user->hasRole('guru')) {
                $user->assignRole('guru');
            }
        }
    });

    $siswaCreated = 0;
    $siswaUpdated = 0;
    $siswaSkipped = 0;

    Student::query()->orderBy('id')->chunk(200, function ($students) use (&$siswaCreated, &$siswaUpdated, &$siswaSkipped, $resetPasswords) {
        foreach ($students as $student) {
            $username = trim((string) $student->nisn);

            if ($username === '') {
                $siswaSkipped++;
                continue;
            }

            $user = User::firstOrNew(['email' => $username]);
            $isNew = !$user->exists;
            $user->name = $student->nama_lengkap;

            if ($isNew || $resetPasswords) {
                $user->password = Hash::make('siswa12345');
            }

            $user->save();
            $isNew ? $siswaCreated++ : $siswaUpdated++;

            if (!$user->hasRole('siswa')) {
                $user->assignRole('siswa');
            }
        }
    });

    $this->newLine();
    $this->info('Generate akun selesai');
    $this->line('Guru   -> dibuat: ' . $guruCreated . ', diperbarui: ' . $guruUpdated . ', dilewati (NIP kosong): ' . $guruSkipped);
    $this->line('Siswa  -> dibuat: ' . $siswaCreated . ', diperbarui: ' . $siswaUpdated . ', dilewati (NISN kosong): ' . $siswaSkipped);
    $this->newLine();
    $this->line('Password default akun baru — guru: guru12345, siswa: siswa12345');
    $this->line($resetPasswords
        ? 'Password akun lama juga direset ke password default.'
        : 'Password akun lama tidak diubah (gunakan --reset-password untuk mereset).');
})->purpose('Generate akun login untuk semua guru (NIP) dan siswa (NISN).');

Artisan::command('analytics:refresh', function (DashboardCacheService $cacheService) {
    $cacheService->bumpVersion();
    $this->info('Cache analytics dashboard berhasil di-refresh melalui cache version bump.');
})->purpose('Refresh cache analytics dashboard dengan mekanisme version bump.');

$pruneBackups = function (string $directory, int $keepDays): int {
    $disk = Storage::disk('local');
    $threshold = Carbon::now()->subDays($keepDays)->getTimestamp();
    $deleted = 0;

    foreach ($disk->files($directory) as $file) {
        if ($disk->lastModified($file) < $threshold) {
            $disk->delete($file);
            $deleted++;
        }
    }

    return $deleted;
};

Artisan::command('backup:db-daily', function () use ($pruneBackups) {
    $connection = (string) config('database.default');

    if (!in_array($connection, ['mysql', 'mariadb'], true)) {
        $this->warn('Backup DB otomatis saat ini dioptimalkan untuk koneksi mysql.');
        return self::SUCCESS;
    }

    $host = (string) config("database.connections.{$connection}.host");
    $port = (string) config("database.connections.{$connection}.port", '3306');
    $database = (string) config("database.connections.{$connection}.database");
    $username = (string) config("database.connections.{$connection}.username");
    $password = (string) config("database.connections.{$connection}.password");

    if ($database === '' || $username === '') {
        $this->error('Konfigurasi database mysql tidak lengkap.');
        return self::FAILURE;
    }

    $disk = Storage::disk('local');
    $directory = 'backups/database';
    $disk->makeDirectory($directory);

    $fileName = 'db_backup_' . Carbon::now()->format('Ymd_His') . '.sql.gz';
    $targetPath = storage_path('app/' . $directory . '/' . $fileName);

    $command = sprintf(
        'mysqldump -h %s -P %s -u %s --single-transaction --quick %s | gzip > %s',
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($username),
        escapeshellarg($database),
        escapeshellarg($targetPath)
    );

    // Password lewat env agar tidak terlihat di daftar proses server.
    $result = Process::env(['MYSQL_PWD' => $password])->timeout(1800)->run($command);

    if (!$result->successful()) {
        $this->error('Backup database gagal: ' . trim($result->errorOutput()));
        return self::FAILURE;
    }

    $keepDays = max(1, (int) config('school.backup.db_keep_days', 14));
    $deleted = $pruneBackups($directory, $keepDays);

    $this->info('Backup database berhasil: ' . $targetPath);
    $this->line("Backup database lebih dari {$keepDays} hari yang dihapus: {$deleted}");
    return self::SUCCESS;
})->purpose('Backup database harian ke storage/app/backups/database.');

Artisan::command('backup:full-weekly', function () use ($pruneBackups) {
    $disk = Storage::disk('local');
    $directory = 'backups/full';
    $disk->makeDirectory($directory);

    $archiveName = 'full_backup_' . Carbon::now()->format('Ymd_His') . '.zip';
    $archivePath = storage_path('app/' . $directory . '/' . $archiveName);

    // Folder backup dikecualikan agar arsip tidak ikut memuat backup lama.
    $command = sprintf(
        'cd %s && zip -r -q %s app -x %s',
        escapeshellarg(storage_path()),
        escapeshellarg($archivePath),
        escapeshellarg('app/backups/*')
    );

    $result = Process::timeout(3600)->run($command);

    if (!$result->successful()) {
        $this->error('Backup full gagal: ' . trim($result->errorOutput()));
        return self::FAILURE;
    }

    $keepDays = max(7, (int) config('school.backup.full_keep_days', 56));
    $deleted = $pruneBackups($directory, $keepDays);

    $this->info('Backup full storage berhasil: ' . $archivePath);
    $this->line("Backup full lebih dari {$keepDays} hari yang dihapus: {$deleted}");
    return self::SUCCESS;
})->purpose('Backup mingguan storage/app ke berkas ZIP.');

Artisan::command('reports:prune-shared', function () {
    $disk = Storage::disk('public');
    $threshold = Carbon::now()->subDays(max(1, (int) config('school.shared_report_keep_days', 7)))->getTimestamp();
    $deleted = 0;

    foreach ($disk->files('wali-reports') as $file) {
        if ($disk->lastModified($file) < $threshold) {
            $disk->delete($file);
            $deleted++;
        }
    }

    $this->info("Laporan WhatsApp lama yang dihapus: {$deleted}");
})->purpose('Hapus PDF laporan wali kelas (WhatsApp) yang sudah lama dari disk publik.');

Artisan::command('reminder:guru-absen-agenda', function () {
    $fonnteEnabled = (bool) config('services.fonnte.enabled', false);
    $reminderEnabled = (bool) config('services.fonnte.reminder_enabled', false);
    $token = (string) config('services.fonnte.token', '');
    $apiUrl = (string) config('services.fonnte.api_url', 'https://api.fonnte.com/send');
    $defaultCountryCode = (string) config('services.fonnte.default_country_code', '62');
    $schoolName = (string) config('services.fonnte.school_name', 'SMKN 8 GARUT');
    $recordAutomaticRun = function (string $status, string $message, int $sent = 0, int $failed = 0, int $skipped = 0): void {
        Cache::put('fonnte:reminder:last-run', [
            'status' => $status,
            'message' => $message,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'at' => now()->toDateTimeString(),
        ], now()->addDays(7));
    };

    if (!$fonnteEnabled || !$reminderEnabled) {
        $message = 'Pengingat otomatis tidak aktif; pesan tidak dikirim.';
        $recordAutomaticRun('info', $message);
        $this->info($message);
        return self::SUCCESS;
    }

    if ($token === '') {
        $message = 'Token Fonnte kosong; tidak ada pesan yang dikirim.';
        $recordAutomaticRun('error', $message, 0, 1);
        $this->error($message);
        return self::FAILURE;
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
    $todayLabel = $today->toDateString();
    $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;

    if ($todayDayName === null || in_array($todayDayName, ['Sabtu', 'Minggu'], true)) {
        $message = 'Hari ini akhir pekan; pengingat otomatis dilewati.';
        $recordAutomaticRun('info', $message);
        $this->info($message);
        return self::SUCCESS;
    }

    if (SchoolCalendar::isHoliday($today)) {
        $message = 'Hari ini ditetapkan sebagai hari libur; pengingat otomatis dilewati.';
        $recordAutomaticRun('info', $message);
        $this->info($message);
        return self::SUCCESS;
    }
    $normalizePhone = function (?string $phone) use ($defaultCountryCode): ?string {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return $defaultCountryCode . substr($digits, 1);
        }

        if (str_starts_with($digits, $defaultCountryCode)) {
            return $digits;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return $defaultCountryCode . $digits;
    };

    $schedules = ScheduleModel::query()
        ->with([
            'teacherSubject.teacher',
            'teacherSubject.subject',
            'teacherSubject.classroom.students',
        ])
        ->where('hari', $todayDayName)
        ->whereHas('teacherSubject.academicYear', fn($query) => $query->where('is_active', true))
        ->orderBy('jam_mulai');

    $schedules = PklMode::applyToScheduleQuery($schedules)->get();

    if ($schedules->isEmpty()) {
        $message = 'Tidak ada jadwal guru hari ini; tidak ada pesan yang dikirim.';
        $recordAutomaticRun('info', $message);
        $this->info($message);
        return self::SUCCESS;
    }

    $sentCount = 0;
    $skipCount = 0;
    $failedCount = 0;
    $attendanceBySchedule = TeacherAttendance::query()
        ->whereDate('tanggal', $todayLabel)
        ->whereIn('schedule_id', $schedules->pluck('id'))
        ->withCount('attendanceDetails')
        ->get()
        ->keyBy('schedule_id');

    $photoMissingSchedules = (SchoolCalendar::isPjj($today) ? collect() : $schedules)
        ->filter(function ($schedule) use ($attendanceBySchedule) {
            $attendance = $attendanceBySchedule->get($schedule->id);

            return empty($attendance?->foto_guru_path)
                && (!$attendance || $attendance->kehadiran_guru === 'Hadir');
        })
        ->groupBy(fn($schedule) => (int) $schedule->teacherSubject->classroom_id);

    foreach ($photoMissingSchedules as $missingClassroomSchedules) {
        $classroom = $missingClassroomSchedules->first()->teacherSubject->classroom;
        if (!$classroom) {
            $skipCount++;
            continue;
        }

        $missingRows = $missingClassroomSchedules->map(function ($schedule) {
            return '- ' . ($schedule->teacherSubject?->teacher?->nama_lengkap ?? 'Guru')
                . ' — ' . ($schedule->teacherSubject?->subject?->nama_mapel ?? '-')
                . ' (' . $schedule->jam_mulai . '-' . $schedule->jam_selesai . ')';
        })->values();

        $officers = $classroom->students->filter(
            fn(Student $student) => $student->canSubmitTeacherAttendance()
        );

        foreach ($officers as $student) {
            $target = $normalizePhone($student->no_hp ?: $student->no_hp_orang_tua);
            if (!$target) {
                $skipCount++;
                continue;
            }

            $cacheKey = 'fonnte:reminder:foto-siswa:' . $todayLabel . ':' . $classroom->id . ':' . hash('sha256', $target);
            if (Cache::has($cacheKey)) {
                $skipCount++;
                continue;
            }

            $message = "Assalamu'alaikum {$student->nama_lengkap},\n"
                . "Pengingat dari {$schoolName}.\n"
                . "Mohon bantu melakukan foto absensi guru pada jadwal berikut yang belum memiliki bukti foto hari ini:\n"
                . $missingRows->implode("\n")
                . "Terima kasih.\n\nAdmin SiAbsensi";

            try {
                $response = Http::timeout(20)
                    ->withHeaders(['Authorization' => $token])
                    ->asForm()
                    ->post($apiUrl, [
                        'target' => $target,
                        'message' => $message,
                        'countryCode' => $defaultCountryCode,
                    ]);

                if ($response->successful() && $response->json('status') !== false) {
                    Cache::put($cacheKey, true, now()->endOfDay());
                    $sentCount++;
                } else {
                    Log::channel('security')->warning('Gagal kirim WA pengingat foto guru ke siswa via Fonnte', [
                        'student_id' => $student->id,
                        'classroom_id' => $classroom->id,
                        'target' => $target,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    $skipCount++;
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                Log::channel('security')->error('Error kirim WA pengingat foto guru ke siswa via Fonnte', [
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'target' => $target,
                    'message' => $e->getMessage(),
                ]);
                $skipCount++;
                $failedCount++;
            }
        }
    }

    $status = $failedCount > 0 || ($sentCount === 0 && $skipCount > 0) ? 'warning' : 'success';
    $message = "Pengingat WA selesai. Permintaan diterima: {$sentCount}, gagal: {$failedCount}, dilewati: "
        . max(0, $skipCount - $failedCount) . '.';
    $recordAutomaticRun($status, $message, $sentCount, $failedCount, max(0, $skipCount - $failedCount));
    $this->info($message);

    return self::SUCCESS;
})->purpose('Kirim pengingat WA kepada pengurus kelas untuk foto absensi guru yang belum dikirim.');

Schedule::command('analytics:refresh')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=48')->hourly();
Schedule::command('backup:db-daily')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:full-weekly')->weeklyOn(0, '01:30')->withoutOverlapping();
Schedule::command('reports:prune-shared')->dailyAt('02:00');
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('reminder:guru-absen-agenda')
    ->dailyAt('15:00')
    ->withoutOverlapping();
// Akhir hari PJJ: siswa yang sama sekali tidak absen selfie ditandai Alpa.
Schedule::command('pjj:mark-alpa')
    ->dailyAt('21:00')
    ->withoutOverlapping();
Artisan::command('reminder:class-schedule-whatsapp', function (App\Services\ScheduleWhatsappReminderService $service) {
    $result = $service->sendDueReminders();
    Cache::put('fonnte:schedule-reminder:last-run', [
        ...$result,
        'at' => now()->toDateTimeString(),
    ], now()->addDays(7));
    $this->info($result['message']);

    return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Kirim pengingat WA absensi dan agenda guru serta siswa berdasarkan jadwal pelajaran.');
Schedule::command('reminder:class-schedule-whatsapp')
    ->everyMinute()
    ->withoutOverlapping();
