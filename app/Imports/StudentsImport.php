<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'nis' => trim((string) ($row['nis'] ?? '')),
                'nisn' => trim((string) ($row['nisn'] ?? '')),
                'nama_lengkap' => trim((string) ($row['nama_lengkap'] ?? '')),
                'jenis_kelamin' => trim((string) ($row['jenis_kelamin'] ?? '')),
                'classroom_kode_kelas' => trim((string) ($row['classroom_kode_kelas'] ?? '')),
                'alamat' => trim((string) ($row['alamat'] ?? '')),
                'no_hp' => trim((string) ($row['no_hp'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'nis' => 'required|max:255',
                'nisn' => 'nullable|max:255',
                'nama_lengkap' => 'required|max:255',
                'jenis_kelamin' => 'required|in:L,P',
                'classroom_kode_kelas' => 'required|max:20',
                'alamat' => 'nullable|max:65535',
                'no_hp' => 'nullable|max:255',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $classroom = Classroom::where('kode_kelas', $data['classroom_kode_kelas'])->first();

            if (!$classroom) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': Kode kelas tidak ditemukan (' . $data['classroom_kode_kelas'] . ').',
                ]);
            }

            Student::updateOrCreate(
                ['nis' => $data['nis']],
                [
                    'nisn' => $data['nisn'] !== '' ? $data['nisn'] : null,
                    'nama_lengkap' => $data['nama_lengkap'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'classroom_id' => $classroom->id,
                    'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
                    'no_hp' => $data['no_hp'] !== '' ? $data['no_hp'] : null,
                ]
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
