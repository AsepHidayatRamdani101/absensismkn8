<?php

namespace App\Imports;

use App\Models\Major;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MajorsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'kode_jurusan' => trim((string) ($row['kode_jurusan'] ?? '')),
                'nama_jurusan' => trim((string) ($row['nama_jurusan'] ?? '')),
                'singkatan' => trim((string) ($row['singkatan'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'kode_jurusan' => 'required|max:10',
                'nama_jurusan' => 'required|max:255',
                'singkatan' => 'required|max:10',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            Major::updateOrCreate(
                ['kode_jurusan' => $data['kode_jurusan']],
                $data
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
