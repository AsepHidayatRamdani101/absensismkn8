<?php

namespace App\Services\Pancawaluya;

use App\Models\StudentCharacterScore;
use Carbon\Carbon;

class CharacterScoreEngineService
{
    public function applyRewardPayload(int $studentId, array $dimensionPayload): void
    {
        foreach ($dimensionPayload as $row) {
            $this->applyDelta($studentId, (int) $row['dimension_id'], (float) $row['weighted_point'], true);
        }
    }

    public function rollbackRewardPayload(int $studentId, array $dimensionPayload): void
    {
        foreach ($dimensionPayload as $row) {
            $this->applyDelta($studentId, (int) $row['dimension_id'], -1 * (float) $row['weighted_point'], true);
        }
    }

    public function applyViolationPayload(int $studentId, array $dimensionPayload): void
    {
        foreach ($dimensionPayload as $row) {
            $this->applyDelta($studentId, (int) $row['dimension_id'], (float) $row['weighted_point'], false);
        }
    }

    public function rollbackViolationPayload(int $studentId, array $dimensionPayload): void
    {
        foreach ($dimensionPayload as $row) {
            $this->applyDelta($studentId, (int) $row['dimension_id'], -1 * (float) $row['weighted_point'], false);
        }
    }

    public function totalScore(int $studentId): float
    {
        return (float) StudentCharacterScore::query()->where('student_id', $studentId)->sum('score_total');
    }

    private function applyDelta(int $studentId, int $dimensionId, float $delta, bool $isReward): void
    {
        $score = StudentCharacterScore::query()->firstOrCreate(
            [
                'student_id' => $studentId,
                'character_dimension_id' => $dimensionId,
            ],
            [
                'reward_score_total' => 0,
                'violation_score_total' => 0,
                'score_total' => 0,
            ]
        );

        if ($isReward) {
            $score->reward_score_total = (float) $score->reward_score_total + $delta;
        } else {
            $score->violation_score_total = (float) $score->violation_score_total + $delta;
        }

        $score->reward_score_total = max((float) $score->reward_score_total, 0);
        $score->violation_score_total = max((float) $score->violation_score_total, 0);
        $score->score_total = (float) $score->reward_score_total - (float) $score->violation_score_total;
        $score->last_calculated_at = Carbon::now();
        $score->save();
    }
}
