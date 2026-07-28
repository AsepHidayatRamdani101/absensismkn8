<?php

namespace App\Imports;

use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class SubjectsImport implements ToCollection, WithCalculatedFormulas
{
    private int $rowsRead = 0;

    private int $rowsSkipped = 0;

    private int $recordsSynced = 0;

    private int $createdSubjects = 0;

    private int $updatedSubjects = 0;

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
                $this->importTemplateFormat($normalizedRows, $rowIndex, $row);

                return;
            }

            if ($this->isMapelListMarker($row)) {
                $this->importMapelListFormat($normalizedRows);

                return;
            }

            if ($this->isJadwalHeader($row)) {
                $this->importJadwalFormat($normalizedRows, $rowIndex, $row);

                return;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Format file tidak dikenali. Gunakan template mapel, daftar mapel.xlsx, atau format jadwal.xlsx (MATA PELAJARAN YANG DIAMPU / MAPEL YANG DIAMPU).',
        ]);
    }

    public function getSuccessMessage(): string
    {
        $formatLabel = match ($this->detectedFormat) {
            'template' => 'Template',
            'mapel_list' => 'Daftar Mapel',
            default => 'Jadwal',
        };

        return 'Import mapel selesai. Format: ' . $formatLabel
            . '. Baris terbaca: ' . $this->rowsRead
            . ', mapel disinkronkan: ' . $this->recordsSynced
            . ', dibuat: ' . $this->createdSubjects
            . ', diperbarui: ' . $this->updatedSubjects
            . ', baris dilewati: ' . $this->rowsSkipped . '.';
    }

    private function importMapelListFormat(Collection $rows): void
    {
        $this->detectedFormat = 'mapel_list';

        $processedCodes = [];

        foreach ($rows as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $no = trim((string) $this->cell($row, 0));
            $listCode = trim((string) $this->cell($row, 1));
            $fullName = trim((string) $this->cell($row, 2));
            $shortCode = trim((string) $this->cell($row, 3));

            // Only process numbered data rows (01..58), skip title/meta rows.
            if (preg_match('/^\d+$/', $no) !== 1) {
                continue;
            }

            if ($fullName === '' && $shortCode === '') {
                $this->rowsSkipped++;
                continue;
            }

            $effectiveCode = $listCode !== '' ? $listCode : ($shortCode !== '' ? $shortCode : $fullName);
            $effectiveCodeKey = Str::lower($this->normalizeSubjectKey($effectiveCode));
            if ($effectiveCodeKey !== '' && isset($processedCodes[$effectiveCodeKey])) {
                continue;
            }

            if ($effectiveCodeKey !== '') {
                $processedCodes[$effectiveCodeKey] = true;
            }

            $this->rowsRead++;

            $nameToStore = $fullName !== '' ? $fullName : $shortCode;
            $codeToStore = $effectiveCode;

            $subject = $this->resolveSubjectForMapelList($codeToStore, $shortCode, $fullName);
            if ($subject) {
                $updated = false;

                if ($fullName !== '' && Str::lower(trim((string) $subject->nama_mapel)) !== Str::lower($fullName)) {
                    $subject->nama_mapel = $fullName;
                    $updated = true;
                }

                if ($shortCode !== '' && Str::lower(trim((string) $subject->kode_mapel)) !== Str::lower($shortCode)) {
                    $subject->kode_mapel = $shortCode;
                    $updated = true;
                }

                if ($updated) {
                    $subject->save();
                    $this->updatedSubjects++;
                }

                $this->recordsSynced++;
                continue;
            }

            $kodeMapel = $this->generateUniqueSubjectCode($codeToStore);

            Subject::create([
                'kode_mapel' => $kodeMapel,
                'nama_mapel' => $nameToStore,
                'kategori' => 'Umum',
                'jam_per_minggu' => 0,
            ]);

            $this->createdSubjects++;
            $this->recordsSynced++;
        }
    }

    private function importTemplateFormat(Collection $rows, int $headerRowIndex, array $header): void
    {
        $this->detectedFormat = 'template';
        $indexes = $this->buildHeaderIndexMap($header);

        foreach ($rows->slice($headerRowIndex + 1)->values() as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $excelRow = $headerRowIndex + $index + 2;

            $data = [
                'kode_mapel' => trim((string) $this->cell($row, $indexes['kode_mapel'] ?? null)),
                'nama_mapel' => trim((string) $this->cell($row, $indexes['nama_mapel'] ?? null)),
                'kategori' => trim((string) $this->cell($row, $indexes['kategori'] ?? null)),
                'jam_per_minggu' => $this->cell($row, $indexes['jam_per_minggu'] ?? null),
            ];

            $this->rowsRead++;

            $validator = Validator::make($data, [
                'kode_mapel' => 'required|max:20',
                'nama_mapel' => 'required|max:255',
                'kategori' => 'required|in:Umum,Kejuruan,Muatan Lokal',
                'jam_per_minggu' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': ' . $validator->errors()->first(),
                ]);
            }

            $this->upsertSubject(
                $data['kode_mapel'],
                $data['nama_mapel'],
                $data['kategori'],
                (int) $data['jam_per_minggu']
            );
        }
    }

    private function importJadwalFormat(Collection $rows, int $headerRowIndex, array $header): void
    {
        $this->detectedFormat = 'jadwal';

        $shortSubjectIndex = $this->findHeaderIndex($header, [
            'MAPEL YANG DIAMPU',
            'MAPEL YANG DI AMPU',
            'MAPEL YG DIAMPU',
        ]);
        $fullSubjectIndex = $this->findHeaderIndex($header, [
            'MATA PELAJARAN YANG DIAMPU',
            'MATA PELAJARAN YANG DI AMPU',
        ]);

        if ($shortSubjectIndex === null && $fullSubjectIndex === null) {
            throw ValidationException::withMessages([
                'file' => 'Format jadwal tidak valid. Kolom MAPEL YANG DIAMPU atau MATA PELAJARAN YANG DIAMPU tidak ditemukan.',
            ]);
        }

        $processedKeys = [];

        foreach ($rows->slice($headerRowIndex + 1)->values() as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $shortCode = trim((string) $this->cell($row, $shortSubjectIndex));
            $fullName = trim((string) $this->cell($row, $fullSubjectIndex));

            if ($shortCode === '' && $fullName === '') {
                continue;
            }

            if (!$this->looksLikeSubjectValue($shortCode, $fullName)) {
                $this->rowsSkipped++;
                continue;
            }

            $this->rowsRead++;

            $stableKey = $this->buildJadwalSubjectDedupKey($shortCode, $fullName);
            if ($stableKey !== '' && isset($processedKeys[$stableKey])) {
                continue;
            }

            if ($stableKey !== '') {
                $processedKeys[$stableKey] = true;
            }

            $subject = $this->resolveSubjectForJadwal($shortCode, $fullName);
            if ($subject) {
                $updated = false;

                if ($fullName !== '' && Str::lower(trim((string) $subject->nama_mapel)) !== Str::lower($fullName)) {
                    $subject->nama_mapel = $fullName;
                    $updated = true;
                }

                if ($subject->kategori === null || trim((string) $subject->kategori) === '') {
                    $subject->kategori = 'Umum';
                    $updated = true;
                }

                if ($subject->jam_per_minggu === null) {
                    $subject->jam_per_minggu = 0;
                    $updated = true;
                }

                if ($updated) {
                    $subject->save();
                    $this->updatedSubjects++;
                }

                $this->recordsSynced++;
                continue;
            }

            $nameToStore = $fullName !== '' ? $fullName : $shortCode;
            if ($nameToStore === '') {
                $this->rowsSkipped++;
                continue;
            }

            $baseCode = $shortCode !== '' ? $shortCode : $nameToStore;
            $kodeMapel = $this->generateUniqueSubjectCode($baseCode);

            Subject::create([
                'kode_mapel' => $kodeMapel,
                'nama_mapel' => $nameToStore,
                'kategori' => 'Umum',
                'jam_per_minggu' => 0,
            ]);

            $this->createdSubjects++;
            $this->recordsSynced++;
        }
    }

    private function isTemplateHeader(array $header): bool
    {
        $map = $this->buildHeaderIndexMap($header);

        return isset($map['kode_mapel'], $map['nama_mapel'], $map['kategori'], $map['jam_per_minggu']);
    }

    private function isJadwalHeader(array $header): bool
    {
        $hasSubject = $this->findHeaderIndex($header, [
            'MAPEL YANG DIAMPU',
            'MAPEL YANG DI AMPU',
            'MAPEL YG DIAMPU',
            'MATA PELAJARAN YANG DIAMPU',
            'MATA PELAJARAN YANG DI AMPU',
        ]) !== null;

        $hasIdentity = $this->findHeaderIndex($header, ['NAMA']) !== null
            || $this->findHeaderIndex($header, ['NIP/NIPPPK', 'NIP']) !== null;

        return $hasSubject && $hasIdentity;
    }

    private function isMapelListMarker(array $row): bool
    {
        foreach ($row as $value) {
            $normalized = $this->normalizeHeaderLabel((string) $value);
            if ($normalized === 'daftarnamamatapelajaran') {
                return true;
            }
        }

        return false;
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

    private function resolveSubject(string $shortCode, string $fullName): ?Subject
    {
        if ($shortCode !== '') {
            $byCode = Subject::query()
                ->whereRaw('LOWER(kode_mapel) = ?', [Str::lower($shortCode)])
                ->first();

            if ($byCode) {
                return $byCode;
            }
        }

        if ($fullName !== '') {
            $byName = Subject::query()
                ->whereRaw('LOWER(nama_mapel) = ?', [Str::lower($fullName)])
                ->first();

            if ($byName) {
                return $byName;
            }
        }

        return null;
    }

    private function resolveSubjectForMapelList(string $listCode, string $shortCode, string $fullName): ?Subject
    {
        if ($listCode !== '') {
            $byListCode = Subject::query()
                ->whereRaw('LOWER(kode_mapel) = ?', [Str::lower($listCode)])
                ->first();

            if ($byListCode) {
                return $byListCode;
            }
        }

        if ($fullName !== '') {
            $byName = Subject::query()
                ->whereRaw('LOWER(nama_mapel) = ?', [Str::lower($fullName)])
                ->first();

            if ($byName) {
                return $byName;
            }
        }

        if ($shortCode !== '') {
            $byShort = Subject::query()
                ->whereRaw('LOWER(kode_mapel) = ?', [Str::lower($shortCode)])
                ->first();

            if ($byShort) {
                return $byShort;
            }
        }

        return null;
    }

    private function resolveSubjectForJadwal(string $shortCode, string $fullName): ?Subject
    {
        if ($fullName !== '') {
            $byName = Subject::query()
                ->whereRaw('LOWER(nama_mapel) = ?', [Str::lower($fullName)])
                ->first();

            if ($byName) {
                return $byName;
            }
        }

        if ($shortCode !== '') {
            $byCode = Subject::query()
                ->whereRaw('LOWER(kode_mapel) = ?', [Str::lower($shortCode)])
                ->first();

            if ($byCode && $fullName === '') {
                return $byCode;
            }

            if ($byCode && $fullName !== '' && Str::lower(trim((string) $byCode->nama_mapel)) === Str::lower($fullName)) {
                return $byCode;
            }
        }

        return null;
    }

    private function upsertSubject(string $kodeMapel, string $namaMapel, string $kategori, int $jamPerMinggu): void
    {
        $existing = Subject::query()
            ->whereRaw('LOWER(kode_mapel) = ?', [Str::lower($kodeMapel)])
            ->first();

        if ($existing) {
            $existing->update([
                'nama_mapel' => $namaMapel,
                'kategori' => $kategori,
                'jam_per_minggu' => $jamPerMinggu,
            ]);
            $this->updatedSubjects++;
            $this->recordsSynced++;

            return;
        }

        Subject::create([
            'kode_mapel' => $kodeMapel,
            'nama_mapel' => $namaMapel,
            'kategori' => $kategori,
            'jam_per_minggu' => $jamPerMinggu,
        ]);

        $this->createdSubjects++;
        $this->recordsSynced++;
    }

    private function generateUniqueSubjectCode(string $base): string
    {
        $candidate = $this->buildSubjectCode($base);
        $counter = 1;

        while (Subject::query()->whereRaw('LOWER(kode_mapel) = ?', [Str::lower($candidate)])->exists()) {
            $suffix = '-' . $counter;
            $trimmed = Str::limit($this->buildSubjectCode($base), 20 - strlen($suffix), '');
            $candidate = $trimmed . $suffix;
            $counter++;
        }

        return $candidate;
    }

    private function buildSubjectCode(string $value): string
    {
        $upper = Str::upper(trim($value));
        $code = preg_replace('/[^A-Z0-9]+/', '-', $upper) ?? '';
        $code = trim((string) preg_replace('/-+/', '-', $code), '-');

        if ($code === '') {
            return 'MAPEL';
        }

        return Str::limit($code, 20, '');
    }

    private function normalizeSubjectKey(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }

    private function looksLikeSubjectValue(string $shortCode, string $fullName): bool
    {
        $short = trim($shortCode);
        $full = trim($fullName);

        if ($short === '' && $full === '') {
            return false;
        }

        // Reject numeric-only noise values from summary/footer rows.
        $fullNumericOnly = $full !== '' && preg_match('/^[0-9]+$/', $full) === 1;
        $shortNumericOnly = $short !== '' && preg_match('/^[0-9]+$/', $short) === 1;

        if (($full === '' || $fullNumericOnly) && ($short === '' || $shortNumericOnly)) {
            return false;
        }

        return true;
    }

    private function buildJadwalSubjectDedupKey(string $shortCode, string $fullName): string
    {
        $fullKey = $this->normalizeSubjectKey($fullName);
        if ($fullKey !== '') {
            return 'full:' . $fullKey;
        }

        $shortKey = $this->normalizeSubjectKey($shortCode);
        if ($shortKey !== '') {
            return 'short:' . $shortKey;
        }

        return '';
    }

    private function normalizeRow($row): array
    {
        if ($row instanceof Collection) {
            $row = $row->values()->toArray();
        } elseif (is_array($row)) {
            $row = array_values($row);
        } else {
            $row = [];
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

    private function isRowEmpty(array $row): bool
    {
        return collect($row)->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }

    private function resetSummary(): void
    {
        $this->rowsRead = 0;
        $this->rowsSkipped = 0;
        $this->recordsSynced = 0;
        $this->createdSubjects = 0;
        $this->updatedSubjects = 0;
        $this->detectedFormat = 'unknown';
    }
}
