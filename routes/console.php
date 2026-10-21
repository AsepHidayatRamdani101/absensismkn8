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

Artisan::command('accounts:generate-guru-siswa', function () {
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $guruCreated = 0;
    $guruUpdated = 0;
    $guruSkipped = 0;

    Teacher::query()->orderBy('id')->chunk(200, function ($teachers) use (&$guruCreated, &$guruUpdated, &$guruSkipped) {
        foreach ($teachers as $teacher) {
            $username = trim((string) $teacher->nip);

            if ($username === '') {
                $guruSkipped++;
                continue;
            }

            $user = User::where('email', $username)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $teacher->nama_lengkap,
                    'email' => $username,
                    'password' => Hash::make('guru12345'),
                ]);
                $guruCreated++;
            } else {
                $user->update([
                    'name' => $teacher->nama_lengkap,
                    'password' => Hash::make('guru12345'),
                ]);
                $guruUpdated++;
            }

            $user->syncRoles(['guru']);
        }
    });

    $siswaCreated = 0;
    $siswaUpdated = 0;
    $siswaSkipped = 0;

    Student::query()->orderBy('id')->chunk(200, function ($students) use (&$siswaCreated, &$siswaUpdated, &$siswaSkipped) {
        foreach ($students as $student) {
            $username = trim((string) $student->nisn);

            if ($username === '') {
                $siswaSkipped++;
                continue;
            }

            $user = User::where('email', $username)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $student->nama_lengkap,
                    'email' => $username,
                    'password' => Hash::make('siswa12345'),
                ]);
                $siswaCreated++;
            } else {
                $user->update([
                    'name' => $student->nama_lengkap,
                    'password' => Hash::make('siswa12345'),
                ]);
                $siswaUpdated++;
            }

            $user->syncRoles(['siswa']);
        }
    });

    $this->newLine();
    $this->info('Generate akun selesai');
    $this->line('Guru   -> dibuat: ' . $guruCreated . ', diperbarui: ' . $guruUpdated . ', dilewati (NIP kosong): ' . $guruSkipped);
    $this->line('Siswa  -> dibuat: ' . $siswaCreated . ', diperbarui: ' . $siswaUpdated . ', dilewati (NISN kosong): ' . $siswaSkipped);
    $this->newLine();
    $this->line('Password default guru: guru12345');
    $this->line('Password default siswa: siswa12345');
})->purpose('Generate akun login untuk semua guru (NIP) dan siswa (NISN).');

Artisan::command('analytics:refresh', function (DashboardCacheService $cacheService) {
    $cacheService->bumpVersion();
    $this->info('Cache analytics dashboard berhasil di-refresh melalui cache version bump.');
})->purpose('Refresh cache analytics dashboard dengan mekanisme version bump.');

Artisan::command('backup:db-daily', function () {
    $connection = (string) config('database.default');

    if ($connection !== 'mysql') {
        $this->warn('Backup DB otomatis saat ini dioptimalkan untuk koneksi mysql.');
        return self::SUCCESS;
    }

    $host = (string) config('database.connections.mysql.host');
    $port = (string) config('database.connections.mysql.port', '3306');
    $database = (string) config('database.connections.mysql.database');
    $username = (string) config('database.connections.mysql.username');
    $password = (string) config('database.connections.mysql.password');

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
        'mysqldump -h %s -P %s -u %s -p%s --single-transaction --quick %s | gzip > %s',
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($database),
        escapeshellarg($targetPath)
    );

    $result = Process::run($command);

    if (!$result->successful()) {
        $this->error('Backup database gagal: ' . trim($result->errorOutput()));
        return self::FAILURE;
    }

    $this->info('Backup database berhasil: ' . $targetPath);
    return self::SUCCESS;
})->purpose('Backup database harian ke storage/app/backups/database.');

Artisan::command('backup:full-weekly', function () {
    $disk = Storage::disk('local');
    $directory = 'backups/full';
    $disk->makeDirectory($directory);

    $archiveName = 'full_backup_' . Carbon::now()->format('Ymd_His') . '.zip';
    $archivePath = storage_path('app/' . $directory . '/' . $archiveName);

    $sourceStoragePath = storage_path('app');
    $command = sprintf(
        'cd %s && zip -r %s app',
        escapeshellarg(storage_path()),
        escapeshellarg($archivePath)
    );

    $result = Process::run($command);

    if (!$result->successful()) {
        $this->error('Backup full gagal: ' . trim($result->errorOutput()));
        return self::FAILURE;
    }

    $this->info('Backup full storage berhasil: ' . $archivePath);
    return self::SUCCESS;
})->purpose('Backup mingguan storage/app ke berkas ZIP.');

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

    $groupedByTeacher = $schedules->filter(function ($schedule) {
        return (int) ($schedule->teacherSubject?->teacher_id ?? 0) > 0;
    })->groupBy(fn($schedule) => (int) $schedule->teacherSubject->teacher_id);

    foreach ($groupedByTeacher as $teacherId => $teacherSchedules) {
        $teacher = $teacherSchedules->first()?->teacherSubject?->teacher;

        if (!$teacher) {
            $skipCount++;
            continue;
        }

        $target = $normalizePhone($teacher->no_hp);
        if (!$target) {
            $skipCount++;
            continue;
        }

        $missingRows = [];

        foreach ($teacherSchedules as $schedule) {
            $attendance = $attendanceBySchedule->get($schedule->id);

            if ($attendance && $attendance->kehadiran_guru !== 'Hadir') {
                continue;
            }

            $missingAttendance = !$attendance || (int) ($attendance->attendance_details_count ?? 0) === 0;
            $missingAgenda = !$attendance || (string) $attendance->status !== 'Selesai';

            if (!$missingAttendance && !$missingAgenda) {
                continue;
            }

            $missingLabels = [];
            if ($missingAttendance) {
                $missingLabels[] = 'absensi siswa';
            }
            if ($missingAgenda) {
                $missingLabels[] = 'agenda';
            }

            $subject = (string) ($schedule->teacherSubject?->subject?->nama_mapel ?? '-');
            $classroom = (string) ($schedule->teacherSubject?->classroom?->nama_kelas ?? '-');
            $missingRows[] = '- ' . $subject . ' (' . $classroom . ') ' . $schedule->jam_mulai . '-' . $schedule->jam_selesai
                . ': belum isi ' . implode(' & ', $missingLabels);
        }

        if (empty($missingRows)) {
            $skipCount++;
            continue;
        }

        $cacheKey = 'fonnte:reminder:guru:' . $todayLabel . ':' . $teacherId;
        $fingerprint = md5(implode('|', $missingRows));

        if (Cache::get($cacheKey) === $fingerprint) {
            $skipCount++;
            continue;
        }

        $message = "Assalamu'alaikum Bapak/Ibu {$teacher->nama_lengkap},\n"
            . "Pengingat dari {$schoolName}.\n"
            . "Hari {$todayDayName}, {$todayLabel} masih ada jadwal yang belum lengkap:\n"
            . implode("\n", $missingRows)
            . "\n\nSilakan lengkapi di aplikasi absensi. Terima kasih.\n\nAdmin SiAbsensi";

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
                Cache::put($cacheKey, $fingerprint, now()->endOfDay());
                $sentCount++;
            } else {
                Log::channel('security')->warning('Gagal kirim WA pengingat guru via Fonnte', [
                    'teacher_id' => $teacherId,
                    'target' => $target,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                $skipCount++;
                $failedCount++;
            }
        } catch (\Throwable $e) {
            Log::channel('security')->error('Error kirim WA pengingat guru via Fonnte', [
                'teacher_id' => $teacherId,
                'target' => $target,
                'message' => $e->getMessage(),
            ]);
            $skipCount++;
            $failedCount++;
        }
    }

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
})->purpose('Kirim pengingat WA otomatis ke guru yang belum melengkapi absensi siswa dan agenda harian.');

Schedule::command('analytics:refresh')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=48')->hourly();
Schedule::command('backup:db-daily')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:full-weekly')->weeklyOn(0, '01:30')->withoutOverlapping();
Schedule::command('reminder:guru-absen-agenda')
    ->dailyAt('15:00')
    ->withoutOverlapping();
