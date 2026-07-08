<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class subjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'kode_mapel' => 'MAT-X',
                'nama_mapel' => 'Matematika',
                'kategori' => 'Umum',
                'jam_per_minggu' => 4,
            ],
            [
                'kode_mapel' => 'BIN-X',
                'nama_mapel' => 'Bahasa Indonesia',
                'kategori' => 'Umum',
                'jam_per_minggu' => 3,
            ],
            [
                'kode_mapel' => 'PBTGM-X',
                'nama_mapel' => 'PBTGM',
                'kategori' => 'Kejuruan',
                'jam_per_minggu' => 6,
            ],
            [
                'kode_mapel' => 'MULOK-SUN',
                'nama_mapel' => 'Bahasa Sunda',
                'kategori' => 'Muatan Lokal',
                'jam_per_minggu' => 2,
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['kode_mapel' => $subject['kode_mapel']],
                $subject
            );
        }
    }
}
