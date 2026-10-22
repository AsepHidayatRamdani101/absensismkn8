<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AssessmentReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $kind,
        private readonly Collection $rows
    ) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn(array $row) => array_values($row));
    }

    public function headings(): array
    {
        if ($this->rows->isNotEmpty()) {
            return array_keys($this->rows->first());
        }

        return match ($this->kind) {
            'daily' => ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Penilaian', 'Jenis', 'Tanggal', 'Nilai', 'Skor Maksimum', 'Bobot'],
            'final' => ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos'],
            default => ['NIS', 'Siswa', 'Mata Pelajaran', 'Guru', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos'],
        };
    }

    public function title(): string
    {
        return match ($this->kind) {
            'daily' => 'Nilai Harian',
            'final' => 'Nilai Akhir',
            default => 'Rapor dan Absensi',
        };
    }
}
