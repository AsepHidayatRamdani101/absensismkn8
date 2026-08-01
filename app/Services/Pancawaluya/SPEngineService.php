<?php

namespace App\Services\Pancawaluya;

use App\Models\StudentWarningLetter;
use Illuminate\Support\Facades\Auth;

class SPEngineService
{
    public function recalculate(int $studentId, int $academicYearId, string $semester, float $violationWeightedTotal): ?StudentWarningLetter
    {
        $rule = $this->resolveRule($violationWeightedTotal);

        StudentWarningLetter::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', 'active')
            ->where('is_manual_override', false)
            ->update(['status' => 'resolved']);

        if ($rule === null) {
            return null;
        }

        return StudentWarningLetter::query()->create([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'semester' => $semester,
            'sp_level' => $rule['level'],
            'violation_weighted_total' => $violationWeightedTotal,
            'is_manual_override' => false,
            'status' => 'active',
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addMonths((int) ($rule['expiration_months'] ?? 6))->toDateString(),
            'note' => 'Generated otomatis oleh sistem berdasarkan akumulasi pelanggaran.',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function resolveRule(float $violationWeightedTotal): ?array
    {
        $rules = config('pancawaluya.sp_rules', [
            ['min' => 25, 'max' => 50, 'level' => 'SP1', 'expiration_months' => 6],
            ['min' => 51, 'max' => 75, 'level' => 'SP2', 'expiration_months' => 6],
            ['min' => 76, 'max' => null, 'level' => 'SP3', 'expiration_months' => 6],
        ]);

        foreach ($rules as $rule) {
            $min = (float) ($rule['min'] ?? 0);
            $max = $rule['max'] ?? null;

            if ($violationWeightedTotal < $min) {
                continue;
            }

            if ($max !== null && $violationWeightedTotal > (float) $max) {
                continue;
            }

            return $rule;
        }

        return null;
    }
}
