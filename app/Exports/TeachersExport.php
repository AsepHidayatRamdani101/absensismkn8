<?php

namespace App\Exports;

use App\Models\Teacher;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeachersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Teacher::query()
            ->select('nip', 'nuptk', 'nama_lengkap', 'jenis_kelamin', 'no_hp', 'alamat')
            ->orderBy('nama_lengkap')
            ->get();
    }

    public function headings(): array
    {
        return ['nip', 'nuptk', 'nama_lengkap', 'jenis_kelamin', 'no_hp', 'alamat'];
    }
}
