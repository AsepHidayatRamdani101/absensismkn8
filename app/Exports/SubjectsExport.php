<?php

namespace App\Exports;

use App\Models\Subject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubjectsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Subject::query()
            ->select('kode_mapel', 'nama_mapel', 'kategori', 'jam_per_minggu')
            ->orderBy('kode_mapel')
            ->get();
    }

    public function headings(): array
    {
        return ['kode_mapel', 'nama_mapel', 'kategori', 'jam_per_minggu'];
    }
}
