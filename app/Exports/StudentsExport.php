<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Student::query()
            ->with('classroom')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function ($student) {
                return [
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'nama_lengkap' => $student->nama_lengkap,
                    'nama_orang_tua_wali' => $student->nama_orang_tua_wali,
                    'jenis_kelamin' => $student->jenis_kelamin,
                    'classroom_kode_kelas' => $student->classroom->kode_kelas ?? '',
                    'jabatan_kelas' => $student->jabatan_kelas_label,
                    'alamat' => $student->alamat,
                    'no_hp' => $student->no_hp,
                    'no_hp_orang_tua' => $student->no_hp_orang_tua,
                    'tinggi_badan' => $student->tinggi_badan,
                    'berat_badan' => $student->berat_badan,
                ];
            });
    }

    public function headings(): array
    {
        return ['nis', 'nisn', 'nama_lengkap', 'nama_orang_tua_wali', 'jenis_kelamin', 'classroom_kode_kelas', 'jabatan_kelas', 'alamat', 'no_hp', 'no_hp_orang_tua', 'tinggi_badan', 'berat_badan'];
    }
}
