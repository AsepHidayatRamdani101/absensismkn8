<?php

namespace App\Exports;

use App\Models\StaffTu;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffTuExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return StaffTu::query()
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn(StaffTu $s) => [
                'nip'           => $s->nip,
                'nama_lengkap'  => $s->nama_lengkap,
                'jabatan'       => $s->jabatan,
                'jenis_kelamin' => $s->jenis_kelamin,
                'no_hp'         => $s->no_hp,
                'alamat'        => $s->alamat,
            ]);
    }

    public function headings(): array
    {
        return ['nip', 'nama_lengkap', 'jabatan', 'jenis_kelamin', 'no_hp', 'alamat'];
    }
}
