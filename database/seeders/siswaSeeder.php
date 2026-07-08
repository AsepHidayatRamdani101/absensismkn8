<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;

class siswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Student::create([
            'nis' => '1234567890',
            'nisn' => '0987654321',
            'nama_lengkap' => 'John Doe',
            'jenis_kelamin' => 'L',
            'classroom_id' => 1,
            'alamat' => 'Jl. Contoh Alamat No. 123',
            'no_hp' => '081234567890',
            'foto' => null,
        ]); 
    }
}
