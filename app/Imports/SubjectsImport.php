<?php

namespace App\Imports;

use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SubjectsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'kode_mapel' => trim((string) ($row['kode_mapel'] ?? '')),
                'nama_mapel' => trim((string) ($row['nama_mapel'] ?? '')),
                'kategori' => trim((string) ($row['kategori'] ?? '')),
                'jam_per_minggu' => $row['jam_per_minggu'] ?? null,
            ];

            $validator = Validator::make($data, [
                'kode_mapel' => 'required|max:20',
                'nama_mapel' => 'required|max:255',
                'kategori' => 'required|in:Umum,Kejuruan,Muatan Lokal',
                'jam_per_minggu' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            Subject::updateOrCreate(
                ['kode_mapel' => $data['kode_mapel']],
                [
                    'nama_mapel' => $data['nama_mapel'],
                    'kategori' => $data['kategori'],
                    'jam_per_minggu' => (int) $data['jam_per_minggu'],
                ]
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
