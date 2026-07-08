<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Teacher;

class teacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Teacher::create([
            'nip' => '1234567890',
            'nuptk' => '0987654321',
            'nama_lengkap' => 'John Doe',
            'jenis_kelamin' => 'L',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Contoh Alamat No. 123, Kota Contoh',
            'foto' => null,
        ]);
    }
}
