<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Major;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClassroomsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'major_kode_jurusan' => trim((string) ($row['major_kode_jurusan'] ?? '')),
                'kode_kelas' => trim((string) ($row['kode_kelas'] ?? '')),
                'nama_kelas' => trim((string) ($row['nama_kelas'] ?? '')),
                'tingkat' => trim((string) ($row['tingkat'] ?? '')),
                'rombel' => trim((string) ($row['rombel'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'major_kode_jurusan' => 'required|max:10',
                'kode_kelas' => 'required|max:20',
                'nama_kelas' => 'required|max:100',
                'tingkat' => 'required|in:X,XI,XII',
                'rombel' => 'required|max:10',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $major = Major::where('kode_jurusan', $data['major_kode_jurusan'])->first();

            if (!$major) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': Kode jurusan tidak ditemukan (' . $data['major_kode_jurusan'] . ').',
                ]);
            }

            Classroom::updateOrCreate(
                ['kode_kelas' => $data['kode_kelas']],
                [
                    'major_id' => $major->id,
                    'nama_kelas' => $data['nama_kelas'],
                    'tingkat' => $data['tingkat'],
                    'rombel' => $data['rombel'],
                ]
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
