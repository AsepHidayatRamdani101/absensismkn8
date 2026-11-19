<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PancawaluyaPermissionSeeder::class,
            UserSeeder::class,
            CharacterDimensionSeeder::class,
        ]);

        if (app()->environment('production')) {
            return;
        }

        $this->call([
            majorSeeder::class,
            classSeeder::class,
            siswaSeeder::class,
            teacherSeeder::class,
            subjectSeeder::class,
            academicYearSeeder::class,
            teacherSubjectSeeder::class,
            scheduleSeeder::class,
            DeviceSeeder::class,
        ]);
    }
}
