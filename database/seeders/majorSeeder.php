<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Major;

class majorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Major::create([
            'kode_jurusan' => 'TJKT',
            'nama_jurusan' => 'Teknik Jaringan Komputer dan Telekomunikasi',
            'singkatan' => 'TJKT',
        ]);

        Major::create([
            'kode_jurusan' => 'DKV',
            'nama_jurusan' => 'Desain Komunikasi Visual',
            'singkatan' => 'DKV',
        ]);

        Major::create([
            'kode_jurusan' => 'MP',
            'nama_jurusan' => 'Manajemen Perkantoran',
            'singkatan' => 'MP',
        ]);

        Major::create([
            'kode_jurusan' => 'TKR',
            'nama_jurusan' => 'Teknik Kendaraan Ringan',
            'singkatan' => 'TKR',
        ]);
    }
}
