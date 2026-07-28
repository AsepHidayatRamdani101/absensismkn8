<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class TeachersImport implements ToCollection, WithCalculatedFormulas
{
    private int $rowsRead = 0;

    private int $rowsProcessed = 0;

    private int $rowsSkipped = 0;

    private int $skippedNotFound = 0;

    private int $createdCount = 0;

    private int $updatedCount = 0;

    private int $waliAssignedCount = 0;

    private int $waliClassNotFoundCount = 0;

    private array $waliAssignedTeacherIds = [];

    private array $waliClassNotFoundDetails = [];

    private string $detectedFormat = 'unknown';

    private ?Collection $teachersCache = null;

    private bool $hasWaliKelasColumns = false;

    private bool $hasKurikulumColumn = false;

    public function collection(Collection $rows)
    {
        $this->resetSummary();

        $normalizedRows = $rows
            ->map(fn($row) => $this->normalizeRow($row))
            ->values();

        $this->hasWaliKelasColumns = Schema::hasColumns('teachers', ['is_wali_kelas', 'wali_classroom_id']);
        $this->hasKurikulumColumn = Schema::hasColumn('teachers', 'is_kurikulum');

        if ($normalizedRows->isEmpty()) {
            return;
        }

        foreach ($normalizedRows as $rowIndex => $row) {
            if ($this->isTemplateHeader($row)) {
                $this->importTemplateFormat($normalizedRows, $rowIndex);

                return;
            }

            if ($this->isJadwalHeader($row)) {
                $this->importJadwalFormat($normalizedRows, $rowIndex, $row);

                return;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Format file tidak dikenali. Gunakan template guru atau file jadwal guru pengampu.',
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
                'nip' => $this->sanitizeNip($this->cell($row, $headerIndexes['nip'] ?? null)),
                'nuptk' => trim((string) $this->cell($row, $headerIndexes['nuptk'] ?? null)),
                'nama_lengkap' => trim((string) $this->cell($row, $headerIndexes['nama_lengkap'] ?? null)),
                'jabatan' => trim((string) ($this->cell($row, $headerIndexes['jabatan'] ?? null) ?? 'guru')),
                'jenis_kelamin' => trim((string) $this->cell($row, $headerIndexes['jenis_kelamin'] ?? null)),
                'no_hp' => trim((string) $this->cell($row, $headerIndexes['no_hp'] ?? null)),
                'alamat' => trim((string) $this->cell($row, $headerIndexes['alamat'] ?? null)),
                'is_wali_kelas' => trim((string) ($this->cell($row, $headerIndexes['is_wali_kelas'] ?? null) ?? '0')),
                'wali_kelas' => trim((string) $this->cell($row, $headerIndexes['wali_kelas'] ?? null)),
                'is_kurikulum' => trim((string) ($this->cell($row, $headerIndexes['is_kurikulum'] ?? null) ?? '0')),
            ];

            $this->rowsRead++;

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
                    'file' => 'Baris ' . $excelRow . ': ' . $validator->errors()->first(),
                ]);
            }

            if ($isWaliKelas && $data['wali_kelas'] !== '' && !$waliClassroomId) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': Kelas wali tidak ditemukan.',
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

            $this->rowsProcessed++;
        }
    }

    private function importJadwalFormat(Collection $rows, int $headerRowIndex, array $header): void
    {
        $this->detectedFormat = 'jadwal';

        $nameIndex = $this->findHeaderIndex($header, ['NAMA']);
        $nipIndex = $this->findHeaderIndex($header, ['NIP/NIPPPK', 'NIP']);
        $phoneIndex = $this->findHeaderIndex($header, ['NO. HP', 'NO HP']);
        $walasIndex = $this->findHeaderIndex($header, ['WALAS', 'WALI KELAS']);
        $subjectShortIndex = $this->findHeaderIndex($header, ['MAPEL YANG DIAMPU']);
        $subjectFullIndex = $this->findHeaderIndex($header, ['MATA PELAJARAN YANG DIAMPU']);

        if ($nameIndex === null) {
            throw ValidationException::withMessages([
                'file' => 'Format jadwal tidak valid. Kolom NAMA tidak ditemukan.',
            ]);
        }

        foreach ($rows->slice($headerRowIndex + 1)->values() as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $excelRow = $headerRowIndex + $index + 2;

            $name = trim((string) $this->cell($row, $nameIndex));
            $nip = $this->sanitizeNip($this->cell($row, $nipIndex));
            $phone = trim((string) $this->cell($row, $phoneIndex));
            $walasText = trim((string) $this->cell($row, $walasIndex));
            $subjectShort = trim((string) $this->cell($row, $subjectShortIndex));
            $subjectFull = trim((string) $this->cell($row, $subjectFullIndex));

            if ($subjectShort === '' && $subjectFull === '') {
                continue;
            }

            $waliClassroomId = null;
            if ($this->hasWaliKelasColumns && $this->isLikelyWalasText($walasText)) {
                $waliClassroomId = $this->resolveWaliClassroomId($walasText);

                if ($waliClassroomId === null) {
                    $this->waliClassNotFoundCount++;
                    $this->recordWaliClassNotFoundDetail($excelRow, $name, $walasText);
                }
            }

            if ($name === '') {
                continue;
            }

            $this->rowsRead++;

            $teacher = $this->resolveExistingTeacher($nip, $name);
            if (!$teacher) {
                $teacher = $this->createTeacherFromJadwalRow($name, $nip, $phone, $waliClassroomId);

                if (!$teacher) {
                    $this->rowsSkipped++;
                    $this->skippedNotFound++;
                    continue;
                }

                if ($this->hasWaliKelasColumns && $waliClassroomId !== null) {
                    $this->countWaliAssignment($teacher->id);
                }

                $this->createdCount++;
                $this->rowsProcessed++;
                continue;
            }

            $payload = [];

            if ($phone !== '') {
                $payload['no_hp'] = $phone;
            }

            if ($nip !== '') {
                $currentNip = trim((string) $teacher->nip);
                if ($currentNip === '' || $currentNip === $nip) {
                    $payload['nip'] = $nip;
                }
            }

            if ($this->hasWaliKelasColumns) {
                $payload['is_wali_kelas'] = $waliClassroomId !== null;
                $payload['wali_classroom_id'] = $waliClassroomId;
            }

            if (!empty($payload)) {
                if ($this->hasWaliKelasColumns && $waliClassroomId !== null) {
                    $this->releaseExistingWaliAssignment($teacher->id, $waliClassroomId);
                    $this->countWaliAssignment($teacher->id);
                }

                $teacher->update($payload);
            }

            $this->rowsProcessed++;
            $this->updatedCount++;
        }
    }

    private function createTeacherFromJadwalRow(string $name, string $nip, string $phone, ?int $waliClassroomId): ?Teacher
    {
        $data = [
            'nama_lengkap' => $name,
            'jabatan' => 'guru',
            // Format jadwal tidak memiliki jenis kelamin, gunakan default aman agar insert valid.
            'jenis_kelamin' => 'L',
            'no_hp' => $phone !== '' ? $phone : null,
        ];

        if ($nip !== '') {
            $data['nip'] = $nip;
        }

        if ($this->hasWaliKelasColumns) {
            $data['is_wali_kelas'] = $waliClassroomId !== null;
            $data['wali_classroom_id'] = $waliClassroomId;
        }

        if ($this->hasKurikulumColumn) {
            $data['is_kurikulum'] = false;
        }

        if ($this->hasWaliKelasColumns && $waliClassroomId !== null) {
            $this->releaseExistingWaliAssignment(0, $waliClassroomId);
        }

        $teacher = Teacher::create($data);

        if ($this->teachersCache !== null) {
            $this->teachersCache->push($teacher);
        }

        return $teacher;
    }

    private function resolveWaliClassroomId(string $walasText): ?int
    {
        $text = Str::upper(trim((string) preg_replace('/\s+/', ' ', $walasText)));
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/^WALI\s*KELAS\s*/', '', $text) ?? $text;

        if (preg_match('/(XII|XI|X)\s+([A-Z]+)\s+(\d+)/', $text, $matches) === 1) {
            $tingkat = Str::upper($matches[1]);
            $major = Str::upper($matches[2]);
            $rombel = (string) ((int) $matches[3]);

            $majorCandidates = $this->buildMajorCandidates($major);
            foreach ($majorCandidates as $majorCode) {
                $kodeKelas = $tingkat . '-' . $majorCode . '-' . $rombel;
                $namaKelas = $tingkat . ' ' . $majorCode . ' ' . $rombel;

                $classroomId = Classroom::query()
                    ->where('kode_kelas', $kodeKelas)
                    ->orWhere('nama_kelas', $namaKelas)
                    ->value('id');

                if ($classroomId) {
                    return (int) $classroomId;
                }
            }
        }

        $classroomId = Classroom::query()
            ->whereRaw('UPPER(nama_kelas) = ?', [$text])
            ->orWhereRaw('UPPER(kode_kelas) = ?', [$text])
            ->value('id');

        return $classroomId ? (int) $classroomId : null;
    }

    private function buildMajorCandidates(string $majorCode): array
    {
        $majorCode = Str::upper(trim($majorCode));
        $candidates = [$majorCode];

        if ($majorCode === 'TKJ') {
            $candidates[] = 'TJKT';
        }

        if ($majorCode === 'TJKT') {
            $candidates[] = 'TKJ';
        }

        return array_values(array_unique($candidates));
    }

    private function releaseExistingWaliAssignment(int $currentTeacherId, int $waliClassroomId): void
    {
        $query = Teacher::query()->where('wali_classroom_id', $waliClassroomId);

        if ($currentTeacherId > 0) {
            $query->where('id', '!=', $currentTeacherId);
        }

        $query->update([
            'wali_classroom_id' => null,
            'is_wali_kelas' => false,
        ]);

        if ($this->teachersCache !== null) {
            $this->teachersCache = Teacher::query()->get();
        }
    }

    private function isLikelyWalasText(string $walasText): bool
    {
        $text = Str::upper(trim((string) preg_replace('/\s+/', ' ', $walasText)));

        if ($text === '') {
            return false;
        }

        if (Str::contains($text, 'WALI')) {
            return true;
        }

        return preg_match('/\b(XII|XI|X)\s+[A-Z]+\s+\d+\b/', $text) === 1;
    }

    private function countWaliAssignment(int $teacherId): void
    {
        if (!in_array($teacherId, $this->waliAssignedTeacherIds, true)) {
            $this->waliAssignedTeacherIds[] = $teacherId;
            $this->waliAssignedCount++;
        }
    }

    private function recordWaliClassNotFoundDetail(int $excelRow, string $name, string $walasText): void
    {
        if (count($this->waliClassNotFoundDetails) >= 10) {
            return;
        }

        $teacherName = $name !== '' ? $name : '-';
        $this->waliClassNotFoundDetails[] = 'Baris ' . $excelRow . ' (' . $teacherName . ': ' . $walasText . ')';
    }

    private function isTemplateHeader(array $row): bool
    {
        $map = $this->buildHeaderIndexMap($row);

        return isset($map['nama_lengkap']) && isset($map['jenis_kelamin']);
    }

    private function isJadwalHeader(array $row): bool
    {
        return $this->findHeaderIndex($row, ['NAMA']) !== null
            && $this->findHeaderIndex($row, ['MATA PELAJARAN YANG DIAMPU', 'MAPEL YANG DIAMPU']) !== null;
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

        // Normalize punctuation/spaces so variants like "NIP/NIPPPK" and "NIP/ NIPPPK" are equivalent.
        return (string) preg_replace('/[^a-z0-9]+/', '', $label);
    }

    private function resolveExistingTeacher(string $nip, string $name): ?Teacher
    {
        $teachers = $this->getTeachers();

        if ($nip !== '') {
            $teacherByNip = $teachers->first(fn(Teacher $item) => trim((string) $item->nip) === $nip);
            if ($teacherByNip) {
                return $teacherByNip;
            }
        }

        $normalizedName = $this->normalizeName($name);
        if ($normalizedName === '') {
            return null;
        }

        return $teachers->first(function (Teacher $item) use ($normalizedName) {
            return $normalizedName === $this->normalizeName((string) $item->nama_lengkap);
        });
    }

    private function normalizeName(string $value): string
    {
        $upper = Str::upper(trim($value));

        return preg_replace('/[^A-Z0-9]+/', '', $upper) ?? '';
    }

    private function sanitizeNip($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return number_format($value, 0, '', '');
        }

        $nip = trim((string) $value);

        if ($nip === '') {
            return '';
        }

        if (Str::contains(Str::lower($nip), 'e+')) {
            if (is_numeric($nip)) {
                return number_format((float) $nip, 0, '', '');
            }

            return '';
        }

        if (Str::endsWith($nip, '.0') && is_numeric($nip)) {
            $nip = substr($nip, 0, -2);
        }

        return $nip;
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

    private function getTeachers(): Collection
    {
        if ($this->teachersCache === null) {
            $this->teachersCache = Teacher::query()->get();
        }

        return $this->teachersCache;
    }

    public function getSuccessMessage(): string
    {
        if ($this->detectedFormat === 'jadwal') {
            $waliNotFoundSample = '';
            if (!empty($this->waliClassNotFoundDetails)) {
                $waliNotFoundSample = ' Contoh WALAS tidak dikenali: ' . implode('; ', $this->waliClassNotFoundDetails) . '.';
            }

            return 'Import master guru dari file jadwal selesai. ' .
                'Baris terbaca: ' . $this->rowsRead . ', ' .
                'baris diproses: ' . $this->rowsProcessed . ', ' .
                'baris dibuat: ' . $this->createdCount . ', ' .
                'baris diperbarui: ' . $this->updatedCount . ', ' .
                'wali kelas terpasang: ' . $this->waliAssignedCount . ', ' .
                'wali kelas tidak ditemukan: ' . $this->waliClassNotFoundCount . ', ' .
                'baris dilewati: ' . $this->rowsSkipped . ' (gagal cocok/buat: ' . $this->skippedNotFound . ').' .
                $waliNotFoundSample;
        }

        return 'Import data guru berhasil. ' .
            'Baris terbaca: ' . $this->rowsRead . ', ' .
            'baris diproses: ' . $this->rowsProcessed . '.';
    }

    private function resetSummary(): void
    {
        $this->rowsRead = 0;
        $this->rowsProcessed = 0;
        $this->rowsSkipped = 0;
        $this->skippedNotFound = 0;
        $this->createdCount = 0;
        $this->updatedCount = 0;
        $this->waliAssignedCount = 0;
        $this->waliClassNotFoundCount = 0;
        $this->waliAssignedTeacherIds = [];
        $this->waliClassNotFoundDetails = [];
        $this->detectedFormat = 'unknown';
        $this->teachersCache = null;
        $this->hasWaliKelasColumns = false;
        $this->hasKurikulumColumn = false;
    }

    private function isRowEmpty(array $row): bool
    {
        return collect($row)->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
