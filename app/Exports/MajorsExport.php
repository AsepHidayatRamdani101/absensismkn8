<?php

namespace App\Exports;

use App\Models\Major;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MajorsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Major::query()
            ->select('kode_jurusan', 'nama_jurusan', 'singkatan')
            ->orderBy('kode_jurusan')
            ->get();
    }

    public function headings(): array
    {
        return ['kode_jurusan', 'nama_jurusan', 'singkatan'];
    }
}
