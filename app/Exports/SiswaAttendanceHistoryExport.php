<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SiswaAttendanceHistoryExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows->map(function ($history) {
            $attendance = $history->teacherAttendance;

            return [
                'tanggal' => optional($attendance?->tanggal)->format('Y-m-d') ?? null,
                'hari' => $attendance?->tanggal ? $attendance->tanggal->translatedFormat('l') : null,
                'mata_pelajaran' => $attendance?->subject?->nama_mapel,
                'guru' => $attendance?->teacher?->nama_lengkap,
                'kelas' => $attendance?->classroom?->nama_kelas,
                'status' => $history->status === 'Alpha' ? 'Alpa' : $history->status,
                'jam_absen' => $history->jam_absen,
                'keterangan' => $history->keterangan,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Hari',
            'Mata Pelajaran',
            'Guru',
            'Kelas',
            'Status',
            'Jam Absen',
            'Keterangan',
        ];
    }
}
