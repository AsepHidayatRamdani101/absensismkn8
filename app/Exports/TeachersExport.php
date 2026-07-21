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
            ->with('waliClassroom')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function (Teacher $teacher) {
                return [
                    'nip' => $teacher->nip,
                    'nuptk' => $teacher->nuptk,
                    'nama_lengkap' => $teacher->nama_lengkap,
                    'jabatan' => $teacher->jabatan,
                    'jenis_kelamin' => $teacher->jenis_kelamin,
                    'no_hp' => $teacher->no_hp,
                    'alamat' => $teacher->alamat,
                    'is_wali_kelas' => $teacher->is_wali_kelas ? '1' : '0',
                    'wali_kelas' => $teacher->waliClassroom->nama_kelas ?? null,
                    'is_kurikulum' => $teacher->is_kurikulum ? '1' : '0',
                ];
            });
    }

    public function headings(): array
    {
        return ['nip', 'nuptk', 'nama_lengkap', 'jabatan', 'jenis_kelamin', 'no_hp', 'alamat', 'is_wali_kelas', 'wali_kelas', 'is_kurikulum'];
    }
}
