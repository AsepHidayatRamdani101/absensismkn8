<?php

namespace App\Exports;

use App\Models\Classroom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassroomsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Classroom::query()
            ->with('major')
            ->orderBy('tingkat')
            ->orderBy('rombel')
            ->get()
            ->map(function ($classroom) {
                return [
                    'major_kode_jurusan' => $classroom->major->kode_jurusan ?? '',
                    'kode_kelas' => $classroom->kode_kelas,
                    'nama_kelas' => $classroom->nama_kelas,
                    'tingkat' => $classroom->tingkat,
                    'rombel' => $classroom->rombel,
                ];
            });
    }

    public function headings(): array
    {
        return ['major_kode_jurusan', 'kode_kelas', 'nama_kelas', 'tingkat', 'rombel'];
    }
}
