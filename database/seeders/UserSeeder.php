<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        $admin = User::firstOrCreate(
            ['email' => 'admin@smkn8.sch.id'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('admin123')
            ]
        );

        $admin->assignRole('admin');


        $guru = User::firstOrCreate(
            ['email' => 'guru@smkn8.sch.id'],
            [
                'name' => 'Guru',
                'password' => bcrypt('guru123')
            ]
        );

        $guru->assignRole('guru');


        $siswa = User::firstOrCreate(
            ['email' => 'siswa@smkn8.sch.id'],
            [
                'name' => 'Siswa',
                'password' => bcrypt('siswa123')
            ]
        );

        $siswa->assignRole('siswa');
    }
}