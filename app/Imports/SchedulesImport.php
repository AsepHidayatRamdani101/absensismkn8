<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SchedulesImport implements ToCollection, WithCalculatedFormulas
{
    private int $rowsRead = 0;

    private int $rowsSkipped = 0;

    private int $recordsSynced = 0;

    private int $skippedTeacher = 0;

    private int $skippedSubject = 0;

    private int $skippedClassroom = 0;

    private int $skippedTeacherSubject = 0;

    private string $detectedFormat = 'unknown';

    private ?int $activeAcademicYearId = null;

    private ?Collection $teachersCache = null;

    private ?Collection $subjectsCache = null;

    private ?Collection $classroomsCache = null;

    private array $teacherSubjectCache = [];

    private ?array $codeClassTeacherSubjectMap = null;

    private const DAY_PREFIX_MAP = [
        'SEN' => 'Senin',
        'SEL' => 'Selasa',
        'RAB' => 'Rabu',
        'KAM' => 'Kamis',
        'JUM' => 'Jumat',
    ];

    private const PERIOD_TIME_MAP = [
        1 => ['start' => '07:15', 'end' => '07:55'],
        2 => ['start' => '07:55', 'end' => '08:35'],
        3 => ['start' => '08:35', 'end' => '09:15'],
        4 => ['start' => '09:15', 'end' => '09:55'],
        5 => ['start' => '09:55', 'end' => '10:35'],
        6 => ['start' => '10:35', 'end' => '11:15'],
        7 => ['start' => '11:15', 'end' => '11:55'],
        8 => ['start' => '11:55', 'end' => '12:35'],
        9 => ['start' => '12:35', 'end' => '13:15'],
        10 => ['start' => '13:15', 'end' => '13:55'],
        11 => ['start' => '13:55', 'end' => '14:35'],
    ];

    public function collection(Collection $rows)
    {
        $this->resetSummary();

        $normalizedRows = $rows
            ->map(fn($row) => $this->normalizeRow($row))
            ->values();

        if ($normalizedRows->isEmpty()) {
            return;
        }

        $activeAcademicYear = AcademicYear::query()->where('is_active', true)->first();
        $this->activeAcademicYearId = $activeAcademicYear?->id;

        foreach ($normalizedRows as $rowIndex => $row) {
            if ($this->isTemplateHeader($row)) {
                $this->importTemplateFormat($normalizedRows, $rowIndex, $row);

                return;
            }

            if ($this->isJadwalHarianHeader($row)) {
                $this->importJadwalHarianFormat($normalizedRows, $rowIndex, $row);

                return;
            }

            if ($this->isHariWaktuHeader($row)) {
                $this->importHariWaktuFormat($normalizedRows, $rowIndex, $row);

                return;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Format file tidak dikenali. Gunakan template import jadwal, file jadwal harian (SEN01...JUM11), atau jadwal hari dan waktu.',
        ]);
    }

    public function getSuccessMessage(): string
    {
        if ($this->detectedFormat === 'template') {
            return 'Import jadwal selesai (template). Baris terbaca: ' . $this->rowsRead
                . ', jadwal tersimpan: ' . $this->recordsSynced . '.';
        }

        if ($this->detectedFormat === 'hari_waktu') {
            return 'Import jadwal selesai (hari dan waktu). Baris terbaca: ' . $this->rowsRead
                . ', jadwal tersimpan: ' . $this->recordsSynced
                . ', baris dilewati: ' . $this->rowsSkipped
                . '. Tidak cocok master: guru ' . $this->skippedTeacher
                . ', mapel ' . $this->skippedSubject
                . ', kelas ' . $this->skippedClassroom
                . ', relasi guru pengampu ' . $this->skippedTeacherSubject . '.';
        }

        return 'Import jadwal selesai (jadwal harian). Baris terbaca: ' . $this->rowsRead
            . ', jadwal tersimpan: ' . $this->recordsSynced
            . ', baris dilewati: ' . $this->rowsSkipped
            . '. Tidak cocok master: guru ' . $this->skippedTeacher
            . ', mapel ' . $this->skippedSubject
            . ', kelas ' . $this->skippedClassroom
            . ', relasi guru pengampu ' . $this->skippedTeacherSubject . '.';
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
                'teacher_subject_id' => $this->cell($row, $indexes['teacher_subject_id'] ?? null),
                'hari' => trim((string) $this->cell($row, $indexes['hari'] ?? null)),
                'jam_mulai' => substr(trim((string) $this->cell($row, $indexes['jam_mulai'] ?? null)), 0, 5),
                'jam_selesai' => substr(trim((string) $this->cell($row, $indexes['jam_selesai'] ?? null)), 0, 5),
                'ruangan' => trim((string) $this->cell($row, $indexes['ruangan'] ?? null)),
            ];

            $this->rowsRead++;

            $validator = Validator::make($data, [
                'teacher_subject_id' => 'required|integer',
                'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                'ruangan' => 'nullable|max:100',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': ' . $validator->errors()->first(),
                ]);
            }

            $teacherSubject = TeacherSubject::find($data['teacher_subject_id']);
            if (!$teacherSubject) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . $excelRow . ': teacher_subject_id tidak ditemukan (' . $data['teacher_subject_id'] . ').',
                ]);
            }

            Schedule::updateOrCreate(
                [
                    'teacher_subject_id' => $data['teacher_subject_id'],
                    'hari' => $data['hari'],
                    'jam_mulai' => $data['jam_mulai'],
                    'jam_selesai' => $data['jam_selesai'],
                ],
                [
                    'ruangan' => $data['ruangan'] !== '' ? $data['ruangan'] : null,
                ]
            );

            $this->recordsSynced++;
        }
    }

    private function importJadwalHarianFormat(Collection $rows, int $headerRowIndex, array $header): void
    {
        $this->detectedFormat = 'jadwal_harian';

        $teacherNameIndex = $this->findHeaderIndex($header, ['NAMA']);
        $nipIndex = $this->findHeaderIndex($header, ['NIP/NIPPPK', 'NIP']);
        $subjectFullIndex = $this->findHeaderIndex($header, ['MATA PELAJARAN YANG DIAMPU', 'MATA PELAJARAN YANG DI AMPU']);
        $subjectShortIndex = $this->findHeaderIndex($header, ['MAPEL YANG DIAMPU', 'MAPEL YANG DI AMPU', 'MAPEL YG DIAMPU']);
        $slotColumns = $this->extractSlotColumns($header);

        if (($teacherNameIndex === null && $nipIndex === null) || ($subjectFullIndex === null && $subjectShortIndex === null) || empty($slotColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Format jadwal harian tidak valid. Pastikan kolom NAMA/NIP, MAPEL, dan kolom slot SEN01-JUM11 tersedia.',
            ]);
        }

        foreach ($rows->slice($headerRowIndex + 1)->values() as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $teacherName = trim((string) $this->cell($row, $teacherNameIndex));
            $nip = $this->sanitizeNip($this->cell($row, $nipIndex));
            $subjectFull = trim((string) $this->cell($row, $subjectFullIndex));
            $subjectShort = trim((string) $this->cell($row, $subjectShortIndex));

            if (($teacherName === '' && $nip === '') || ($subjectFull === '' && $subjectShort === '')) {
                continue;
            }

            $this->rowsRead++;

            $teacher = $this->resolveTeacher($nip, $teacherName);
            if (!$teacher) {
                $this->rowsSkipped++;
                $this->skippedTeacher++;
                continue;
            }

            $subject = $this->resolveSubject($subjectShort, $subjectFull);
            if (!$subject) {
                $this->rowsSkipped++;
                $this->skippedSubject++;
                continue;
            }

            $assignments = [];
            foreach ($slotColumns as $slot) {
                $cellValue = trim((string) $this->cell($row, $slot['index']));

                if (!$this->looksLikeClassLabel($cellValue)) {
                    continue;
                }

                $classroom = $this->resolveClassroom($cellValue);
                if (!$classroom) {
                    $this->skippedClassroom++;
                    continue;
                }

                $teacherSubjectId = $this->resolveTeacherSubjectId($teacher->id, $subject->id, $classroom->id);
                if (!$teacherSubjectId) {
                    $this->skippedTeacherSubject++;
                    continue;
                }

                $assignments[$slot['day']][$slot['period']] = $teacherSubjectId;
            }

            if (empty($assignments)) {
                $this->rowsSkipped++;
                continue;
            }

            $this->storeScheduleAssignments($assignments);
        }
    }

    private function importHariWaktuFormat(Collection $rows, int $headerRowIndex, array $header): void
    {
        $this->detectedFormat = 'hari_waktu';

        $dayIndex = $this->findHeaderIndex($header, ['HARI']);
        $hourIndex = $this->findHeaderIndex($header, ['JAM KE']);
        $timeIndex = $this->findHeaderIndex($header, ['WAKTU']);
        $classColumns = $this->extractHariWaktuClassroomColumns($header);

        if ($dayIndex === null || $hourIndex === null || $timeIndex === null || empty($classColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Format jadwal hari dan waktu tidak valid. Pastikan kolom HARI, JAM KE, WAKTU, dan kolom kelas tersedia.',
            ]);
        }

        $codeClassMap = $this->buildTeacherSubjectCodeClassMap();
        if (empty($codeClassMap)) {
            throw ValidationException::withMessages([
                'file' => 'Mapping kode guru pengampu tidak ditemukan. Pastikan file referensi jadwal.xlsx tersedia untuk pemetaan kode.',
            ]);
        }

        $currentDay = null;

        foreach ($rows->slice($headerRowIndex + 1)->values() as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $dayRaw = trim((string) $this->cell($row, $dayIndex));
            if ($dayRaw !== '') {
                $normalizedDay = $this->normalizeDayName($dayRaw);
                if ($normalizedDay !== null) {
                    $currentDay = $normalizedDay;
                }
            }

            $jamKe = trim((string) $this->cell($row, $hourIndex));
            if (preg_match('/^\d+$/', $jamKe) !== 1) {
                continue;
            }

            $timeRange = $this->parseTimeRange((string) $this->cell($row, $timeIndex));
            if ($currentDay === null || $timeRange === null) {
                continue;
            }

            $this->rowsRead++;
            $assignedInRow = 0;

            foreach ($classColumns as $column) {
                $cellValue = trim((string) $this->cell($row, $column['index']));
                if (!$this->looksLikeTeacherSubjectCode($cellValue)) {
                    continue;
                }

                $code = $this->normalizeTeacherSubjectCode($cellValue);
                if ($code === '') {
                    continue;
                }

                $mapKey = $code . '|' . $column['classroom_id'];
                $teacherSubjectId = $codeClassMap[$mapKey] ?? null;
                if (!$teacherSubjectId) {
                    $this->skippedTeacherSubject++;
                    continue;
                }

                Schedule::updateOrCreate(
                    [
                        'teacher_subject_id' => $teacherSubjectId,
                        'hari' => $currentDay,
                        'jam_mulai' => $timeRange['start'],
                        'jam_selesai' => $timeRange['end'],
                    ],
                    [
                        'ruangan' => null,
                    ]
                );

                $this->recordsSynced++;
                $assignedInRow++;
            }

            if ($assignedInRow === 0) {
                $this->rowsSkipped++;
            }
        }
    }

    private function storeScheduleAssignments(array $assignmentsByDay): void
    {
        foreach ($assignmentsByDay as $day => $periodTeacherSubjects) {
            ksort($periodTeacherSubjects);
            $periods = array_keys($periodTeacherSubjects);
            $count = count($periods);

            $i = 0;
            while ($i < $count) {
                $startPeriod = (int) $periods[$i];
                $endPeriod = $startPeriod;
                $teacherSubjectId = (int) $periodTeacherSubjects[$startPeriod];

                while (($i + 1) < $count) {
                    $current = (int) $periods[$i];
                    $next = (int) $periods[$i + 1];
                    $nextTeacherSubjectId = (int) $periodTeacherSubjects[$next];

                    if ($next === ($current + 1) && $nextTeacherSubjectId === $teacherSubjectId) {
                        $endPeriod = $next;
                        $i++;
                        continue;
                    }

                    break;
                }

                $startTime = self::PERIOD_TIME_MAP[$startPeriod]['start'] ?? null;
                $endTime = self::PERIOD_TIME_MAP[$endPeriod]['end'] ?? null;

                if ($startTime !== null && $endTime !== null) {
                    Schedule::updateOrCreate(
                        [
                            'teacher_subject_id' => $teacherSubjectId,
                            'hari' => $day,
                            'jam_mulai' => $startTime,
                            'jam_selesai' => $endTime,
                        ],
                        [
                            'ruangan' => null,
                        ]
                    );

                    $this->recordsSynced++;
                }

                $i++;
            }
        }
    }

    private function isTemplateHeader(array $header): bool
    {
        $map = $this->buildHeaderIndexMap($header);

        return isset($map['teacher_subject_id'], $map['hari'], $map['jam_mulai'], $map['jam_selesai']);
    }

    private function isJadwalHarianHeader(array $header): bool
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

        return $hasIdentity && $hasSubject && !empty($this->extractSlotColumns($header));
    }

    private function isHariWaktuHeader(array $header): bool
    {
        $dayIndex = $this->findHeaderIndex($header, ['HARI']);
        $hourIndex = $this->findHeaderIndex($header, ['JAM KE']);
        $timeIndex = $this->findHeaderIndex($header, ['WAKTU']);

        return $dayIndex !== null
            && $hourIndex !== null
            && $timeIndex !== null
            && !empty($this->extractHariWaktuClassroomColumns($header));
    }

    private function extractSlotColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $value) {
            $label = Str::upper(trim((string) $value));
            if ($label === '') {
                continue;
            }

            if (preg_match('/^(SEN|SEL|RAB|KAM|JUM)(\d{2})$/', $label, $matches) !== 1) {
                continue;
            }

            $prefix = $matches[1];
            $period = (int) $matches[2];

            if (!isset(self::DAY_PREFIX_MAP[$prefix]) || !isset(self::PERIOD_TIME_MAP[$period])) {
                continue;
            }

            $columns[] = [
                'index' => $index,
                'day' => self::DAY_PREFIX_MAP[$prefix],
                'period' => $period,
            ];
        }

        return $columns;
    }

    private function extractHariWaktuClassroomColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $value) {
            $label = trim((string) $value);
            if (!$this->looksLikeClassLabel($label)) {
                continue;
            }

            $classroom = $this->resolveClassroom($label);
            if (!$classroom) {
                continue;
            }

            $columns[] = [
                'index' => $index,
                'classroom_id' => $classroom->id,
            ];
        }

        return $columns;
    }

    private function buildTeacherSubjectCodeClassMap(): array
    {
        if ($this->codeClassTeacherSubjectMap !== null) {
            return $this->codeClassTeacherSubjectMap;
        }

        $this->codeClassTeacherSubjectMap = [];
        $referencePath = public_path('jadwal.xlsx');

        if (!is_file($referencePath)) {
            return $this->codeClassTeacherSubjectMap;
        }

        $sheet = IOFactory::load($referencePath)->getActiveSheet();
        $rows = collect($sheet->toArray(null, true, true, false))
            ->map(fn($row) => $this->normalizeRow($row))
            ->values();

        $headerIndex = null;
        $header = [];

        foreach ($rows as $index => $row) {
            if (
                $this->findHeaderIndex($row, ['KODE']) !== null
                && $this->findHeaderIndex($row, ['NAMA']) !== null
                && $this->findHeaderIndex($row, ['MATA PELAJARAN YANG DIAMPU', 'MAPEL YANG DIAMPU']) !== null
            ) {
                $headerIndex = $index;
                $header = $row;
                break;
            }
        }

        if ($headerIndex === null) {
            return $this->codeClassTeacherSubjectMap;
        }

        $codeIndex = $this->findHeaderIndex($header, ['KODE']);
        $teacherNameIndex = $this->findHeaderIndex($header, ['NAMA']);
        $nipIndex = $this->findHeaderIndex($header, ['NIP/NIPPPK', 'NIP']);
        $subjectFullIndex = $this->findHeaderIndex($header, ['MATA PELAJARAN YANG DIAMPU', 'MATA PELAJARAN YANG DI AMPU']);
        $subjectShortIndex = $this->findHeaderIndex($header, ['MAPEL YANG DIAMPU', 'MAPEL YANG DI AMPU', 'MAPEL YG DIAMPU']);

        if ($codeIndex === null || ($teacherNameIndex === null && $nipIndex === null) || ($subjectFullIndex === null && $subjectShortIndex === null)) {
            return $this->codeClassTeacherSubjectMap;
        }

        $classColumns = $this->extractHariWaktuClassroomColumns($header);

        foreach ($rows->slice($headerIndex + 1)->values() as $row) {
            $code = $this->normalizeTeacherSubjectCode((string) $this->cell($row, $codeIndex));
            if ($code === '') {
                continue;
            }

            $teacherName = trim((string) $this->cell($row, $teacherNameIndex));
            $nip = $this->sanitizeNip($this->cell($row, $nipIndex));
            $subjectFull = trim((string) $this->cell($row, $subjectFullIndex));
            $subjectShort = trim((string) $this->cell($row, $subjectShortIndex));

            if (($teacherName === '' && $nip === '') || ($subjectFull === '' && $subjectShort === '')) {
                continue;
            }

            $teacher = $this->resolveTeacher($nip, $teacherName);
            $subject = $this->resolveSubject($subjectShort, $subjectFull);
            if (!$teacher || !$subject) {
                continue;
            }

            foreach ($classColumns as $column) {
                $loadValue = $this->cell($row, $column['index']);
                $teacherSubjectId = $this->resolveTeacherSubjectId($teacher->id, $subject->id, $column['classroom_id']);
                if (!$teacherSubjectId) {
                    continue;
                }

                $mapKey = $code . '|' . $column['classroom_id'];

                if ($this->hasReferenceTeachingLoad($loadValue) || !isset($this->codeClassTeacherSubjectMap[$mapKey])) {
                    $this->codeClassTeacherSubjectMap[$mapKey] = $teacherSubjectId;
                }
            }
        }

        return $this->codeClassTeacherSubjectMap;
    }

    private function hasReferenceTeachingLoad($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return ((float) $value) > 0;
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '-' || strtoupper($value) === '0') {
            return false;
        }

        if (is_numeric($value)) {
            return ((float) $value) > 0;
        }

        return false;
    }

    private function looksLikeTeacherSubjectCode(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return false;
        }

        $normalized = $this->normalizeLabel($value);
        if ($normalized === '' || in_array($normalized, ['upacara', 'pembiasaan', 'rehat1', 'rehat2', 'istirahat'], true)) {
            return false;
        }

        $code = $this->normalizeTeacherSubjectCode($value);

        return preg_match('/^[A-Z]{1,5}[0-9]{2}$/', $code) === 1;
    }

    private function normalizeTeacherSubjectCode(string $value): string
    {
        $value = Str::upper(trim($value));

        return (string) preg_replace('/[^A-Z0-9]+/', '', $value);
    }

    private function parseTimeRange(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[\.:](\d{2})\s*-\s*(\d{1,2})[\.:](\d{2})$/', $value, $matches) !== 1) {
            return null;
        }

        $start = sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        $end = sprintf('%02d:%02d', (int) $matches[3], (int) $matches[4]);

        if ($end <= $start) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function normalizeDayName(string $value): ?string
    {
        $normalized = $this->normalizeLabel($value);

        return match ($normalized) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            default => null,
        };
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

    private function resolveTeacher(string $nip, string $name): ?Teacher
    {
        $teachers = $this->getTeachers();

        if ($nip !== '') {
            $teacher = $teachers->first(function (Teacher $item) use ($nip) {
                return $this->sanitizeNip((string) $item->nip) === $nip;
            });

            if ($teacher) {
                return $teacher;
            }
        }

        if ($name !== '') {
            $normalizedName = Str::lower(trim($name));

            $teacher = $teachers->first(function (Teacher $item) use ($normalizedName) {
                return Str::lower(trim((string) $item->nama_lengkap)) === $normalizedName;
            });

            if ($teacher) {
                return $teacher;
            }

            return $teachers->first(function (Teacher $item) use ($normalizedName) {
                return $this->normalizeLabel((string) $item->nama_lengkap) === $this->normalizeLabel($normalizedName);
            });
        }

        return null;
    }

    private function resolveSubject(string $shortLabel, string $fullLabel): ?Subject
    {
        $subjects = $this->getSubjects();
        $shortLabel = trim($shortLabel);
        $fullLabel = trim($fullLabel);

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

        $shortNormalized = $this->normalizeLabel($shortLabel);
        if ($shortNormalized !== '') {
            $subject = $subjects->first(function (Subject $item) use ($shortNormalized) {
                return $shortNormalized === $this->normalizeLabel((string) $item->kode_mapel)
                    || $shortNormalized === $this->normalizeLabel((string) $item->nama_mapel);
            });

            if ($subject) {
                return $subject;
            }
        }

        $fullNormalized = $this->normalizeLabel($fullLabel);
        if ($fullNormalized !== '') {
            return $subjects->first(function (Subject $item) use ($fullNormalized) {
                return $fullNormalized === $this->normalizeLabel((string) $item->nama_mapel)
                    || $fullNormalized === $this->normalizeLabel((string) $item->kode_mapel);
            });
        }

        return null;
    }

    private function resolveClassroom(string $label): ?Classroom
    {
        $normalized = $this->normalizeClassLabel($label);
        if ($normalized === '') {
            return null;
        }

        $normalizedAlias = $this->swapMajorAlias($normalized);

        foreach ($this->getClassrooms() as $classroom) {
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

    private function resolveTeacherSubjectId(int $teacherId, int $subjectId, int $classroomId): ?int
    {
        $activeKey = implode('|', [$teacherId, $subjectId, $classroomId, (int) $this->activeAcademicYearId]);
        if (array_key_exists($activeKey, $this->teacherSubjectCache)) {
            return $this->teacherSubjectCache[$activeKey];
        }

        $teacherSubject = null;

        if ($this->activeAcademicYearId !== null) {
            $teacherSubject = TeacherSubject::query()
                ->where('teacher_id', $teacherId)
                ->where('subject_id', $subjectId)
                ->where('classroom_id', $classroomId)
                ->where('academic_year_id', $this->activeAcademicYearId)
                ->first();
        }

        if (!$teacherSubject) {
            $fallbackKey = implode('|', [$teacherId, $subjectId, $classroomId, 0]);
            if (array_key_exists($fallbackKey, $this->teacherSubjectCache)) {
                return $this->teacherSubjectCache[$fallbackKey];
            }

            $teacherSubject = TeacherSubject::query()
                ->where('teacher_id', $teacherId)
                ->where('subject_id', $subjectId)
                ->where('classroom_id', $classroomId)
                ->orderByDesc('academic_year_id')
                ->orderByDesc('id')
                ->first();

            $this->teacherSubjectCache[$fallbackKey] = $teacherSubject?->id;
        }

        $this->teacherSubjectCache[$activeKey] = $teacherSubject?->id;

        return $teacherSubject?->id;
    }

    private function looksLikeClassLabel(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || $value === '0') {
            return false;
        }

        $normalized = $this->normalizeLabel($value);
        if ($normalized === '' || in_array($normalized, ['upacara', 'pembiasaan', 'istirahat', 'cek', 'ok'], true)) {
            return false;
        }

        return preg_match('/^(X|XI|XII)\s+[A-Z0-9]+(?:\s+\d+)?$/i', $value) === 1;
    }

    private function normalizeClassLabel(string $value): string
    {
        $value = Str::upper(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return str_replace(['-', '.'], ' ', trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    private function swapMajorAlias(string $label): string
    {
        $parts = preg_split('/\s+/', trim($label)) ?: [];
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

    private function sanitizeNip($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return (string) number_format((float) $value, 0, '', '');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[0-9]+(\.[0-9]+)?E\+[0-9]+$/i', $value) === 1) {
            return (string) number_format((float) $value, 0, '', '');
        }

        return preg_replace('/\s+/', '', $value) ?? '';
    }

    private function normalizeLabel(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
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

    private function isRowEmpty(array $row): bool
    {
        return collect($row)->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
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
            $this->teachersCache = Teacher::query()->select(['id', 'nip', 'nama_lengkap'])->get();
        }

        return $this->teachersCache;
    }

    private function getSubjects(): Collection
    {
        if ($this->subjectsCache === null) {
            $this->subjectsCache = Subject::query()->select(['id', 'kode_mapel', 'nama_mapel'])->get();
        }

        return $this->subjectsCache;
    }

    private function getClassrooms(): Collection
    {
        if ($this->classroomsCache === null) {
            $this->classroomsCache = Classroom::query()->select(['id', 'kode_kelas', 'nama_kelas'])->get();
        }

        return $this->classroomsCache;
    }

    private function resetSummary(): void
    {
        $this->rowsRead = 0;
        $this->rowsSkipped = 0;
        $this->recordsSynced = 0;
        $this->skippedTeacher = 0;
        $this->skippedSubject = 0;
        $this->skippedClassroom = 0;
        $this->skippedTeacherSubject = 0;
        $this->detectedFormat = 'unknown';
        $this->activeAcademicYearId = null;
        $this->teacherSubjectCache = [];
        $this->codeClassTeacherSubjectMap = null;
    }
}
