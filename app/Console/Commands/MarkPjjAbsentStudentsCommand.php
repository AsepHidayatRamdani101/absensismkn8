<?php

namespace App\Console\Commands;

use App\Services\PjjAttendanceSyncService;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkPjjAbsentStudentsCommand extends Command
{
    protected $signature = 'pjj:mark-alpa
        {--date= : Tanggal yang diproses (Y-m-d), default hari ini}
        {--force : Proses meskipun tanggal bukan hari PJJ}';

    protected $description = 'Tandai siswa yang tidak absen selfie PJJ seharian penuh sebagai Alpa di absensi reguler';

    public function handle(PjjAttendanceSyncService $sync): int
    {
        $date = Carbon::parse($this->option('date') ?: Carbon::today());

        if (!SchoolCalendar::isPjj($date) && !$this->option('force')) {
            $this->info('Tanggal ' . $date->toDateString() . ' bukan hari PJJ; tidak ada siswa yang ditandai Alpa. Gunakan --force untuk memaksa.');

            return self::SUCCESS;
        }

        $marked = $sync->markAbsentStudents($date);

        $this->info('Siswa tanpa absensi PJJ pada ' . $date->toDateString() . ' ditandai Alpa: ' . $marked . ' baris absensi.');

        return self::SUCCESS;
    }
}
