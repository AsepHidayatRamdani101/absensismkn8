<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
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
