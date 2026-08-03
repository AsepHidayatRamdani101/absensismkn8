<?php

namespace App\Imports;

use App\Models\StaffTu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class StaffTuImport implements ToCollection, WithCalculatedFormulas
{
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    public function collection(Collection $rows)
    {
        $headerRow = null;
        $headerIndexes = [];

        foreach ($rows as $rowIndex => $row) {
            $normalized = $row->map(fn($v) => strtolower(trim((string) $v)))->toArray();

            if (in_array('nama_lengkap', $normalized, true)) {
                $headerRow = $rowIndex;
                $headerIndexes = array_flip($normalized);
                break;
            }
        }

        if ($headerRow === null) {
            return;
        }

        foreach ($rows->slice($headerRow + 1)->values() as $row) {
            $cell = fn($key) => isset($headerIndexes[$key]) ? trim((string) $row[$headerIndexes[$key]]) : '';

            $nama = $cell('nama_lengkap');
            if ($nama === '') {
                $this->skipped++;
                continue;
            }

            $jabatan = $cell('jabatan') ?: 'staf_tu';
            if (!in_array($jabatan, ['kepala_tu', 'staf_tu'], true)) {
                $jabatan = 'staf_tu';
            }

            $jk = strtoupper($cell('jenis_kelamin'));
            if (!in_array($jk, ['L', 'P'], true)) {
                $jk = 'L';
            }

            $nip = $cell('nip') ?: null;

            $data = [
                'nama_lengkap'  => $nama,
                'jabatan'       => $jabatan,
                'jenis_kelamin' => $jk,
                'no_hp'         => $cell('no_hp') ?: null,
                'alamat'        => $cell('alamat') ?: null,
            ];

            if ($nip !== null) {
                $staff = StaffTu::query()->where('nip', $nip)->first();
                if ($staff) {
                    $staff->update($data);
                    $this->updated++;
                    continue;
                }
                $data['nip'] = $nip;
            }

            StaffTu::create($data);
            $this->created++;
        }
    }

    public function getSuccessMessage(): string
    {
        return "Import selesai. Dibuat: {$this->created}, Diperbarui: {$this->updated}, Dilewati: {$this->skipped}.";
    }
}
