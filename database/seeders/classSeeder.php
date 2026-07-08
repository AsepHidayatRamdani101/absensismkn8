<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Classroom;

class classSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Classroom::create([
            'kode_kelas' => 'X-TJKT-1',
            'nama_kelas' => 'X TJKT 1 ',
            'major_id' => 1,
            'rombel' => '36',

           
        ]);

        Classroom::create([
            'kode_kelas' => 'XI-TJKT-1',
            'nama_kelas' => 'XI TJKT 1',
            'major_id' => 1,
            'rombel' => '36',
           
        ]);

        Classroom::create([
            'kode_kelas' => 'XII-TJKT-1',
            'nama_kelas' => 'XII TJKT 1',
            'major_id' => 1,
            'rombel' => '36',
            
        ]);
    }
}
