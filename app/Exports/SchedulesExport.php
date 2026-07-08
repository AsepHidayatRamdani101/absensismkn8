<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SchedulesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Schedule::query()
            ->with(['teacherSubject.teacher', 'teacherSubject.subject', 'teacherSubject.classroom', 'teacherSubject.academicYear'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get()
            ->map(function ($schedule) {
                return [
                    'teacher_subject_id' => $schedule->teacher_subject_id,
                    'guru' => $schedule->teacherSubject->teacher->nama_lengkap ?? '',
                    'mapel' => $schedule->teacherSubject->subject->nama_mapel ?? '',
                    'kelas' => $schedule->teacherSubject->classroom->nama_kelas ?? '',
                    'tahun_ajaran' => $schedule->teacherSubject->academicYear->tahun_ajaran ?? '',
                    'hari' => $schedule->hari,
                    'jam_mulai' => $schedule->jam_mulai,
                    'jam_selesai' => $schedule->jam_selesai,
                    'ruangan' => $schedule->ruangan,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'teacher_subject_id',
            'guru',
            'mapel',
            'kelas',
            'tahun_ajaran',
            'hari',
            'jam_mulai',
            'jam_selesai',
            'ruangan',
        ];
    }
}
