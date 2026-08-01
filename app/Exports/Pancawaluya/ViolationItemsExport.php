<?php

namespace App\Exports\Pancawaluya;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ViolationItemsExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            $mapping = $row->mappings->first();

            return [
                'code' => $row->code,
                'category' => $row->category?->name,
                'name' => $row->name,
                'point' => $row->point,
                'dimension' => $mapping?->dimension?->name,
                'weight' => $mapping?->weight,
                'description' => $row->description,
                'status' => $row->is_active ? 'Aktif' : 'Nonaktif',
            ];
        });
    }

    public function headings(): array
    {
        return ['Kode', 'Kategori', 'Nama Pelanggaran', 'Point', 'Dimensi', 'Weight', 'Deskripsi', 'Status'];
    }
}
