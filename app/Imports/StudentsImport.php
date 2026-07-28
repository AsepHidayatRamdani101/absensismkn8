<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsImport
{
    private int $rowsRead = 0;

    private int $rowsSkipped = 0;

    private int $recordsSynced = 0;

    private int $skippedClassroom = 0;

    private int $skippedInvalidIdentity = 0;

    private string $detectedFormat = 'unknown';

    private ?array $classroomMap = null;

    public function importFile(string $filePath): void
    {
        $this->resetSummary();

        $spreadsheet = IOFactory::load($filePath);
        $recognizedSheet = false;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if ($this->sheetLooksLikeTemplate($sheet)) {
                $this->detectedFormat = 'template';
                $recognizedSheet = true;
                $this->importTemplateSheet($sheet);
                continue;
            }

            if ($this->sheetLooksLikeAbsensi($sheet)) {
                if (!$this->sheetHasUsableStudentRows($sheet)) {
                    continue;
                }

                $this->detectedFormat = 'absensi';
                $recognizedSheet = true;
                $this->importAbsensiSheet($sheet);
            }
        }

        if (!$recognizedSheet) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak dikenali. Gunakan template siswa atau file ABSENSI siswa (blok NO/NIS/NISN/NAMA SISWA).',
            ]);
        }
    }

    public function getSuccessMessage(): string
    {
        return 'Import data siswa selesai. Format: ' . ($this->detectedFormat === 'template' ? 'Template' : 'ABSENSI')
            . '. Baris terbaca: ' . $this->rowsRead
            . ', data tersimpan: ' . $this->recordsSynced
            . ', baris dilewati: ' . $this->rowsSkipped
            . '. Detail lewati: kelas tidak cocok ' . $this->skippedClassroom
            . ', identitas siswa tidak valid ' . $this->skippedInvalidIdentity . '.';
    }

    private function importTemplateSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $headerRowIndex = null;
        $headerMap = [];

        for ($rowIndex = 1; $rowIndex <= min(10, $highestRow); $rowIndex++) {
            $candidateMap = $this->extractTemplateHeaderMap($sheet, $rowIndex);

            if (isset($candidateMap['nis'], $candidateMap['nama_lengkap'], $candidateMap['jenis_kelamin'], $candidateMap['classroom_kode_kelas'])) {
                $headerRowIndex = $rowIndex;
                $headerMap = $candidateMap;
                break;
            }
        }

        if ($headerRowIndex === null) {
            return;
        }

        for ($rowIndex = $headerRowIndex + 1; $rowIndex <= $highestRow; $rowIndex++) {
            $nis = $this->sanitizeIdentity($sheet->getCellByColumnAndRow(($headerMap['nis'] ?? 0) + 1, $rowIndex)->getCalculatedValue());
            $nisn = $this->sanitizeIdentity($sheet->getCellByColumnAndRow(($headerMap['nisn'] ?? -1) + 1, $rowIndex)->getCalculatedValue());
            $nama = trim((string) $sheet->getCellByColumnAndRow(($headerMap['nama_lengkap'] ?? 0) + 1, $rowIndex)->getCalculatedValue());
            $jenisKelamin = strtoupper(trim((string) $sheet->getCellByColumnAndRow(($headerMap['jenis_kelamin'] ?? 0) + 1, $rowIndex)->getCalculatedValue()));
            $classroomLabel = trim((string) $sheet->getCellByColumnAndRow(($headerMap['classroom_kode_kelas'] ?? 0) + 1, $rowIndex)->getCalculatedValue());
            $jabatanRaw = trim((string) $sheet->getCellByColumnAndRow(($headerMap['jabatan_kelas'] ?? -1) + 1, $rowIndex)->getCalculatedValue());

            if ($nis === '' && $nisn === '' && $nama === '' && $classroomLabel === '') {
                continue;
            }

            $this->rowsRead++;

            if ($nama === '' || $jenisKelamin === '' || $classroomLabel === '' || !in_array($jenisKelamin, ['L', 'P'], true)) {
                $this->rowsSkipped++;
                $this->skippedInvalidIdentity++;
                continue;
            }

            $identity = $nis !== '' ? $nis : $nisn;
            if ($identity === '') {
                $this->rowsSkipped++;
                $this->skippedInvalidIdentity++;
                continue;
            }

            $classroom = $this->resolveClassroom($classroomLabel);
            if (!$classroom) {
                $this->rowsSkipped++;
                $this->skippedClassroom++;
                continue;
            }

            $jabatan = $this->normalizeJabatan($jabatanRaw);
            if ($jabatan === '__INVALID__') {
                $jabatan = null;
            }

            Student::updateOrCreate(
                ['nis' => $identity],
                [
                    'nisn' => $nisn !== '' ? $nisn : null,
                    'nama_lengkap' => $nama,
                    'jenis_kelamin' => $jenisKelamin,
                    'classroom_id' => $classroom->id,
                    'jabatan_kelas' => $jabatan,
                ]
            );

            $this->recordsSynced++;
        }
    }

    private function importAbsensiSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $currentClassroom = null;
        $activeColumns = null;

        for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
            $cellA = strtoupper(trim((string) $sheet->getCell("A{$rowIndex}")->getCalculatedValue()));

            if ($cellA === 'KELAS') {
                $currentClassroom = $this->extractClassroomLabel($sheet, $rowIndex);
                continue;
            }

            $headerColumns = $this->detectStudentHeaderColumns($sheet, $rowIndex);
            if ($headerColumns !== null) {
                $activeColumns = $headerColumns;
                continue;
            }

            if ($activeColumns === null) {
                continue;
            }

            $noValue = trim((string) $sheet->getCellByColumnAndRow($activeColumns['no'] + 1, $rowIndex)->getCalculatedValue());
            if ($noValue === '' || preg_match('/^\d+$/', $noValue) !== 1) {
                continue;
            }

            $nis = $this->sanitizeIdentity($sheet->getCellByColumnAndRow($activeColumns['nis'] + 1, $rowIndex)->getCalculatedValue());
            $nisn = $this->sanitizeIdentity($sheet->getCellByColumnAndRow($activeColumns['nisn'] + 1, $rowIndex)->getCalculatedValue());
            $nama = trim((string) $sheet->getCellByColumnAndRow($activeColumns['nama'] + 1, $rowIndex)->getCalculatedValue());
            $jenisKelaminRaw = strtoupper(trim((string) $sheet->getCellByColumnAndRow($activeColumns['jk'] + 1, $rowIndex)->getCalculatedValue()));

            // Ignore numbered placeholder rows with no student payload.
            if ($nis === '' && $nisn === '' && $nama === '' && $jenisKelaminRaw === '') {
                continue;
            }

            $this->rowsRead++;

            $existingStudent = $this->findExistingStudent($nis, $nisn);
            $jenisKelamin = $this->resolveJenisKelamin($jenisKelaminRaw, $existingStudent, $nama);

            $nisToStore = $nis !== ''
                ? $nis
                : ($existingStudent?->nis ?: $nisn);

            if ($nisToStore === '' || $nama === '' || $jenisKelamin === null) {
                $this->rowsSkipped++;
                $this->skippedInvalidIdentity++;
                continue;
            }

            $classroom = $this->resolveClassroom((string) $currentClassroom);
            if (!$classroom) {
                $this->rowsSkipped++;
                $this->skippedClassroom++;
                continue;
            }

            Student::updateOrCreate(
                ['nis' => $nisToStore],
                [
                    'nisn' => $nisn !== '' ? $nisn : ($existingStudent?->nisn ?: null),
                    'nama_lengkap' => $nama,
                    'jenis_kelamin' => $jenisKelamin,
                    'classroom_id' => $classroom->id,
                ]
            );

            $this->recordsSynced++;
        }
    }

    private function sheetLooksLikeTemplate(Worksheet $sheet): bool
    {
        for ($rowIndex = 1; $rowIndex <= min(10, $sheet->getHighestRow()); $rowIndex++) {
            $map = $this->extractTemplateHeaderMap($sheet, $rowIndex);
            if (isset($map['nis'], $map['nama_lengkap'], $map['jenis_kelamin'], $map['classroom_kode_kelas'])) {
                return true;
            }
        }

        return false;
    }

    private function sheetLooksLikeAbsensi(Worksheet $sheet): bool
    {
        for ($rowIndex = 1; $rowIndex <= min($sheet->getHighestRow(), 300); $rowIndex++) {
            if ($this->detectStudentHeaderColumns($sheet, $rowIndex) !== null) {
                return true;
            }
        }

        return false;
    }

    private function sheetHasUsableStudentRows(Worksheet $sheet): bool
    {
        $highestRow = min($sheet->getHighestRow(), 350);
        $activeColumns = null;

        for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
            $detected = $this->detectStudentHeaderColumns($sheet, $rowIndex);
            if ($detected !== null) {
                $activeColumns = $detected;
                continue;
            }

            if ($activeColumns === null) {
                continue;
            }

            $noValue = trim((string) $sheet->getCellByColumnAndRow($activeColumns['no'] + 1, $rowIndex)->getCalculatedValue());
            if ($noValue === '' || preg_match('/^\d+$/', $noValue) !== 1) {
                continue;
            }

            $name = trim((string) $sheet->getCellByColumnAndRow($activeColumns['nama'] + 1, $rowIndex)->getCalculatedValue());
            $nis = $this->sanitizeIdentity($sheet->getCellByColumnAndRow($activeColumns['nis'] + 1, $rowIndex)->getCalculatedValue());
            $nisn = $this->sanitizeIdentity($sheet->getCellByColumnAndRow($activeColumns['nisn'] + 1, $rowIndex)->getCalculatedValue());

            if ($name !== '' && ($nis !== '' || $nisn !== '')) {
                return true;
            }
        }

        return false;
    }

    private function resolveJenisKelamin(string $jenisKelaminRaw, ?Student $existingStudent, string $nama): ?string
    {
        if (in_array($jenisKelaminRaw, ['L', 'P'], true)) {
            return $jenisKelaminRaw;
        }

        if ($existingStudent && in_array($existingStudent->jenis_kelamin, ['L', 'P'], true)) {
            return $existingStudent->jenis_kelamin;
        }

        if (trim($nama) !== '') {
            return $this->inferJenisKelaminFromName($nama);
        }

        return null;
    }

    private function inferJenisKelaminFromName(string $nama): string
    {
        $normalized = Str::upper(trim($nama));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        $femaleKeywords = [
            'PUTRI',
            'DEWI',
            'AISYAH',
            'AISHA',
            'ANNISA',
            'NISA',
            'AMELIA',
            'FITRIA',
            'NADILA',
            'NAYLA',
            'SRI',
            'LENI',
            'NURHAYATI',
            'GEISHA',
            'SUCI',
            'RANI',
            'LAILA',
        ];

        foreach ($femaleKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return 'P';
            }
        }

        return 'L';
    }

    private function findExistingStudent(string $nis, string $nisn): ?Student
    {
        if ($nis !== '') {
            $student = Student::query()->where('nis', $nis)->first();
            if ($student) {
                return $student;
            }
        }

        if ($nisn !== '') {
            return Student::query()->where('nisn', $nisn)->first();
        }

        return null;
    }

    private function extractTemplateHeaderMap(Worksheet $sheet, int $rowIndex): array
    {
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $map = [];

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $value = trim((string) $sheet->getCellByColumnAndRow($col, $rowIndex)->getCalculatedValue());
            if ($value === '') {
                continue;
            }

            $normalized = $this->normalizeTemplateHeading($value);
            if ($normalized !== '') {
                $map[$normalized] = $col - 1;
            }
        }

        return $map;
    }

    private function detectStudentHeaderColumns(Worksheet $sheet, int $rowIndex): ?array
    {
        $highestColIndex = min(12, Coordinate::columnIndexFromString($sheet->getHighestColumn()));
        $indexes = [
            'no' => null,
            'nis' => null,
            'nisn' => null,
            'nama' => null,
            'jk' => null,
        ];

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $label = strtoupper(trim((string) $sheet->getCellByColumnAndRow($col, $rowIndex)->getCalculatedValue()));

            if ($label === 'NO') {
                $indexes['no'] = $col - 1;
            } elseif ($label === 'NIS') {
                $indexes['nis'] = $col - 1;
            } elseif ($label === 'NISN') {
                $indexes['nisn'] = $col - 1;
            } elseif ($label === 'NAMA SISWA') {
                $indexes['nama'] = $col - 1;
            } elseif ($label === 'L/P') {
                $indexes['jk'] = $col - 1;
            }
        }

        if (
            $indexes['no'] === null ||
            $indexes['nis'] === null ||
            $indexes['nisn'] === null ||
            $indexes['nama'] === null ||
            $indexes['jk'] === null
        ) {
            return null;
        }

        return $indexes;
    }

    private function extractClassroomLabel(Worksheet $sheet, int $rowIndex): string
    {
        $candidateC = trim((string) $sheet->getCell("C{$rowIndex}")->getCalculatedValue());
        $candidateD = trim((string) $sheet->getCell("D{$rowIndex}")->getCalculatedValue());

        $label = $candidateC !== '' ? $candidateC : $candidateD;
        $label = ltrim($label, ':');
        $label = trim((string) preg_replace('/\s+/', ' ', $label));

        return $label;
    }

    private function resolveClassroom(string $value): ?Classroom
    {
        $normalized = $this->normalizeClassLabel($value);
        if ($normalized === '') {
            return null;
        }

        $classroomMap = $this->getClassroomMap();

        if (isset($classroomMap[$normalized])) {
            return $classroomMap[$normalized];
        }

        $alias = $this->swapMajorAlias($normalized);
        if (isset($classroomMap[$alias])) {
            return $classroomMap[$alias];
        }

        $alias2 = str_replace(' TKJT ', ' TJKT ', ' ' . $normalized . ' ');
        $alias2 = trim($alias2);
        if (isset($classroomMap[$alias2])) {
            return $classroomMap[$alias2];
        }

        return null;
    }

    private function getClassroomMap(): array
    {
        if ($this->classroomMap !== null) {
            return $this->classroomMap;
        }

        $map = [];
        $classrooms = Classroom::query()->get(['id', 'kode_kelas', 'nama_kelas']);

        foreach ($classrooms as $classroom) {
            $kode = $this->normalizeClassLabel((string) $classroom->kode_kelas);
            $nama = $this->normalizeClassLabel((string) $classroom->nama_kelas);

            if ($kode !== '') {
                $map[$kode] = $classroom;
            }

            if ($nama !== '') {
                $map[$nama] = $classroom;
            }
        }

        $this->classroomMap = $map;

        return $this->classroomMap;
    }

    private function normalizeClassLabel(string $value): string
    {
        $value = Str::upper(trim($value));
        if ($value === '') {
            return '';
        }

        $value = str_replace(['-', '.'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = trim($value);
        $value = str_replace('TKJT', 'TJKT', $value);

        return $value;
    }

    private function swapMajorAlias(string $label): string
    {
        $label = trim($label);
        $parts = preg_split('/\s+/', $label) ?: [];

        if (count($parts) < 3) {
            return $label;
        }

        if ($parts[1] === 'TKJ') {
            $parts[1] = 'TJKT';
        } elseif ($parts[1] === 'TJKT') {
            $parts[1] = 'TKJ';
        }

        return implode(' ', $parts);
    }

    private function sanitizeIdentity($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return (string) number_format($value, 0, '', '');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = ltrim($value, "'");

        if (preg_match('/^[0-9]+(\.[0-9]+)?E\+[0-9]+$/i', $value) === 1) {
            return (string) number_format((float) $value, 0, '', '');
        }

        return preg_replace('/\s+/', '', $value) ?? '';
    }

    private function normalizeTemplateHeading(string $heading): string
    {
        $normalized = Str::lower(trim($heading));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    private function normalizeJabatan(string $jabatan): ?string
    {
        if ($jabatan === '') {
            return null;
        }

        $normalized = strtolower(trim($jabatan));

        return match ($normalized) {
            'km',
            'ketua kelas',
            'ketua_kelas' => 'ketua_kelas',
            'sekretaris' => 'sekretaris',
            'bendahara' => 'bendahara',
            default => '__INVALID__',
        };
    }

    private function resetSummary(): void
    {
        $this->rowsRead = 0;
        $this->rowsSkipped = 0;
        $this->recordsSynced = 0;
        $this->skippedClassroom = 0;
        $this->skippedInvalidIdentity = 0;
        $this->detectedFormat = 'unknown';
        $this->classroomMap = null;
    }
}
