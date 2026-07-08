<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class academicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicYear::query()->update(['is_active' => false]);

        $academicYears = [
            [
                'tahun_ajaran' => '2025/2026',
                'semester' => 'Genap',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2026/2027',
                'semester' => 'Ganjil',
                'is_active' => true,
            ],
        ];

        foreach ($academicYears as $academicYear) {
            AcademicYear::updateOrCreate(
                [
                    'tahun_ajaran' => $academicYear['tahun_ajaran'],
                    'semester' => $academicYear['semester'],
                ],
                $academicYear
            );
        }
    }
}
