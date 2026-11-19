<?php

namespace App\Services\Assessment;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DailyAssessmentScore;
use App\Models\ExamScore;
use App\Models\FinalGrade;
use App\Models\ReportCard;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportCardService
{
    public const SUBJECT_GROUPS = [
        'Umum' => 'A. Muatan Umum',
        'Kejuruan' => 'B. Muatan Kejuruan',
        'Muatan Lokal' => 'C. Muatan Lokal',
    ];

    public function __construct(private readonly ProcessScoreCalculator $calculator)
    {
    }

    public function settings(): array
    {
        $setting = SchoolSetting::query()->first();

        $weights = [
            'daily_weight' => (int) ($setting->report_daily_weight ?? 50),
            'uts_weight' => (int) ($setting->report_uts_weight ?? 25),
            'uas_weight' => (int) ($setting->report_uas_weight ?? 25),
        ];

        if (array_sum($weights) <= 0) {
            $weights = ['daily_weight' => 50, 'uts_weight' => 25, 'uas_weight' => 25];
        }

        return $weights + [
            'kkm' => max(0, min(100, (int) ($setting->report_kkm ?? 75))),
            'city' => $setting->report_city ?? null,
            'principal_name' => $setting->principal_name ?? null,
            'principal_nip' => $setting->principal_nip ?? null,
        ];
    }

    public static function compose(?float $daily, ?float $uts, ?float $uas, array $settings): array
    {
        $components = [
            'Harian' => [$daily, (int) $settings['daily_weight']],
            'UTS' => [$uts, (int) $settings['uts_weight']],
            'UAS' => [$uas, (int) $settings['uas_weight']],
        ];

        $sum = 0.0;
        $weightTotal = 0;
        $missing = [];

        foreach ($components as $label => [$value, $weight]) {
            if ($weight <= 0) {
                continue;
            }

            if ($value === null) {
                $missing[] = $label;
                continue;
            }

            $sum += $value * $weight;
            $weightTotal += $weight;
        }

        return [
            'score' => $weightTotal > 0 ? round($sum / $weightTotal, 2) : null,
            'missing' => $missing,
        ];
    }

    public static function predicate(?float $score, int $kkm): ?string
    {
        if ($score === null) {
            return null;
        }

        $range = (100 - $kkm) / 3;

        return match (true) {
            $score >= $kkm + 2 * $range => 'A',
            $score >= $kkm + $range => 'B',
            $score >= $kkm => 'C',
            default => 'D',
        };
    }

    public function forClassroom(Classroom $classroom, AcademicYear $year, ?array $onlyStudentIds = null): array
    {
        $classroom->loadMissing('major');
        $settings = $this->settings();

        $assignments = TeacherSubject::query()
            ->with(['subject:id,kode_mapel,nama_mapel,kategori', 'teacher:id,nama_lengkap'])
            ->where('classroom_id', $classroom->id)
            ->where('academic_year_id', $year->id)
            ->get();

        $students = $this->studentsFor($classroom, $assignments, $onlyStudentIds);
        $studentIds = $students->pluck('id')->all();

        $process = $this->calculator->calculate($assignments, $studentIds);
        $exams = ExamScore::query()
            ->whereIn('teacher_subject_id', $assignments->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy(fn (ExamScore $score) => $score->teacher_subject_id . ':' . $score->student_id);
        $reportCards = ReportCard::query()
            ->where('academic_year_id', $year->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');
        $absences = $this->absenceDays($year, $studentIds);
        $subjects = $this->subjectsFor($assignments);

        $entries = $students->map(function (Student $student) use ($subjects, $process, $exams, $reportCards, $absences, $settings) {
            $rows = $subjects->map(function (array $subject) use ($student, $process, $exams, $settings) {
                return $this->subjectRow($subject, $student, $process, $exams, $settings);
            })->values();

            $graded = $rows->whereNotNull('final_rounded');
            $computedAbsence = $absences[$student->id] ?? ['sakit' => 0, 'izin' => 0, 'alpa' => 0];
            $reportCard = $reportCards->get($student->id);
            $total = (int) $graded->sum('final_rounded');

            return [
                'student' => $student,
                'rows' => $rows,
                'groups' => $rows->groupBy('group_label'),
                'total' => $total,
                'average' => $graded->isNotEmpty() ? round($total / $graded->count(), 2) : null,
                'graded_subjects' => $graded->count(),
                'incomplete_subjects' => $rows->filter(fn (array $row) => $row['missing'] !== [])->count(),
                'attendance' => [
                    'sakit' => $reportCard?->sick_days ?? $computedAbsence['sakit'],
                    'izin' => $reportCard?->permit_days ?? $computedAbsence['izin'],
                    'alpa' => $reportCard?->absent_days ?? $computedAbsence['alpa'],
                    'computed' => $computedAbsence,
                    'is_override' => $reportCard !== null
                        && ($reportCard->sick_days !== null || $reportCard->permit_days !== null || $reportCard->absent_days !== null),
                ],
                'report_card' => $reportCard,
                'rank' => null,
            ];
        })->values();

        $entries = $this->rank($entries);

        return [
            'classroom' => $classroom,
            'year' => $year,
            'settings' => $settings,
            'subjects' => $subjects,
            'entries' => $entries,
            'homeroom_teacher' => Teacher::query()->where('wali_classroom_id', $classroom->id)
                ->where('is_wali_kelas', true)->orderBy('id')->first(),
            'principal' => $this->principal($settings),
            'semester_label' => $this->semesterLabel($classroom, $year),
        ];
    }

    private function studentsFor(Classroom $classroom, Collection $assignments, ?array $onlyStudentIds): Collection
    {
        $assignmentIds = $assignments->pluck('id')->all();
        $gradedStudentIds = collect();

        if ($assignmentIds !== []) {
            $gradedStudentIds = ExamScore::query()->whereIn('teacher_subject_id', $assignmentIds)->pluck('student_id')
                ->merge(FinalGrade::query()->whereIn('teacher_subject_id', $assignmentIds)->pluck('student_id'))
                ->merge(DailyAssessmentScore::query()
                    ->whereHas('assessment', fn ($query) => $query->whereIn('teacher_subject_id', $assignmentIds))
                    ->whereNotNull('score')
                    ->pluck('student_id'))
                ->unique();
        }

        return Student::query()
            ->where(function ($query) use ($classroom, $gradedStudentIds) {
                $query->where('classroom_id', $classroom->id);

                if ($gradedStudentIds->isNotEmpty()) {
                    $query->orWhereIn('id', $gradedStudentIds->all());
                }
            })
            ->when($onlyStudentIds !== null, fn ($query) => $query->whereIn('id', $onlyStudentIds))
            ->orderBy('nama_lengkap')
            ->get();
    }

    private function subjectsFor(Collection $assignments): Collection
    {
        $groupOrder = array_flip(array_keys(self::SUBJECT_GROUPS));

        return $assignments
            ->groupBy('subject_id')
            ->map(function (Collection $items) {
                $subject = $items->first()->subject;
                $group = $subject?->kategori;
                $group = array_key_exists((string) $group, self::SUBJECT_GROUPS) ? $group : 'Umum';

                return [
                    'subject_id' => (int) $items->first()->subject_id,
                    'name' => $subject?->nama_mapel ?? 'Mata Pelajaran',
                    'code' => $subject?->kode_mapel,
                    'group' => $group,
                    'group_label' => self::SUBJECT_GROUPS[$group],
                    'teachers' => $items->pluck('teacher.nama_lengkap')->filter()->unique()->implode(', '),
                    'assignment_ids' => $items->pluck('id')->all(),
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => $groupOrder[$a['group']] <=> $groupOrder[$b['group']],
                fn (array $a, array $b) => strnatcasecmp((string) ($a['code'] ?? $a['name']), (string) ($b['code'] ?? $b['name'])),
            ])
            ->values();
    }

    private function subjectRow(array $subject, Student $student, array $process, Collection $exams, array $settings): array
    {
        $daily = [];
        $uts = [];
        $uas = [];

        foreach ($subject['assignment_ids'] as $assignmentId) {
            $key = $assignmentId . ':' . $student->id;

            if (($value = $process[$key]['final'] ?? null) !== null) {
                $daily[] = (float) $value;
            }

            $exam = $exams->get($key);

            if ($exam?->uts_score !== null) {
                $uts[] = (float) $exam->uts_score;
            }

            if ($exam?->uas_score !== null) {
                $uas[] = (float) $exam->uas_score;
            }
        }

        $average = fn (array $values) => $values === [] ? null : round(array_sum($values) / count($values), 2);
        $dailyScore = $average($daily);
        $utsScore = $average($uts);
        $uasScore = $average($uas);
        $composed = self::compose($dailyScore, $utsScore, $uasScore, $settings);
        $finalRounded = $composed['score'] === null ? null : (int) round($composed['score']);

        return [
            'subject_id' => $subject['subject_id'],
            'subject' => $subject['name'],
            'teachers' => $subject['teachers'],
            'group' => $subject['group'],
            'group_label' => $subject['group_label'],
            'daily' => $dailyScore,
            'uts' => $utsScore,
            'uas' => $uasScore,
            'final' => $composed['score'],
            'final_rounded' => $finalRounded,
            'predicate' => self::predicate($finalRounded === null ? null : (float) $finalRounded, $settings['kkm']),
            'passed' => $finalRounded === null ? null : $finalRounded >= $settings['kkm'],
            'missing' => $composed['missing'],
        ];
    }

    private function absenceDays(AcademicYear $year, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $rows = DB::table('attendance_details')
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->where('teacher_attendances.academic_year_id', $year->id)
            ->whereIn('attendance_details.student_id', $studentIds)
            ->whereIn('attendance_details.status', ['Sakit', 'Izin', 'Alpha', 'Alpa'])
            ->select('attendance_details.student_id', 'attendance_details.status')
            ->selectRaw('COUNT(DISTINCT teacher_attendances.tanggal) AS days')
            ->groupBy('attendance_details.student_id', 'attendance_details.status')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $bucket = match ($row->status) {
                'Sakit' => 'sakit',
                'Izin' => 'izin',
                default => 'alpa',
            };

            $result[$row->student_id] ??= ['sakit' => 0, 'izin' => 0, 'alpa' => 0];
            $result[$row->student_id][$bucket] += (int) $row->days;
        }

        return $result;
    }

    private function rank(Collection $entries): Collection
    {
        $averages = $entries->pluck('average')->filter(fn ($value) => $value !== null)->sortDesc()->values();

        return $entries->map(function (array $entry) use ($averages) {
            if ($entry['average'] !== null) {
                $entry['rank'] = $averages->search(fn ($value) => abs($value - $entry['average']) < 0.005) + 1;
            }

            return $entry;
        });
    }

    private function principal(array $settings): array
    {
        if (!empty($settings['principal_name'])) {
            return ['name' => $settings['principal_name'], 'nip' => $settings['principal_nip']];
        }

        $principal = Teacher::query()->where('jabatan', Teacher::JABATAN_KEPALA_SEKOLAH)->orderBy('id')->first();

        return ['name' => $principal?->nama_lengkap, 'nip' => $principal?->nip];
    }

    private function semesterLabel(Classroom $classroom, AcademicYear $year): string
    {
        $semester = (string) $year->semester;
        $level = ['X' => 1, 'XI' => 2, 'XII' => 3][strtoupper((string) $classroom->tingkat)] ?? null;

        if ($level === null) {
            return $semester;
        }

        $number = ($level - 1) * 2 + ($semester === 'Genap' ? 2 : 1);

        return "{$number} ({$semester})";
    }
}
