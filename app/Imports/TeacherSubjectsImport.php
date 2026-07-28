<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class TeacherSubjectsImport implements ToCollection, WithCalculatedFormulas
{
    public function __construct(private readonly bool $autoCreateMaster = true) {}

    private ?Collection $subjectsCache = null;

    private ?Collection $classroomsCache = null;

    private ?Collection $teachersCache = null;

    private int $rowsRead = 0;

    private int $rowsSkipped = 0;

    private int $recordsSynced = 0;

    private int $skippedTeacher = 0;

    private int $skippedSubject = 0;

    private int $skippedNoClassLoad = 0;

    private int $createdSubjects = 0;

    private int $createdClassrooms = 0;

    private int $createdMajors = 0;

    private int $createdTeachers = 0;

    private string $detectedFormat = 'unknown';

    private bool $hasKurikulumColumn = false;

    private bool $hasWaliKelasColumns = false;

    private int $skippedMissingMaster = 0;

    public function collection(Collection $rows)
    {
        $this->resetSummary();

        $normalizedRows = $rows
            ->map(fn($row) => $this->normalizeRow($row))
            ->values();

        $this->hasKurikulumColumn = Schema::hasColumn('teachers', 'is_kurikulum');
        $this->hasWaliKelasColumns = Schema::hasColumns('teachers', ['is_wali_kelas', 'wali_classroom_id']);

        if ($normalizedRows->isEmpty()) {
            return;
        }

        foreach ($normalizedRows as $rowIndex => $row) {
            if ($this->isTemplateHeader($row)) {
                $this->importTemplateFormat($normalizedRows, $rowIndex);

                return;
            }

            if ($this->isJadwalHeader($row)) {
                $this->importJadwalFormat($normalizedRows, $row, $rowIndex);

                return;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Format file tidak dikenali. Gunakan template standar atau format jadwal guru pengampu.',
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
                'teacher_nip' => $this->sanitizeNip($this->cell($row, $headerIndexes['teacher_nip'] ?? null)),
                'subject_kode_mapel' => trim((string) $this->cell($row, $headerIndexes['subject_kode_mapel'] ?? null)),
                'classroom_kode_kelas' => trim((string) $this->cell($row, $headerIndexes['classroom_kode_kelas'] ?? null)),
                'tahun_ajaran' => trim((string) $this->cell($row, $headerIndexes['tahun_ajaran'] ?? null)),
                'semester' => $this->normalizeSemester((string) $this->cell($row, $headerIndexes['semester'] ?? null)),
            ];

            $this->rowsRead++;

            $validator = Validator::make($data, [
                'teacher_nip' => 'required|max:255',
                'subject_kode_mapel' => 'required|max:20',
                'classroom_kode_kelas' => 'required|max:50',
                'tahun_ajaran' => 'required|max:20',
                'semester' => 'required|in:Ganjil,Genap',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': ' . $validator->errors()->first(),
                ]);
            }

            $teacher = Teacher::where('nip', $data['teacher_nip'])->first();
            if (!$teacher) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': NIP guru tidak ditemukan (' . $data['teacher_nip'] . ').',
                ]);
            }

            $subject = Subject::whereRaw('LOWER(kode_mapel) = ?', [Str::lower($data['subject_kode_mapel'])])->first();
            if (!$subject) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': Kode mapel tidak ditemukan (' . $data['subject_kode_mapel'] . ').',
                ]);
            }

            $classroom = $this->resolveClassroom($data['classroom_kode_kelas']);
            if (!$classroom) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': Kode kelas tidak ditemukan (' . $data['classroom_kode_kelas'] . ').',
                ]);
            }

            $academicYear = AcademicYear::where('tahun_ajaran', $data['tahun_ajaran'])
                ->where('semester', $data['semester'])
                ->first();

            if (!$academicYear) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': Tahun ajaran/semester tidak ditemukan (' . $data['tahun_ajaran'] . ' - ' . $data['semester'] . ').',
                ]);
            }

            $this->storeTeacherSubject($teacher->id, $subject->id, $classroom->id, $academicYear->id);
        }
    }

    private function importJadwalFormat(Collection $rows, array $header, int $headerRowIndex): void
    {
        $this->detectedFormat = 'jadwal';

        $nipIndex = $this->findHeaderIndex($header, ['NIP/NIPPPK', 'NIP']);
        $teacherNameIndex = $this->findHeaderIndex($header, ['NAMA']);
        $phoneIndex = $this->findHeaderIndex($header, ['NO. HP', 'NO HP']);
        $shortSubjectIndex = $this->findHeaderIndex($header, [
            'MAPEL YANG DIAMPU',
            'MAPEL YANG DI AMPU',
            'MAPEL YG DIAMPU',
        ]);
        $fullSubjectIndex = $this->findHeaderIndex($header, [
            'MATA PELAJARAN YANG DIAMPU',
            'MATA PELAJARAN YANG DI AMPU',
        ]);
        $classroomColumns = $this->extractJadwalClassroomColumns($header);

        if (($nipIndex === null && $teacherNameIndex === null) || ($shortSubjectIndex === null && $fullSubjectIndex === null) || empty($classroomColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Format jadwal tidak valid. Pastikan kolom identitas guru, MAPEL YANG DIAMPU, dan kolom kelas tersedia.',
            ]);
        }

        $academicYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::query()->latest('id')->first();
        if (!$academicYear) {
            throw ValidationException::withMessages([
                'file' => 'Tahun ajaran aktif belum tersedia. Tambahkan data tahun ajaran terlebih dahulu.',
            ]);
        }

        $classroomColumnMap = [];
        foreach ($classroomColumns as $column) {
            $classroom = $this->autoCreateMaster
                ? $this->ensureClassroomFromLabel($column['label'])
                : $this->resolveClassroom($column['label']);

            if (!$classroom) {
                continue;
            }

            $classroomColumnMap[] = [
                'index' => $column['index'],
                'classroom_id' => $classroom->id,
            ];
        }

        if (empty($classroomColumnMap)) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada kolom kelas pada file jadwal yang cocok dengan data kelas di database.',
            ]);
        }

        foreach ($rows->slice($headerRowIndex + 1)->values() as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $nip = $this->sanitizeNip($this->cell($row, $nipIndex));
            $teacherName = trim((string) $this->cell($row, $teacherNameIndex));
            $phone = trim((string) $this->cell($row, $phoneIndex));
            $subjectShort = trim((string) $this->cell($row, $shortSubjectIndex));
            $subjectFull = trim((string) $this->cell($row, $fullSubjectIndex));

            if (($nip === '' && $teacherName === '') || ($subjectShort === '' && $subjectFull === '')) {
                continue;
            }

            $this->rowsRead++;

            $teacher = $this->resolveTeacher($nip, $teacherName);
            if (!$teacher) {
                $teacher = $this->autoCreateMaster
                    ? $this->createTeacherFromJadwal($teacherName, $nip, $phone)
                    : null;

                if (!$teacher) {
                    $this->rowsSkipped++;
                    $this->skippedTeacher++;
                    $this->skippedMissingMaster++;
                    continue;
                }
            }

            $subject = $this->resolveSubject($subjectShort, $subjectFull);
            if (!$subject) {
                $subject = $this->autoCreateMaster
                    ? $this->createSubjectFromJadwal($subjectShort, $subjectFull)
                    : null;

                if (!$subject) {
                    $this->rowsSkipped++;
                    $this->skippedSubject++;
                    $this->skippedMissingMaster++;
                    continue;
                }
            }

            $classroomIds = [];
            foreach ($classroomColumnMap as $column) {
                $load = $this->cell($row, $column['index']);
                if (!$this->hasTeachingLoad($load)) {
                    continue;
                }

                $classroomIds[] = $column['classroom_id'];
            }

            foreach (array_unique($classroomIds) as $classroomId) {
                $this->storeTeacherSubject($teacher->id, $subject->id, $classroomId, $academicYear->id);
            }

            if (empty($classroomIds)) {
                $this->rowsSkipped++;
                $this->skippedNoClassLoad++;
            }
        }
    }

    private function isTemplateHeader(array $header): bool
    {
        $headerMap = $this->buildHeaderIndexMap($header);

        return isset(
            $headerMap['teacher_nip'],
            $headerMap['subject_kode_mapel'],
            $headerMap['classroom_kode_kelas'],
            $headerMap['tahun_ajaran'],
            $headerMap['semester']
        );
    }

    private function isJadwalHeader(array $header): bool
    {
        $hasIdentity = $this->findHeaderIndex($header, ['NAMA']) !== null
            || $this->findHeaderIndex($header, ['NIP/NIPPPK', 'NIP']) !== null;

        $hasSubject = $this->findHeaderIndex($header, [
            'MAPEL YANG DIAMPU',
            'MAPEL YANG DI AMPU',
            'MAPEL YG DIAMPU',
            'MATA PELAJARAN YANG DIAMPU',
            'MATA PELAJARAN YANG DI AMPU',
        ]) !== null;

        return $hasIdentity && $hasSubject;
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
        $normalized = trim($normalized, '_');

        return $normalized;
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

    private function extractJadwalClassroomColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $value) {
            $label = trim((string) $value);
            if ($label === '') {
                continue;
            }

            if (preg_match('/^(X|XI|XII)\s+[A-Z]+\s+\d+$/i', $label) === 1) {
                $columns[] = [
                    'index' => $index,
                    'label' => $label,
                ];
            }
        }

        return $columns;
    }

    private function normalizeHeaderLabel(string $label): string
    {
        $label = str_replace("\xc2\xa0", ' ', $label);
        $label = Str::lower(trim((string) preg_replace('/\s+/', ' ', str_replace('.', '', $label))));

        // Normalize punctuation/spaces so variants like "NIP/NIPPPK" and "NIP/ NIPPPK" are equivalent.
        return (string) preg_replace('/[^a-z0-9]+/', '', $label);
    }

    private function resolveClassroom(string $value): ?Classroom
    {
        $normalized = $this->normalizeClassLabel($value);
        if ($normalized === '') {
            return null;
        }

        $classrooms = $this->getClassrooms();
        $normalizedAlias = $this->swapMajorAlias($normalized);

        foreach ($classrooms as $classroom) {
            $kode = $this->normalizeClassLabel((string) $classroom->kode_kelas);
            $nama = $this->normalizeClassLabel((string) $classroom->nama_kelas);

            if ($normalized === $kode || $normalized === $nama) {
                return $classroom;
            }

            if ($normalizedAlias === $kode || $normalizedAlias === $nama) {
                return $classroom;
            }
        }

        return null;
    }

    private function resolveSubject(string $shortLabel, string $fullLabel): ?Subject
    {
        $shortLabel = trim($shortLabel);
        $fullLabel = trim($fullLabel);
        $subjects = $this->getSubjects();

        if ($shortLabel !== '') {
            $subject = $subjects->first(fn(Subject $item) => Str::lower((string) $item->kode_mapel) === Str::lower($shortLabel));
            if ($subject) {
                return $subject;
            }
        }

        if ($fullLabel !== '') {
            $subject = $subjects->first(fn(Subject $item) => Str::lower((string) $item->nama_mapel) === Str::lower($fullLabel));
            if ($subject) {
                return $subject;
            }
        }

        if ($shortLabel !== '') {
            $shortNormalized = $this->normalizeSubjectLabel($shortLabel);
            $subject = $subjects->first(function (Subject $item) use ($shortNormalized) {
                return $shortNormalized === $this->normalizeSubjectLabel((string) $item->kode_mapel)
                    || $shortNormalized === $this->normalizeSubjectLabel((string) $item->nama_mapel);
            });

            if ($subject) {
                return $subject;
            }
        }

        if ($fullLabel !== '') {
            $fullNormalized = $this->normalizeSubjectLabel($fullLabel);
            $subject = $subjects->first(function (Subject $item) use ($fullNormalized) {
                return $fullNormalized === $this->normalizeSubjectLabel((string) $item->nama_mapel)
                    || $fullNormalized === $this->normalizeSubjectLabel((string) $item->kode_mapel);
            });

            if ($subject) {
                return $subject;
            }
        }

        return null;
    }

    private function createSubjectFromJadwal(string $shortLabel, string $fullLabel): ?Subject
    {
        $shortLabel = trim($shortLabel);
        $fullLabel = trim($fullLabel);

        $codeSource = $shortLabel !== '' ? $shortLabel : $fullLabel;
        if ($codeSource === '') {
            return null;
        }

        $baseCode = $this->buildSubjectCode($codeSource);
        $kodeMapel = $this->generateUniqueSubjectCode($baseCode);
        $namaMapel = $fullLabel !== '' ? $fullLabel : $shortLabel;

        $subject = Subject::create([
            'kode_mapel' => $kodeMapel,
            'nama_mapel' => $namaMapel,
            'kategori' => 'Umum',
            'jam_per_minggu' => 0,
        ]);

        if ($this->subjectsCache !== null) {
            $this->subjectsCache->push($subject);
        }

        $this->createdSubjects++;

        return $subject;
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

    private function generateUniqueSubjectCode(string $baseCode): string
    {
        $candidate = $baseCode;
        $counter = 1;

        while (Subject::where('kode_mapel', $candidate)->exists()) {
            $suffix = '-' . $counter;
            $prefix = Str::limit($baseCode, 20 - strlen($suffix), '');
            $candidate = $prefix . $suffix;
            $counter++;
        }

        return $candidate;
    }

    private function ensureClassroomFromLabel(string $label): Classroom
    {
        $existing = $this->resolveClassroom($label);
        if ($existing) {
            return $existing;
        }

        if (preg_match('/^(X|XI|XII)\s+([A-Z]+)\s+(\d+)$/i', trim($label), $matches) !== 1) {
            throw ValidationException::withMessages([
                'file' => 'Format kolom kelas tidak valid (' . $label . ').',
            ]);
        }

        $tingkat = Str::upper($matches[1]);
        $majorCode = Str::upper($matches[2]);
        $rombel = (string) ((int) $matches[3]);
        $majorCode = $majorCode === 'TKJ' ? 'TJKT' : $majorCode;

        $major = $this->ensureMajor($majorCode);
        $kodeKelas = $tingkat . '-' . $majorCode . '-' . $rombel;
        $namaKelas = $tingkat . ' ' . $majorCode . ' ' . $rombel;

        $classroom = Classroom::firstOrCreate(
            ['kode_kelas' => $kodeKelas],
            [
                'nama_kelas' => $namaKelas,
                'tingkat' => $tingkat,
                'major_id' => $major->id,
                'rombel' => $rombel,
            ]
        );

        if ($classroom->wasRecentlyCreated) {
            $this->createdClassrooms++;

            if ($this->classroomsCache !== null) {
                $this->classroomsCache->push($classroom);
            }
        }

        return $classroom;
    }

    private function ensureMajor(string $majorCode): Major
    {
        $major = Major::where('kode_jurusan', $majorCode)->first();
        if ($major) {
            return $major;
        }

        $majorNames = [
            'TJKT' => 'Teknik Jaringan Komputer dan Telekomunikasi',
            'TKR' => 'Teknik Kendaraan Ringan',
            'DKV' => 'Desain Komunikasi Visual',
            'MP' => 'Manajemen Perkantoran',
        ];

        $major = Major::create([
            'kode_jurusan' => $majorCode,
            'singkatan' => $majorCode,
            'nama_jurusan' => $majorNames[$majorCode] ?? ('Jurusan ' . $majorCode),
        ]);

        $this->createdMajors++;

        return $major;
    }

    private function normalizeSubjectLabel(string $value): string
    {
        $upper = Str::upper(trim($value));

        return preg_replace('/[^A-Z0-9]+/', '', $upper) ?? '';
    }

    private function normalizeClassLabel(string $value): string
    {
        $upper = Str::upper(trim($value));
        $upper = preg_replace('/\s+/', '-', $upper) ?? '';
        $upper = preg_replace('/[^A-Z0-9-]/', '', $upper) ?? '';
        $upper = preg_replace('/-+/', '-', $upper) ?? '';

        return trim($upper, '-');
    }

    private function swapMajorAlias(string $normalizedClass): string
    {
        if (Str::contains($normalizedClass, '-TKJ-')) {
            return str_replace('-TKJ-', '-TJKT-', $normalizedClass);
        }

        if (Str::contains($normalizedClass, '-TJKT-')) {
            return str_replace('-TJKT-', '-TKJ-', $normalizedClass);
        }

        return $normalizedClass;
    }

    private function normalizeSemester(string $semester): string
    {
        $semester = Str::lower(trim($semester));

        if ($semester === 'ganjil') {
            return 'Ganjil';
        }

        if ($semester === 'genap') {
            return 'Genap';
        }

        return $semester;
    }

    private function hasTeachingLoad($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '' || $stringValue === '-' || $stringValue === '0') {
            return false;
        }

        return true;
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

    private function resolveTeacher(string $nip, string $teacherName): ?Teacher
    {
        $teachers = $this->getTeachers();

        if ($nip !== '') {
            $teacherByNip = $teachers->first(fn(Teacher $item) => trim((string) $item->nip) === $nip);
            if ($teacherByNip) {
                return $teacherByNip;
            }
        }

        if ($teacherName === '') {
            return null;
        }

        $normalizedName = $this->normalizeTeacherName($teacherName);

        if ($normalizedName === '') {
            return null;
        }

        return $teachers->first(function (Teacher $item) use ($normalizedName) {
            return $normalizedName === $this->normalizeTeacherName((string) $item->nama_lengkap);
        });
    }

    private function normalizeTeacherName(string $value): string
    {
        $upper = Str::upper(trim($value));

        return preg_replace('/[^A-Z0-9]+/', '', $upper) ?? '';
    }

    private function createTeacherFromJadwal(string $teacherName, string $nip, string $phone): ?Teacher
    {
        if (trim($teacherName) === '') {
            return null;
        }

        $data = [
            'nama_lengkap' => trim($teacherName),
            'jabatan' => 'guru',
            // File jadwal tidak memuat jenis kelamin, gunakan default agar data valid.
            'jenis_kelamin' => 'L',
            'no_hp' => $phone !== '' ? $phone : null,
        ];

        if ($nip !== '') {
            $data['nip'] = $nip;
        }

        if ($this->hasKurikulumColumn) {
            $data['is_kurikulum'] = false;
        }

        if ($this->hasWaliKelasColumns) {
            $data['is_wali_kelas'] = false;
            $data['wali_classroom_id'] = null;
        }

        $teacher = Teacher::create($data);
        $this->createdTeachers++;

        if ($this->teachersCache !== null) {
            $this->teachersCache->push($teacher);
        }

        return $teacher;
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

    private function storeTeacherSubject(int $teacherId, int $subjectId, int $classroomId, int $academicYearId): void
    {
        TeacherSubject::updateOrCreate(
            [
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'classroom_id' => $classroomId,
                'academic_year_id' => $academicYearId,
            ],
            []
        );

        $this->recordsSynced++;
    }

    public function getSuccessMessage(): string
    {
        if ($this->detectedFormat === 'jadwal') {
            return 'Import jadwal guru pengampu selesai. ' .
                'Mode: ' . ($this->autoCreateMaster ? 'Auto-create master' : 'Hanya data existing') . '. ' .
                'Baris terbaca: ' . $this->rowsRead . ', ' .
                'relasi diproses: ' . $this->recordsSynced . ', ' .
                'guru dibuat: ' . $this->createdTeachers . ', ' .
                'mapel dibuat: ' . $this->createdSubjects . ', ' .
                'kelas dibuat: ' . $this->createdClassrooms . ', ' .
                'jurusan dibuat: ' . $this->createdMajors . ', ' .
                'baris dilewati: ' . $this->rowsSkipped . '. ' .
                'data master tidak ditemukan: ' . $this->skippedMissingMaster . '. ' .
                'Detail lewati: guru tidak cocok ' . $this->skippedTeacher . ', ' .
                'mapel tidak cocok ' . $this->skippedSubject . ', ' .
                'tanpa jam kelas valid ' . $this->skippedNoClassLoad . '.';
        }

        return 'Import data guru pengampu berhasil. ' .
            'Baris terbaca: ' . $this->rowsRead . ', ' .
            'relasi diproses: ' . $this->recordsSynced . '.';
    }

    private function resetSummary(): void
    {
        $this->rowsRead = 0;
        $this->rowsSkipped = 0;
        $this->recordsSynced = 0;
        $this->skippedTeacher = 0;
        $this->skippedSubject = 0;
        $this->skippedNoClassLoad = 0;
        $this->createdSubjects = 0;
        $this->createdClassrooms = 0;
        $this->createdMajors = 0;
        $this->createdTeachers = 0;
        $this->skippedMissingMaster = 0;
        $this->detectedFormat = 'unknown';
        $this->hasKurikulumColumn = false;
        $this->hasWaliKelasColumns = false;
        $this->subjectsCache = null;
        $this->classroomsCache = null;
        $this->teachersCache = null;
    }

    private function getSubjects(): Collection
    {
        if ($this->subjectsCache === null) {
            $this->subjectsCache = Subject::query()->get();
        }

        return $this->subjectsCache;
    }

    private function getClassrooms(): Collection
    {
        if ($this->classroomsCache === null) {
            $this->classroomsCache = Classroom::query()->get();
        }

        return $this->classroomsCache;
    }

    private function getTeachers(): Collection
    {
        if ($this->teachersCache === null) {
            $this->teachersCache = Teacher::query()->get();
        }

        return $this->teachersCache;
    }

    private function isRowEmpty(array $row): bool
    {
        return collect($row)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->isEmpty();
    }
}
