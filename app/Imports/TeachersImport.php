<?php

namespace App\Imports;

use App\Models\Classroom;
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
                'jabatan' => trim((string) ($row['jabatan'] ?? 'guru')),
                'jenis_kelamin' => trim((string) ($row['jenis_kelamin'] ?? '')),
                'no_hp' => trim((string) ($row['no_hp'] ?? '')),
                'alamat' => trim((string) ($row['alamat'] ?? '')),
                'is_wali_kelas' => trim((string) ($row['is_wali_kelas'] ?? '0')),
                'wali_kelas' => trim((string) ($row['wali_kelas'] ?? '')),
                'is_kurikulum' => trim((string) ($row['is_kurikulum'] ?? '0')),
            ];

            $isWaliKelas = in_array(strtolower($data['is_wali_kelas']), ['1', 'true', 'ya', 'yes'], true);
            $isKurikulum = in_array(strtolower($data['is_kurikulum']), ['1', 'true', 'ya', 'yes'], true);
            $waliClassroomId = null;

            if ($isWaliKelas && $data['wali_kelas'] !== '') {
                $waliClassroomId = Classroom::query()
                    ->where('nama_kelas', $data['wali_kelas'])
                    ->orWhere('kode_kelas', $data['wali_kelas'])
                    ->value('id');
            }

            $validator = Validator::make($data, [
                'nip' => 'nullable|max:255',
                'nuptk' => 'nullable|max:255',
                'nama_lengkap' => 'required|max:255',
                'jabatan' => 'required|in:guru,kepala_program,kepala_sekolah,bk',
                'jenis_kelamin' => 'required|in:L,P',
                'no_hp' => 'nullable|max:255',
                'alamat' => 'nullable|max:65535',
                'is_wali_kelas' => 'nullable',
                'wali_kelas' => 'nullable|max:255',
                'is_kurikulum' => 'nullable',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            if ($isWaliKelas && $data['wali_kelas'] !== '' && !$waliClassroomId) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': Kelas wali tidak ditemukan.',
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
                    'jabatan' => $data['jabatan'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'no_hp' => $data['no_hp'] !== '' ? $data['no_hp'] : null,
                    'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
                    'is_wali_kelas' => $isWaliKelas,
                    'wali_classroom_id' => $isWaliKelas ? $waliClassroomId : null,
                    'is_kurikulum' => $isKurikulum,
                ]
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
