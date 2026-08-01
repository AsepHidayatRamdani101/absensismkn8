<?php

namespace App\Exports\Pancawaluya;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RewardTransactionsExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return [
                'date' => optional($row->transaction_date)->format('Y-m-d'),
                'academic_year' => $row->academicYear?->tahun_ajaran,
                'semester' => $row->semester,
                'student_nis' => $row->student?->nis,
                'student_name' => $row->student?->nama_lengkap,
                'class' => $row->classroom?->nama_kelas,
                'category' => $row->rewardCategory?->name,
                'reward' => $row->rewardItem?->name,
                'point' => $row->point,
                'weighted_point' => $row->weighted_point,
                'source' => $row->source,
                'teacher' => $row->teacher?->nama_lengkap,
                'status' => $row->status,
                'description' => $row->description,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Tahun Ajaran',
            'Semester',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Kategori Reward',
            'Reward',
            'Point',
            'Point Berbobot',
            'Sumber',
            'Guru',
            'Status',
            'Deskripsi',
        ];
    }
}
