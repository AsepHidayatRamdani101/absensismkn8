<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
}
