<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        Role::firstOrCreate([
            'name' => 'guru',
            'guard_name' => 'web'
        ]);

        Role::firstOrCreate([
            'name' => 'siswa',
            'guard_name' => 'web'
        ]);

        Role::firstOrCreate([
            'name' => 'wali_kelas',
            'guard_name' => 'web'
        ]);

        Role::firstOrCreate([
            'name' => 'bk',
            'guard_name' => 'web'
        ]);

        Role::firstOrCreate([
            'name' => 'kesiswaan',
            'guard_name' => 'web'
        ]);
    }
}
