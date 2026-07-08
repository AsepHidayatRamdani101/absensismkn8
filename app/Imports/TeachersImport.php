<?php

namespace App\Imports;

use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TeachersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'nip' => trim((string) ($row['nip'] ?? '')),
                'nuptk' => trim((string) ($row['nuptk'] ?? '')),
                'nama_lengkap' => trim((string) ($row['nama_lengkap'] ?? '')),
                'jenis_kelamin' => trim((string) ($row['jenis_kelamin'] ?? '')),
                'no_hp' => trim((string) ($row['no_hp'] ?? '')),
                'alamat' => trim((string) ($row['alamat'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'nip' => 'nullable|max:255',
                'nuptk' => 'nullable|max:255',
                'nama_lengkap' => 'required|max:255',
                'jenis_kelamin' => 'required|in:L,P',
                'no_hp' => 'nullable|max:255',
                'alamat' => 'nullable|max:65535',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $lookup = $data['nip'] !== ''
                ? ['nip' => $data['nip']]
                : ['nama_lengkap' => $data['nama_lengkap']];

            Teacher::updateOrCreate(
                $lookup,
                [
                    'nip' => $data['nip'] !== '' ? $data['nip'] : null,
                    'nuptk' => $data['nuptk'] !== '' ? $data['nuptk'] : null,
                    'nama_lengkap' => $data['nama_lengkap'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'no_hp' => $data['no_hp'] !== '' ? $data['no_hp'] : null,
                    'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
                ]
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
