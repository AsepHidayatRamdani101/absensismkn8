<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Major;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class ClassroomsImport implements ToCollection, WithCalculatedFormulas
{
    private int $rowsRead = 0;

    private int $rowsProcessed = 0;

    private int $rowsSkipped = 0;

    private int $createdCount = 0;

    private int $updatedCount = 0;

    private string $detectedFormat = 'unknown';

    public function collection(Collection $rows)
    {
        $this->resetSummary();

        $normalizedRows = $rows
            ->map(fn($row) => $this->normalizeRow($row))
            ->values();

        if ($normalizedRows->isEmpty()) {
            return;
        }

        foreach ($normalizedRows as $rowIndex => $row) {
            if ($this->isTemplateHeader($row)) {
                $this->importTemplateFormat($normalizedRows, $rowIndex);

                return;
            }

            if ($this->isJadwalHeader($row)) {
                $this->importJadwalFormat($row);

                return;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Format file tidak dikenali. Gunakan template kelas atau format jadwal guru pengampu.',
        ]);
    }

    private function importTemplateFormat(Collection $rows, int $headerRowIndex): void
    {
        $this->detectedFormat = 'template';

        $header = $rows->get($headerRowIndex, []);
        $headerIndexes = $this->buildHeaderIndexMap($header);

        foreach ($rows->slice($headerRowIndex + 1)->values() as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $excelRow = $headerRowIndex + $index + 2;

            $data = [
                'major_kode_jurusan' => trim((string) $this->cell($row, $headerIndexes['major_kode_jurusan'] ?? null)),
                'kode_kelas' => trim((string) $this->cell($row, $headerIndexes['kode_kelas'] ?? null)),
                'nama_kelas' => trim((string) $this->cell($row, $headerIndexes['nama_kelas'] ?? null)),
                'tingkat' => trim((string) $this->cell($row, $headerIndexes['tingkat'] ?? null)),
                'rombel' => trim((string) $this->cell($row, $headerIndexes['rombel'] ?? null)),
            ];

            $this->rowsRead++;

            $validator = Validator::make($data, [
                'major_kode_jurusan' => 'required|max:10',
                'kode_kelas' => 'required|max:20',
                'nama_kelas' => 'required|max:100',
                'tingkat' => 'required|in:X,XI,XII',
                'rombel' => 'required|max:10',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': ' . $validator->errors()->first(),
                ]);
            }

            [$major, $resolvedMajorCode] = $this->resolveMajorWithCode($data['major_kode_jurusan']);

            if (!$major) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': Kode jurusan tidak ditemukan (' . $data['major_kode_jurusan'] . ').',
                ]);
            }

            $tingkat = Str::upper($data['tingkat']);
            $rombel = trim($data['rombel']);
            $autoKodeKelas = $tingkat . '-' . $resolvedMajorCode . '-' . $rombel;
            $kodeKelas = trim($data['kode_kelas']) !== '' ? $data['kode_kelas'] : $autoKodeKelas;

            $classroom = Classroom::updateOrCreate(
                ['kode_kelas' => $kodeKelas],
                [
                    'major_id' => $major->id,
                    'nama_kelas' => $data['nama_kelas'],
                    'tingkat' => $tingkat,
                    'rombel' => $rombel,
                ]
            );

            if ($classroom->wasRecentlyCreated) {
                $this->createdCount++;
            } else {
                $this->updatedCount++;
            }

            $this->rowsProcessed++;
        }
    }

    private function importJadwalFormat(array $header): void
    {
        $this->detectedFormat = 'jadwal';

        $classroomColumns = $this->extractJadwalClassroomColumns($header);

        if (empty($classroomColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Kolom kelas tidak ditemukan pada file jadwal.',
            ]);
        }

        foreach ($classroomColumns as $columnLabel) {
            $this->rowsRead++;

            $parsed = $this->parseClassroomLabel($columnLabel);
            if ($parsed === null) {
                $this->rowsSkipped++;
                continue;
            }

            [$major, $resolvedMajorCode] = $this->resolveMajorWithCode($parsed['major_code']);

            if (!$major) {
                throw ValidationException::withMessages([
                    'file' => 'Kode jurusan hasil penyesuaian tidak ditemukan untuk kelas ' . $columnLabel . '.',
                ]);
            }

            $kodeKelas = $parsed['tingkat'] . '-' . $resolvedMajorCode . '-' . $parsed['rombel'];
            $namaKelas = $parsed['tingkat'] . ' ' . $resolvedMajorCode . ' ' . $parsed['rombel'];

            $classroom = Classroom::updateOrCreate(
                ['kode_kelas' => $kodeKelas],
                [
                    'major_id' => $major->id,
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $parsed['tingkat'],
                    'rombel' => $parsed['rombel'],
                ]
            );

            if ($classroom->wasRecentlyCreated) {
                $this->createdCount++;
            } else {
                $this->updatedCount++;
            }

            $this->rowsProcessed++;
        }
    }

    private function isTemplateHeader(array $row): bool
    {
        $map = $this->buildHeaderIndexMap($row);

        return isset(
            $map['major_kode_jurusan'],
            $map['kode_kelas'],
            $map['nama_kelas'],
            $map['tingkat'],
            $map['rombel']
        );
    }

    private function isJadwalHeader(array $row): bool
    {
        return $this->findHeaderIndex($row, ['NAMA']) !== null
            && $this->findHeaderIndex($row, ['MAPEL YANG DIAMPU', 'MATA PELAJARAN YANG DIAMPU']) !== null
            && !empty($this->extractJadwalClassroomColumns($row));
    }

    private function buildHeaderIndexMap(array $header): array
    {
        $map = [];

        foreach ($header as $index => $value) {
            $normalized = $this->normalizeTemplateHeading((string) $value);
            if ($normalized !== '') {
                $map[$normalized] = $index;
            }
        }

        return $map;
    }

    private function normalizeTemplateHeading(string $heading): string
    {
        $normalized = Str::lower(trim($heading));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    private function findHeaderIndex(array $header, array $possibleHeadings): ?int
    {
        foreach ($header as $index => $value) {
            $normalizedCell = $this->normalizeHeaderLabel((string) $value);

            foreach ($possibleHeadings as $candidate) {
                if ($normalizedCell === $this->normalizeHeaderLabel($candidate)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function normalizeHeaderLabel(string $label): string
    {
        $label = str_replace("\xc2\xa0", ' ', $label);
        $label = Str::lower(trim((string) preg_replace('/\s+/', ' ', str_replace('.', '', $label))));

        return (string) preg_replace('/[^a-z0-9]+/', '', $label);
    }

    private function extractJadwalClassroomColumns(array $header): array
    {
        $classrooms = [];

        foreach ($header as $value) {
            $label = trim((string) $value);
            if ($label === '') {
                continue;
            }

            if (preg_match('/^(X|XI|XII)\s+[A-Z]+\s+\d+$/i', $label) === 1) {
                $classrooms[$label] = true;
            }
        }

        return array_keys($classrooms);
    }

    private function parseClassroomLabel(string $label): ?array
    {
        if (preg_match('/^(X|XI|XII)\s+([A-Z]+)\s+(\d+)$/i', trim($label), $matches) !== 1) {
            return null;
        }

        return [
            'tingkat' => Str::upper($matches[1]),
            'major_code' => Str::upper($matches[2]),
            'rombel' => (string) ((int) $matches[3]),
        ];
    }

    private function normalizeMajorCode(string $majorCode): string
    {
        $majorCode = Str::upper(trim($majorCode));

        return $majorCode;
    }

    private function resolveMajor(string $majorCode): ?Major
    {
        $major = Major::whereRaw('LOWER(kode_jurusan) = ?', [Str::lower($majorCode)])->first();
        if ($major) {
            return $major;
        }

        return Major::whereRaw('LOWER(singkatan) = ?', [Str::lower($majorCode)])->first();
    }

    private function resolveMajorWithCode(string $rawMajorCode): array
    {
        $candidates = $this->buildMajorCandidates($rawMajorCode);

        foreach ($candidates as $candidate) {
            $major = $this->resolveMajor($candidate);
            if ($major) {
                return [$major, Str::upper((string) $major->kode_jurusan)];
            }
        }

        return [null, $this->normalizeMajorCode($rawMajorCode)];
    }

    private function buildMajorCandidates(string $rawMajorCode): array
    {
        $normalized = $this->normalizeMajorCode($rawMajorCode);
        $candidates = [$normalized];

        if ($normalized === 'TKJ') {
            $candidates[] = 'TJKT';
        }

        if ($normalized === 'TJKT') {
            $candidates[] = 'TKJ';
        }

        return array_values(array_unique($candidates));
    }

    private function normalizeRow($row): array
    {
        if ($row instanceof Collection) {
            $row = $row->toArray();
        }

        if (!is_array($row)) {
            return [];
        }

        return array_map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }

            return $value;
        }, $row);
    }

    private function cell(array $row, ?int $index)
    {
        if ($index === null) {
            return null;
        }

        return $row[$index] ?? null;
    }

    public function getSuccessMessage(): string
    {
        if ($this->detectedFormat === 'jadwal') {
            return 'Import data kelas dari format jadwal berhasil. ' .
                'Kolom kelas terbaca: ' . $this->rowsRead . ', ' .
                'diproses: ' . $this->rowsProcessed . ', ' .
                'dibuat: ' . $this->createdCount . ', ' .
                'diperbarui: ' . $this->updatedCount . ', ' .
                'dilewati: ' . $this->rowsSkipped . '.';
        }

        return 'Import data kelas berhasil. ' .
            'Baris terbaca: ' . $this->rowsRead . ', ' .
            'diproses: ' . $this->rowsProcessed . ', ' .
            'dibuat: ' . $this->createdCount . ', ' .
            'diperbarui: ' . $this->updatedCount . '.';
    }

    private function resetSummary(): void
    {
        $this->rowsRead = 0;
        $this->rowsProcessed = 0;
        $this->rowsSkipped = 0;
        $this->createdCount = 0;
        $this->updatedCount = 0;
        $this->detectedFormat = 'unknown';
    }

    private function isRowEmpty(array $row): bool
    {
        return collect($row)->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
