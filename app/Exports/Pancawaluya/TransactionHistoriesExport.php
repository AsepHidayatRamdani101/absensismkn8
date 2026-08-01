<?php

namespace App\Exports\Pancawaluya;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionHistoriesExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return [
                'date' => optional($row->transaction_date)->format('Y-m-d'),
                'type' => $row->reference_type,
                'action' => $row->action,
                'student_nis' => $row->student?->nis,
                'student_name' => $row->student?->nama_lengkap,
                'class' => $row->classroom?->nama_kelas,
                'semester' => $row->semester,
                'status' => $row->status,
                'before' => $row->score_before,
                'after' => $row->score_after,
                'source' => $row->source,
                'actor' => $row->actor?->name,
                'reason' => $row->reason,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Tipe Referensi',
            'Aksi',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Semester',
            'Status',
            'Skor Sebelum',
            'Skor Sesudah',
            'Sumber',
            'Aktor',
            'Alasan',
        ];
    }
}
