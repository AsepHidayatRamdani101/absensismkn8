<?php

namespace App\Repositories\Dashboard;

use App\Models\AcademicYear;
use App\Models\AttendanceDetail;
use App\Models\CharacterDimension;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\PancawaluyaTransactionHistory;
use App\Models\RewardTransaction;
use App\Models\Student;
use App\Models\StudentCharacterScore;
use App\Models\StudentCharacterStatistic;
use App\Models\StudentWarningLetter;
use App\Models\Teacher;
use App\Models\User;
use App\Models\ViolationTransaction;
use App\Repositories\Contracts\Dashboard\DashboardAnalyticsRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardAnalyticsRepository implements DashboardAnalyticsRepositoryInterface
{
    /**
     * Cache table existence checks within a request.
     *
     * @var array<string, bool>
     */
    private array $tableExistsCache = [];

    public function buildScope(User $user): array
    {
        $teacher = Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();

        $student = Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return ['role' => 'admin', 'teacher_id' => null, 'classroom_id' => null, 'student_id' => null, 'user_id' => (int) $user->id];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('kesiswaan')) {
            return ['role' => 'kesiswaan', 'teacher_id' => null, 'classroom_id' => null, 'student_id' => null, 'user_id' => (int) $user->id];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('bk')) {
            return ['role' => 'bk', 'teacher_id' => null, 'classroom_id' => null, 'student_id' => null, 'user_id' => (int) $user->id];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('wali_kelas')) {
            return [
                'role' => 'wali_kelas',
                'teacher_id' => $teacher?->id,
                'classroom_id' => $teacher?->wali_classroom_id,
                'student_id' => null,
                'user_id' => (int) $user->id,
            ];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('guru')) {
            return ['role' => 'guru', 'teacher_id' => $teacher?->id, 'classroom_id' => null, 'student_id' => null, 'user_id' => (int) $user->id];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('siswa')) {
            return ['role' => 'siswa', 'teacher_id' => null, 'classroom_id' => $student?->classroom_id, 'student_id' => $student?->id, 'user_id' => (int) $user->id];
        }

        return ['role' => 'unknown', 'teacher_id' => null, 'classroom_id' => null, 'student_id' => null, 'user_id' => (int) $user->id];
    }

    public function kpi(array $filters, array $scope): array
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);
        $hasRewardTransactions = $this->tableExists('reward_transactions');
        $hasViolationTransactions = $this->tableExists('violation_transactions');
        $hasStudentWarningLetters = $this->tableExists('student_warning_letters');
        $hasCharacterStatistics = $this->tableExists('student_character_statistics');

        $rewardCount = $hasRewardTransactions
            ? $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope)->count()
            : 0;
        $violationCount = $hasViolationTransactions
            ? $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope)->count()
            : 0;

        $spCounts = $hasStudentWarningLetters
            ? StudentWarningLetter::query()
            ->where('status', 'active')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']))
            ->selectRaw('sp_level, COUNT(*) as total')
            ->groupBy('sp_level')
            ->pluck('total', 'sp_level')
            : collect();

        $characterAverage = $hasCharacterStatistics
            ? (StudentCharacterStatistic::query()
                ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
                ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
                ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']))
                ->avg('character_score_total') ?? 0)
            : 0;

        $pendingValidation = ($hasRewardTransactions
            ? $this->applyTransactionFilters(RewardTransaction::query()->where('status', 'pending'), $filters, $scope)->count()
            : 0)
            + ($hasViolationTransactions
                ? $this->applyTransactionFilters(ViolationTransaction::query()->where('status', 'pending'), $filters, $scope)->count()
                : 0);

        return [
            'total_students' => count($studentIds),
            'total_teachers' => $this->scopedTeacherCount($filters, $scope),
            'total_rewards' => $rewardCount,
            'total_violations' => $violationCount,
            'total_sp1' => (int) ($spCounts['SP1'] ?? 0),
            'total_sp2' => (int) ($spCounts['SP2'] ?? 0),
            'total_sp3' => (int) ($spCounts['SP3'] ?? 0),
            'character_average' => round((float) $characterAverage, 2),
            'pending_validation' => $pendingValidation,
        ];
    }

    public function trends(array $filters, array $scope): array
    {
        $from = $filters['date_from'] !== '' ? Carbon::parse($filters['date_from']) : now()->subMonths(5)->startOfMonth();
        $to = $filters['date_to'] !== '' ? Carbon::parse($filters['date_to']) : now()->endOfMonth();

        $labels = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($to)) {
            $labels[] = $cursor->format('M Y');
            $cursor->addMonth();
        }

        $rewardRows = $this->tableExists('reward_transactions')
            ? $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope)
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as ym, COUNT(*) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym')
            : collect();

        $violationRows = $this->tableExists('violation_transactions')
            ? $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope)
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as ym, COUNT(*) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym')
            : collect();

        $characterRows = $this->tableExists('student_character_statistics')
            ? StudentCharacterStatistic::query()
            ->when(!empty($this->scopedStudentIds($filters, $scope)), fn(Builder $q) => $q->whereIn('student_id', $this->scopedStudentIds($filters, $scope)))
            ->selectRaw('DATE_FORMAT(last_calculated_at, "%Y-%m") as ym, AVG(character_score_total) as avg_score')
            ->whereNotNull('last_calculated_at')
            ->whereBetween('last_calculated_at', [$from->toDateString(), $to->toDateString()])
            ->groupBy('ym')
            ->pluck('avg_score', 'ym')
            : collect();

        $rewardTrend = [];
        $violationTrend = [];
        $characterGrowth = [];
        foreach ($labels as $label) {
            $ym = Carbon::createFromFormat('M Y', $label)->format('Y-m');
            $rewardTrend[] = (int) ($rewardRows[$ym] ?? 0);
            $violationTrend[] = (int) ($violationRows[$ym] ?? 0);
            $characterGrowth[] = round((float) ($characterRows[$ym] ?? 0), 2);
        }

        $spDistribution = $this->tableExists('student_warning_letters')
            ? StudentWarningLetter::query()
            ->where('status', 'active')
            ->when(!empty($this->scopedStudentIds($filters, $scope)), fn(Builder $q) => $q->whereIn('student_id', $this->scopedStudentIds($filters, $scope)))
            ->selectRaw('sp_level, COUNT(*) as total')
            ->groupBy('sp_level')
            ->pluck('total', 'sp_level')
            : collect();

        return [
            'labels' => $labels,
            'reward_trend' => $rewardTrend,
            'violation_trend' => $violationTrend,
            'character_growth' => $characterGrowth,
            'sp_distribution' => [
                'SP1' => (int) ($spDistribution['SP1'] ?? 0),
                'SP2' => (int) ($spDistribution['SP2'] ?? 0),
                'SP3' => (int) ($spDistribution['SP3'] ?? 0),
            ],
        ];
    }

    public function radar(array $filters, array $scope): array
    {
        if (!$this->tableExists('character_dimensions') || !$this->tableExists('student_character_scores')) {
            return [
                'labels' => [],
                'current' => [],
                'previous' => [],
                'school_average' => [],
                'class_average' => [],
                'department_average' => [],
                'comparisons' => [
                    'student_vs_class' => [],
                    'student_vs_department' => [],
                    'student_vs_school' => [],
                    'class_vs_school' => [],
                    'department_vs_school' => [],
                ],
            ];
        }

        $dimensions = CharacterDimension::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $labels = $dimensions->pluck('name')->values()->all();

        $studentIds = $this->scopedStudentIds($filters, $scope);
        $classStudentIds = $this->classScopedStudentIds($filters, $scope);
        $departmentStudentIds = $this->departmentScopedStudentIds($filters, $scope);

        $currentByDimension = StudentCharacterScore::query()
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->selectRaw('character_dimension_id, AVG(score_total) as avg_score')
            ->groupBy('character_dimension_id')
            ->pluck('avg_score', 'character_dimension_id');

        $schoolByDimension = StudentCharacterScore::query()
            ->selectRaw('character_dimension_id, AVG(score_total) as avg_score')
            ->groupBy('character_dimension_id')
            ->pluck('avg_score', 'character_dimension_id');

        $classByDimension = StudentCharacterScore::query()
            ->when(!empty($classStudentIds), fn(Builder $q) => $q->whereIn('student_id', $classStudentIds))
            ->selectRaw('character_dimension_id, AVG(score_total) as avg_score')
            ->groupBy('character_dimension_id')
            ->pluck('avg_score', 'character_dimension_id');

        $departmentByDimension = StudentCharacterScore::query()
            ->when(!empty($departmentStudentIds), fn(Builder $q) => $q->whereIn('student_id', $departmentStudentIds))
            ->selectRaw('character_dimension_id, AVG(score_total) as avg_score')
            ->groupBy('character_dimension_id')
            ->pluck('avg_score', 'character_dimension_id');

        $previousDateTo = $filters['date_from'] !== '' ? Carbon::parse($filters['date_from'])->subDay() : now()->subMonth();
        $previousDateFrom = $previousDateTo->copy()->subMonths(1);

        $previous = $this->previousDimensionScores($filters, $scope, $previousDateFrom, $previousDateTo);

        $current = [];
        $school = [];
        $class = [];
        $department = [];
        $previousSeries = [];

        foreach ($dimensions as $dimension) {
            $current[] = round((float) ($currentByDimension[$dimension->id] ?? 0), 2);
            $school[] = round((float) ($schoolByDimension[$dimension->id] ?? 0), 2);
            $class[] = round((float) ($classByDimension[$dimension->id] ?? 0), 2);
            $department[] = round((float) ($departmentByDimension[$dimension->id] ?? 0), 2);
            $previousSeries[] = round((float) ($previous[$dimension->name] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previousSeries,
            'school_average' => $school,
            'class_average' => $class,
            'department_average' => $department,
            'comparisons' => [
                'student_vs_class' => $this->differenceSeries($current, $class),
                'student_vs_department' => $this->differenceSeries($current, $department),
                'student_vs_school' => $this->differenceSeries($current, $school),
                'class_vs_school' => $this->differenceSeries($class, $school),
                'department_vs_school' => $this->differenceSeries($department, $school),
            ],
        ];
    }

    public function rankings(array $filters, array $scope): array
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);
        $topLimit = $this->topLimit($filters);

        if (!$this->tableExists('student_character_statistics')) {
            return [
                'best_students' => [],
                'highest_violations' => [],
                'highest_rewards' => [],
                'most_active_teachers' => $this->buildMostActiveTeachers($filters, $scope, $topLimit),
            ];
        }

        $bestStudents = StudentCharacterStatistic::query()
            ->with('student:id,nama_lengkap,nis,classroom_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']))
            ->orderByDesc('character_score_total')
            ->limit($topLimit)
            ->get()
            ->map(fn(StudentCharacterStatistic $row) => [
                'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                'score' => round((float) $row->character_score_total, 2),
                'reward_count' => (int) $row->reward_count,
                'violation_count' => (int) $row->violation_count,
            ])->values()->all();

        $highestViolations = StudentCharacterStatistic::query()
            ->with('student:id,nama_lengkap')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->orderByDesc('violation_count')
            ->limit($topLimit)
            ->get()
            ->map(fn(StudentCharacterStatistic $row) => [
                'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                'total' => (int) $row->violation_count,
            ])->values()->all();

        $highestRewards = StudentCharacterStatistic::query()
            ->with('student:id,nama_lengkap')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->orderByDesc('reward_count')
            ->limit($topLimit)
            ->get()
            ->map(fn(StudentCharacterStatistic $row) => [
                'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                'total' => (int) $row->reward_count,
            ])->values()->all();

        $mostActiveTeachers = $this->buildMostActiveTeachers($filters, $scope, $topLimit);

        return [
            'best_students' => $bestStudents,
            'highest_violations' => $highestViolations,
            'highest_rewards' => $highestRewards,
            'most_active_teachers' => $mostActiveTeachers,
        ];
    }

    public function alerts(array $filters, array $scope): array
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);

        $nearSp = [];
        if ($this->tableExists('student_warning_letters')) {
            $nearSp = StudentWarningLetter::query()
                ->with('student:id,nama_lengkap')
                ->where('status', 'active')
                ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
                ->whereIn('sp_level', ['SP1', 'SP2'])
                ->orderByDesc('violation_weighted_total')
                ->limit(10)
                ->get()
                ->map(fn(StudentWarningLetter $sp) => [
                    'student' => (string) ($sp->student?->nama_lengkap ?? '-'),
                    'sp_level' => (string) $sp->sp_level,
                    'weighted' => round((float) $sp->violation_weighted_total, 2),
                ])->values()->all();
        }

        $lowCharacter = [];
        $withoutReward = [];
        if ($this->tableExists('student_character_statistics')) {
            $lowCharacter = StudentCharacterStatistic::query()
                ->with('student:id,nama_lengkap')
                ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
                ->where('character_score_total', '<', 60)
                ->orderBy('character_score_total')
                ->limit(10)
                ->get()
                ->map(fn(StudentCharacterStatistic $row) => [
                    'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                    'score' => round((float) $row->character_score_total, 2),
                ])->values()->all();

            $withoutReward = StudentCharacterStatistic::query()
                ->with('student:id,nama_lengkap')
                ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
                ->where('reward_count', 0)
                ->orderByDesc('violation_count')
                ->limit(10)
                ->get()
                ->map(fn(StudentCharacterStatistic $row) => [
                    'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                    'violation_count' => (int) $row->violation_count,
                ])->values()->all();
        }

        return [
            'near_sp' => $nearSp,
            'low_character' => $lowCharacter,
            'without_reward' => $withoutReward,
        ];
    }

    public function recommendations(array $filters, array $scope): array
    {
        $alerts = $this->alerts($filters, $scope);

        $items = collect();

        foreach (array_slice($alerts['near_sp'], 0, 5) as $row) {
            $items->push([
                'type' => 'counseling',
                'priority' => 'high',
                'message' => 'Jadwalkan konseling untuk ' . $row['student'] . ' (' . $row['sp_level'] . ').',
            ]);
        }

        foreach (array_slice($alerts['low_character'], 0, 5) as $row) {
            $items->push([
                'type' => 'character_program',
                'priority' => 'medium',
                'message' => 'Rekomendasikan program pembinaan karakter untuk ' . $row['student'] . '.',
            ]);
        }

        foreach (array_slice($alerts['without_reward'], 0, 5) as $row) {
            $items->push([
                'type' => 'reward_opportunity',
                'priority' => 'low',
                'message' => 'Dorong guru memberi penguatan positif untuk ' . $row['student'] . '.',
            ]);
        }

        return $items->take(10)->values()->all();
    }

    public function recentActivities(array $filters, array $scope, int $limit = 20): array
    {
        if (!$this->tableExists('pancawaluya_transaction_histories')) {
            return [];
        }

        $rows = $this->applyHistoryFilters(PancawaluyaTransactionHistory::query()->with(['student:id,nama_lengkap', 'actor:id,name']), $filters, $scope)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $rows->map(function (PancawaluyaTransactionHistory $row): array {
            return [
                'date' => optional($row->transaction_date)->format('Y-m-d'),
                'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                'action' => (string) $row->action,
                'type' => (string) $row->reference_type,
                'status' => (string) ($row->status ?? '-'),
                'source' => (string) ($row->source ?? '-'),
                'actor' => (string) ($row->actor?->name ?? '-'),
            ];
        })->values()->all();
    }

    public function recentActivitiesDatatable(array $filters, array $scope, int $start, int $length, string $search = ''): array
    {
        if (!$this->tableExists('pancawaluya_transaction_histories')) {
            return [
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ];
        }

        $query = $this->applyHistoryFilters(
            PancawaluyaTransactionHistory::query()->with(['student:id,nama_lengkap', 'actor:id,name']),
            $filters,
            $scope
        );

        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('action', 'like', '%' . $search . '%')
                    ->orWhere('reference_type', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', '%' . $search . '%')
                    ->orWhereHas('student', fn(Builder $s) => $s->where('nama_lengkap', 'like', '%' . $search . '%'))
                    ->orWhereHas('actor', fn(Builder $a) => $a->where('name', 'like', '%' . $search . '%'));
            });
        }

        $filtered = (clone $query)->count();

        $rows = $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->map(function (PancawaluyaTransactionHistory $row): array {
            return [
                'date' => optional($row->transaction_date)->format('Y-m-d'),
                'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                'action' => (string) $row->action,
                'type' => (string) $row->reference_type,
                'status' => (string) ($row->status ?? '-'),
                'source' => (string) ($row->source ?? '-'),
                'actor' => (string) ($row->actor?->name ?? '-'),
            ];
        })->values()->all();

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }

    public function filterOptions(array $filters, array $scope): array
    {
        $students = Student::query()
            ->with('classroom:id,tingkat')
            ->when(!empty($this->scopedStudentIds($filters, $scope)), fn(Builder $q) => $q->whereIn('id', $this->scopedStudentIds($filters, $scope)))
            ->orderBy('nama_lengkap')
            ->limit(200)
            ->get(['id', 'nama_lengkap', 'nis', 'jenis_kelamin', 'classroom_id']);

        $genders = $students
            ->pluck('jenis_kelamin')
            ->filter(fn($value) => trim((string) $value) !== '')
            ->unique()
            ->values()
            ->all();

        $gradeLevels = $students
            ->map(fn(Student $student) => $student->classroom?->tingkat)
            ->filter(fn($value) => trim((string) $value) !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'academic_years' => AcademicYear::query()->orderByDesc('id')->get(['id', 'tahun_ajaran']),
            'semesters' => ['Ganjil', 'Genap'],
            'majors' => Major::query()->orderBy('nama_jurusan')->get(['id', 'nama_jurusan']),
            'classrooms' => Classroom::query()
                ->when($filters['major_id'] !== '', fn(Builder $q) => $q->where('major_id', (int) $filters['major_id']))
                ->orderBy('nama_kelas')
                ->get(['id', 'nama_kelas']),
            'students' => $students->map(fn(Student $student) => [
                'id' => $student->id,
                'nama_lengkap' => $student->nama_lengkap,
                'nis' => $student->nis,
            ])->values()->all(),
            'teachers' => Teacher::query()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip']),
            'sources' => $this->sourceOptions($filters, $scope),
            'genders' => $genders,
            'grade_levels' => $gradeLevels,
            'top_limits' => [10, 20, 50],
            'compare_modes' => ['student_vs_class', 'student_vs_department', 'student_vs_school', 'class_vs_school', 'department_vs_school'],
        ];
    }

    public function characterAnalytics(array $filters, array $scope): array
    {
        if (!$this->tableExists('student_character_statistics') || !$this->tableExists('student_character_scores') || !$this->tableExists('character_dimensions')) {
            return [
                'distribution' => [
                    'sangat_baik' => 0,
                    'baik' => 0,
                    'cukup' => 0,
                    'perlu_pendampingan' => 0,
                ],
                'growth' => 0.0,
                'decline_count' => 0,
                'trend' => [
                    'labels' => [],
                    'reward_trend' => [],
                    'violation_trend' => [],
                    'character_growth' => [],
                    'sp_distribution' => ['SP1' => 0, 'SP2' => 0, 'SP3' => 0],
                ],
                'achievement_rate' => 0,
                'comparison' => [],
                'progress' => [],
                'top_dimension' => ['name' => '-', 'score' => 0],
                'lowest_dimension' => ['name' => '-', 'score' => 0],
                'heatmap' => [],
                'insight' => 'Data dimensi karakter belum cukup untuk menghasilkan insight.',
            ];
        }

        $studentIds = $this->scopedStudentIds($filters, $scope);

        $query = StudentCharacterStatistic::query()
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']));

        $scores = (clone $query)->pluck('character_score_total')->map(fn($value) => (float) $value);

        $dimensions = StudentCharacterScore::query()
            ->join('character_dimensions', 'character_dimensions.id', '=', 'student_character_scores.character_dimension_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_character_scores.student_id', $studentIds))
            ->selectRaw('character_dimensions.name as dimension, AVG(student_character_scores.score_total) as avg_score')
            ->groupBy('character_dimensions.name')
            ->orderByDesc('avg_score')
            ->get();

        $trend = $this->trends($filters, $scope);
        $growthSeries = collect($trend['character_growth'] ?? [])->map(fn($value) => (float) $value);
        $declineCount = 0;
        for ($i = 1; $i < $growthSeries->count(); $i++) {
            if ($growthSeries[$i] < $growthSeries[$i - 1]) {
                $declineCount++;
            }
        }

        $heatmap = StudentCharacterScore::query()
            ->join('students', 'students.id', '=', 'student_character_scores.student_id')
            ->join('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->join('character_dimensions', 'character_dimensions.id', '=', 'student_character_scores.character_dimension_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_character_scores.student_id', $studentIds))
            ->selectRaw('classrooms.nama_kelas as kelas, character_dimensions.name as dimensi, AVG(student_character_scores.score_total) as rata_rata')
            ->groupBy('classrooms.nama_kelas', 'character_dimensions.name')
            ->orderBy('classrooms.nama_kelas')
            ->limit(100)
            ->get();

        $topDimension = $dimensions->first();
        $lowestDimension = $dimensions->last();

        return [
            'distribution' => [
                'sangat_baik' => $scores->filter(fn(float $score) => $score >= 90)->count(),
                'baik' => $scores->filter(fn(float $score) => $score >= 75 && $score < 90)->count(),
                'cukup' => $scores->filter(fn(float $score) => $score >= 60 && $score < 75)->count(),
                'perlu_pendampingan' => $scores->filter(fn(float $score) => $score < 60)->count(),
            ],
            'growth' => $this->percentageGrowth($growthSeries->first() ?? 0, $growthSeries->last() ?? 0),
            'decline_count' => $declineCount,
            'trend' => $trend,
            'achievement_rate' => $scores->count() > 0 ? round(($scores->filter(fn(float $score) => $score >= 75)->count() / $scores->count()) * 100, 2) : 0,
            'comparison' => $dimensions->map(fn($row) => [
                'dimension' => (string) $row->dimension,
                'average' => round((float) $row->avg_score, 2),
            ])->values()->all(),
            'progress' => $growthSeries->values()->all(),
            'top_dimension' => [
                'name' => (string) ($topDimension->dimension ?? '-'),
                'score' => round((float) ($topDimension->avg_score ?? 0), 2),
            ],
            'lowest_dimension' => [
                'name' => (string) ($lowestDimension->dimension ?? '-'),
                'score' => round((float) ($lowestDimension->avg_score ?? 0), 2),
            ],
            'heatmap' => $heatmap->map(fn($row) => [
                'kelas' => (string) $row->kelas,
                'dimensi' => (string) $row->dimensi,
                'rata_rata' => round((float) $row->rata_rata, 2),
            ])->values()->all(),
            'insight' => $this->buildCharacterInsight($topDimension?->dimension, $lowestDimension?->dimension, $declineCount),
        ];
    }

    public function rewardAnalytics(array $filters, array $scope): array
    {
        if (!$this->tableExists('reward_transactions')) {
            return [
                'trend' => [],
                'category_distribution' => [],
                'monthly_reward' => [],
                'teacher_contribution' => [],
                'department_reward' => [],
                'class_reward' => [],
                'most_rewarded_students' => [],
                'most_active_teachers' => [],
                'most_effective_category' => [
                    'kategori' => '-',
                    'total' => 0,
                ],
                'growth_percentage' => 0.0,
            ];
        }

        $base = $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope);

        $monthly = (clone $base)
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as periode, COUNT(*) as total')
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

        $teacherContribution = $this->tableExists('teachers')
            ? (clone $base)
            ->join('teachers', 'teachers.id', '=', 'reward_transactions.teacher_id')
            ->selectRaw('teachers.nama_lengkap as guru, COUNT(*) as total')
            ->groupBy('teachers.nama_lengkap')
            ->orderByDesc('total')
            ->limit($this->topLimit($filters))
            ->get()
            : collect();

        $category = $this->tableExists('reward_categories')
            ? (clone $base)
            ->join('reward_categories', 'reward_categories.id', '=', 'reward_transactions.reward_category_id')
            ->selectRaw('reward_categories.name as kategori, COUNT(*) as total')
            ->groupBy('reward_categories.name')
            ->orderByDesc('total')
            ->get()
            : collect();

        $classReward = $this->tableExists('classrooms')
            ? (clone $base)
            ->join('classrooms', 'classrooms.id', '=', 'reward_transactions.classroom_id')
            ->selectRaw('classrooms.nama_kelas as kelas, COUNT(*) as total')
            ->groupBy('classrooms.nama_kelas')
            ->orderByDesc('total')
            ->get()
            : collect();

        $departmentReward = $this->tableExists('classrooms') && $this->tableExists('majors')
            ? (clone $base)
            ->join('classrooms', 'classrooms.id', '=', 'reward_transactions.classroom_id')
            ->join('majors', 'majors.id', '=', 'classrooms.major_id')
            ->selectRaw('majors.nama_jurusan as jurusan, COUNT(*) as total')
            ->groupBy('majors.nama_jurusan')
            ->orderByDesc('total')
            ->get()
            : collect();

        $mostRewardedStudents = $this->tableExists('students')
            ? (clone $base)
            ->join('students', 'students.id', '=', 'reward_transactions.student_id')
            ->selectRaw('students.nama_lengkap as siswa, COUNT(*) as total')
            ->groupBy('students.nama_lengkap')
            ->orderByDesc('total')
            ->limit($this->topLimit($filters))
            ->get()
            : collect();

        $firstMonth = (int) ($monthly->first()->total ?? 0);
        $lastMonth = (int) ($monthly->last()->total ?? 0);

        return [
            'trend' => $monthly->map(fn($row) => ['periode' => (string) $row->periode, 'total' => (int) $row->total])->values()->all(),
            'category_distribution' => $category->map(fn($row) => ['kategori' => (string) $row->kategori, 'total' => (int) $row->total])->values()->all(),
            'monthly_reward' => $monthly->map(fn($row) => ['periode' => (string) $row->periode, 'total' => (int) $row->total])->values()->all(),
            'teacher_contribution' => $teacherContribution->map(fn($row) => ['guru' => (string) $row->guru, 'total' => (int) $row->total])->values()->all(),
            'department_reward' => $departmentReward->map(fn($row) => ['jurusan' => (string) $row->jurusan, 'total' => (int) $row->total])->values()->all(),
            'class_reward' => $classReward->map(fn($row) => ['kelas' => (string) $row->kelas, 'total' => (int) $row->total])->values()->all(),
            'most_rewarded_students' => $mostRewardedStudents->map(fn($row) => ['siswa' => (string) $row->siswa, 'total' => (int) $row->total])->values()->all(),
            'most_active_teachers' => $teacherContribution->map(fn($row) => ['guru' => (string) $row->guru, 'total' => (int) $row->total])->values()->all(),
            'most_effective_category' => [
                'kategori' => (string) ($category->first()->kategori ?? '-'),
                'total' => (int) ($category->first()->total ?? 0),
            ],
            'growth_percentage' => $this->percentageGrowth($firstMonth, $lastMonth),
        ];
    }

    public function violationAnalytics(array $filters, array $scope): array
    {
        if (!$this->tableExists('violation_transactions')) {
            return [
                'trend' => [],
                'category' => [],
                'frequency' => [],
                'repeat_violations' => [],
                'department' => [],
                'teacher' => [],
                'by_month' => [],
                'by_class' => [],
                'top_violations' => [],
                'risk_indicators' => [
                    'tinggi' => 0,
                    'sedang' => 0,
                    'rendah' => 0,
                ],
            ];
        }

        $base = $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope);

        $monthly = (clone $base)
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as periode, COUNT(*) as total')
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

        $category = $this->tableExists('violation_categories')
            ? (clone $base)
            ->join('violation_categories', 'violation_categories.id', '=', 'violation_transactions.violation_category_id')
            ->selectRaw('violation_categories.name as kategori, COUNT(*) as total')
            ->groupBy('violation_categories.name')
            ->orderByDesc('total')
            ->get()
            : collect();

        $repeat = $this->tableExists('students')
            ? (clone $base)
            ->join('students', 'students.id', '=', 'violation_transactions.student_id')
            ->selectRaw('students.nama_lengkap as siswa, COUNT(*) as total')
            ->groupBy('students.nama_lengkap')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->limit($this->topLimit($filters))
            ->get()
            : collect();

        $department = $this->tableExists('classrooms') && $this->tableExists('majors')
            ? (clone $base)
            ->join('classrooms', 'classrooms.id', '=', 'violation_transactions.classroom_id')
            ->join('majors', 'majors.id', '=', 'classrooms.major_id')
            ->selectRaw('majors.nama_jurusan as jurusan, COUNT(*) as total')
            ->groupBy('majors.nama_jurusan')
            ->orderByDesc('total')
            ->get()
            : collect();

        $teacher = $this->tableExists('teachers')
            ? (clone $base)
            ->join('teachers', 'teachers.id', '=', 'violation_transactions.teacher_id')
            ->selectRaw('teachers.nama_lengkap as guru, COUNT(*) as total')
            ->groupBy('teachers.nama_lengkap')
            ->orderByDesc('total')
            ->limit($this->topLimit($filters))
            ->get()
            : collect();

        $class = $this->tableExists('classrooms')
            ? (clone $base)
            ->join('classrooms', 'classrooms.id', '=', 'violation_transactions.classroom_id')
            ->selectRaw('classrooms.nama_kelas as kelas, COUNT(*) as total')
            ->groupBy('classrooms.nama_kelas')
            ->orderByDesc('total')
            ->get()
            : collect();

        $riskIndicator = [
            'tinggi' => $repeat->filter(fn($row) => (int) $row->total >= 5)->count(),
            'sedang' => $repeat->filter(fn($row) => (int) $row->total >= 3 && (int) $row->total < 5)->count(),
            'rendah' => $repeat->filter(fn($row) => (int) $row->total < 3)->count(),
        ];

        return [
            'trend' => $monthly->map(fn($row) => ['periode' => (string) $row->periode, 'total' => (int) $row->total])->values()->all(),
            'category' => $category->map(fn($row) => ['kategori' => (string) $row->kategori, 'total' => (int) $row->total])->values()->all(),
            'frequency' => $monthly->map(fn($row) => ['periode' => (string) $row->periode, 'total' => (int) $row->total])->values()->all(),
            'repeat_violations' => $repeat->map(fn($row) => ['siswa' => (string) $row->siswa, 'total' => (int) $row->total])->values()->all(),
            'department' => $department->map(fn($row) => ['jurusan' => (string) $row->jurusan, 'total' => (int) $row->total])->values()->all(),
            'teacher' => $teacher->map(fn($row) => ['guru' => (string) $row->guru, 'total' => (int) $row->total])->values()->all(),
            'by_month' => $monthly->map(fn($row) => ['periode' => (string) $row->periode, 'total' => (int) $row->total])->values()->all(),
            'by_class' => $class->map(fn($row) => ['kelas' => (string) $row->kelas, 'total' => (int) $row->total])->values()->all(),
            'top_violations' => $category->take($this->topLimit($filters))->map(fn($row) => ['kategori' => (string) $row->kategori, 'total' => (int) $row->total])->values()->all(),
            'risk_indicators' => $riskIndicator,
        ];
    }

    public function spAnalytics(array $filters, array $scope): array
    {
        if (!$this->tableExists('student_warning_letters')) {
            return [
                'sp1_distribution' => 0,
                'sp2_distribution' => 0,
                'sp3_distribution' => 0,
                'sp_trend' => [],
                'students_near_sp' => [],
                'sp_growth_percentage' => 0.0,
                'department_comparison' => [],
                'class_comparison' => [],
                'early_warning' => [
                    'count' => 0,
                    'message' => 'Data SP belum tersedia pada sistem ini.',
                ],
            ];
        }

        $studentIds = $this->scopedStudentIds($filters, $scope);

        $base = StudentWarningLetter::query()
            ->where('status', 'active')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']));

        $distribution = (clone $base)
            ->selectRaw('sp_level, COUNT(*) as total')
            ->groupBy('sp_level')
            ->pluck('total', 'sp_level');

        $trend = (clone $base)
            ->selectRaw('DATE_FORMAT(issued_at, "%Y-%m") as periode, COUNT(*) as total')
            ->whereNotNull('issued_at')
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

        $nearSp = $this->alerts($filters, $scope)['near_sp'];

        $departmentComparison = $this->tableExists('students') && $this->tableExists('classrooms') && $this->tableExists('majors')
            ? (clone $base)
            ->join('students', 'students.id', '=', 'student_warning_letters.student_id')
            ->join('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->join('majors', 'majors.id', '=', 'classrooms.major_id')
            ->selectRaw('majors.nama_jurusan as jurusan, COUNT(*) as total')
            ->groupBy('majors.nama_jurusan')
            ->orderByDesc('total')
            ->get()
            : collect();

        $classComparison = $this->tableExists('students') && $this->tableExists('classrooms')
            ? (clone $base)
            ->join('students', 'students.id', '=', 'student_warning_letters.student_id')
            ->join('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->selectRaw('classrooms.nama_kelas as kelas, COUNT(*) as total')
            ->groupBy('classrooms.nama_kelas')
            ->orderByDesc('total')
            ->get()
            : collect();

        $spTrendFirst = (int) ($trend->first()->total ?? 0);
        $spTrendLast = (int) ($trend->last()->total ?? 0);

        return [
            'sp1_distribution' => (int) ($distribution['SP1'] ?? 0),
            'sp2_distribution' => (int) ($distribution['SP2'] ?? 0),
            'sp3_distribution' => (int) ($distribution['SP3'] ?? 0),
            'sp_trend' => $trend->map(fn($row) => ['periode' => (string) $row->periode, 'total' => (int) $row->total])->values()->all(),
            'students_near_sp' => $nearSp,
            'sp_growth_percentage' => $this->percentageGrowth($spTrendFirst, $spTrendLast),
            'department_comparison' => $departmentComparison->map(fn($row) => ['jurusan' => (string) $row->jurusan, 'total' => (int) $row->total])->values()->all(),
            'class_comparison' => $classComparison->map(fn($row) => ['kelas' => (string) $row->kelas, 'total' => (int) $row->total])->values()->all(),
            'early_warning' => [
                'count' => count($nearSp),
                'message' => count($nearSp) > 0
                    ? 'Terdapat siswa yang mendekati SP, tindak lanjut konseling disarankan.'
                    : 'Tidak ada siswa yang mendekati ambang SP pada periode ini.',
            ],
        ];
    }

    public function comparativeAnalytics(array $filters, array $scope): array
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);

        $hasCharacterStats = $this->tableExists('student_character_statistics');

        $classVsClass = ($hasCharacterStats && $this->tableExists('students') && $this->tableExists('classrooms'))
            ? StudentCharacterStatistic::query()
            ->join('students', 'students.id', '=', 'student_character_statistics.student_id')
            ->join('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_character_statistics.student_id', $studentIds))
            ->selectRaw('classrooms.nama_kelas as label, AVG(student_character_statistics.character_score_total) as nilai')
            ->groupBy('classrooms.nama_kelas')
            ->orderByDesc('nilai')
            ->get()
            : collect();

        $departmentVsDepartment = ($hasCharacterStats && $this->tableExists('students') && $this->tableExists('classrooms') && $this->tableExists('majors'))
            ? StudentCharacterStatistic::query()
            ->join('students', 'students.id', '=', 'student_character_statistics.student_id')
            ->join('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->join('majors', 'majors.id', '=', 'classrooms.major_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_character_statistics.student_id', $studentIds))
            ->selectRaw('majors.nama_jurusan as label, AVG(student_character_statistics.character_score_total) as nilai')
            ->groupBy('majors.nama_jurusan')
            ->orderByDesc('nilai')
            ->get()
            : collect();

        $teacherVsTeacher = $this->buildMostActiveTeachers($filters, $scope, $this->topLimit($filters));

        $semesterVsSemester = $hasCharacterStats
            ? StudentCharacterStatistic::query()
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->selectRaw('semester as label, AVG(character_score_total) as nilai')
            ->groupBy('semester')
            ->orderBy('semester')
            ->get()
            : collect();

        $yearVsYear = ($hasCharacterStats && $this->tableExists('academic_years'))
            ? StudentCharacterStatistic::query()
            ->join('academic_years', 'academic_years.id', '=', 'student_character_statistics.academic_year_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_character_statistics.student_id', $studentIds))
            ->selectRaw('academic_years.tahun_ajaran as label, AVG(student_character_statistics.character_score_total) as nilai')
            ->groupBy('academic_years.tahun_ajaran')
            ->orderBy('academic_years.tahun_ajaran')
            ->get()
            : collect();

        $maleVsFemale = ($hasCharacterStats && $this->tableExists('students'))
            ? StudentCharacterStatistic::query()
            ->join('students', 'students.id', '=', 'student_character_statistics.student_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_character_statistics.student_id', $studentIds))
            ->selectRaw('students.jenis_kelamin as label, AVG(student_character_statistics.character_score_total) as nilai')
            ->groupBy('students.jenis_kelamin')
            ->get()
            : collect();

        $rewardVsViolation = [
            'reward' => $this->tableExists('reward_transactions')
                ? $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope)->count()
                : 0,
            'violation' => $this->tableExists('violation_transactions')
                ? $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope)->count()
                : 0,
        ];

        $metrics = $this->collectStudentMetricRows($filters, $scope);

        return [
            'class_vs_class' => $classVsClass->map(fn($row) => ['label' => (string) $row->label, 'nilai' => round((float) $row->nilai, 2)])->values()->all(),
            'department_vs_department' => $departmentVsDepartment->map(fn($row) => ['label' => (string) $row->label, 'nilai' => round((float) $row->nilai, 2)])->values()->all(),
            'teacher_vs_teacher' => $teacherVsTeacher,
            'semester_vs_semester' => $semesterVsSemester->map(fn($row) => ['label' => (string) $row->label, 'nilai' => round((float) $row->nilai, 2)])->values()->all(),
            'year_vs_year' => $yearVsYear->map(fn($row) => ['label' => (string) $row->label, 'nilai' => round((float) $row->nilai, 2)])->values()->all(),
            'male_vs_female' => $maleVsFemale->map(fn($row) => ['label' => (string) $row->label, 'nilai' => round((float) $row->nilai, 2)])->values()->all(),
            'reward_vs_violation' => $rewardVsViolation,
            'character_vs_attendance' => $metrics->map(fn(array $row) => [
                'siswa' => $row['student'],
                'karakter' => $row['character_score'],
                'kehadiran' => $row['attendance_rate'],
            ])->take(100)->values()->all(),
        ];
    }

    public function correlationAnalytics(array $filters, array $scope): array
    {
        $metrics = $this->collectStudentMetricRows($filters, $scope);

        $attendance = $metrics->pluck('attendance_rate')->map(fn($v) => (float) $v)->values()->all();
        $reward = $metrics->pluck('reward_count')->map(fn($v) => (float) $v)->values()->all();
        $violation = $metrics->pluck('violation_count')->map(fn($v) => (float) $v)->values()->all();
        $character = $metrics->pluck('character_score')->map(fn($v) => (float) $v)->values()->all();
        $sp = $metrics->pluck('sp_level_numeric')->map(fn($v) => (float) $v)->values()->all();

        $matrix = [
            'attendance_reward' => $this->pearsonCorrelation($attendance, $reward),
            'attendance_violation' => $this->pearsonCorrelation($attendance, $violation),
            'attendance_character' => $this->pearsonCorrelation($attendance, $character),
            'reward_character' => $this->pearsonCorrelation($reward, $character),
            'violation_character' => $this->pearsonCorrelation($violation, $character),
            'violation_sp' => $this->pearsonCorrelation($violation, $sp),
        ];

        $strongest = collect($matrix)->sortByDesc(fn($value) => abs((float) $value))->keys()->first();

        return [
            'correlation_matrix' => $matrix,
            'scatter_plot' => $metrics->map(fn(array $row) => [
                'x' => $row['attendance_rate'],
                'y' => $row['character_score'],
                'r' => max(5, min(20, $row['violation_count'] + 4)),
                'label' => $row['student'],
            ])->take(200)->values()->all(),
            'coefficient' => $matrix,
            'interpretation' => $strongest
                ? 'Korelasi paling kuat terdeteksi pada metrik ' . str_replace('_', ' ', $strongest) . '.'
                : 'Data belum cukup untuk membaca pola korelasi.',
        ];
    }

    public function predictiveAnalytics(array $filters, array $scope): array
    {
        $metrics = $this->collectStudentMetricRows($filters, $scope);

        $likelySp = $metrics
            ->filter(fn(array $row) => $row['violation_count'] >= 4 || $row['sp_level_numeric'] >= 2)
            ->sortByDesc('violation_count')
            ->take($this->topLimit($filters))
            ->values();

        $likelyImprove = $metrics
            ->filter(fn(array $row) => $row['reward_count'] >= 2 && $row['attendance_rate'] >= 85)
            ->sortByDesc('reward_count')
            ->take($this->topLimit($filters))
            ->values();

        $needCounseling = $metrics
            ->filter(fn(array $row) => $row['character_score'] < 65 || $row['violation_count'] >= 3)
            ->sortBy('character_score')
            ->take($this->topLimit($filters))
            ->values();

        $deservingAppreciation = $metrics
            ->filter(fn(array $row) => $row['character_score'] >= 85 && $row['reward_count'] >= 2)
            ->sortByDesc('character_score')
            ->take($this->topLimit($filters))
            ->values();

        $decliningCharacter = $metrics
            ->filter(fn(array $row) => $row['character_score'] < 70 && $row['violation_count'] > $row['reward_count'])
            ->sortBy('character_score')
            ->take($this->topLimit($filters))
            ->values();

        return [
            'likely_receive_sp' => $likelySp->map(fn(array $row) => $this->predictionRow($row, 'Risiko pelanggaran tinggi dan/atau level SP sudah meningkat.'))->all(),
            'likely_to_improve' => $likelyImprove->map(fn(array $row) => $this->predictionRow($row, 'Reward dan disiplin hadir menunjukkan tren perbaikan.'))->all(),
            'requiring_counseling' => $needCounseling->map(fn(array $row) => $this->predictionRow($row, 'Skor karakter rendah atau pelanggaran berulang memerlukan pendampingan.'))->all(),
            'deserving_appreciation' => $deservingAppreciation->map(fn(array $row) => $this->predictionRow($row, 'Konsisten berprestasi dengan skor karakter tinggi.'))->all(),
            'declining_character' => $decliningCharacter->map(fn(array $row) => $this->predictionRow($row, 'Skor karakter menurun relatif terhadap perilaku pelanggaran.'))->all(),
        ];
    }

    public function executiveSummary(array $filters, array $scope): array
    {
        $kpi = $this->kpi($filters, $scope);
        $trends = $this->trends($filters, $scope);
        $character = $this->characterAnalytics($filters, $scope);
        $violations = $this->violationAnalytics($filters, $scope);

        $rewardGrowth = $this->percentageGrowth(
            (int) ((collect($trends['reward_trend'] ?? [0]))->first() ?? 0),
            (int) ((collect($trends['reward_trend'] ?? [0]))->last() ?? 0)
        );

        $violationGrowth = $this->percentageGrowth(
            (int) ((collect($trends['violation_trend'] ?? [0]))->first() ?? 0),
            (int) ((collect($trends['violation_trend'] ?? [0]))->last() ?? 0)
        );

        $topViolation = collect($violations['top_violations'] ?? [])->first();
        $topDimension = $character['top_dimension']['name'] ?? '-';

        return [
            'narrative' => [
                'Rata-rata karakter saat ini berada di angka ' . round((float) ($kpi['character_average'] ?? 0), 2) . '.',
                'Pertumbuhan reward tercatat ' . $rewardGrowth . '%.',
                'Perubahan pelanggaran tercatat ' . $violationGrowth . '%.',
                'Dimensi karakter terkuat saat ini adalah ' . $topDimension . '.',
                'Pelanggaran terbanyak masih didominasi oleh ' . (string) ($topViolation['kategori'] ?? 'belum tersedia') . '.',
            ],
            'highlights' => [
                'character_score' => round((float) ($kpi['character_average'] ?? 0), 2),
                'reward_growth_percentage' => $rewardGrowth,
                'violation_growth_percentage' => $violationGrowth,
                'top_character_dimension' => $topDimension,
                'top_violation' => (string) ($topViolation['kategori'] ?? '-'),
            ],
        ];
    }

    public function exportRows(array $filters, array $scope): array
    {
        $kpi = $this->kpi($filters, $scope);
        $summary = $this->executiveSummary($filters, $scope);
        $ranking = $this->rankings($filters, $scope);

        $rows = [
            ['section' => 'KPI', 'metric' => 'Total Siswa', 'value' => (string) ($kpi['total_students'] ?? 0)],
            ['section' => 'KPI', 'metric' => 'Total Guru', 'value' => (string) ($kpi['total_teachers'] ?? 0)],
            ['section' => 'KPI', 'metric' => 'Total Penghargaan', 'value' => (string) ($kpi['total_rewards'] ?? 0)],
            ['section' => 'KPI', 'metric' => 'Total Pelanggaran', 'value' => (string) ($kpi['total_violations'] ?? 0)],
            ['section' => 'Ringkasan Eksekutif', 'metric' => 'Narasi 1', 'value' => (string) ($summary['narrative'][0] ?? '-')],
            ['section' => 'Ringkasan Eksekutif', 'metric' => 'Narasi 2', 'value' => (string) ($summary['narrative'][1] ?? '-')],
        ];

        foreach (($ranking['best_students'] ?? []) as $index => $row) {
            $rows[] = [
                'section' => 'Peringkat Siswa Terbaik',
                'metric' => 'Peringkat ' . ($index + 1),
                'value' => (string) ($row['student'] ?? '-') . ' | Skor: ' . (string) ($row['score'] ?? 0),
            ];
        }

        return $rows;
    }

    private function sourceOptions(array $filters, array $scope): array
    {
        $hasRewardTransactions = $this->tableExists('reward_transactions');
        $hasViolationTransactions = $this->tableExists('violation_transactions');

        if (!$hasRewardTransactions && !$hasViolationTransactions) {
            return [];
        }

        if (!$hasRewardTransactions) {
            return $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope)
                ->whereNotNull('source')
                ->distinct()
                ->limit(20)
                ->pluck('source')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (!$hasViolationTransactions) {
            return $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope)
                ->whereNotNull('source')
                ->distinct()
                ->limit(20)
                ->pluck('source')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $rewardSources = $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope)
            ->whereNotNull('source')
            ->distinct()
            ->limit(20)
            ->pluck('source');

        $violationSources = $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope)
            ->whereNotNull('source')
            ->distinct()
            ->limit(20)
            ->pluck('source');

        return $rewardSources->merge($violationSources)->filter()->unique()->values()->all();
    }

    private function topLimit(array $filters): int
    {
        $allowed = [10, 20, 50];
        $value = (int) ($filters['top_limit'] ?? 10);

        return in_array($value, $allowed, true) ? $value : 10;
    }

    private function departmentScopedStudentIds(array $filters, array $scope): array
    {
        $majorId = (int) ($filters['major_id'] !== '' ? $filters['major_id'] : 0);

        if ($majorId <= 0 && ($filters['classroom_id'] ?? '') === '') {
            return [];
        }

        $query = Student::query();

        if ($majorId > 0) {
            $query->whereHas('classroom', fn(Builder $q) => $q->where('major_id', $majorId));
        }

        if (($filters['classroom_id'] ?? '') !== '') {
            $query->where('classroom_id', (int) $filters['classroom_id']);
        }

        return $query->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    private function differenceSeries(array $left, array $right): array
    {
        $max = max(count($left), count($right));
        $diff = [];

        for ($i = 0; $i < $max; $i++) {
            $diff[] = round(((float) ($left[$i] ?? 0)) - ((float) ($right[$i] ?? 0)), 2);
        }

        return $diff;
    }

    private function percentageGrowth(float|int $first, float|int $last): float
    {
        $firstValue = (float) $first;
        $lastValue = (float) $last;

        if ($firstValue == 0.0) {
            return $lastValue > 0 ? 100.0 : 0.0;
        }

        return round((($lastValue - $firstValue) / abs($firstValue)) * 100, 2);
    }

    private function buildCharacterInsight(?string $topDimension, ?string $lowestDimension, int $declineCount): string
    {
        if ($topDimension === null && $lowestDimension === null) {
            return 'Data dimensi karakter belum cukup untuk menghasilkan insight.';
        }

        $insight = 'Dimensi terkuat saat ini adalah ' . ($topDimension ?? '-') . ' dan dimensi terendah adalah ' . ($lowestDimension ?? '-') . '.';

        if ($declineCount > 0) {
            $insight .= ' Terdeteksi ' . $declineCount . ' periode penurunan yang memerlukan intervensi.';
        }

        return $insight;
    }

    private function collectStudentMetricRows(array $filters, array $scope): Collection
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);

        if (!$this->tableExists('student_character_statistics')) {
            return collect();
        }

        $stats = StudentCharacterStatistic::query()
            ->with('student:id,nama_lengkap')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']))
            ->get(['student_id', 'reward_count', 'violation_count', 'character_score_total']);

        if ($stats->isEmpty()) {
            return collect();
        }

        $attendanceRows = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('attendance_details.student_id', $studentIds))
            ->when($filters['date_from'] !== '', fn(Builder $q) => $q->whereDate('teacher_attendances.tanggal', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn(Builder $q) => $q->whereDate('teacher_attendances.tanggal', '<=', $filters['date_to']))
            ->selectRaw('attendance_details.student_id as student_id, COUNT(*) as total_records, SUM(CASE WHEN attendance_details.status IN ("Hadir","Terlambat") THEN 1 ELSE 0 END) as hadir_records')
            ->groupBy('attendance_details.student_id')
            ->get()
            ->keyBy('student_id');

        $spRows = $this->tableExists('student_warning_letters')
            ? StudentWarningLetter::query()
            ->where('status', 'active')
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->selectRaw('student_id, MAX(sp_level) as sp_level')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id')
            : collect();

        return $stats->map(function (StudentCharacterStatistic $row) use ($attendanceRows, $spRows): array {
            $attendance = $attendanceRows->get($row->student_id);
            $totalRecords = (int) ($attendance->total_records ?? 0);
            $presentRecords = (int) ($attendance->hadir_records ?? 0);
            $attendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 2) : 0.0;
            $spLevel = (string) ($spRows->get($row->student_id)->sp_level ?? '');

            return [
                'student_id' => (int) $row->student_id,
                'student' => (string) ($row->student?->nama_lengkap ?? '-'),
                'attendance_rate' => $attendanceRate,
                'reward_count' => (int) $row->reward_count,
                'violation_count' => (int) $row->violation_count,
                'character_score' => round((float) $row->character_score_total, 2),
                'sp_level' => $spLevel,
                'sp_level_numeric' => $this->spLevelToNumber($spLevel),
            ];
        })->values();
    }

    private function pearsonCorrelation(array $x, array $y): float
    {
        $n = min(count($x), count($y));

        if ($n < 2) {
            return 0.0;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;
        $sumY2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $xVal = (float) $x[$i];
            $yVal = (float) $y[$i];
            $sumX += $xVal;
            $sumY += $yVal;
            $sumXY += $xVal * $yVal;
            $sumX2 += $xVal * $xVal;
            $sumY2 += $yVal * $yVal;
        }

        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));

        if ($denominator == 0.0) {
            return 0.0;
        }

        return round($numerator / $denominator, 4);
    }

    private function spLevelToNumber(string $spLevel): int
    {
        return match (strtoupper(trim($spLevel))) {
            'SP3' => 3,
            'SP2' => 2,
            'SP1' => 1,
            default => 0,
        };
    }

    private function predictionRow(array $row, string $reason): array
    {
        return [
            'student_id' => $row['student_id'],
            'student' => $row['student'],
            'attendance_rate' => $row['attendance_rate'],
            'reward_count' => $row['reward_count'],
            'violation_count' => $row['violation_count'],
            'character_score' => $row['character_score'],
            'sp_level' => $row['sp_level'],
            'reason' => $reason,
        ];
    }

    private function scopedStudentIds(array $filters, array $scope): array
    {
        if (!empty($scope['student_id'])) {
            return [(int) $scope['student_id']];
        }

        $query = Student::query();

        if (!empty($scope['classroom_id'])) {
            $query->where('classroom_id', (int) $scope['classroom_id']);
        }

        if ($filters['major_id'] !== '') {
            $query->whereHas('classroom', fn(Builder $q) => $q->where('major_id', (int) $filters['major_id']));
        }

        if ($filters['classroom_id'] !== '') {
            $query->where('classroom_id', (int) $filters['classroom_id']);
        }

        if ($filters['student_id'] !== '') {
            $query->where('id', (int) $filters['student_id']);
        }

        if (($filters['gender'] ?? '') !== '') {
            $query->where('jenis_kelamin', $filters['gender']);
        }

        if (($filters['grade_level'] ?? '') !== '') {
            $query->whereHas('classroom', fn(Builder $q) => $q->where('tingkat', $filters['grade_level']));
        }

        return $query->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    private function classScopedStudentIds(array $filters, array $scope): array
    {
        $query = Student::query();

        if ($filters['classroom_id'] !== '') {
            $query->where('classroom_id', (int) $filters['classroom_id']);
        } elseif (!empty($scope['classroom_id'])) {
            $query->where('classroom_id', (int) $scope['classroom_id']);
        } else {
            return [];
        }

        return $query->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    private function scopedTeacherCount(array $filters, array $scope): int
    {
        if (!empty($scope['teacher_id'])) {
            return 1;
        }

        if ($filters['teacher_id'] !== '') {
            return Teacher::query()->where('id', (int) $filters['teacher_id'])->count();
        }

        return Teacher::query()->count();
    }

    private function previousDimensionScores(array $filters, array $scope, Carbon $from, Carbon $to): array
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);

        $scores = [];

        $rewardRows = $this->tableExists('reward_transactions')
            ? RewardTransaction::query()
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->get(['dimension_payload'])
            : collect();

        foreach ($rewardRows as $row) {
            foreach ((array) $row->dimension_payload as $payload) {
                $name = (string) ($payload['dimension_name'] ?? 'Unknown');
                $scores[$name] = ($scores[$name] ?? 0) + (float) ($payload['weighted_point'] ?? 0);
            }
        }

        $violationRows = $this->tableExists('violation_transactions')
            ? ViolationTransaction::query()
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->get(['dimension_payload'])
            : collect();

        foreach ($violationRows as $row) {
            foreach ((array) $row->dimension_payload as $payload) {
                $name = (string) ($payload['dimension_name'] ?? 'Unknown');
                $scores[$name] = ($scores[$name] ?? 0) - (float) ($payload['weighted_point'] ?? 0);
            }
        }

        return $scores;
    }

    private function buildMostActiveTeachers(array $filters, array $scope, int $topLimit = 10): array
    {
        $reward = $this->tableExists('reward_transactions')
            ? $this->applyTransactionFilters(RewardTransaction::query(), $filters, $scope)
            ->select('created_by', DB::raw('COUNT(*) as total'))
            ->groupBy('created_by')
            ->get()
            : collect();

        $violation = $this->tableExists('violation_transactions')
            ? $this->applyTransactionFilters(ViolationTransaction::query(), $filters, $scope)
            ->select('created_by', DB::raw('COUNT(*) as total'))
            ->groupBy('created_by')
            ->get()
            : collect();

        $merged = collect();

        foreach ($reward as $row) {
            $merged[$row->created_by] = (int) ($merged[$row->created_by] ?? 0) + (int) $row->total;
        }

        foreach ($violation as $row) {
            $merged[$row->created_by] = (int) ($merged[$row->created_by] ?? 0) + (int) $row->total;
        }

        if ($merged->isEmpty()) {
            return [];
        }

        $users = User::query()->whereIn('id', $merged->keys()->all())->pluck('name', 'id');

        return $merged
            ->sortDesc()
            ->take($topLimit)
            ->map(fn(int $total, $userId) => [
                'teacher' => (string) ($users[$userId] ?? 'User #' . $userId),
                'total' => $total,
            ])
            ->values()
            ->all();
    }

    private function applyTransactionFilters(Builder $query, array $filters, array $scope): Builder
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);

        return $query
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']))
            ->when($filters['classroom_id'] !== '', fn(Builder $q) => $q->where('classroom_id', (int) $filters['classroom_id']))
            ->when($filters['student_id'] !== '', fn(Builder $q) => $q->where('student_id', (int) $filters['student_id']))
            ->when($filters['teacher_id'] !== '', fn(Builder $q) => $q->where('teacher_id', (int) $filters['teacher_id']))
            ->when($filters['source'] !== '', fn(Builder $q) => $q->where('source', $filters['source']))
            ->when($filters['date_from'] !== '', fn(Builder $q) => $q->whereDate('transaction_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn(Builder $q) => $q->whereDate('transaction_date', '<=', $filters['date_to']))
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($scope['role'] === 'guru' && !empty($scope['teacher_id']), fn(Builder $q) => $q->where(function (Builder $sub) use ($scope): void {
                $sub->where('teacher_id', (int) $scope['teacher_id'])
                    ->orWhere('created_by', (int) ($scope['user_id'] ?? 0));
            }));
    }

    private function applyHistoryFilters(Builder $query, array $filters, array $scope): Builder
    {
        $studentIds = $this->scopedStudentIds($filters, $scope);

        return $query
            ->when($filters['academic_year_id'] !== '', fn(Builder $q) => $q->where('academic_year_id', (int) $filters['academic_year_id']))
            ->when($filters['semester'] !== '', fn(Builder $q) => $q->where('semester', $filters['semester']))
            ->when($filters['classroom_id'] !== '', fn(Builder $q) => $q->where('classroom_id', (int) $filters['classroom_id']))
            ->when($filters['student_id'] !== '', fn(Builder $q) => $q->where('student_id', (int) $filters['student_id']))
            ->when($filters['source'] !== '', fn(Builder $q) => $q->where('source', $filters['source']))
            ->when($filters['date_from'] !== '', fn(Builder $q) => $q->whereDate('transaction_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn(Builder $q) => $q->whereDate('transaction_date', '<=', $filters['date_to']))
            ->when(!empty($studentIds), fn(Builder $q) => $q->whereIn('student_id', $studentIds));
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        return $this->tableExistsCache[$table] = Schema::hasTable($table);
    }
}
