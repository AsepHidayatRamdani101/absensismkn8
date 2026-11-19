<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->seedProductionAdmin();

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@smkn8.sch.id'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('admin123'),
            ]
        );

        $admin->syncRoles(['admin']);

        $guru = User::updateOrCreate(
            ['email' => 'guru@smkn8.sch.id'],
            [
                'name' => 'Guru',
                'password' => bcrypt('guru123'),
            ]
        );

        $guru->syncRoles(['guru']);

        $siswa = User::updateOrCreate(
            ['email' => 'siswa@smkn8.sch.id'],
            [
                'name' => 'Siswa',
                'password' => bcrypt('siswa123'),
            ]
        );

        $siswa->syncRoles(['siswa']);
    }

    private function seedProductionAdmin(): void
    {
        $email = (string) config('school.seed_admin.email', 'admin@smkn8.sch.id');
        $admin = User::firstOrNew(['email' => $email]);

        if (!$admin->exists) {
            $password = (string) config('school.seed_admin.password', '');
            $generated = $password === '';

            if ($generated) {
                $password = Str::password(16, symbols: false);
            }

            $admin->name = 'Administrator';
            $admin->password = bcrypt($password);
            $admin->save();

            if ($generated) {
                $this->command?->warn("Akun admin dibuat: {$email} / {$password}");
                $this->command?->warn('Simpan password ini dan segera ganti setelah login pertama.');
            } else {
                $this->command?->info("Akun admin dibuat: {$email} (password dari SEED_ADMIN_PASSWORD).");
            }
        }

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
