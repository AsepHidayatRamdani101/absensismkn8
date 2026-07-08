<?php

namespace App\Exports;

use App\Models\TeacherSubject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeacherSubjectsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return TeacherSubject::query()
            ->with(['teacher', 'subject', 'classroom', 'academicYear'])
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'teacher_nip' => $item->teacher->nip ?? '',
                    'teacher_nama' => $item->teacher->nama_lengkap ?? '',
                    'subject_kode_mapel' => $item->subject->kode_mapel ?? '',
                    'subject_nama_mapel' => $item->subject->nama_mapel ?? '',
                    'classroom_kode_kelas' => $item->classroom->kode_kelas ?? '',
                    'classroom_nama_kelas' => $item->classroom->nama_kelas ?? '',
                    'tahun_ajaran' => $item->academicYear->tahun_ajaran ?? '',
                    'semester' => $item->academicYear->semester ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'teacher_nip',
            'teacher_nama',
            'subject_kode_mapel',
            'subject_nama_mapel',
            'classroom_kode_kelas',
            'classroom_nama_kelas',
            'tahun_ajaran',
            'semester',
        ];
    }
}
