<?php

namespace App\Exports\Pancawaluya;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RewardCategoriesExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return [
                'code' => $row->code,
                'name' => $row->name,
                'description' => $row->description,
                'status' => $row->is_active ? 'Aktif' : 'Nonaktif',
            ];
        });
    }

    public function headings(): array
    {
        return ['Kode', 'Nama Kategori', 'Deskripsi', 'Status'];
    }
}
