<?php

namespace App\Services\Pancawaluya;

use App\Models\RewardTransaction;
use App\Models\StudentCharacterStatistic;
use App\Models\ViolationTransaction;
use Carbon\Carbon;

class StatisticsEngineService
{
    public function refreshStudentPeriod(int $studentId, int $academicYearId, string $semester, float $characterTotal): StudentCharacterStatistic
    {
        $rewardQuery = RewardTransaction::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'validated', 'approved']);

        $violationQuery = ViolationTransaction::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'validated', 'approved']);

        $stats = StudentCharacterStatistic::query()->firstOrNew([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'semester' => $semester,
        ]);

        $stats->reward_count = (int) $rewardQuery->count();
        $stats->violation_count = (int) $violationQuery->count();
        $stats->reward_weighted_total = (float) $rewardQuery->sum('weighted_point');
        $stats->violation_weighted_total = (float) $violationQuery->sum('weighted_point');
        $stats->character_score_total = $characterTotal;
        $stats->last_calculated_at = Carbon::now();
        $stats->save();

        return $stats;
    }
}
